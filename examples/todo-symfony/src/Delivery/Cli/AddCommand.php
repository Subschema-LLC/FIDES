<?php
namespace App\Delivery\Cli;

use App\Features\TodoManagement\AddTodo\AddTodo;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

/** php bin/console todo:add "Write the spec". An invokable command:
 *  since Symfony 7.3 it needs no Command base class. */
#[AsCommand(name: 'todo:add', description: 'Add a to-do')]
final class AddCommand
{
    public function __construct(private AddTodo $addTodo)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('What needs to be done')] string $title,
    ): int
    {
        try {
            $item = $this->addTodo->add($title);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return 1;
        }
        $io->writeln(sprintf('Added #%d: %s', (int) $item->id, $item->title));
        return 0;
    }
}
