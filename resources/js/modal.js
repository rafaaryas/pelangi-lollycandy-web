const focusableSelector = 'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';
let activeModal = null;
let returnFocus = null;
let pendingForm = null;
let pendingSubmitter = null;

function modalDialog(modal) {
    const dialog = modal.querySelector('.modal-content');
    if (!dialog) return null;

    dialog.setAttribute('role', modal.hasAttribute('data-confirm-dialog') ? 'alertdialog' : 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('tabindex', '-1');
    const heading = dialog.querySelector('h1,h2,h3,h4');
    if (heading) {
        if (!heading.id) heading.id = `${modal.id || 'modal'}-title`;
        dialog.setAttribute('aria-labelledby', heading.id);
    }

    return dialog;
}

function openModal(modal, trigger = document.activeElement) {
    if (!modal) return;
    if (activeModal && activeModal !== modal) closeModal(activeModal, false);
    activeModal = modal;
    returnFocus = trigger;
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('is-open');
    document.body.classList.add('modal-open');

    const dialog = modalDialog(modal);
    const initialFocus = dialog?.querySelector('[data-initial-focus], [aria-invalid="true"], [autofocus]')
        || dialog?.querySelector('button[data-modal-close]')
        || dialog?.querySelector('input:not([type="hidden"]),select,textarea,button:not([data-modal-close])');
    requestAnimationFrame(() => (initialFocus || dialog)?.focus());
}

function closeModal(modal = activeModal, restore = true) {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    if (activeModal === modal) activeModal = null;
    if (!activeModal) document.body.classList.remove('modal-open');
    if (restore && returnFocus?.isConnected) returnFocus.focus();
    returnFocus = null;
}

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-modal-open]');
    if (opener) {
        event.preventDefault();
        openModal(document.getElementById(opener.dataset.modalOpen), opener);
        return;
    }

    const closer = event.target.closest('[data-modal-close]');
    if (closer) {
        closeModal(closer.closest('[data-modal]'));
        return;
    }

    if (event.target.closest('[data-confirm-cancel]')) {
        closeModal(document.querySelector('[data-confirm-dialog]'));
        pendingForm = null;
        pendingSubmitter = null;
        return;
    }

    if (event.target.closest('[data-confirm-accept]') && pendingForm) {
        const form = pendingForm;
        const submitter = pendingSubmitter;
        pendingForm = null;
        pendingSubmitter = null;
        form.dataset.confirmApproved = 'true';
        closeModal(document.querySelector('[data-confirm-dialog]'));
        if (submitter?.isConnected) form.requestSubmit(submitter);
        else form.requestSubmit();
        return;
    }

    const backdrop = event.target.closest('.modal-backdrop');
    if (backdrop && backdrop.closest('[data-modal]') === activeModal) closeModal(activeModal);
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !document.body.classList.contains('admin-body')) return;

    if (form.dataset.confirmMessage && form.dataset.confirmApproved !== 'true') {
        event.preventDefault();
        pendingForm = form;
        pendingSubmitter = event.submitter;
        const modal = document.querySelector('[data-confirm-dialog]');
        const title = modal.querySelector('#confirm-title');
        const message = modal.querySelector('#confirm-message');
        const accept = modal.querySelector('[data-confirm-accept]');
        title.textContent = form.dataset.confirmTitle || 'Konfirmasi tindakan';
        message.textContent = form.dataset.confirmMessage;
        accept.textContent = form.dataset.confirmLabel || 'Konfirmasi';
        openModal(modal, event.submitter || document.activeElement);
        return;
    }

    if (form.dataset.confirmApproved === 'true') delete form.dataset.confirmApproved;
    if (form.method.toLowerCase() === 'get' || form.dataset.noLoading === 'true') return;

    const submitter = event.submitter;
    if (submitter?.name) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = submitter.name;
        hidden.value = submitter.value;
        form.append(hidden);
        submitter.removeAttribute('name');
    }

    form.querySelectorAll('button[type="submit"],button:not([type]),input[type="submit"]').forEach((button) => {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        if (button instanceof HTMLButtonElement) button.insertAdjacentHTML('afterbegin', '<span class="button-spinner" aria-hidden="true"></span>');
    });
});

document.addEventListener('keydown', (event) => {
    if (!activeModal) return;
    if (event.key === 'Escape') {
        event.preventDefault();
        closeModal(activeModal);
        pendingForm = null;
        pendingSubmitter = null;
        return;
    }
    if (event.key !== 'Tab') return;

    const dialog = modalDialog(activeModal);
    const focusable = [...dialog.querySelectorAll(focusableSelector)].filter((element) => element.offsetParent !== null);
    if (focusable.length === 0) {
        event.preventDefault();
        dialog.focus();
    } else if (!dialog.contains(document.activeElement)) {
        event.preventDefault();
        focusable[0].focus();
    } else if (event.shiftKey && document.activeElement === focusable[0]) {
        event.preventDefault();
        focusable.at(-1).focus();
    } else if (!event.shiftKey && document.activeElement === focusable.at(-1)) {
        event.preventDefault();
        focusable[0].focus();
    }
});

document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-image-preview-input]');
    if (!input) return;
    const target = document.getElementById(input.dataset.imagePreviewInput);
    const file = input.files?.[0];
    if (!target || !file) return;
    if (target.dataset.previewUrl) URL.revokeObjectURL(target.dataset.previewUrl);
    const previewUrl = URL.createObjectURL(file);
    target.dataset.previewUrl = previewUrl;
    target.src = previewUrl;
    target.hidden = false;
    const remove = input.closest('.form-group')?.querySelector('[data-image-remove]');
    if (remove) remove.hidden = false;
});

document.addEventListener('click', (event) => {
    const remove = event.target.closest('[data-image-remove]');
    if (!remove) return;
    const target = document.getElementById(remove.dataset.imageRemove);
    const input = remove.closest('.form-group')?.querySelector('[data-image-preview-input]');
    if (!target || !input) return;
    if (target.dataset.previewUrl) URL.revokeObjectURL(target.dataset.previewUrl);
    delete target.dataset.previewUrl;
    input.value = '';
    const original = target.dataset.originalSrc || '';
    if (original) target.src = original;
    else target.removeAttribute('src');
    target.hidden = !original;
    remove.hidden = true;
});

document.querySelectorAll('[data-image-preview-img]').forEach((image) => { image.dataset.originalSrc = image.getAttribute('src') || ''; });

document.addEventListener('dragover', (event) => {
    const zone = event.target.closest('[data-dropzone]');
    if (!zone) return;
    event.preventDefault();
    zone.classList.add('is-dragging');
});
document.addEventListener('dragleave', (event) => event.target.closest('[data-dropzone]')?.classList.remove('is-dragging'));
document.addEventListener('drop', (event) => {
    const zone = event.target.closest('[data-dropzone]');
    if (!zone) return;
    event.preventDefault();
    zone.classList.remove('is-dragging');
    const file = event.dataTransfer?.files?.[0];
    if (!file?.type.startsWith('image/')) return;
    const input = zone.querySelector('input[type="file"]');
    if (!input) return;
    const transfer = new DataTransfer();
    transfer.items.add(file);
    input.files = transfer.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
});

function initializeModalTree(root = document) {
    root.querySelectorAll('[data-modal]').forEach((modal) => {
        modal.setAttribute('aria-hidden', 'true');
        modalDialog(modal);
    });
    const invalidModal = root.querySelector('[data-open-on-error="true"]');
    if (invalidModal) openModal(invalidModal, root.querySelector('.admin-alert') || document.querySelector('.admin-alert'));
}

initializeModalTree();
document.addEventListener('admin:navigated', (event) => {
    activeModal = null;
    returnFocus = null;
    pendingForm = null;
    pendingSubmitter = null;
    document.body.classList.remove('modal-open');
    initializeModalTree(event.detail?.main || document);
});
