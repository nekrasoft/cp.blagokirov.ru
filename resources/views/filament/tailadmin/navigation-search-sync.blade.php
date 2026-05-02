<script>
    (() => {
        const state = window.__blagoNavigationSearchSync ??= { initialized: false };

        const eventName = 'blago:navigation-search-sync';
        const searchParameter = 'search';
        const navigationLinkSelector = [
            '[data-preserve-navigation-search] a[href]',
            'a[data-preserve-navigation-search][href]',
        ].join(',');

        const readSearch = () => {
            const value = new URLSearchParams(window.location.search).get(searchParameter);

            return value?.trim() || null;
        };

        const syncNavigationLinks = () => {
            const search = readSearch();

            document.querySelectorAll(navigationLinkSelector).forEach((link) => {
                const href = link.getAttribute('href');

                if (! href) {
                    return;
                }

                let url;

                try {
                    url = new URL(href, window.location.origin);
                } catch {
                    return;
                }

                if (url.origin !== window.location.origin) {
                    return;
                }

                if (search) {
                    url.searchParams.set(searchParameter, search);
                } else {
                    url.searchParams.delete(searchParameter);
                }

                link.setAttribute('href', url.toString());
            });
        };

        const scheduleSync = () => window.requestAnimationFrame(syncNavigationLinks);

        if (state.initialized) {
            scheduleSync();

            return;
        }

        state.initialized = true;

        ['pushState', 'replaceState'].forEach((method) => {
            const original = window.history[method];

            window.history[method] = function (...args) {
                const result = original.apply(this, args);

                window.dispatchEvent(new Event(eventName));

                return result;
            };
        });

        window.addEventListener(eventName, scheduleSync);
        window.addEventListener('popstate', scheduleSync);
        document.addEventListener('livewire:navigated', scheduleSync);
        document.addEventListener('livewire:init', scheduleSync);
        document.addEventListener('DOMContentLoaded', scheduleSync);

        scheduleSync();
    })();
</script>
