const loginForm = document.querySelector('[data-login-form]');

if (loginForm) {
    const password = loginForm.querySelector('#login-password');
    const toggle = loginForm.querySelector('[data-password-toggle]');
    const showIcon = toggle?.querySelector('[data-password-show-icon]');
    const hideIcon = toggle?.querySelector('[data-password-hide-icon]');

    toggle?.addEventListener('click', () => {
        const reveal = password.type === 'password';
        password.type = reveal ? 'text' : 'password';
        toggle.setAttribute('aria-label', reveal ? 'Sembunyikan password' : 'Tampilkan password');
        toggle.setAttribute('title', reveal ? 'Sembunyikan password' : 'Tampilkan password');
        showIcon.hidden = reveal;
        hideIcon.hidden = !reveal;
        password.focus();
    });

    loginForm.addEventListener('submit', (event) => {
        if (loginForm.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }

        loginForm.dataset.submitting = 'true';
        const submit = loginForm.querySelector('[data-login-submit]');
        const label = submit?.querySelector('[data-login-submit-label]');
        if (!submit || !label) return;

        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');
        const spinner = document.createElement('span');
        spinner.className = 'button-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        submit.insertBefore(spinner, label);
        label.textContent = 'Memproses…';
    });
}
