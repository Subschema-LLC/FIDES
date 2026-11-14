document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('[data-flash]');
    if (flash) {
        flash.addEventListener('click', () => flash.remove());
    }
});