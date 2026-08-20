(function () {
    var INTERVAL_MS = 20000;
    var countUrl = document.documentElement.getAttribute('data-partner-request-pending-count-url');
    var liveUrl = document.documentElement.getAttribute('data-partner-request-live-url');
    var timer = null;
    var lastCount = null;

    if (!countUrl) {
        return;
    }

    function isFilterFormActive() {
        var toolbar = document.querySelector('.partner-review-filter-toolbar');
        var results = document.querySelector('[data-partner-request-live-part="results"]');
        var active = document.activeElement;

        return !!(active && (
            (toolbar && toolbar.contains(active)) ||
            (results && results.contains(active))
        ));
    }

    function pulse(el) {
        if (!el) {
            return;
        }

        el.classList.remove('partner-request-badge-pulse');
        void el.offsetWidth;
        el.classList.add('partner-request-badge-pulse');
        window.setTimeout(function () {
            el.classList.remove('partner-request-badge-pulse');
        }, 1400);
    }

    function setBadge(el, count, pulseOnIncrease) {
        if (!el) {
            return;
        }

        var previous = parseInt(el.textContent, 10);
        if (Number.isNaN(previous)) {
            previous = 0;
        }

        el.textContent = String(count);

        if (count > 0) {
            el.removeAttribute('hidden');
        } else {
            el.setAttribute('hidden', 'hidden');
        }

        if (pulseOnIncrease && count > previous) {
            pulse(el);
        }
    }

    function updateCountBadges(count) {
        var increased = lastCount !== null && count > lastCount;
        setBadge(document.querySelector('[data-partner-request-pending-badge]'), count, increased);

        var tabBadge = document.querySelector('[data-partner-request-pending-tab-badge]');
        if (tabBadge) {
            var queuePage = document.querySelector('[data-partner-request-queue-page]');
            var pendingTabIsActive = queuePage && queuePage.getAttribute('data-partner-request-status') === 'pending';

            tabBadge.textContent = String(count);
            if (!pendingTabIsActive && count > 0) {
                tabBadge.removeAttribute('hidden');
                if (increased) {
                    pulse(tabBadge);
                }
            } else {
                tabBadge.setAttribute('hidden', 'hidden');
            }
        }

        lastCount = count;
    }

    function refreshCount() {
        return fetch(countUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok) {
                return null;
            }
            return response.json();
        }).then(function (data) {
            if (!data || typeof data.pending_count !== 'number') {
                return;
            }
            updateCountBadges(data.pending_count);
        }).catch(function () {});
    }

    function refreshQueue() {
        if (!liveUrl || !document.querySelector('[data-partner-request-queue-page]')) {
            return Promise.resolve();
        }

        if (isFilterFormActive()) {
            return Promise.resolve();
        }

        var url = liveUrl + window.location.search;

        return fetch(url, {
            headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok) {
                return null;
            }
            return response.text();
        }).then(function (html) {
            if (!html || isFilterFormActive()) {
                return;
            }

            var parsed = new DOMParser().parseFromString(html, 'text/html');
            ['banner', 'results'].forEach(function (part) {
                var incoming = parsed.querySelector('[data-partner-request-live-part="' + part + '"]');
                var current = document.querySelector('[data-partner-request-live-part="' + part + '"]');
                if (!incoming || !current || current.innerHTML === incoming.innerHTML) {
                    return;
                }
                current.innerHTML = incoming.innerHTML;
            });
        }).catch(function () {});
    }

    function tick() {
        if (document.hidden) {
            return;
        }

        refreshCount();
        refreshQueue();
    }

    function start() {
        stop();
        timer = window.setInterval(tick, INTERVAL_MS);
    }

    function stop() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stop();
            return;
        }
        tick();
        start();
    });

    if (!document.hidden) {
        start();
    }
})();
