<?php
namespace App\Features\AddTodo;

use App\Entities\TodoItem\TodoItem;
use App\Entities\TodoItem\TodoItemStore;
use App\Shared\Clock;

/** Add one to-do. The entity checks the title; the store keeps it. */
final class AddTodo
{
    public function __construct(
        private TodoItemStore $store,
        private Clock $clock,
    ) {
    }

    public function add(string $title): TodoItem
    {
        return $this->store->save(TodoItem::create($title, $this->clock->now()));
    }
}
