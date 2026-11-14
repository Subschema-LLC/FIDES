<?php
namespace App\Entities\TodoItem;

/** The settings TodoItem declares: every key, its default and its
 *  validation, in the entity's own folder. Overrides live in
 *  config/TodoItem.yaml and in the environment (TODOITEM_LIST_LIMIT);
 *  ConfigLoader merges them in that order and an undeclared key
 *  fails startup. */
final class TodoItemSettings
{
    public function __construct(public readonly int $listLimit = 50)
    {
        if ($this->listLimit < 1 || $this->listLimit > 500) {
            throw new \InvalidArgumentException('TodoItem list_limit must be between 1 and 500.');
        }
    }

    /** The declared keys and their defaults, for ConfigLoader. */
    public static function defaults(): array
    {
        return ['list_limit' => 50];
    }

    public static function fromMerged(array $values): self
    {
        return new self((int) $values['list_limit']);
    }
}
