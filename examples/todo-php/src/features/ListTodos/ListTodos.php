<?php
namespace App\Features\ListTodos;

use App\Entities\TodoItem\TodoItemSettings;
use App\Entities\TodoItem\TodoItemStore;

/** Show the to-do list, newest first, up to the entity's list limit.
 *  The view lives in this folder: view.php renders the items this
 *  feature returns. */
final class ListTodos
{
    public function __construct(
        private TodoItemStore $store,
        private TodoItemSettings $settings,
    ) {
    }

    /** @return list<\App\Entities\TodoItem\TodoItem> */
    public function items(): array
    {
        return $this->store->all($this->settings->listLimit);
    }

    /** @param list<\App\Entities\TodoItem\TodoItem> $items */
    public function view(array $items, string $csrf): string
    {
        ob_start();
        require __DIR__ . '/view.php';
        return (string) ob_get_clean();
    }
}
