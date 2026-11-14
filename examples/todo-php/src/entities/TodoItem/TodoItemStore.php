<?php
namespace App\Entities\TodoItem;

/** How TodoItems are kept. The entity declares the contract; an
 *  integration fulfills it. A save is the whole consistency unit:
 *  the item is small enough that nothing else must change with it. */
interface TodoItemStore
{
    /** Save a new or changed item; returns it with its id. */
    public function save(TodoItem $item): TodoItem;

    public function get(int $id): ?TodoItem;

    /** @return list<TodoItem> newest first, at most $limit */
    public function all(int $limit): array;

    public function delete(int $id): void;
}
