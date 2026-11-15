// Progressive enhancement only: all store forms remain usable without JavaScript.
const resetPendingForms = () => {
    document.querySelectorAll('form[data-submit-once]').forEach((form) => {
        delete form.dataset.submitting;
        form.querySelectorAll('[data-busy-label]').forEach((button) => {
            button.removeAttribute('aria-disabled');
            button.classList.remove('is-submitting');
            if (button.dataset.originalLabel !== undefined) {
                button.textContent = button.dataset.originalLabel;
            }
        });
    });
};

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-submit-once]')) return;

    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }

    form.dataset.submitting = 'true';
    const button = event.submitter instanceof HTMLButtonElement
        ? event.submitter
        : form.querySelector('button[type="submit"]');

    if (!button || !button.dataset.busyLabel) return;
    button.dataset.originalLabel = button.textContent.trim();
    button.textContent = button.dataset.busyLabel;
    button.setAttribute('aria-disabled', 'true');
    button.classList.add('is-submitting');
});

window.addEventListener('pageshow', resetPendingForms);


// Copy controls are progressive enhancements; numbers remain visible/selectable without JS.
if (navigator.clipboard?.writeText) {
    document.querySelectorAll('[data-copy-value]').forEach((button) => {
        button.hidden = false;
        button.addEventListener('click', async () => {
            const label = button.dataset.copyLabel || 'کپی';
            try {
                await navigator.clipboard.writeText(button.dataset.copyValue || '');
                button.textContent = 'کپی شد ✓';
            } catch {
                button.textContent = 'امکان کپی نبود؛ شماره را انتخاب کنید';
            }
            window.setTimeout(() => { button.textContent = label; }, 2200);
        });
    });
}
