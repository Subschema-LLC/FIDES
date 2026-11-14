document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-stat-filter]').forEach((card) => {
        card.addEventListener('click', () => {
            const filter = card.getAttribute('data-stat-filter');
            const target = document.querySelector(`[data-todo-filter="${filter}"]`);
            if (target instanceof HTMLElement) {
                target.click();
            }
        });
    });
});