<?php
namespace App\Features\TodoManagement\AddTodo;

use App\Entities\TodoItem\TodoItem;
use App\Entities\TodoItem\TodoItemStore;
use App\Shared\Clock;

/** Add one to-do. The entity checks the title; the store keeps it.
 *  The form that submits to this feature lives beside it, in its view. */
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

    /** @return array{csrf: string, actionPath: string, returnTo: string, maxLength: int} */
    public function formValues(string $csrf, string $actionPath = '/todo', string $returnTo = '/todo'): array
    {
        return [
            'csrf' => $csrf,
            'actionPath' => $actionPath,
            'returnTo' => $returnTo,
            'maxLength' => TodoItem::MAX_TITLE_LENGTH,
        ];
    }
}
