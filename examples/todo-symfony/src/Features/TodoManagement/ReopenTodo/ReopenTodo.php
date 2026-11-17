<?php
namespace App\Features\TodoManagement\ReopenTodo;

use App\Entities\TodoItem\TodoItemStore;

/** Put one done to-do back on the open list. */
final class ReopenTodo
{
    public function __construct(private TodoItemStore $store)
    {
    }

    public function reopen(int $id): bool
    {
        $item = $this->store->get($id);
        if ($item === null) {
            return false;
        }
        $this->store->save($item->reopen());
        return true;
    }
}
