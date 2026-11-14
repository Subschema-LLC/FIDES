<?php
namespace App\Features\TodoManagement\ListTodos;

use App\Entities\TodoItem\TodoItem;
use App\Entities\TodoItem\TodoItemSettings;
use App\Entities\TodoItem\TodoItemStore;

/** Show the to-do list, newest first, up to the entity's list limit.
 *  This feature also shapes plain TodoRow and TodoSummary records so
 *  ListTodosView.php displays values without touching TodoItem. */
final class ListTodos
{
    public function __construct(
        private TodoItemStore $store,
        private TodoItemSettings $settings,
    ) {
    }

    /** @return list<TodoItem> */
    public function items(): array
    {
        return $this->store->all($this->settings->listLimit);
    }

    /**
     * @param list<TodoItem>|null $items
     * @return list<TodoRow>
     */
    public function rows(?array $items = null): array
    {
        $source = $items ?? $this->items();
        return array_map(
            static fn(TodoItem $item): TodoRow => new TodoRow(
                id: (int) $item->id,
                title: $item->title,
                done: $item->done,
                status: $item->done ? 'done' : 'open',
                createdDate: $item->createdAt->format('Y-m-d'),
                completedDate: $item->completedAt?->format('Y-m-d') ?? '',
            ),
            $source,
        );
    }

    /** @param list<TodoItem>|null $items */
    public function summary(?array $items = null): TodoSummary
    {
        $source = $items ?? $this->items();
        $done = 0;
        foreach ($source as $item) {
            if ($item->done) {
                $done++;
            }
        }
        $total = count($source);
        return new TodoSummary(
            total: $total,
            open: $total - $done,
            done: $done,
            limit: $this->settings->listLimit,
        );
    }
}
