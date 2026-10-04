const homeSections = [
    ['#home-hero', '[data-scroll-home]'],
    ['#about', '[data-about-link]'],
    ['#featured-products', '.nav-menu a[href$="/products"]'],
    ['#marketplace-hub', '.nav-menu a[href$="#marketplace-hub"]'],
    ['#contact-cta', '.nav-menu a[href$="/contact"]'],
].map(([sectionSelector, linkSelector]) => ({
    section: document.querySelector(sectionSelector),
    link: document.querySelector(linkSelector),
})).filter(({ section, link }) => section && link);

if (document.body.classList.contains('home-page') && homeSections.length && 'IntersectionObserver' in window) {
    const updateActive = () => {
        const top = document.querySelector('[data-navbar]')?.getBoundingClientRect().height ?? 0;
        const bottom = window.innerHeight * .47;
        const current = [...homeSections].reverse().find(({ section }) => {
            const rect = section.getBoundingClientRect();
            return rect.top < bottom && rect.bottom > top + 4;
        });
        if (!current) return;

        homeSections.forEach(({ link }) => {
            const active = link === current.link;
            link.classList.toggle('is-active', active);
            if (active && (link.matches('[data-scroll-home]') || link.hash)) {
                link.setAttribute('aria-current', link.matches('[data-scroll-home]') ? 'page' : 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    const observer = new IntersectionObserver(updateActive, {
        rootMargin: '-88px 0px -53% 0px',
        threshold: 0,
    });
    homeSections.forEach(({ section }) => observer.observe(section));
    window.addEventListener('load', updateActive, { once: true });
    updateActive();
}
