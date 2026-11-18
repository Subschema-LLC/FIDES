<?php
namespace App\Delivery\Cli;

use App\Features\TodoManagement\ListTodos\ListTodos;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

/** php bin/console todo:list: newest first, up to the list limit. */
#[AsCommand(name: 'todo:list', description: 'List to-dos, newest first')]
final class ListCommand
{
    public function __construct(private ListTodos $listTodos)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $rows = $this->listTodos->rows();
        if ($rows === []) {
            $io->writeln('Nothing here yet.');
        }
        foreach ($rows as $row) {
            $io->writeln(sprintf('%-4d%s %s', $row->id, $row->done ? '[x]' : '[ ]', $row->title));
        }
        return 0;
    }
}
