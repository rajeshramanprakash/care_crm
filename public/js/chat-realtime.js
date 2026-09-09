(function () {
    function debugLog() {
        if (!window.chatRealtimeDebug || !window.console || typeof window.console.log !== "function") {
            return;
        }
        window.console.log.apply(window.console, arguments);
    }

    function logUnavailable(reason) {
        if (window.console && typeof window.console.warn === "function") {
            window.console.warn("[ChatRealtime] disabled:", reason);
        }
    }

    function resolveEchoCtor() {
        if (typeof window.Echo === "function") {
            debugLog("[ChatRealtime] using window.Echo constructor");
            return window.Echo;
        }
        if (window.Echo && typeof window.Echo.default === "function") {
            debugLog("[ChatRealtime] using window.Echo.default constructor");
            return window.Echo.default;
        }
        if (typeof window.LaravelEcho === "function") {
            debugLog("[ChatRealtime] using window.LaravelEcho constructor");
            return window.LaravelEcho;
        }
        return null;
    }

    function buildEcho() {
        if (!window.Pusher) {
            logUnavailable("window.Pusher missing");
            return null;
        }
        if (typeof window.Pusher !== "function") {
            logUnavailable("window.Pusher is not a constructor");
            return null;
        }

        var EchoCtor = resolveEchoCtor();
        if (!EchoCtor) {
            logUnavailable("Echo constructor missing");
            return null;
        }

        var cfg = window.chatRealtimeConfig || {};
        if (cfg.reverbEnabled === false) {
            logUnavailable("reverb websocket client disabled (start reverb or set REVERB_CLIENT_ENABLED=true)");
            return null;
        }
        if (!cfg.enabled && !cfg.whatsappEnabled) {
            logUnavailable("feature flags disabled");
            return null;
        }

        if (!cfg.key) {
            logUnavailable("missing realtime key");
            return null;
        }

        if (window.__carecrmEcho) {
            return window.__carecrmEcho;
        }

        window.Pusher.logToConsole = false;
        try {
            window.__carecrmEcho = new EchoCtor({
                broadcaster: "reverb",
                key: cfg.key,
                cluster: "",
                wsHost: cfg.wsHost,
                wsPort: cfg.wsPort || 8080,
                wssPort: cfg.wsPort || 8080,
                forceTLS: String(cfg.scheme || "http").toLowerCase() === "https",
                enabledTransports: ["ws", "wss"],
                authEndpoint: "/broadcasting/auth",
                auth: {
                    headers: {
                        "X-CSRF-TOKEN": (document.querySelector('meta[name="csrf-token"]') || {}).content || ""
                    }
                }
            });
            debugLog("[ChatRealtime] echo initialized", {
                host: cfg.wsHost,
                port: cfg.wsPort || 8080,
                tls: String(cfg.scheme || "http").toLowerCase() === "https"
            });
        } catch (e) {
            if (window.console && typeof window.console.error === "function") {
                window.console.error("[ChatRealtime] echo init exception:", e);
            }
            logUnavailable(e && e.message ? ("echo init failed: " + e.message) : "echo init failed");
            return null;
        }

        return window.__carecrmEcho;
    }

    window.ChatRealtime = {
        getEcho: buildEcho,
        subscribeUserChannel: function (userId, callback) {
            var echo = buildEcho();
            if (!echo || !userId) return null;
            return echo.private("chat.user." + userId)
                .listen(".direct.message.created", function (payload) {
                    callback(payload, ".direct.message.created");
                })
                .listen(".direct.messages.read", function (payload) {
                    callback(payload, ".direct.messages.read");
                });
        },
        subscribeWhatsappNumber: function (number, callback) {
            var echo = buildEcho();
            if (!echo || !number) return null;
            return echo.private("whatsapp.number." + number).listen(".whatsapp.chat.updated", callback);
        }
    };
})();
