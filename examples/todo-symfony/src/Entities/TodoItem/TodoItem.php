<?php
namespace App\Entities\TodoItem;

/** One to-do. The entity owns the rules that are true everywhere:
 *  a title is trimmed and between 1 and 200 characters, an item is
 *  either open or done, and finishing or reopening an item that is
 *  already in that state changes nothing. Time is passed in, never
 *  read here. */
final class TodoItem
{
    public const MAX_TITLE_LENGTH = 200;

    private function __construct(
        public readonly ?int $id,
        public readonly string $title,
        public readonly bool $done,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $completedAt,
    ) {
    }

    public static function create(string $title, \DateTimeImmutable $now): self
    {
        return new self(null, self::checkTitle($title), false, $now, null);
    }

    /** Rebuild an item from its stored row (stores only). */
    public static function restore(int $id, string $title, bool $done, \DateTimeImmutable $createdAt, ?\DateTimeImmutable $completedAt): self
    {
        return new self($id, self::checkTitle($title), $done, $createdAt, $completedAt);
    }

    public function complete(\DateTimeImmutable $now): self
    {
        if ($this->done) {
            return $this;
        }
        return new self($this->id, $this->title, true, $this->createdAt, $now);
    }

    public function reopen(): self
    {
        if (!$this->done) {
            return $this;
        }
        return new self($this->id, $this->title, false, $this->createdAt, null);
    }

    /** The same item with the id its store gave it. */
    public function withId(int $id): self
    {
        return new self($id, $this->title, $this->done, $this->createdAt, $this->completedAt);
    }

    private static function checkTitle(string $title): string
    {
        $title = trim($title);
        $length = function_exists('mb_strlen') ? mb_strlen($title) : strlen($title);
        if ($length < 1 || $length > self::MAX_TITLE_LENGTH) {
            throw new \InvalidArgumentException('A to-do title must be between 1 and ' . self::MAX_TITLE_LENGTH . ' characters.');
        }
        return $title;
    }
}
