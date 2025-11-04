/**
 * Dark Mode Persistence Fix
 * This script ensures dark mode preference persists across page loads
 */

(function() {
    'use strict';
    
    // On page load, check localStorage for theme preference
    function initTheme() {
        const savedTheme = localStorage.getItem('data-bs-theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            // Also update sessionStorage to keep consistency
            sessionStorage.setItem('data-bs-theme', savedTheme);
        }
    }
    
    // Initialize theme immediately (before page renders)
    initTheme();
    
    // Wait for DOM to be ready to set up the click handler
    document.addEventListener('DOMContentLoaded', function() {
        const darkModeBtn = document.querySelector('.light-dark-mode');
        
        if (darkModeBtn) {
            darkModeBtn.addEventListener('click', function() {
                // Wait a tiny bit for the original handler to execute
                setTimeout(function() {
                    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                    if (currentTheme) {
                        // Save to localStorage for persistence
                        localStorage.setItem('data-bs-theme', currentTheme);
                        // Also keep sessionStorage updated
                        sessionStorage.setItem('data-bs-theme', currentTheme);
                    }
                }, 50);
            });
        }
    });
    
    // Listen for storage events (if theme is changed in another tab)
    window.addEventListener('storage', function(e) {
        if (e.key === 'data-bs-theme' && e.newValue) {
            document.documentElement.setAttribute('data-bs-theme', e.newValue);
            sessionStorage.setItem('data-bs-theme', e.newValue);
        }
    });
})();

