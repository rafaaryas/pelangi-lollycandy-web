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

document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-image-preview-input]');
    if (!input) return;

    const target = document.getElementById(input.dataset.imagePreviewInput);
    const file = input.files?.[0];

    if (!target || !file) return;

    if (target.dataset.previewUrl) {
        URL.revokeObjectURL(target.dataset.previewUrl);
    }

    const previewUrl = URL.createObjectURL(file);
    target.dataset.previewUrl = previewUrl;
    target.src = previewUrl;
});
