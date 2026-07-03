// Shared offline visit queue (IndexedDB) — used by servant panel and web-app wizard

function dispatchToLivewire(event, payload) {
    try {
        if (window.Livewire) {
            window.Livewire.dispatch(event, payload);
        }
    } catch (e) {
        // Livewire not ready yet
    }
}

export const offlineQueue = {
    DB_NAME:    'servant-offline',
    DB_VERSION: 1,
    STORE:      'pending-visits',
    db:         null,

    async init() {
        if (!('indexedDB' in window)) return;

        this.db = await this._openDb();

        window.addEventListener('online', () => this._syncPending());

        if (navigator.onLine) {
            await this._syncPending();
        }

        this._notifyCount();
    },

    async enqueue(visitData) {
        if (!this.db) return false;
        const tx    = this.db.transaction(this.STORE, 'readwrite');
        const store = tx.objectStore(this.STORE);
        store.add({ ...visitData, queuedAt: Date.now() });
        await this._txDone(tx);
        this._notifyCount();
        return true;
    },

    async count() {
        if (!this.db) return 0;
        const tx    = this.db.transaction(this.STORE, 'readonly');
        const store = tx.objectStore(this.STORE);
        return new Promise((resolve, reject) => {
            const req = store.count();
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    },

    async _syncPending() {
        if (!this.db) return;

        const tx      = this.db.transaction(this.STORE, 'readwrite');
        const store   = tx.objectStore(this.STORE);
        const records = await this._getAll(store);

        if (records.length === 0) return;

        for (const record of records) {
            try {
                await window.axios.post('/servant/visits/sync', record);
                store.delete(record.id);
            } catch (err) {
                if (err?.response?.status === 409) {
                    store.delete(record.id);
                    dispatchToLivewire('offlineSyncConflict', {
                        beneficiaryId: record.beneficiary_id,
                    });
                }
            }
        }

        await this._txDone(tx);
        this._notifyCount();
    },

    _notifyCount() {
        this.count().then((n) => {
            dispatchToLivewire('offlineQueueCount', { count: n });
        });
    },

    _openDb() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(this.DB_NAME, this.DB_VERSION);

            req.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(this.STORE)) {
                    db.createObjectStore(this.STORE, {
                        keyPath:       'id',
                        autoIncrement: true,
                    });
                }
            };

            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    },

    _getAll(store) {
        return new Promise((resolve, reject) => {
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    },

    _txDone(tx) {
        return new Promise((resolve, reject) => {
            tx.oncomplete = () => resolve();
            tx.onerror    = () => reject(tx.error);
        });
    },
};
