let activeBar;
let tooltip;
let touchTimer;
let lastPointerType;

function getTooltip() {
    if (tooltip) return tooltip;
    tooltip = document.createElement('div');
    tooltip.className = 'dashboard-chart-tooltip';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.setAttribute('aria-hidden', 'true');
    tooltip.innerHTML = '<span data-tooltip-period></span><strong data-tooltip-value></strong><span data-tooltip-count></span>';
    document.body.append(tooltip);
    return tooltip;
}

function positionTooltip(bar, pointer, touch = false) {
    const popup = getTooltip();
    const anchor = bar.getBoundingClientRect();
    const x = pointer?.clientX ?? anchor.left + anchor.width / 2;
    const y = pointer?.clientY ?? anchor.top;
    const width = popup.offsetWidth;
    const height = popup.offsetHeight;
    if (touch) {
        popup.style.left = `${Math.max(8, (window.innerWidth - width) / 2)}px`;
        popup.style.top = `${Math.max(8, window.innerHeight - height - 12)}px`;
        return;
    }
    popup.style.left = `${Math.max(8, Math.min(x - width / 2, window.innerWidth - width - 8))}px`;
    popup.style.top = `${y - height - 12 >= 8 ? y - height - 12 : Math.min(y + 16, window.innerHeight - height - 8)}px`;
}

function showTooltip(bar, pointer, touch = false) {
    if (!bar?.matches('[data-chart-bar]')) return;
    window.clearTimeout(touchTimer);
    activeBar?.classList.remove('is-chart-active');
    activeBar = bar;
    const popup = getTooltip();
    popup.querySelector('[data-tooltip-period]').textContent = bar.dataset.chartPeriod;
    popup.querySelector('[data-tooltip-value]').textContent = `${bar.dataset.chartLabel}: ${bar.dataset.chartValue}`;
    popup.querySelector('[data-tooltip-count]').textContent = bar.dataset.chartCount;
    popup.classList.add('is-visible');
    popup.setAttribute('aria-hidden', 'false');
    bar.classList.add('is-chart-active');
    positionTooltip(bar, pointer, touch);
}

function hideTooltip() {
    window.clearTimeout(touchTimer);
    activeBar?.classList.remove('is-chart-active');
    activeBar = undefined;
    tooltip?.classList.remove('is-visible');
    tooltip?.setAttribute('aria-hidden', 'true');
}

document.addEventListener('pointerover', (event) => {
    if (event.pointerType !== 'touch') showTooltip(event.target.closest?.('[data-chart-bar]'), event);
});

document.addEventListener('pointermove', (event) => {
    if (event.pointerType === 'mouse' && activeBar === event.target.closest?.('[data-chart-bar]')) {
        positionTooltip(activeBar, event);
    }
});

document.addEventListener('pointerout', (event) => {
    if (event.pointerType !== 'touch' && activeBar === event.target.closest?.('[data-chart-bar]') && document.activeElement !== activeBar) {
        hideTooltip();
    }
});

document.addEventListener('focusin', (event) => {
    if (event.target.matches?.('[data-chart-bar]')) showTooltip(event.target);
});

document.addEventListener('focusout', (event) => {
    if (event.target === activeBar) hideTooltip();
});

document.addEventListener('click', (event) => {
    const bar = event.target.closest?.('[data-chart-bar]');
    if (!bar) return;
    const touch = lastPointerType === 'touch' || window.matchMedia('(hover: none)').matches;
    showTooltip(bar, undefined, touch);
    if (touch) touchTimer = window.setTimeout(hideTooltip, 4000);
    lastPointerType = undefined;
});

document.addEventListener('pointerdown', (event) => {
    lastPointerType = event.pointerType;
    if (activeBar && !event.target.closest?.('[data-chart-bar]')) hideTooltip();
});

document.addEventListener('admin:navigated', hideTooltip);
document.addEventListener('keydown', (event) => { if (event.key === 'Escape') hideTooltip(); });
window.addEventListener('scroll', hideTooltip, { passive: true });
