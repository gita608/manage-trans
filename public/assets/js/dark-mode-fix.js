/**
 * Dark Mode Persistence Fix
 * Ensures theme preference persists across page loads and handles race conditions
 */

(function() {
    'use strict';
    
    // Intercept theme toggle button clicks and save immediately to prevent race conditions
    document.addEventListener('DOMContentLoaded', function() {
        const darkModeBtn = document.querySelector('.light-dark-mode');
        
        if (darkModeBtn) {
            // Use capture phase to run BEFORE the original handler
            darkModeBtn.addEventListener('click', function() {
                // Predict the next theme state and save it immediately
                const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                // Save to localStorage synchronously before any page navigation
                localStorage.setItem('data-bs-theme', nextTheme);
                
            }, true); // Capture phase ensures this runs first
        }
    });
    
    // Sync theme changes across browser tabs
    window.addEventListener('storage', function(e) {
        if (e.key === 'data-bs-theme' && e.newValue) {
            document.documentElement.setAttribute('data-bs-theme', e.newValue);
        }
    });
})();

