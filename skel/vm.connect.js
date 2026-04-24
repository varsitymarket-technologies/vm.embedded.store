/**
 * Varsity Market Store Connector
 * Dynamic script that allows themes to connect their website with the VM API.
 *
 * Usage in themes:
 *   <script src="vm.connect.js" data-api="api.php" data-currency="R" data-store="My Store"></script>
 *
 * Or auto-detect (no attributes needed if api.php is in the same directory):
 *   <script src="vm.connect.js"></script>
 *
 * This script:
 *   1. Auto-detects the API endpoint from the script tag or same-origin path
 *   2. Initializes StoreConfig globally for theme use
 *   3. Dynamically loads vm.api.js and vm.theme.js in the correct order
 *   4. Exposes a ready callback: VMStore.onReady(callback)
 *
 * Version: 1.0.0
 */

(function() {
    'use strict';

    // Find this script's tag to read data attributes
    const currentScript = document.currentScript || (function() {
        const scripts = document.getElementsByTagName('script');
        return scripts[scripts.length - 1];
    })();

    const scriptDir = currentScript.src.substring(0, currentScript.src.lastIndexOf('/') + 1);

    // Read configuration from data attributes or defaults
    const config = {
        apiEndpoint: currentScript.getAttribute('data-api') || (scriptDir + 'api.php'),
        currency: currentScript.getAttribute('data-currency') || null,
        storeName: currentScript.getAttribute('data-store') || null,
        apiScript: currentScript.getAttribute('data-api-js') || (scriptDir + 'vm.api.js'),
        themeScript: currentScript.getAttribute('data-theme-js') || (scriptDir + 'vm.theme.js'),
        autoInit: currentScript.getAttribute('data-auto-init') !== 'false'
    };

    // Initialize global StoreConfig early so scripts can reference it
    window.StoreConfig = window.StoreConfig || {};
    window.StoreConfig.apiEndpoint = config.apiEndpoint;
    if (config.currency) window.StoreConfig.currency = config.currency;
    if (config.storeName) window.StoreConfig.name = config.storeName;

    // Ready callbacks queue
    const readyCallbacks = [];
    let isReady = false;

    // Public API
    window.VMStore = {
        config: window.StoreConfig,

        onReady: function(callback) {
            if (isReady) {
                callback(window.VMThemeEngine);
            } else {
                readyCallbacks.push(callback);
            }
        },

        getApi: function() {
            return window.VMThemeEngine ? window.VMThemeEngine.api : null;
        },

        getCart: function() {
            return window.VMCart || null;
        }
    };

    // Script loader helper
    function loadScript(src) {
        return new Promise(function(resolve, reject) {
            var script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = function() {
                reject(new Error('Failed to load: ' + src));
            };
            document.head.appendChild(script);
        });
    }

    // Fire ready callbacks
    function fireReady() {
        isReady = true;
        for (var i = 0; i < readyCallbacks.length; i++) {
            try {
                readyCallbacks[i](window.VMThemeEngine);
            } catch(e) {
                console.error('VMStore.onReady callback error:', e);
            }
        }
    }

    // Load scripts sequentially: api first, then theme engine
    function bootstrap() {
        loadScript(config.apiScript)
            .then(function() {
                return loadScript(config.themeScript);
            })
            .then(function() {
                // VMTheme initializes on DOMContentLoaded, but if DOM is already loaded
                // we need to wait a tick for it to initialize
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        // Give VMTheme time to init and load config
                        setTimeout(fireReady, 100);
                    });
                } else {
                    // DOM already loaded — VMTheme should init immediately
                    // If auto-init is enabled and VMThemeEngine doesn't exist yet, create it
                    if (config.autoInit && !window.VMThemeEngine && typeof VMTheme !== 'undefined') {
                        window.VMThemeEngine = new VMTheme();
                    }
                    setTimeout(fireReady, 100);
                }
            })
            .catch(function(error) {
                console.error('VMStore: Failed to load store scripts.', error);
            });
    }

    // Start bootstrap
    bootstrap();
})();
