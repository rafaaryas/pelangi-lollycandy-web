function closeMenus(except = null) {
    document.querySelectorAll('[data-export-menu]').forEach((menu) => {
        if (menu === except) return;
        menu.querySelector('[data-export-panel]').hidden = true;
        menu.querySelector('[data-export-toggle]').setAttribute('aria-expanded', 'false');
    });
}

function exportNotice(message, kind = 'success') {
    const main = document.querySelector('[data-admin-main]');
    if (!main) return;
    main.querySelector('[data-export-notice]')?.remove();
    const notice = document.createElement('div');
    notice.className = 'admin-toast';
    notice.dataset.exportNotice = '';
    notice.dataset.kind = kind;
    notice.setAttribute('role', kind === 'error' ? 'alert' : 'status');
    notice.textContent = message;
    main.prepend(notice);
    window.setTimeout(() => notice.remove(), 5000);
}

async function downloadPreparedFile(link) {
    const menu = link.closest('[data-export-menu]');
    const trigger = menu.querySelector('[data-export-toggle]');
    const originalLabel = trigger.innerHTML;
    trigger.disabled = true;
    trigger.setAttribute('aria-busy', 'true');
    trigger.textContent = 'Menyiapkan file…';
    closeMenus();
    try {
        const response = await fetch(link.href, { credentials: 'same-origin', headers: { Accept: 'application/octet-stream' } });
        const type = response.headers.get('Content-Type') || '';
        if (!response.ok || type.includes('text/html')) throw new Error('Ekspor tidak dapat disiapkan. Periksa filter dan coba lagi.');
        const total = Number(response.headers.get('Content-Length')) || 0;
        const reader = response.body?.getReader();
        const chunks = [];
        let received = 0;
        if (reader) {
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                chunks.push(value);
                received += value.length;
                if (total) trigger.textContent = `Mengunduh ${Math.min(100, Math.round(received / total * 100))}%`;
            }
        } else chunks.push(new Uint8Array(await response.arrayBuffer()));
        const disposition = response.headers.get('Content-Disposition') || '';
        const filename = disposition.match(/filename="?([^";]+)"?/i)?.[1] || `pelangi-export.${new URL(link.href).searchParams.get('format')}`;
        const url = URL.createObjectURL(new Blob(chunks, { type }));
        const download = document.createElement('a');
        download.href = url;
        download.download = filename;
        document.body.append(download);
        download.click();
        download.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 30000);
        exportNotice(`${filename} siap diunduh.`);
    } catch (error) {
        exportNotice(error.message || 'Ekspor gagal. Silakan coba lagi.', 'error');
    } finally {
        trigger.disabled = false;
        trigger.removeAttribute('aria-busy');
        trigger.innerHTML = originalLabel;
    }
}

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-export-toggle]');
    if (toggle) {
        const menu = toggle.closest('[data-export-menu]');
        const panel = menu.querySelector('[data-export-panel]');
        const open = panel.hidden;
        closeMenus(menu);
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (open) panel.querySelector('a')?.focus();
        return;
    }
    const link = event.target.closest('[data-export-link]');
    if (link) {
        const format = new URL(link.href).searchParams.get('format');
        if (format !== 'csv' && window.fetch && window.ReadableStream) {
            event.preventDefault();
            downloadPreparedFile(link);
        } else closeMenus();
        return;
    }
    if (!event.target.closest('[data-export-menu]')) closeMenus();
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const open = document.querySelector('[data-export-toggle][aria-expanded="true"]');
    if (!open) return;
    closeMenus();
    open.focus();
});

document.addEventListener('admin:navigated', () => closeMenus());
