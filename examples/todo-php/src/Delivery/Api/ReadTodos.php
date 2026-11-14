<?php
namespace App\Delivery\Api;

use App\Features\TodoManagement\ListTodos\ListTodos;

/** The API door for reading to-dos (GET /api/todos). Calls ListTodos
 *  for the shaped rows and summary and returns JSON. */
final class ReadTodos
{
    public function __construct(private ListTodos $listTodos)
    {
    }

    public function handle(): string
    {
        $items = $this->listTodos->items();
        $summary = $this->listTodos->summary($items);
        $rows = $this->listTodos->rows($items);

        return (string) json_encode([
            'summary' => [
                'total' => $summary->total,
                'open' => $summary->open,
                'done' => $summary->done,
                'limit' => $summary->limit,
            ],
            'items' => array_map(
                static fn($row): array => [
                    'id' => $row->id,
                    'title' => $row->title,
                    'done' => $row->done,
                    'status' => $row->status,
                    'created_at' => $row->createdDate,
                    'completed_at' => $row->completedDate !== '' ? $row->completedDate : null,
                ],
                $rows,
            ),
        ], JSON_THROW_ON_ERROR);
    }
}
