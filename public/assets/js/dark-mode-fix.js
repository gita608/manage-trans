/**
 * Dark Mode Persistence Fix
 * Ensures theme preference persists across page loads and handles race conditions
 */

(function() {
    'use strict';
    
    // CRITICAL: Set theme IMMEDIATELY from localStorage before any other scripts run
    // This must run synchronously to prevent flash of wrong theme
    (function() {
        const savedTheme = localStorage.getItem('data-bs-theme');
        if (savedTheme && (savedTheme === 'dark' || savedTheme === 'light')) {
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            // Also sync to sessionStorage for layout.js compatibility
            sessionStorage.setItem('data-bs-theme', savedTheme);
        }
    })();
    
    // Intercept theme toggle button clicks and save immediately to prevent race conditions
    document.addEventListener('DOMContentLoaded', function() {
        const darkModeBtn = document.querySelector('.light-dark-mode');
        
        if (darkModeBtn) {
            // Use capture phase to run BEFORE the original handler
            darkModeBtn.addEventListener('click', function() {
                // Predict the next theme state and save it immediately
                const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                // Save to both localStorage and sessionStorage for compatibility
                localStorage.setItem('data-bs-theme', nextTheme);
                sessionStorage.setItem('data-bs-theme', nextTheme);
                
            }, true); // Capture phase ensures this runs first
        }
    });
    
    // Sync theme changes across browser tabs
    window.addEventListener('storage', function(e) {
        if (e.key === 'data-bs-theme' && e.newValue) {
            document.documentElement.setAttribute('data-bs-theme', e.newValue);
            sessionStorage.setItem('data-bs-theme', e.newValue);
        }
    });
    
    // Listen for theme changes from layout.js and sync to localStorage
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-bs-theme') {
                const theme = document.documentElement.getAttribute('data-bs-theme');
                if (theme) {
                    localStorage.setItem('data-bs-theme', theme);
                    sessionStorage.setItem('data-bs-theme', theme);
                }
            }
        });
    });
    
    // Start observing theme changes
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme']
    });
})();

