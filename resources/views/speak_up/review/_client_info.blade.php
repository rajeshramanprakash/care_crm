<script>
    window.speakUpClientInfo = function () {
        var n = window.navigator || {};
        var info = {};
        try { info.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone; } catch (e) {}
        info.tz_offset = String(-new Date().getTimezoneOffset() / 60);
        info.screen = (window.screen ? screen.width + 'x' + screen.height : '');
        info.viewport = window.innerWidth + 'x' + window.innerHeight;
        info.pixel_ratio = String(window.devicePixelRatio || 1);
        info.language = n.language || '';
        info.languages = (n.languages || []).join(',');
        info.platform = (n.userAgentData && n.userAgentData.platform) || n.platform || '';
        info.cores = String(n.hardwareConcurrency || '');
        info.memory = n.deviceMemory ? n.deviceMemory + ' GB' : '';
        info.touch = String(('ontouchstart' in window) || (n.maxTouchPoints > 0));
        info.cookies = String(!!n.cookieEnabled);
        info.local_time = new Date().toString();
        return JSON.stringify(info);
    };
</script>
