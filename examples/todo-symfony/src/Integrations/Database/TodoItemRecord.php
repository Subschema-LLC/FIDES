<?php
namespace App\Integrations\Database;

use Doctrine\ORM\Mapping as ORM;

/** The row Doctrine maps, one per to-do. It is the store's record, not
 *  the entity: only DoctrineTodoItemStore touches it, copying values
 *  to and from TodoItem, which carries no mapping at all. It refers to
 *  nothing else, so it maps no associations. It is not final because
 *  Doctrine generates lazy proxies that extend it (PHP 8.4's native
 *  lazy objects remove that need); the framework extends it, it
 *  extends nothing. */
#[ORM\Entity]
#[ORM\Table(name: 'todo_items')]
class TodoItemRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 200)]
    public string $title = '';

    #[ORM\Column]
    public bool $done = false;

    #[ORM\Column]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $completedAt = null;
}
