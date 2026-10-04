document.querySelectorAll('[data-toast]').forEach((toast) => {
    let timer;
    const dismiss = () => toast.remove();
    const startTimer = () => { timer = window.setTimeout(dismiss, 3800); };
    const stopTimer = () => window.clearTimeout(timer);

    toast.querySelector('[data-toast-dismiss]')?.addEventListener('click', dismiss);
    toast.addEventListener('mouseenter', stopTimer);
    toast.addEventListener('mouseleave', startTimer);
    toast.addEventListener('focusin', stopTimer);
    toast.addEventListener('focusout', startTimer);
    startTimer();
});
