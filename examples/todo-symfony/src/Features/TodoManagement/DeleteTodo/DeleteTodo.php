<?php
namespace App\Features\TodoManagement\DeleteTodo;

use App\Entities\TodoItem\TodoItemStore;

/** Remove one to-do for good. */
final class DeleteTodo
{
    public function __construct(private TodoItemStore $store)
    {
    }

    public function delete(int $id): bool
    {
        if ($this->store->get($id) === null) {
            return false;
        }
        $this->store->delete($id);
        return true;
    }
}
