document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
        const id = button.dataset.modalOpen;
        document.getElementById(id)?.classList.add('is-open');
    });
});

document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => {
        button.closest('[data-modal]')?.classList.remove('is-open');
    });
});
