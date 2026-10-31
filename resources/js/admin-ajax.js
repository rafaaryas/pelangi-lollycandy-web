/* Keep product and category management in their existing dialogs. */
function showToast(message, kind = 'success', needsReload = false) {
    const main = document.querySelector('.admin-main');
    if (!main) return;
    main.querySelector('[data-ajax-toast]')?.remove();
    const toast = document.createElement('div');
    toast.className = 'admin-toast';
    toast.dataset.ajaxToast = '';
    toast.dataset.kind = kind;
    toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');
    toast.textContent = message;
    if (needsReload) {
        const reload = document.createElement('button');
        reload.type = 'button';
        reload.className = 'text-action';
        reload.textContent = 'Muat ulang daftar';
        reload.addEventListener('click', () => window.location.reload());
        toast.append(' ', reload);
    }
    main.prepend(toast);
    if (!needsReload) window.setTimeout(() => toast.remove(), 3800);
}

function showFormError(form, message, errors = {}) {
    form.querySelector('.modal-inline-error')?.remove();
    form.querySelectorAll('[aria-invalid="true"]').forEach((field) => field.removeAttribute('aria-invalid'));
    const notice = document.createElement('div');
    notice.className = 'modal-inline-error';
    notice.setAttribute('role', 'alert');
    notice.setAttribute('tabindex', '-1');
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
    const replacements = ['[data-admin-list]', '[data-admin-pagination]', '[data-admin-modals]'].map((selector) => [document.querySelector(selector), next.querySelector(selector)]);
    if (replacements.some(([current, replacement]) => !current || !replacement)) throw new Error('refresh');
    replacements.forEach(([current, replacement]) => {
        replacement.classList.add('is-refreshed');
        current.replaceWith(replacement);
    });
    document.querySelectorAll('[data-image-preview-img]').forEach((image) => { image.dataset.originalSrc = image.getAttribute('src') || ''; });
    document.dispatchEvent(new CustomEvent('admin:collection-refreshed'));
}

document.querySelectorAll('form[data-admin-ajax]').forEach((form) => { form.dataset.noLoading = 'true'; });

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-admin-ajax]')) return;
    event.preventDefault();
    form.dataset.noLoading = 'true';
    if (form.dataset.saving === 'true' || form.dataset.saved === 'true') return;
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
        if (response.status === 401 || response.status === 419 || new URL(response.url).pathname === '/admin/login') {
            showFormError(form, 'Sesi telah berakhir. Masuk kembali lalu muat ulang halaman sebelum menyimpan.');
            return;
        }
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = Object.values(result.errors || {})[0]?.[0] || result.message || 'Terjadi kesalahan. Silakan coba lagi.';
            showFormError(form, message, result.errors || {});
            showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
            return;
        }
        if (!response.headers.get('Content-Type')?.includes('application/json') || !result.message) {
            showFormError(form, 'Status penyimpanan belum dapat dipastikan. Periksa daftar sebelum mengirim ulang.');
            return;
        }
        form.dataset.saved = 'true';
        form.closest('[data-modal]')?.querySelector('[data-modal-close]')?.click();
        await refreshCollection();
        showToast(result.message || 'Perubahan berhasil disimpan.');
    } catch {
        if (form.dataset.saved === 'true') {
            showToast('Perubahan sudah tersimpan, tetapi daftar belum diperbarui. Jangan kirim ulang formulir.', 'error', true);
        } else {
            showFormError(form, 'Koneksi terputus. Status penyimpanan belum dapat dipastikan. Periksa daftar sebelum mengirim ulang.');
        }
    } finally {
        delete form.dataset.saving;
        if (submit?.isConnected) {
            submit.disabled = form.dataset.saved === 'true';
            submit.removeAttribute('aria-busy');
            submit.innerHTML = originalLabel;
        }
    }
}, true);
