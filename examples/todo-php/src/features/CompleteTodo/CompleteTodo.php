<?php
namespace App\Features\CompleteTodo;

use App\Entities\TodoItem\TodoItemStore;
use App\Shared\Clock;

/** Mark one to-do done. An unknown id is a plain "not found". */
final class CompleteTodo
{
    public function __construct(
        private TodoItemStore $store,
        private Clock $clock,
    ) {
    }

    public function complete(int $id): bool
    {
        $item = $this->store->get($id);
        if ($item === null) {
            return false;
        }
        $this->store->save($item->complete($this->clock->now()));
        return true;
    }
}
