<?php
namespace App\Integrations\Database;

use App\Entities\TodoItem\TodoItem;
use App\Entities\TodoItem\TodoItemStore;
use Doctrine\ORM\EntityManagerInterface;

/** Fulfills TodoItemStore with Doctrine ORM. Each save is one flush:
 *  the whole consistency unit for a to-do. The store copies between
 *  the TodoItem entity and its TodoItemRecord, so the entity never
 *  depends on Doctrine. */
final class DoctrineTodoItemStore implements TodoItemStore
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(TodoItem $item): TodoItem
    {
        $record = $item->id === null ? new TodoItemRecord() : $this->record($item->id);
        if ($record === null) {
            throw new \RuntimeException("No to-do #{$item->id} to save.");
        }
        $record->title = $item->title;
        $record->done = $item->done;
        $record->createdAt = $item->createdAt;
        $record->completedAt = $item->completedAt;
        $this->entityManager->persist($record);
        $this->entityManager->flush();
        return $item->id === null ? $item->withId((int) $record->id) : $item;
    }

    public function get(int $id): ?TodoItem
    {
        $record = $this->record($id);
        return $record === null ? null : self::restore($record);
    }

    public function all(int $limit): array
    {
        $records = $this->entityManager->getRepository(TodoItemRecord::class)
            ->findBy([], ['id' => 'DESC'], $limit);
        return array_values(array_map(self::restore(...), $records));
    }

    public function delete(int $id): void
    {
        $record = $this->record($id);
        if ($record !== null) {
            $this->entityManager->remove($record);
            $this->entityManager->flush();
        }
    }

    private function record(int $id): ?TodoItemRecord
    {
        return $this->entityManager->find(TodoItemRecord::class, $id);
    }

    private static function restore(TodoItemRecord $record): TodoItem
    {
        return TodoItem::restore(
            (int) $record->id,
            $record->title,
            $record->done,
            $record->createdAt,
            $record->completedAt,
        );
    }
}
