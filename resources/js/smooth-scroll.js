const motionReduced = window.matchMedia('(prefers-reduced-motion: reduce)');

function syncAnchorNav() {
    if (!document.body.classList.contains('home-page')) return;
    const hash = window.location.hash;
    const links = [
        document.querySelector('[data-scroll-home]'),
        document.querySelector('[data-about-link]'),
        document.querySelector('.nav-menu a[href$="#marketplace-hub"]'),
    ];
    links.forEach((link, index) => {
        if (!link) return;
        const active = index === 0 ? !hash || !['#about', '#marketplace-hub'].includes(hash) : link.hash === hash;
        link.classList.toggle('is-active', active);
        if (active) link.setAttribute('aria-current', index === 0 ? 'page' : 'location');
        else link.removeAttribute('aria-current');
    });
}

function scrollToAnchor(hash, behavior = 'smooth') {
    const id = decodeURIComponent(hash.slice(1));
    const target = document.getElementById(id);
    if (!target) return false;
    target.scrollIntoView({ behavior: motionReduced.matches ? 'auto' : behavior, block: 'start' });
    return true;
}

document.querySelectorAll('a[href*="#"]').forEach((link) => {
    link.addEventListener('click', (event) => {
        const targetUrl = new URL(link.href, window.location.origin);
        if (targetUrl.origin !== window.location.origin || targetUrl.pathname !== window.location.pathname || !targetUrl.hash) return;
        if (!scrollToAnchor(targetUrl.hash)) return;
        event.preventDefault();
        window.history.pushState(null, '', targetUrl.hash);
        syncAnchorNav();
    });
});

document.querySelectorAll('a[data-scroll-home]').forEach((link) => {
    link.addEventListener('click', (event) => {
        const targetUrl = new URL(link.href, window.location.origin);
        if (targetUrl.origin !== window.location.origin || targetUrl.pathname !== window.location.pathname) return;
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: motionReduced.matches ? 'auto' : 'smooth' });
        window.history.pushState(null, '', targetUrl.pathname);
        syncAnchorNav();
    });
});

if (window.location.hash) {
    window.addEventListener('load', () => {
        // Let image dimensions settle before adjusting a cross-page anchor.
        window.requestAnimationFrame(() => scrollToAnchor(window.location.hash, 'auto'));
    }, { once: true });
}

window.addEventListener('hashchange', () => {
    if (window.location.hash) scrollToAnchor(window.location.hash);
    syncAnchorNav();
});

window.addEventListener('popstate', syncAnchorNav);
syncAnchorNav();
