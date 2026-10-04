const contactForm = document.querySelector('.contact-form');

if (contactForm && window.fetch) {
    const fields = [...contactForm.querySelectorAll('input:not([type="hidden"]), textarea')];
    const button = contactForm.querySelector('[type="submit"]');
    const buttonLabel = contactForm.querySelector('[data-contact-button-label]');
    const feedback = contactForm.querySelector('[data-contact-feedback]');
    const initialLabel = buttonLabel.textContent;
    contactForm.noValidate = true;

    const messageFor = (field) => {
        if (field.validity.valueMissing) return `${field.labels?.[0]?.textContent.replace('*', '').replace('(opsional)', '').trim() || 'Kolom ini'} wajib diisi.`;
        if (field.validity.typeMismatch) return 'Masukkan alamat email yang valid.';
        if (field.validity.tooLong) return 'Isi kolom ini terlalu panjang.';
        return '';
    };

    const showFieldError = (field, message) => {
        let error = contactForm.querySelector(`#${field.id}-error`);
        if (!error) {
            error = document.createElement('span');
            error.id = `${field.id}-error`;
            error.className = 'field-error';
            field.insertAdjacentElement('afterend', error);
        }
        error.textContent = message;
        error.hidden = !message;
        if (message) {
            field.setAttribute('aria-invalid', 'true');
            field.setAttribute('aria-describedby', error.id);
        } else {
            field.removeAttribute('aria-invalid');
            field.removeAttribute('aria-describedby');
        }
    };

    fields.forEach((field) => {
        field.addEventListener('blur', () => showFieldError(field, messageFor(field)));
        field.addEventListener('input', () => {
            if (field.getAttribute('aria-invalid') === 'true' && field.checkValidity()) showFieldError(field, '');
        });
    });

    const showFeedback = (message, kind) => {
        feedback.replaceChildren(document.createTextNode(message));
        feedback.dataset.kind = kind;
        feedback.hidden = false;
    };

    contactForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (button.disabled) return;
        const invalidFields = fields.filter((field) => {
            const message = messageFor(field);
            showFieldError(field, message);
            return Boolean(message);
        });
        if (invalidFields.length) {
            showFeedback('Periksa kolom yang ditandai, lalu coba lagi.', 'error');
            invalidFields[0].focus();
            return;
        }

        button.disabled = true;
        button.dataset.state = 'loading';
        buttonLabel.textContent = 'Menyiapkan pesan…';
        feedback.hidden = true;

        try {
            const response = await fetch(contactForm.action, {
                method: 'POST',
                body: new FormData(contactForm),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();
            if (response.status === 422 && data.errors) {
                for (const [name, messages] of Object.entries(data.errors)) {
                    const field = contactForm.elements.namedItem(name);
                    if (field) showFieldError(field, messages[0]);
                }
                showFeedback('Periksa kolom yang ditandai, lalu coba lagi.', 'error');
                contactForm.querySelector('[aria-invalid="true"]')?.focus();
                return;
            }
            if (!response.ok || !data.url) throw new Error('Contact request failed');

            showFeedback('Pesan siap dikirim melalui WhatsApp. Kami sedang membukanya…', 'success');
            const fallback = document.createElement('a');
            fallback.href = data.url;
            fallback.textContent = 'Buka WhatsApp';
            feedback.append(' ', fallback);
            button.dataset.state = 'success';
            buttonLabel.textContent = 'Pesan siap';
            window.setTimeout(() => window.location.assign(data.url), 500);
        } catch {
            showFeedback('Pesan belum bisa disiapkan. Periksa koneksi, lalu coba lagi.', 'error');
        } finally {
            if (button.dataset.state !== 'success') {
                button.disabled = false;
                button.dataset.state = '';
                buttonLabel.textContent = initialLabel;
            }
        }
    });

    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        button.disabled = false;
        button.dataset.state = '';
        buttonLabel.textContent = initialLabel;
    });
}
