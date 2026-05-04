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

document.querySelectorAll('[data-modal]').forEach((modal) => {
    modal.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal-backdrop')) {
            modal.classList.remove('is-open');
        }
    });
});

document.querySelectorAll('[data-delete-check]').forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
        const targetId = checkbox.dataset.deleteCheck;
        const submit = document.getElementById(targetId);
        if (submit) submit.disabled = !checkbox.checked;
    });
});

document.querySelectorAll('[data-image-preview-input]').forEach((input) => {
    input.addEventListener('change', () => {
        const target = document.getElementById(input.dataset.imagePreviewInput);
        const file = input.files?.[0];
        if (!target || !file) return;
        target.src = URL.createObjectURL(file);
    });
});
