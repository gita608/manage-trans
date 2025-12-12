/**
 * Dynamic Plugin Loader
 * Loads plugins only when needed, using proper script injection instead of document.write
 */
(function() {
    'use strict';
    
    // Check if any plugins are needed
    var needsToastify = document.querySelectorAll("[toast-list]").length > 0;
    var needsChoices = document.querySelectorAll("[data-choices]").length > 0;
    var needsFlatpickr = document.querySelectorAll("[data-provider]").length > 0;
    
    if (!needsToastify && !needsChoices && !needsFlatpickr) {
        return; // No plugins needed
    }
    
    // Function to load a script dynamically
    function loadScript(src, callback) {
        // Check if script is already loaded
        var existingScript = document.querySelector('script[src="' + src + '"]');
        if (existingScript) {
            if (callback) callback();
            return;
        }
        
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = src;
        script.async = false; // Load in order
        
        if (callback) {
            script.onload = callback;
            script.onerror = function() {
                console.error('Failed to load script: ' + src);
            };
        }
        
        document.head.appendChild(script);
    }
    
    // Load scripts in order
    var scriptsToLoad = [];
    
    if (needsToastify) {
        scriptsToLoad.push('assets/libs/toastify-js/src/toastify.js');
    }
    if (needsChoices) {
        scriptsToLoad.push('assets/libs/choices.js/public/assets/scripts/choices.min.js');
    }
    if (needsFlatpickr) {
        scriptsToLoad.push('assets/libs/flatpickr/flatpickr.min.js');
    }
    
    // Load scripts sequentially
    function loadNext(index) {
        if (index >= scriptsToLoad.length) {
            return; // All scripts loaded
        }
        
        loadScript(scriptsToLoad[index], function() {
            loadNext(index + 1);
        });
    }
    
    // Start loading
    if (scriptsToLoad.length > 0) {
        loadNext(0);
    }
})();