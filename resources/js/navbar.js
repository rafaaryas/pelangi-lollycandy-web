const navToggle = document.querySelector('[data-nav-toggle]');
const navMenu = document.querySelector('[data-nav-menu]');
const navbar = document.querySelector('[data-navbar]');

if (navToggle && navMenu) {
    navMenu.classList.add('is-collapsible');
    navToggle.hidden = false;
    const mobileNav = window.matchMedia('(max-width: 900px)');

    const setMenuOpen = (isOpen) => {
        const open = isOpen && mobileNav.matches;
        navMenu.classList.toggle('is-open', open);
        navMenu.inert = mobileNav.matches && !open;
        document.body.classList.toggle('nav-open', open);
        navToggle.setAttribute('aria-expanded', String(open));
        navToggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
    };

    setMenuOpen(false);
    navToggle.addEventListener('click', () => setMenuOpen(!navMenu.classList.contains('is-open')));

    mobileNav.addEventListener('change', () => setMenuOpen(false));

    navMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            setMenuOpen(false);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !navMenu.classList.contains('is-open')) return;
        setMenuOpen(false);
        navToggle.focus();
    });

    document.addEventListener('pointerdown', (event) => {
        if (!navMenu.classList.contains('is-open') || navbar?.contains(event.target)) return;
        setMenuOpen(false);
    });
}

const adminNavToggle = document.querySelector('[data-admin-nav-toggle]');
const adminSidebar = document.querySelector('[data-admin-sidebar]');

if (adminNavToggle && adminSidebar) {
    const adminMobile = window.matchMedia('(max-width: 900px)');
    const closeButton = adminSidebar.querySelector('[data-admin-nav-close]');
    const overlay = document.querySelector('[data-admin-nav-overlay]');
    const setAdminMenuOpen = (open, restoreFocus = false) => {
        const isOpen = Boolean(open && adminMobile.matches);
        adminSidebar.classList.toggle('is-open', isOpen);
        document.body.classList.toggle('admin-nav-open', isOpen);
        adminNavToggle.setAttribute('aria-expanded', String(isOpen));
        overlay?.setAttribute('tabindex', isOpen ? '0' : '-1');
        if (!isOpen && restoreFocus) adminNavToggle.focus();
        if (isOpen) window.requestAnimationFrame(() => closeButton?.focus());
    };

    adminNavToggle.addEventListener('click', () => setAdminMenuOpen(!adminSidebar.classList.contains('is-open')));
    closeButton?.addEventListener('click', () => setAdminMenuOpen(false, true));
    overlay?.addEventListener('click', () => setAdminMenuOpen(false, true));
    adminSidebar.addEventListener('click', (event) => {
        if (event.target.closest('a[href]')) setAdminMenuOpen(false);
    });
    adminMobile.addEventListener('change', () => setAdminMenuOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !adminSidebar.classList.contains('is-open')) return;
        setAdminMenuOpen(false, true);
    });
}

if (navbar) {
    let scheduled = false;
    const update = () => {
        navbar.classList.toggle('is-scrolled', window.scrollY > 12);
        scheduled = false;
    };
    update();
    window.addEventListener('scroll', () => {
        if (scheduled) return;
        scheduled = true;
        window.requestAnimationFrame(update);
    }, { passive: true });
}

const publicFooter = document.querySelector('.public-page .footer');
if (publicFooter && 'IntersectionObserver' in window) {
    new IntersectionObserver(([entry]) => {
        document.body.classList.toggle('is-near-footer', entry.isIntersecting);
    }).observe(publicFooter);
}
