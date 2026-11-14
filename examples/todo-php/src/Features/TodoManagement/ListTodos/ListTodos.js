document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-todo-list]').forEach((section) => {
        const buttons = section.querySelectorAll('[data-todo-filter]');
        const items = section.querySelectorAll('.todos li[data-status]');

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.getAttribute('data-todo-filter') || 'all';
                buttons.forEach((btn) => btn.classList.toggle('active', btn === button));
                items.forEach((item) => {
                    const status = item.getAttribute('data-status');
                    item.hidden = filter !== 'all' && status !== filter;
                });
            });
        });
    });
});