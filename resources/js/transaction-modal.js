import { initTransactionForm } from './transaction-form';

let requestId = 0;

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
            form.querySelector('input:not([type="hidden"]), select, textarea')?.focus();
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
        if (form.dataset.saving === 'true') return;
        form.dataset.saving = 'true';
        const submitter = event.submitter;
        const buttons = [...form.querySelectorAll('button[type="submit"]')];
        buttons.forEach((button) => { button.disabled = true; });
        submitter?.setAttribute('aria-busy', 'true');
        submitter?.classList.add('is-loading');
        form.querySelector('.modal-inline-error')?.remove();

        try {
            const payload = new FormData(form);
            if (submitter?.name) payload.set(submitter.name, submitter.value);
            const response = await fetch(form.action, {
                method: 'POST', body: payload,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
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
                    input?.setAttribute('aria-invalid', 'true');
                });
                notice.focus();
                return;
            }
            modal.querySelector('[data-modal-close]')?.click();
            const fresh = await fetch(window.location.href, { headers: { Accept: 'text/html' }, cache: 'no-store' });
            if (!fresh.ok) throw new Error('Transaksi tersimpan. Muat ulang untuk melihatnya.');
            const next = new DOMParser().parseFromString(await fresh.text(), 'text/html');
            for (const selector of ['.table-meta', '.module-table-wrap', '.module-pagination']) {
                const current = document.querySelector(selector);
                const replacement = next.querySelector(selector);
                if (current && replacement) {
                    replacement.classList.add('is-refreshed');
                    current.replaceWith(replacement);
                }
            }
            const toast = document.createElement('div');
            toast.className = 'admin-toast';
            toast.setAttribute('role', 'status');
            toast.textContent = 'Transaksi berhasil disimpan.';
            document.querySelector('.admin-main')?.prepend(toast);
            setTimeout(() => toast.remove(), 3800);
        } catch (error) {
            const notice = document.createElement('div');
            notice.className = 'modal-inline-error';
            notice.setAttribute('role', 'alert');
            notice.textContent = error.message || 'Terjadi kesalahan. Silakan coba lagi.';
            form.prepend(notice);
        } finally {
            delete form.dataset.saving;
            buttons.forEach((button) => { button.disabled = false; button.removeAttribute('aria-busy'); button.classList.remove('is-loading'); });
        }
}, true);
