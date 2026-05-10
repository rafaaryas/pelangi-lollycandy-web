const navToggle = document.querySelector('[data-nav-toggle]');
const navMenu = document.querySelector('[data-nav-menu]');
const navbar = document.querySelector('[data-navbar]');

if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
        const isOpen = navMenu.classList.toggle('is-open');
        navToggle.setAttribute('aria-expanded', String(isOpen));
    });

    navMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('is-open');
            navToggle.setAttribute('aria-expanded', 'false');
        });
    });
}

const adminNavToggle = document.querySelector('[data-admin-nav-toggle]');
const adminSidebar = document.querySelector('[data-admin-sidebar]');

if (adminNavToggle && adminSidebar) {
    adminNavToggle.addEventListener('click', () => {
        const isOpen = adminSidebar.classList.toggle('is-open');
        adminNavToggle.setAttribute('aria-expanded', String(isOpen));
    });
}

if (navbar) {
    const onScroll = () => navbar.classList.toggle('is-scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll);
}
