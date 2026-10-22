document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-duitku-reference]');

    if (!button || button.disabled) {
        return;
    }

    const emit = (name, detail) => {
        button.dispatchEvent(new CustomEvent(`duitku:${name}`, { bubbles: true, detail }));
    };

    if (!window.checkout || typeof window.checkout.process !== 'function') {
        emit('error', { message: 'Duitku checkout has not loaded. Try again after the page finishes loading.' });
        return;
    }

    window.checkout.process(button.dataset.duitkuReference, {
        successEvent: (result) => emit('success', result),
        pendingEvent: (result) => emit('pending', result),
        errorEvent: (result) => emit('error', result),
        closeEvent: (result) => emit('close', result),
    });
});
