
document.querySelectorAll('a[href*="#"]').forEach((link) => {
    link.addEventListener('click', (event) => {
        const targetUrl = new URL(link.href, window.location.origin);
        const isSamePage = targetUrl.origin === window.location.origin
            && targetUrl.pathname === window.location.pathname;

        if (!isSamePage || !targetUrl.hash) {
            return;
        }

        const target = document.querySelector(targetUrl.hash);

        if (!target) {
            return;
        }

        event.preventDefault();

        const navbar = document.querySelector('[data-navbar]');
        const navbarHeight = navbar?.offsetHeight ?? 0;
        const targetTop = target.getBoundingClientRect().top + window.scrollY - navbarHeight - 12;

        window.scrollTo({
            top: targetTop,
            behavior: 'smooth',
        });

        document.querySelector('[data-nav-menu]')?.classList.remove('is-open');
        window.history.pushState(null, '', targetUrl.hash);
    });
});

document.querySelectorAll('a[data-scroll-home]').forEach((link) => {
    link.addEventListener('click', (event) => {
        const targetUrl = new URL(link.href, window.location.origin);
        const isSamePage = targetUrl.origin === window.location.origin
            && targetUrl.pathname === window.location.pathname;

        if (!isSamePage) {
            return;
        }

        event.preventDefault();

        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });

        document.querySelector('[data-nav-menu]')?.classList.remove('is-open');
        window.history.pushState(null, '', targetUrl.pathname);
    });
});
