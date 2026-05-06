const navToggle = document.querySelector('[data-nav-toggle]');
const navMenu = document.querySelector('[data-nav-menu]');
const navbar = document.querySelector('[data-navbar]');

if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => navMenu.classList.toggle('is-open'));
}

if (navbar) {
    const onScroll = () => navbar.classList.toggle('is-scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll);
}
