const hero = document.querySelector('.hero');
if (hero) {
    window.addEventListener('scroll', () => {
        const y = Math.min(window.scrollY * 0.1, 30);
        hero.style.backgroundPositionY = `${y}px`;
    });
}
