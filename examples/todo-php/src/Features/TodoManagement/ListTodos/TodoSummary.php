<?php
namespace App\Features\TodoManagement\ListTodos;

/** Plain summary counts shaped by ListTodos for dashboard and page headers. */
final readonly class TodoSummary
{
    public function __construct(
        public int $total,
        public int $open,
        public int $done,
        public int $limit,
    ) {
    }
}
