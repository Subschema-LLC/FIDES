document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('[data-todo-title-input]');
    if (!(input instanceof HTMLInputElement)) {
        return;
    }
    document.addEventListener('keydown', (event) => {
        if (event.key === '/' && document.activeElement !== input) {
            event.preventDefault();
            input.focus();
        }
    });
});