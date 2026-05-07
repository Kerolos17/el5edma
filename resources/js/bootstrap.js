import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Initialize Laravel Echo (Pusher) if env vars are present
import Echo from "laravel-echo";
import Pusher from "pusher-js";

try {
    window.Pusher = Pusher;

    const pusherKey =
        import.meta.env.VITE_PUSHER_APP_KEY ||
        import.meta.env.MIX_PUSHER_APP_KEY;
    if (pusherKey) {
        const echoOptions = {
            broadcaster: "pusher",
            key: pusherKey,
            cluster:
                import.meta.env.VITE_PUSHER_APP_CLUSTER ||
                import.meta.env.MIX_PUSHER_APP_CLUSTER ||
                undefined,
            forceTLS:
                (import.meta.env.VITE_PUSHER_FORCE_TLS || "true") === "true",
            encrypted:
                (import.meta.env.VITE_PUSHER_FORCE_TLS || "true") === "true",
            disableStats: true,
        };

        // For self-hosted websockets you may override host/port via env
        if (import.meta.env.VITE_WEBSOCKETS_HOST) {
            echoOptions.wsHost = import.meta.env.VITE_WEBSOCKETS_HOST;
            echoOptions.wsPort = import.meta.env.VITE_WEBSOCKETS_PORT || 6001;
            echoOptions.forceTLS = false;
            echoOptions.encrypted = false;
            echoOptions.enabledTransports = ["ws", "wss"];
        }

        window.Echo = new Echo(echoOptions);
    }
} catch (e) {
    console.warn("[bootstrap] Echo/Pusher initialization failed:", e);
}
