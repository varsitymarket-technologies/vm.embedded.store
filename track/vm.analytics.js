/**
 * VarsityMarket Analytics Tag
 * Lightweight page view tracker — embed on your storefront.
 *
 * Usage:
 *   <script src="https://yourhost/track/vm.analytics.js"
 *           data-store-id="YOUR_STORE_ID" defer></script>
 */
(function () {
    'use strict';

    var script = document.currentScript;
    if (!script) return;

    var storeId = script.getAttribute('data-store-id');
    if (!storeId) return;

    // Derive the tracking endpoint from the script's own URL
    var src = script.src || '';
    var endpoint = src.replace(/\/vm\.analytics\.js.*$/, '/');

    function send(event) {
        var data = {
            sid: storeId,
            p: location.pathname + location.search,
            r: document.referrer || '',
            t: document.title || '',
            e: event || 'pageview',
            w: String(window.innerWidth || screen.width)
        };

        // Prefer sendBeacon for reliability (fires even on page unload)
        if (navigator.sendBeacon) {
            navigator.sendBeacon(
                endpoint + '?sid=' + encodeURIComponent(storeId),
                JSON.stringify(data)
            );
        } else {
            // Fallback: pixel request
            var img = new Image();
            img.src = endpoint + '?sid=' + encodeURIComponent(storeId)
                + '&p=' + encodeURIComponent(data.p)
                + '&r=' + encodeURIComponent(data.r)
                + '&t=' + encodeURIComponent(data.t)
                + '&e=' + encodeURIComponent(data.e)
                + '&w=' + encodeURIComponent(data.w);
        }
    }

    // Track initial page view once DOM is ready
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        send('pageview');
    } else {
        document.addEventListener('DOMContentLoaded', function () { send('pageview'); });
    }

    // Expose a global for custom event tracking
    window.vmAnalytics = {
        track: function (eventName) { send(eventName || 'custom'); }
    };
})();
