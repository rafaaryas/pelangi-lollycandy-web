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
    const adminNav = adminSidebar.querySelector('.admin-nav');
    const closeButton = adminSidebar.querySelector('[data-admin-nav-close]');
    const overlay = document.querySelector('[data-admin-nav-overlay]');
    const drawerBackground = [document.querySelector('[data-admin-main]'), document.querySelector('.admin-topbar')].filter(Boolean);
    const backgroundInert = new Map();
    const tooltip = document.createElement('div');
    tooltip.className = 'admin-nav-tooltip';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.hidden = true;
    document.body.append(tooltip);
    const showTooltip = (target) => {
        if (adminMobile.matches || !document.body.classList.contains('is-sidebar-collapsed')) return;
        const control = target.closest('.admin-nav-parent, .admin-nav-link, .admin-logout-button');
        if (!control || !adminSidebar.contains(control)) return;
        const rect = control.getBoundingClientRect();
        tooltip.textContent = control.getAttribute('aria-label') || control.title;
        tooltip.style.left = `${rect.right + 8}px`;
        tooltip.style.top = `${Math.max(8, rect.top + rect.height / 2 - 16)}px`;
        tooltip.hidden = false;
    };
    const hideTooltip = () => { tooltip.hidden = true; };
    adminSidebar.addEventListener('pointerover', (event) => showTooltip(event.target));
    adminSidebar.addEventListener('pointerout', hideTooltip);
    adminSidebar.addEventListener('focusin', (event) => showTooltip(event.target));
    adminSidebar.addEventListener('focusout', hideTooltip);
    adminNav?.addEventListener('scroll', hideTooltip, { passive: true });
    const revealActiveLink = () => {
        const active = adminNav?.querySelector('.admin-nav-link[aria-current="page"]');
        if (!active) return;
        const navRect = adminNav.getBoundingClientRect();
        const linkRect = active.getBoundingClientRect();
        if (linkRect.top < navRect.top + 8) adminNav.scrollTop += linkRect.top - navRect.top - 8;
        else if (linkRect.bottom > navRect.bottom - 8) adminNav.scrollTop += linkRect.bottom - navRect.bottom + 8;
    };
    const storageKey = 'pelangi-admin-sidebar-collapsed';
    const setCollapsed = (collapsed) => {
        hideTooltip();
        document.body.classList.toggle('is-sidebar-collapsed', collapsed);
        if (!adminMobile.matches) {
            adminNavToggle.setAttribute('aria-expanded', String(!collapsed));
            adminNavToggle.setAttribute('aria-label', collapsed ? 'Perluas navigasi' : 'Perkecil navigasi');
        }
        adminSidebar.querySelectorAll('.admin-nav-children').forEach((children) => {
            children.inert = children.previousElementSibling?.getAttribute('aria-expanded') !== 'true';
        });
        try { window.localStorage.setItem(storageKey, collapsed ? '1' : '0'); } catch (_) { /* Storage may be disabled. */ }
    };
    try { setCollapsed(window.localStorage.getItem(storageKey) === '1'); } catch (_) { setCollapsed(false); }
    const setAdminMenuOpen = (open, restoreFocus = false) => {
        const isOpen = Boolean(open && adminMobile.matches);
        adminSidebar.classList.toggle('is-open', isOpen);
        adminSidebar.inert = adminMobile.matches && !isOpen;
        document.body.classList.toggle('admin-nav-open', isOpen);
        if (adminMobile.matches) {
            adminNavToggle.setAttribute('aria-expanded', String(isOpen));
            adminNavToggle.setAttribute('aria-label', isOpen ? 'Tutup navigasi' : 'Buka navigasi');
        }
        if (isOpen) {
            adminSidebar.setAttribute('role', 'dialog');
            adminSidebar.setAttribute('aria-modal', 'true');
            adminSidebar.setAttribute('aria-label', 'Navigasi admin');
            drawerBackground.forEach((element) => {
                if (!backgroundInert.has(element)) backgroundInert.set(element, element.inert);
                element.inert = true;
            });
        } else {
            adminSidebar.removeAttribute('role');
            adminSidebar.removeAttribute('aria-modal');
            adminSidebar.removeAttribute('aria-label');
            backgroundInert.forEach((inert, element) => { element.inert = inert; });
            backgroundInert.clear();
        }
        overlay?.setAttribute('tabindex', '-1');
        if (!isOpen && restoreFocus) adminNavToggle.focus();
        if (isOpen) window.requestAnimationFrame(() => closeButton?.focus());
    };
    setAdminMenuOpen(false);

    adminNavToggle.addEventListener('click', () => {
        if (adminMobile.matches) setAdminMenuOpen(!adminSidebar.classList.contains('is-open'));
        else setCollapsed(!document.body.classList.contains('is-sidebar-collapsed'));
    });
    adminSidebar.querySelectorAll('.admin-nav-parent').forEach((parent) => parent.addEventListener('click', () => {
        const expanded = parent.getAttribute('aria-expanded') !== 'true';
        parent.setAttribute('aria-expanded', String(expanded));
        parent.nextElementSibling.inert = !expanded;
        try { window.sessionStorage.setItem(`pelangi-nav-${parent.closest('[data-nav-group]').dataset.navGroup}`, expanded ? '1' : '0'); } catch (_) { /* Optional preference. */ }
    }));
    adminSidebar.querySelectorAll('[data-nav-group]').forEach((group) => {
        let preference = null;
        try { preference = window.sessionStorage.getItem(`pelangi-nav-${group.dataset.navGroup}`); } catch (_) { /* Optional preference. */ }
        const expanded = group.dataset.active === 'true' || preference === '1';
        group.querySelector('.admin-nav-parent').setAttribute('aria-expanded', String(expanded));
        group.querySelector('.admin-nav-children').inert = !expanded;
    });
    window.setTimeout(revealActiveLink, 260);
    document.addEventListener('admin:navigated', () => window.setTimeout(revealActiveLink, 260));
    closeButton?.addEventListener('click', () => setAdminMenuOpen(false, true));
    overlay?.addEventListener('click', () => setAdminMenuOpen(false, true));
    adminSidebar.addEventListener('click', (event) => {
        if (event.target.closest('a[href]')) setAdminMenuOpen(false);
    });
    adminMobile.addEventListener('change', () => { setAdminMenuOpen(false, adminSidebar.contains(document.activeElement)); setCollapsed(document.body.classList.contains('is-sidebar-collapsed')); });

    document.addEventListener('keydown', (event) => {
        if (!adminMobile.matches || !adminSidebar.classList.contains('is-open')) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            setAdminMenuOpen(false, true);
            return;
        }
        if (event.key !== 'Tab') return;
        const controls = [...adminSidebar.querySelectorAll('a[href],button:not([disabled]),[tabindex="0"]')]
            .filter((element) => !element.closest('[inert]') && element.getClientRects().length && getComputedStyle(element).visibility !== 'hidden');
        const first = controls[0];
        const last = controls.at(-1);
        if (!first) return;
        if (!adminSidebar.contains(document.activeElement) || (event.shiftKey && document.activeElement === first)) {
            event.preventDefault();
            (event.shiftKey ? last : first).focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
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
