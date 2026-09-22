// Shared offline visit queue (IndexedDB + localStorage fallback)
// Used by servant panel and web-app wizard

function dispatchToLivewire(event, payload) {
    try {
        if (window.Livewire) {
            window.Livewire.dispatch(event, payload);
        }
    } catch (e) {
        // Livewire not ready yet
    }
}

const LS_KEY = 'servant_offline_visits_v1';

export const offlineQueue = {
    DB_NAME:    'servant-offline',
    DB_VERSION: 1,
    STORE:      'pending-visits',
    db:         null,
    useLS:      false,

    async init() {
        if (!('indexedDB' in window)) {
            this._initLS();
            return;
        }

        try {
            this.db = await this._openDb();
            this.useLS = false;
        } catch (e) {
            // IndexedDB blocked (private mode, quota, etc.) — fall back to localStorage
            this._initLS();
            return;
        }

        window.addEventListener('online', () => this._syncPending());

        if (navigator.onLine) {
            await this._syncPending();
        }

        this._notifyCount();
    },

    _initLS() {
        this.useLS = true;
        if (!localStorage.getItem(LS_KEY)) {
            localStorage.setItem(LS_KEY, JSON.stringify([]));
        }
        window.addEventListener('online', () => this._syncPending());
        if (navigator.onLine) this._syncPending();
        this._notifyCount();
    },

    async enqueue(visitData) {
        const record = { ...visitData, queuedAt: Date.now() };

        if (this.useLS) {
            const arr = JSON.parse(localStorage.getItem(LS_KEY) || '[]');
            arr.push(record);
            localStorage.setItem(LS_KEY, JSON.stringify(arr));
            this._notifyCount();
            return true;
        }

        if (!this.db) return false;

        const tx    = this.db.transaction(this.STORE, 'readwrite');
        const store = tx.objectStore(this.STORE);
        store.add(record);
        await this._txDone(tx);
        this._notifyCount();
        return true;
    },

    async count() {
        if (this.useLS) {
            const arr = JSON.parse(localStorage.getItem(LS_KEY) || '[]');
            return arr.length;
        }

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
        if (!navigator.onLine) return;

        let records = [];

        if (this.useLS) {
            const arr = JSON.parse(localStorage.getItem(LS_KEY) || '[]');
            records = arr.map((r, i) => ({ ...r, _lsIndex: i }));
        } else if (this.db) {
            const tx      = this.db.transaction(this.STORE, 'readwrite');
            const store   = tx.objectStore(this.STORE);
            records = await this._getAll(store);
        }

        if (records.length === 0) return;

        for (const record of records) {
            try {
                await window.axios.post('/servant/visits/sync', record);
                if (this.useLS) {
                    const arr = JSON.parse(localStorage.getItem(LS_KEY) || '[]');
                    arr.splice(record._lsIndex, 1);
                    localStorage.setItem(LS_KEY, JSON.stringify(arr));
                } else {
                    const tx    = this.db.transaction(this.STORE, 'readwrite');
                    const store = tx.objectStore(this.STORE);
                    store.delete(record.id);
                    await this._txDone(tx);
                }
            } catch (err) {
                if (err?.response?.status === 409) {
                    // Conflict — remove from queue and notify
                    if (this.useLS) {
                        const arr = JSON.parse(localStorage.getItem(LS_KEY) || '[]');
                        arr.splice(record._lsIndex, 1);
                        localStorage.setItem(LS_KEY, JSON.stringify(arr));
                    } else {
                        const tx    = this.db.transaction(this.STORE, 'readwrite');
                        const store = tx.objectStore(this.STORE);
                        store.delete(record.id);
                        await this._txDone(tx);
                    }
                    dispatchToLivewire('offlineSyncConflict', {
                        beneficiaryId: record.beneficiary_id,
                        message: 'A visit for this beneficiary already exists on the server.',
                    });
                }
                // Other errors: leave in queue for next retry
            }
        }

        this._notifyCount();
    },

    // Called manually from UI (e.g., "Retry" button)
    async retryConflicts() {
        await this._syncPending();
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