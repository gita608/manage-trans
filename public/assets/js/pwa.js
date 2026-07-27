/**
 * Service worker registration and "add to home screen" affordances.
 *
 * Chrome/Edge (Android + desktop) fire `beforeinstallprompt`, which gives a real
 * install dialog. iOS Safari has no such event, so it gets manual instructions.
 */
(function () {
    'use strict';

    var DISMISS_KEY = 'mt-install-dismissed';

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.matchMedia('(display-mode: minimal-ui)').matches
            || window.navigator.standalone === true;
    }

    function isIos() {
        var ua = window.navigator.userAgent;

        return /iPad|iPhone|iPod/.test(ua)
            // iPadOS 13+ reports as desktop Safari but still has touch points.
            || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
    }

    function wasDismissed() {
        try {
            return window.localStorage.getItem(DISMISS_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function remember() {
        try {
            window.localStorage.setItem(DISMISS_KEY, '1');
        } catch (e) {
            /* storage unavailable (private mode) — just skip persistence */
        }
    }

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {
                /* registration is best-effort; the app works without it */
            });
        });
    }

    if (isStandalone()) {
        return;
    }

    var promptEvent = null;

    // Chrome fires this as soon as the page is eligible, often before DOMContentLoaded,
    // so it is captured here at parse time and applied to the UI once the DOM exists.
    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        promptEvent = event;

        var trigger = document.getElementById('pwa-install-btn');

        if (trigger) {
            trigger.classList.remove('d-none');
        }
    });

    /**
     * Pages without a topbar (login, error pages) get a floating pill instead.
     */
    function resolveTrigger() {
        var existing = document.getElementById('pwa-install-btn');

        if (existing) {
            return existing;
        }

        var floating = document.createElement('button');
        floating.type = 'button';
        floating.id = 'pwa-install-btn';
        floating.className = 'pwa-install-floating d-none';
        floating.innerHTML = '<i class="ri-download-2-line"></i><span>Install app</span>';
        document.body.appendChild(floating);

        return floating;
    }

    function show(trigger) {
        trigger.classList.remove('d-none');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var trigger = resolveTrigger();
        var iosHelp = document.getElementById('pwa-ios-help');

        trigger.addEventListener('click', function () {
            if (promptEvent) {
                promptEvent.prompt();
                promptEvent.userChoice.then(function () {
                    promptEvent = null;
                    trigger.classList.add('d-none');
                });

                return;
            }

            if (iosHelp && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(iosHelp).show();
            }
        });

        var dismiss = document.getElementById('pwa-install-dismiss');

        if (dismiss) {
            dismiss.addEventListener('click', function () {
                remember();
                trigger.classList.add('d-none');
            });
        }

        // The install event may already have fired before this point.
        if (promptEvent) {
            show(trigger);
        }

        // Safari never fires that event, so offer the manual route instead.
        if (isIos() && !wasDismissed()) {
            show(trigger);
        }
    });

    window.addEventListener('appinstalled', function () {
        var trigger = document.getElementById('pwa-install-btn');

        if (trigger) {
            trigger.classList.add('d-none');
        }

        promptEvent = null;
    });
})();
