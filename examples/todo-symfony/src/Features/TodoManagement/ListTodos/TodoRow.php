<?php
namespace App\Features\TodoManagement\ListTodos;

/** Plain, view-ready values for one to-do row. The feature shapes
 *  these from TodoItem so templates only display strings and booleans
 *  and never touch the entity. */
final readonly class TodoRow
{
    public function __construct(
        public int $id,
        public string $title,
        public bool $done,
        public string $status,
        public string $createdDate,
        public string $completedDate,
    ) {
    }
}
