/**
 * App.js Compatibility Wrapper
 * Ensures required elements exist before app.js runs to prevent errors
 * This runs synchronously to ensure elements exist before app.js executes
 */
(function() {
    'use strict';
    
    // Run immediately - don't wait for DOMContentLoaded
    // This ensures the element exists when app.js tries to access it
    (function ensureNavbarMenu() {
        // Check if .navbar-menu exists
        var navbarMenu = document.querySelector('.navbar-menu');
        if (!navbarMenu) {
            // Create a dummy element if it doesn't exist to prevent errors
            // This will be created in the body, but hidden
            var dummyMenu = document.createElement('div');
            dummyMenu.className = 'navbar-menu';
            dummyMenu.style.display = 'none';
            dummyMenu.innerHTML = '';
            
            // Try to append to body, or wait for it if not ready
            if (document.body) {
                document.body.appendChild(dummyMenu);
            } else {
                // If body doesn't exist yet, wait for it
                var observer = new MutationObserver(function(mutations, obs) {
                    if (document.body) {
                        document.body.appendChild(dummyMenu);
                        obs.disconnect();
                    }
                });
                observer.observe(document.documentElement, {
                    childList: true,
                    subtree: true
                });
            }
        }
    })();

    // Catch-all safety proxy for optional theme element IDs accessed by app.js
    var dummyElementCache = {};
    var optionalThemeIds = [
        'navbar-menu',
        'vertical-hover',
        'removeNotificationModal',
        'delete-notification',
        'NotificationModalbtn-close',
        'notificationDropdown',
        'notification-actions',
        'sidebar-size-small-hover',
        'sidebar-size-default',
        'layout-width-fluid',
        'sidebar-view-default',
        'sidebar-color-gradient',
        'collapseBgGradient',
        'search-close-options',
        'search-dropdown',
        'search-options',
        'search-dropdown-reponsive',
        'search-options-reponsive',
        'cart-item-total',
        'empty-cart',
        'checkout-elem',
        'reset-layout'
    ];

    function createDummy(id) {
        if (!dummyElementCache[id]) {
            var dummy = document.createElement('div');
            dummy.id = id;
            dummy.style.display = 'none';
            dummy.click = function() {};
            dummy.addEventListener = function() {};
            dummy.removeEventListener = function() {};
            dummy.setAttribute = function() {};
            dummy.getAttribute = function() { return null; };
            dummy.removeAttribute = function() {};
            dummy.querySelector = function() { return null; };
            dummy.querySelectorAll = function() { return []; };
            dummyElementCache[id] = dummy;
        }
        return dummyElementCache[id];
    }

    var originalGetElementById = document.getElementById.bind(document);
    document.getElementById = function(id) {
        var el = originalGetElementById(id);
        if (!el && optionalThemeIds.indexOf(id) !== -1) {
            return createDummy(id);
        }
        return el;
    };
})();

