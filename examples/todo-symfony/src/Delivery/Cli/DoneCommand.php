<?php
namespace App\Delivery\Cli;

use App\Features\TodoManagement\CompleteTodo\CompleteTodo;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

/** php bin/console todo:done 1 */
#[AsCommand(name: 'todo:done', description: 'Mark a to-do done')]
final class DoneCommand
{
    public function __construct(private CompleteTodo $completeTodo)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Argument('The to-do number')] int $id): int
    {
        if (!$this->completeTodo->complete($id)) {
            $io->error("No to-do #$id.");
            return 1;
        }
        $io->writeln("Marked #$id done.");
        return 0;
    }
}
