{{-- Anonymous cases are tracked only in the sender's own browser (localStorage); the server never links them to the sender. --}}
@php
    $suDeviceStoreKey = 'speakup_saved_' . substr(hash_hmac('sha256', $submitter['type'] . ':' . $submitter['id'], (string) config('app.key')), 0, 20);
@endphp
<script>
    window.SpeakUpDevice = window.SpeakUpDevice || (function () {
        var storeKey = @json($suDeviceStoreKey);
        function read() {
            try { var list = JSON.parse(localStorage.getItem(storeKey) || '[]'); return Array.isArray(list) ? list : []; } catch (e) { return []; }
        }
        function write(list) {
            try { localStorage.setItem(storeKey, JSON.stringify(list.slice(0, 20))); return true; } catch (e) { return false; }
        }
        return {
            list: read,
            has: function (ref) { return read().some(function (i) { return i.reference_no === ref; }); },
            save: function (ref, key) {
                var list = read().filter(function (i) { return i.reference_no !== ref; });
                list.unshift({ reference_no: ref, follow_up_key: key, saved_at: new Date().toISOString() });
                return write(list);
            },
            remove: function (ref) { write(read().filter(function (i) { return i.reference_no !== ref; })); }
        };
    })();
</script>
