/* Keep product and category management in their existing dialogs. */
function showToast(message, kind = 'success') {
    const main = document.querySelector('.admin-main');
    if (!main) return;
    main.querySelector('[data-ajax-toast]')?.remove();
    const toast = document.createElement('div');
    toast.className = 'admin-toast';
    toast.dataset.ajaxToast = '';
    toast.dataset.kind = kind;
    toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');
    toast.textContent = message;
    main.prepend(toast);
    window.setTimeout(() => toast.remove(), 3800);
}

function showFormError(form, message, errors = {}) {
    form.querySelector('.modal-inline-error')?.remove();
    form.querySelectorAll('[aria-invalid="true"]').forEach((field) => field.removeAttribute('aria-invalid'));
    const notice = document.createElement('div');
    notice.className = 'modal-inline-error';
    notice.setAttribute('role', 'alert');
    notice.textContent = message;
    form.prepend(notice);
    let firstInvalid;
    Object.entries(errors).forEach(([name]) => {
        const field = [...form.elements].find((element) => element.name === name);
        if (!field) return;
        field.setAttribute('aria-invalid', 'true');
        firstInvalid ||= field;
    });
    (firstInvalid || notice).focus?.();
}

async function refreshCollection() {
    const response = await fetch(window.location.href, { headers: { Accept: 'text/html' }, cache: 'no-store' });
    if (!response.ok || response.redirected && response.url.includes('/login')) throw new Error('refresh');
    const next = new DOMParser().parseFromString(await response.text(), 'text/html');
    for (const selector of ['[data-admin-list]', '[data-admin-pagination]', '[data-admin-modals]']) {
        const current = document.querySelector(selector);
        const replacement = next.querySelector(selector);
        if (!current || !replacement) throw new Error('refresh');
        replacement.classList.add('is-refreshed');
        current.replaceWith(replacement);
    }
    document.querySelectorAll('[data-image-preview-img]').forEach((image) => { image.dataset.originalSrc = image.getAttribute('src') || ''; });
}

document.querySelectorAll('form[data-admin-ajax]').forEach((form) => { form.dataset.noLoading = 'true'; });

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-admin-ajax]')) return;
    event.preventDefault();
    form.dataset.noLoading = 'true';
    if (form.dataset.saving === 'true') return;
    form.dataset.saving = 'true';
    const submit = event.submitter || form.querySelector('[type="submit"]');
    const originalLabel = submit?.innerHTML;
    if (submit) {
        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');
        submit.textContent = form.querySelector('input[name="_method"]')?.value === 'DELETE' ? 'Menghapus...' : 'Menyimpan...';
    }
    form.querySelector('.modal-inline-error')?.remove();
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = Object.values(result.errors || {})[0]?.[0] || result.message || 'Terjadi kesalahan. Silakan coba lagi.';
            showFormError(form, message, result.errors || {});
            showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
            return;
        }
        form.closest('[data-modal]')?.querySelector('[data-modal-close]')?.click();
        await refreshCollection();
        showToast(result.message || 'Perubahan berhasil disimpan.');
    } catch {
        showFormError(form, 'Terjadi kesalahan. Silakan coba lagi.');
        showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
    } finally {
        delete form.dataset.saving;
        if (submit?.isConnected) {
            submit.disabled = false;
            submit.removeAttribute('aria-busy');
            submit.innerHTML = originalLabel;
        }
    }
}, true);
