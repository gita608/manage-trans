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
})();

