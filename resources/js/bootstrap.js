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
        const customHost = import.meta.env.VITE_PUSHER_HOST;
        const customPort = Number(import.meta.env.VITE_PUSHER_PORT || 443);
        const customScheme = import.meta.env.VITE_PUSHER_SCHEME || "https";
        const forceTLS = ["https", "wss"].includes(customScheme);

        const echoOptions = {
            broadcaster: "pusher",
            key: pusherKey,
            cluster:
                import.meta.env.VITE_PUSHER_APP_CLUSTER ||
                import.meta.env.MIX_PUSHER_APP_CLUSTER ||
                undefined,
            forceTLS,
            encrypted: forceTLS,
            disableStats: true,
        };

        // Self-hosted Pusher-compatible websocket servers can use the same
        // VITE_PUSHER_* names documented in .env.example.
        if (customHost) {
            echoOptions.wsHost = customHost;
            echoOptions.wsPort = customPort;
            echoOptions.wssPort = customPort;
            echoOptions.enabledTransports = ["ws", "wss"];
        }

        window.Echo = new Echo(echoOptions);
    }
} catch (error) {
    console.warn("[bootstrap] Echo/Pusher initialization failed:", error);
}
