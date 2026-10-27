const states = new WeakMap();
const fields = ['name', 'category_id', 'description'];

function stateFor(form) {
    if (!states.has(form)) {
        states.set(form, { requestId: 0, controller: null, aiValues: {}, changed: new Set(), busy: false });
        const toggle = form.querySelector('[data-ai-toggle]');
        try { toggle.checked = localStorage.getItem(`pelangi-ai-autofill-${form.dataset.aiUser}`) === 'on'; } catch { /* storage may be disabled */ }
        render(form);
    }
    return states.get(form);
}

function cancel(form) {
    const state = stateFor(form);
    state.requestId += 1;
    state.controller?.abort();
    state.controller = null;
    state.busy = false;
    render(form);
}

function setStatus(form, message = '', kind = '') {
    const status = form.querySelector('[data-ai-status]');
    status.textContent = message;
    status.hidden = !message;
    status.dataset.kind = kind;
}

function render(form) {
    const state = states.get(form);
    const on = form.querySelector('[data-ai-toggle]').checked;
    const hasFile = !!form.querySelector('input[name="image"]').files?.length;
    form.querySelector('[data-ai-subtitle]').textContent = on
        ? 'AI akan otomatis mengisi informasi produk setelah foto diunggah.'
        : 'Isi informasi produk secara manual.';
    const actions = form.querySelector('[data-ai-actions]');
    actions.hidden = !on || !hasFile;
    form.querySelector('[data-ai-analyze]').hidden = !!state?.aiValues.name;
    form.querySelector('[data-ai-retry]').hidden = !state?.aiValues.name;
    actions.querySelectorAll('button').forEach((button) => { button.disabled = !!state?.busy; });
}

function requiresConfirmation(form, state) {
    return fields.some((name) => {
        const field = form.elements.namedItem(name);
        if (!field) return false;
        if (name === 'category_id' && !state.changed.has(name) && !state.aiValues[name]) return false;
        return state.changed.has(name) || (field.value.trim() !== '' && field.value !== (state.aiValues[name] ?? ''));
    });
}

async function analyze(form) {
    const state = stateFor(form);
    if (!form.querySelector('[data-ai-toggle]').checked || state.busy) return;
    const file = form.querySelector('input[name="image"]').files?.[0];
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 4 * 1024 * 1024) {
        setStatus(form, 'Pilih foto JPEG, PNG, atau WebP maksimal 4 MB.', 'error');
        return;
    }
    if (requiresConfirmation(form, state) && !window.confirm('Analisis AI akan mengganti nama, kategori, atau deskripsi yang Anda isi. Lanjutkan?')) return;

    const requestId = ++state.requestId;
    state.controller?.abort();
    state.controller = new AbortController();
    state.busy = true;
    render(form);
    setStatus(form, '✨ Menganalisis gambar produk... Mengidentifikasi kategori dan nama produk, lalu menyusun deskripsi.');
    const data = new FormData();
    data.append('image', file);
    data.append('name', form.elements.namedItem('name').value);
    data.append('description', form.elements.namedItem('description').value);
    const snapshot = Object.fromEntries(fields.map((name) => [name, form.elements.namedItem(name).value]));

    try {
        const response = await fetch(form.dataset.aiEndpoint, {
            method: 'POST', body: data, signal: state.controller.signal,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
            },
        });
        const result = await response.json().catch(() => ({}));
        if (state.requestId !== requestId || !form.isConnected || !form.querySelector('[data-ai-toggle]').checked) return;
        if (!response.ok) throw new Error(result.errors?.image?.[0] || result.message || 'Analisis gagal. Coba lagi.');
        if (typeof result.name !== 'string' || typeof result.description !== 'string') throw new Error('Respons AI tidak valid. Coba lagi.');

        for (const name of fields) {
            const field = form.elements.namedItem(name);
            if (field.value !== snapshot[name]) continue; // Admin edited during the request.
            if (name === 'category_id' && result.category_id === null) continue;
            const value = String(result[name]);
            if (name === 'category_id' && ![...field.options].some((option) => option.value === value)) continue;
            field.value = value;
            field.dispatchEvent(new Event('change', { bubbles: true }));
            state.aiValues[name] = value;
            state.changed.delete(name);
        }
        setStatus(form, result.needs_review
            ? `✓ Informasi produk diisi. Periksa kembali: ${result.reason || 'Kategori perlu dipilih manual.'}`
            : '✓ Informasi produk berhasil diisi otomatis.', 'success');
    } catch (error) {
        if (error.name !== 'AbortError' && state.requestId === requestId && form.isConnected) {
            setStatus(form, error.message === 'Failed to fetch' ? 'Koneksi gagal. Periksa internet lalu coba lagi.' : error.message, 'error');
        }
    } finally {
        if (state.requestId === requestId) {
            state.busy = false;
            state.controller = null;
            render(form);
        }
    }
}

document.addEventListener('change', (event) => {
    const form = event.target.closest('[data-ai-product-form]');
    if (!form) return;
    const state = stateFor(form);
    if (event.target.matches('[data-ai-toggle]')) {
        try { localStorage.setItem(`pelangi-ai-autofill-${form.dataset.aiUser}`, event.target.checked ? 'on' : 'off'); } catch { /* storage may be disabled */ }
        if (!event.target.checked) { cancel(form); setStatus(form); }
        render(form);
    } else if (event.target.matches('input[name="image"]')) {
        cancel(form);
        setStatus(form);
        if (form.querySelector('[data-ai-toggle]').checked && event.target.files?.length) analyze(form);
    } else if (fields.includes(event.target.name)) {
        state.changed.add(event.target.name);
    }
});

document.addEventListener('input', (event) => {
    const form = event.target.closest('[data-ai-product-form]');
    if (form && fields.includes(event.target.name)) stateFor(form).changed.add(event.target.name);
});

document.addEventListener('click', (event) => {
    const form = event.target.closest('[data-ai-product-form]');
    if (form && event.target.closest('[data-ai-analyze], [data-ai-retry]')) analyze(form);
    if (event.target.closest('[data-image-remove]')) {
        const owner = event.target.closest('[data-ai-product-form]');
        if (owner) { cancel(owner); setStatus(owner); }
    }
    if (event.target.closest('[data-modal-close], .modal-backdrop')) {
        event.target.closest('[data-modal]')?.querySelectorAll('[data-ai-product-form]').forEach(cancel);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') document.querySelectorAll('[data-modal].is-open [data-ai-product-form]').forEach(cancel);
});

function initialize(root = document) {
    root.querySelectorAll('[data-ai-product-form]').forEach(stateFor);
}
initialize();
document.addEventListener('admin:navigated', (event) => initialize(event.detail?.main || document));
document.addEventListener('admin:collection-refreshed', () => initialize());
