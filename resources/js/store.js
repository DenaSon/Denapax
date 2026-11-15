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
