const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });

function refreshLine(form, row) {
    const selector = row.querySelector('[data-material], [data-product]');
    const option = selector?.selectedOptions[0];
    const unit = row.querySelector('[data-unit-label]');
    const stock = row.querySelector('[data-stock-label]');
    const price = row.querySelector('[data-price]');

    if (unit) unit.textContent = option?.dataset.unit || (selector?.matches('[data-product]') ? 'pcs' : '—');
    if (stock) {
        const amount = option?.dataset.stock;
        stock.textContent = amount === undefined ? '—' : (amount === 'null' ? 'Belum dicatat' : `${Number(amount).toLocaleString('id-ID')} pcs`);
    }
    if (price && selector?.matches('[data-product]') && option?.dataset.price && !price.dataset.edited && !price.value) {
        price.value = option.dataset.price;
    }

    const quantity = Number(row.querySelector('[data-quantity]')?.value || 0);
    const unitPrice = Number(price?.value || 0);
    const subtotal = row.querySelector('[data-subtotal]');
    if (subtotal) subtotal.textContent = rupiah.format(quantity * unitPrice);
}

function refreshTotal(form) {
    const amount = [...form.querySelectorAll('[data-subtotal]')].reduce((sum, cell) => {
        const number = Number(cell.closest('[data-line]')?.querySelector('[data-quantity]')?.value || 0);
        const price = Number(cell.closest('[data-line]')?.querySelector('[data-price]')?.value || 0);
        return sum + number * price;
    }, 0);
    const total = form.querySelector('[data-total]');
    if (total) total.textContent = rupiah.format(amount);

    form.querySelectorAll('[data-production-summary]').forEach((summary) => {
        const section = summary.closest('[data-line-section]');
        const totals = new Map();
        section.querySelectorAll('[data-line]').forEach((row) => {
            const quantity = Number(row.querySelector('[data-quantity]')?.value || 0);
            const selector = row.querySelector('[data-material], [data-product]');
            if (quantity <= 0 || !selector?.value) return;
            const unit = selector.selectedOptions[0]?.dataset.unit || 'pcs';
            totals.set(unit, (totals.get(unit) || 0) + quantity);
        });
        const totalText = [...totals].map(([unit, quantity]) => `${quantity.toLocaleString('id-ID', { maximumFractionDigits: 3 })} ${unit}`).join(' · ');
        summary.textContent = `${summary.dataset.productionSummary === 'materials' ? 'Total bahan digunakan' : 'Total hasil produksi'}: ${totalText || (summary.dataset.productionSummary === 'materials' ? '—' : '0 pcs')}`;
    });
}

export function initTransactionForm(form) {
    if (form.dataset.initialized === 'true') return;
    form.dataset.initialized = 'true';
    form.querySelectorAll('[data-line]').forEach((row) => refreshLine(form, row));
    refreshTotal(form);

    form.addEventListener('input', (event) => {
        const row = event.target.closest('[data-line]');
        if (!row) return;
        if (event.target.matches('[data-price]')) event.target.dataset.edited = 'true';
        refreshLine(form, row);
        refreshTotal(form);
    });

    form.addEventListener('change', (event) => {
        const row = event.target.closest('[data-line]');
        if (!row) return;
        if (event.target.matches('[data-product]')) {
            const price = row.querySelector('[data-price]');
            if (price) {
                price.value = '';
                delete price.dataset.edited;
            }
        }
        refreshLine(form, row);
        refreshTotal(form);
    });

    form.addEventListener('click', (event) => {
        const add = event.target.closest('[data-add-line]');
        if (add) {
            const section = add.closest('[data-line-section]');
            const rows = section.querySelector('[data-lines]');
            const clone = rows.querySelector('[data-line]').cloneNode(true);
            const index = rows.querySelectorAll('[data-line]').length;
            clone.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/\[\d+\]/g, `[${index}]`);
                if (input.tagName === 'SELECT') input.selectedIndex = 0;
                else input.value = '';
                delete input.dataset.edited;
            });
            clone.querySelectorAll('[data-subtotal]').forEach((cell) => { cell.textContent = rupiah.format(0); });
            clone.querySelectorAll('[data-unit-label], [data-stock-label]').forEach((cell) => { cell.textContent = '—'; });
            rows.append(clone);
            refreshLine(form, clone);
            refreshTotal(form);
            return;
        }

        const remove = event.target.closest('[data-remove-line]');
        if (remove) {
            const section = remove.closest('[data-line-section]');
            const rows = section.querySelector('[data-lines]');
            if (rows.querySelectorAll('[data-line]').length > 1) remove.closest('[data-line]').remove();
            else {
                const row = remove.closest('[data-line]');
                row.querySelectorAll('select').forEach((select) => { select.selectedIndex = 0; });
                row.querySelectorAll('input').forEach((input) => { input.value = ''; delete input.dataset.edited; });
                refreshLine(form, row);
            }
            refreshTotal(form);
        }
    });
}

document.querySelectorAll('[data-transaction-form]').forEach(initTransactionForm);
