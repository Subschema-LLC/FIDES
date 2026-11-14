<?php
namespace App\Delivery\Cli;

/** The command-line door. It translates arguments into feature
 *  calls and prints the result; it holds no rules of its own. */
final class CliApp
{
    public function __construct(private array $app)
    {
    }

    /** @param list<string> $args */
    public function run(array $args): int
    {
        $command = $args[0] ?? 'list';
        try {
            return match ($command) {
                'list' => $this->list(),
                'add' => $this->add($args[1] ?? ''),
                'done' => $this->done((int) ($args[1] ?? 0)),
                'reopen' => $this->reopen((int) ($args[1] ?? 0)),
                'delete' => $this->delete((int) ($args[1] ?? 0)),
                'config' => $this->config($args[1] ?? 'TodoItem'),
                default => $this->usage(),
            };
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            fwrite(STDERR, $e->getMessage() . PHP_EOL);
            return 1;
        }
    }

    private function list(): int
    {
        $items = $this->app['list']->items();
        if ($items === []) {
            echo "Nothing here yet.\n";
            return 0;
        }
        foreach ($items as $item) {
            echo str_pad((string) $item->id, 4) . ($item->done ? '[x] ' : '[ ] ') . $item->title . PHP_EOL;
        }
        return 0;
    }

    private function add(string $title): int
    {
        $item = $this->app['add']->add($title);
        echo "Added #{$item->id}: {$item->title}\n";
        return 0;
    }

    private function done(int $id): int
    {
        echo $this->app['complete']->complete($id) ? "Done: #$id\n" : "No to-do #$id.\n";
        return 0;
    }

    private function reopen(int $id): int
    {
        echo $this->app['reopen']->reopen($id) ? "Reopened: #$id\n" : "No to-do #$id.\n";
        return 0;
    }

    private function delete(int $id): int
    {
        echo $this->app['delete']->delete($id) ? "Deleted: #$id\n" : "No to-do #$id.\n";
        return 0;
    }

    private function config(string $entity): int
    {
        if ($entity !== 'TodoItem') {
            echo "Only TodoItem has settings in this app.\n";
            return 1;
        }
        foreach ($this->app['todo_settings_report'] as $key => [$value, $origin]) {
            echo "$key = " . var_export($value, true) . " ($origin)\n";
        }
        return 0;
    }

    private function usage(): int
    {
        echo "Usage: php app.php [list | add \"title\" | done <id> | reopen <id> | delete <id> | config TodoItem | serve]\n";
        return 1;
    }
}
