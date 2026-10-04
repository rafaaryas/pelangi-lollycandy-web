const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

if (document.body.classList.contains('home-page') && !reduceMotion.matches && 'IntersectionObserver' in window) {
    const targets = [
        ...document.querySelectorAll('.home-about .section-head, .home-about .about-card'),
        ...document.querySelectorAll('.home-bestseller .section-head, .home-bestseller .product-card'),
        ...document.querySelectorAll('.home-marketplace .section-head, .home-marketplace .marketplace-card'),
        ...document.querySelectorAll('.home-contact-cta .cta-card'),
    ];
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target);
            window.setTimeout(() => {
                entry.target.classList.remove('is-reveal-pending', 'is-revealed');
                entry.target.style.removeProperty('--reveal-delay');
            }, 760);
        });
    }, { rootMargin: '0px 0px -35px 0px', threshold: .08 });

    targets.forEach((target) => {
        const index = [...target.parentElement.children].indexOf(target);
        target.style.setProperty('--reveal-delay', `${Math.min(index, 3) * 55}ms`);
        target.classList.add('is-reveal-pending');
        observer.observe(target);
    });
}
