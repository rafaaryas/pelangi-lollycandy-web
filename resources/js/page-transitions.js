import { initTransactionForm } from './transaction-form';

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const progress = document.querySelector('[data-route-progress]');
let progressTimer;
let navigationController;

function startProgress() {
    if (!progress) return;
    window.clearTimeout(progressTimer);
    progress.classList.remove('is-complete');
    void progress.offsetWidth;
    progress.classList.add('is-loading');
}

function finishProgress() {
    if (!progress) return;
    progress.classList.add('is-complete');
    progressTimer = window.setTimeout(() => {
        progress.classList.remove('is-loading', 'is-complete');
    }, reducedMotion.matches ? 0 : 190);
}

function skeletonMarkup() {
    return `
        <div class="admin-route-skeleton" aria-label="Memuat halaman" aria-busy="true">
            <div class="admin-route-skeleton-head">
                <span class="admin-route-skeleton-line"></span>
                <span class="admin-route-skeleton-line"></span>
            </div>
            <div class="admin-route-skeleton-grid">
                <span class="admin-route-skeleton-card"></span>
                <span class="admin-route-skeleton-card"></span>
                <span class="admin-route-skeleton-card"></span>
            </div>
            <span class="admin-route-skeleton-card admin-route-skeleton-table"></span>
        </div>`;
}

function syncAdminNavigation(nextDocument) {
    const currentLinks = [...document.querySelectorAll('.admin-nav-link[href]')];
    const nextLinks = [...nextDocument.querySelectorAll('.admin-nav-link[href]')];
    currentLinks.forEach((link) => {
        const matching = nextLinks.find((candidate) => candidate.href === link.href);
        link.classList.toggle('is-active', matching?.classList.contains('is-active') ?? false);
        link.classList.remove('is-pending');
        if (matching?.hasAttribute('aria-current')) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
}

function initializeInsertedAdminContent(main) {
    main.querySelectorAll('[data-transaction-form]').forEach(initTransactionForm);
    main.querySelectorAll('[data-image-preview-img]').forEach((image) => {
        image.dataset.originalSrc = image.getAttribute('src') || '';
    });
    main.querySelectorAll('[data-toast]').forEach((toast) => {
        const dismiss = () => toast.remove();
        toast.querySelector('[data-toast-dismiss]')?.addEventListener('click', dismiss);
        window.setTimeout(dismiss, 3800);
    });
    document.dispatchEvent(new CustomEvent('admin:navigated', { detail: { main } }));
}

async function loadAdminPage(url, { push = true, focus = true } = {}) {
    const main = document.querySelector('[data-admin-main]');
    if (!main) {
        window.location.assign(url.href);
        return;
    }

    navigationController?.abort();
    navigationController = new AbortController();
    const currentController = navigationController;
    startProgress();
    document.body.classList.add('admin-is-loading');
    main.innerHTML = skeletonMarkup();

    try {
        const response = await fetch(url, {
            headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store',
            signal: currentController.signal,
        });
        if (!response.ok) throw new Error('Halaman tidak dapat dimuat.');
        const resolvedUrl = new URL(response.url);
        if (!resolvedUrl.pathname.startsWith('/admin') || resolvedUrl.pathname === '/admin/login') {
            window.location.assign(response.url);
            return;
        }

        const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
        const nextMain = nextDocument.querySelector('[data-admin-main]');
        if (!nextMain || !nextDocument.body.classList.contains('admin-body')) throw new Error('Respons halaman tidak lengkap.');
        if (currentController !== navigationController) return;

        main.innerHTML = nextMain.innerHTML;
        document.title = nextDocument.title;
        syncAdminNavigation(nextDocument);
        if (push) window.history.pushState({ adminPartial: true }, '', resolvedUrl);
        if (focus) {
            window.scrollTo({ top: 0, behavior: 'instant' });
            main.focus({ preventScroll: true });
        }
        main.classList.remove('admin-content-enter');
        void main.offsetWidth;
        main.classList.add('admin-content-enter');
        initializeInsertedAdminContent(main);
        finishProgress();
    } catch (error) {
        if (error.name === 'AbortError') return;
        window.location.assign(url.href);
    } finally {
        if (currentController === navigationController) document.body.classList.remove('admin-is-loading');
    }
}

function isPlainNavigation(event, link) {
    return !event.defaultPrevented
        && event.button === 0
        && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey
        && !link.hasAttribute('download')
        && (!link.target || link.target === '_self')
        && !link.matches('[data-modal-open], [data-transaction-url], [data-no-transition]');
}

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || !isPlainNavigation(event, link)) return;
    const next = new URL(link.href, location.href);
    if (next.origin !== location.origin) return;
    if (next.pathname === location.pathname && next.search === location.search) return;

    const isAdminPartial = document.body.classList.contains('admin-body')
        && next.pathname.startsWith('/admin')
        && next.pathname !== '/admin/login';

    if (isAdminPartial) {
        event.preventDefault();
        document.querySelectorAll('.admin-nav-link.is-pending').forEach((item) => item.classList.remove('is-pending'));
        link.closest('.admin-nav-link')?.classList.add('is-pending');
        loadAdminPage(next);
        return;
    }

    if (reducedMotion.matches) {
        startProgress();
        return;
    }
    event.preventDefault();
    startProgress();
    link.classList.add('is-pending');
    document.body.classList.add('page-is-leaving');
    window.setTimeout(() => window.location.assign(next.href), 110);
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
    if (form.method.toLowerCase() !== 'get' || form.dataset.noLoading !== 'true') startProgress();
});

window.addEventListener('popstate', () => {
    if (!document.body.classList.contains('admin-body')) return;
    loadAdminPage(new URL(window.location.href), { push: false, focus: false });
});

window.addEventListener('pageshow', () => {
    document.body.classList.remove('page-is-leaving', 'admin-is-loading');
    finishProgress();
});
