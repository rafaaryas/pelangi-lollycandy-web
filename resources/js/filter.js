const filterForm = document.querySelector('[data-filter-form]');

if (filterForm && window.fetch && window.AbortController) {
    const grid = document.querySelector('.catalog-products-grid');
    const resultsLine = document.querySelector('.catalog-results-line');
    const pagination = document.querySelector('.catalog-pagination');
    const feedback = document.querySelector('[data-catalog-feedback]');
    const submitButton = filterForm.querySelector('[type="submit"]');
    const defaultButtonLabel = submitButton.textContent;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let controller;
    let debounceTimer;

    const formUrl = () => {
        const url = new URL(filterForm.action);
        const parameters = new URLSearchParams(new FormData(filterForm));
        for (const [key, value] of [...parameters.entries()]) {
            if (!String(value).trim()) parameters.delete(key);
        }
        url.search = parameters.toString();
        return url;
    };

    const syncForm = (url) => {
        for (const name of ['q', 'category', 'sort']) {
            const field = filterForm.elements.namedItem(name);
            if (field) field.value = url.searchParams.get(name) ?? (name === 'sort' ? 'latest' : '');
        }
    };

    const setLoading = (loading) => {
        grid.setAttribute('aria-busy', String(loading));
        filterForm.setAttribute('aria-busy', String(loading));
        filterForm.classList.toggle('is-loading', loading);
        submitButton.disabled = loading;
        submitButton.textContent = loading ? 'Mencari…' : defaultButtonLabel;
    };

    const loadProducts = async (url, addHistory = true) => {
        controller?.abort();
        controller = new AbortController();
        const currentRequest = controller;
        feedback.hidden = true;
        setLoading(true);

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: currentRequest.signal,
            });
            if (!response.ok) throw new Error('Catalog request failed');
            const nextPage = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextGrid = nextPage.querySelector('.catalog-products-grid');
            const nextResults = nextPage.querySelector('.catalog-results-line');
            const nextPagination = nextPage.querySelector('.catalog-pagination');
            if (!nextGrid || !nextResults || !nextPagination) throw new Error('Catalog response incomplete');

            grid.innerHTML = nextGrid.innerHTML;
            resultsLine.innerHTML = nextResults.innerHTML;
            pagination.innerHTML = nextPagination.innerHTML;
            syncForm(url);
            if (addHistory) window.history.pushState(null, '', url);
            if (!reducedMotion.matches && grid.animate) {
                grid.animate([
                    { opacity: .86, transform: 'translateY(4px)' },
                    { opacity: 1, transform: 'translateY(0)' },
                ], { duration: 180, easing: 'ease-out' });
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                feedback.textContent = 'Produk belum bisa dimuat. Coba lagi sebentar.';
                feedback.hidden = false;
            }
        } finally {
            if (controller === currentRequest) setLoading(false);
        }
    };

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        window.clearTimeout(debounceTimer);
        loadProducts(formUrl());
    });

    filterForm.querySelectorAll('select').forEach((field) => {
        field.addEventListener('change', () => {
            window.clearTimeout(debounceTimer);
            loadProducts(formUrl());
        });
    });

    filterForm.querySelector('[name="q"]').addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => loadProducts(formUrl()), 260);
    });

    document.querySelector('.catalog-page')?.addEventListener('click', (event) => {
        const link = event.target.closest('.catalog-pagination a, .catalog-results-line a, .catalog-empty a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href);
        if (url.origin !== window.location.origin || url.pathname !== new URL(filterForm.action).pathname) return;
        event.preventDefault();
        window.clearTimeout(debounceTimer);
        loadProducts(url);
    });

    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        syncForm(url);
        loadProducts(url, false);
    });
}
