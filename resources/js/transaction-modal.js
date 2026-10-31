import { initTransactionForm } from './transaction-form';

let requestId = 0;

function savedNotice(message, needsReload = false) {
    const main = document.querySelector('.admin-main');
    if (!main) return;
    main.querySelector('[data-transaction-notice]')?.remove();
    const notice = document.createElement('div');
    notice.className = 'admin-toast';
    notice.dataset.transactionNotice = '';
    notice.setAttribute('role', needsReload ? 'alert' : 'status');
    notice.textContent = message;
    if (needsReload) {
        const reload = document.createElement('button');
        reload.type = 'button';
        reload.className = 'text-action';
        reload.textContent = 'Muat ulang daftar';
        reload.addEventListener('click', () => window.location.reload());
        notice.append(' ', reload);
    } else window.setTimeout(() => notice.remove(), 3800);
    main.prepend(notice);
}

function sessionExpired(response) {
    return response.status === 401 || response.status === 419 || new URL(response.url).pathname === '/admin/login';
}

document.addEventListener('click', async (event) => {
        const link = event.target.closest('[data-transaction-url]');
        if (!link) return;
        const modal = document.getElementById('transaction-modal');
        const body = modal?.querySelector('[data-transaction-modal-body]');
        const title = modal?.querySelector('[data-transaction-modal-title]');
        if (!modal || !body || !title) return;
        const currentRequest = ++requestId;
        body.textContent = 'Memuat formulir…';
        title.textContent = link.textContent.trim() || link.getAttribute('aria-label') || 'Formulir transaksi';
        try {
            const response = await fetch(link.dataset.transactionUrl, { headers: { Accept: 'text/html' }, cache: 'no-store' });
            if (sessionExpired(response)) throw new Error('Sesi telah berakhir. Masuk kembali lalu muat ulang halaman.');
            if (!response.ok) throw new Error('Formulir tidak dapat dimuat.');
            const documentFragment = new DOMParser().parseFromString(await response.text(), 'text/html');
            const form = documentFragment.querySelector('[data-transaction-form]');
            if (!form) throw new Error('Formulir tidak ditemukan.');
            if (currentRequest !== requestId) return;
            title.textContent = documentFragment.querySelector('.module-page-head h1')?.textContent || title.textContent;
            form.dataset.noLoading = 'true';
            form.querySelector('.transaction-form-footer > a')?.remove();
            body.replaceChildren(form);
            initTransactionForm(form);
            document.dispatchEvent(new CustomEvent('admin:modal-form-ready', { detail: { modal } }));
            if (modal.classList.contains('is-open')) form.querySelector('input:not([type="hidden"]), select, textarea')?.focus();
        } catch (error) {
            if (currentRequest !== requestId) return;
            body.textContent = error.message || 'Formulir tidak dapat dimuat.';
        }
});

document.addEventListener('click', (event) => {
    if (event.target.closest('#transaction-modal [data-modal-close]')) ++requestId;
});

document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const modal = form.closest('#transaction-modal');
        if (!modal) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (form.dataset.saving === 'true' || form.dataset.saved === 'true') return;
        const confirmation = event.submitter?.dataset.confirmMessage || form.dataset.confirmMessage;
        if (confirmation && !window.confirm(confirmation)) return;
        form.dataset.saving = 'true';
        const submitter = event.submitter;
        const buttons = [...form.querySelectorAll('button[type="submit"]')];
        buttons.forEach((button) => { button.disabled = true; });
        submitter?.setAttribute('aria-busy', 'true');
        submitter?.classList.add('is-loading');
        form.querySelector('.modal-inline-error')?.remove();
        form.querySelectorAll('.transaction-field-error').forEach((node) => node.remove());
        form.querySelectorAll('[aria-invalid="true"]').forEach((input) => { input.removeAttribute('aria-invalid'); input.removeAttribute('aria-describedby'); });

        try {
            const payload = new FormData(form);
            if (submitter?.name) payload.set(submitter.name, submitter.value);
            const response = await fetch(form.action, {
                method: 'POST', body: payload,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (sessionExpired(response)) throw new Error('Sesi telah berakhir. Masuk kembali lalu muat ulang halaman sebelum menyimpan.');
            if (!response.ok) {
                const result = await response.json().catch(() => ({}));
                const errors = result.errors || {};
                const notice = document.createElement('div');
                notice.className = 'modal-inline-error';
                notice.setAttribute('role', 'alert');
                notice.setAttribute('tabindex', '-1');
                notice.textContent = Object.values(errors)[0]?.[0] || result.message || 'Data belum dapat disimpan.';
                form.prepend(notice);
                Object.keys(errors).forEach((name) => {
                    const parts = name.split('.');
                    const fieldName = parts.length > 1 ? parts[0] + parts.slice(1).map((part) => `[${part}]`).join('') : name;
                    const input = [...form.elements].find((element) => element.name === fieldName);
                    if (!input) return;
                    input.setAttribute('aria-invalid', 'true');
                    const message = document.createElement('small');
                    message.className = 'transaction-field-error';
                    message.id = `transaction-error-${fieldName.replace(/[^a-z0-9]/gi, '-')}`;
                    message.textContent = errors[name][0];
                    input.setAttribute('aria-describedby', message.id);
                    input.insertAdjacentElement('afterend', message);
                });
                notice.focus();
                return;
            }
            // These controllers redirect to the saved document, rather than returning JSON.
            const savedDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (!/^\/admin\/(purchases|productions|sales)\/\d+\/?$/.test(new URL(response.url).pathname)
                || !savedDocument.querySelector('.document-summary')) {
                throw new Error('Status penyimpanan belum dapat dipastikan. Periksa daftar transaksi sebelum mengirim ulang.');
            }
            form.dataset.saved = 'true';
            modal.querySelector('[data-modal-close]')?.click();
            const fresh = await fetch(window.location.href, { headers: { Accept: 'text/html' }, cache: 'no-store' });
            if (!fresh.ok || sessionExpired(fresh)) throw new Error('refresh');
            const next = new DOMParser().parseFromString(await fresh.text(), 'text/html');
            const replacements = ['.table-meta', '.module-table-wrap', '.module-pagination'].map((selector) => [document.querySelector(selector), next.querySelector(selector)]);
            if (replacements.some(([current, replacement]) => !current || !replacement)) throw new Error('refresh');
            replacements.forEach(([current, replacement]) => {
                replacement.classList.add('is-refreshed');
                current.replaceWith(replacement);
            });
            savedNotice('Transaksi berhasil disimpan.');
        } catch (error) {
            if (form.dataset.saved === 'true') {
                savedNotice('Transaksi sudah tersimpan, tetapi daftar belum diperbarui. Jangan kirim ulang transaksi.', true);
                return;
            }
            const notice = document.createElement('div');
            notice.className = 'modal-inline-error';
            notice.setAttribute('role', 'alert');
            notice.setAttribute('tabindex', '-1');
            notice.textContent = error instanceof TypeError
                ? 'Koneksi terputus. Status penyimpanan belum dapat dipastikan. Periksa daftar transaksi sebelum mengirim ulang.'
                : error.message || 'Data belum dapat disimpan.';
            form.prepend(notice);
            notice.focus();
        } finally {
            delete form.dataset.saving;
            buttons.forEach((button) => { button.disabled = form.dataset.saved === 'true'; button.removeAttribute('aria-busy'); button.classList.remove('is-loading'); });
        }
}, true);
