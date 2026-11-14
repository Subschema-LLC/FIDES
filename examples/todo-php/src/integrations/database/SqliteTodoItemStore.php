<?php
namespace App\Integrations\Database;

use App\Entities\TodoItem\TodoItem;
use App\Entities\TodoItem\TodoItemStore;

/** TodoItemStore on SQLite. The table is transactional data, so it
 *  belongs in the database; nothing about configuration lives here. */
final class SqliteTodoItemStore implements TodoItemStore
{
    public function __construct(private \PDO $pdo)
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS todo_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                done INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                completed_at TEXT
            )'
        );
    }

    public function save(TodoItem $item): TodoItem
    {
        if ($item->id === null) {
            $stmt = $this->pdo->prepare('INSERT INTO todo_items (title, done, created_at, completed_at) VALUES (?, ?, ?, ?)');
            $stmt->execute([$item->title, $item->done ? 1 : 0, self::stamp($item->createdAt), $item->completedAt === null ? null : self::stamp($item->completedAt)]);
            return $item->withId((int) $this->pdo->lastInsertId());
        }
        $stmt = $this->pdo->prepare('UPDATE todo_items SET title = ?, done = ?, completed_at = ? WHERE id = ?');
        $stmt->execute([$item->title, $item->done ? 1 : 0, $item->completedAt === null ? null : self::stamp($item->completedAt), $item->id]);
        return $item;
    }

    public function get(int $id): ?TodoItem
    {
        $stmt = $this->pdo->prepare('SELECT * FROM todo_items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : self::fromRow($row);
    }

    public function all(int $limit): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM todo_items ORDER BY id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn(array $row): TodoItem => self::fromRow($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM todo_items WHERE id = ?');
        $stmt->execute([$id]);
    }

    private static function fromRow(array $row): TodoItem
    {
        return TodoItem::restore(
            (int) $row['id'],
            (string) $row['title'],
            (int) $row['done'] === 1,
            new \DateTimeImmutable((string) $row['created_at']),
            $row['completed_at'] === null ? null : new \DateTimeImmutable((string) $row['completed_at']),
        );
    }

    private static function stamp(\DateTimeImmutable $when): string
    {
        return $when->format('Y-m-d H:i:s');
    }
}
