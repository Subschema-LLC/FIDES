<?php
namespace App\Delivery\Cli;

use App\Shared\SettingsSources;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

/** php bin/console todo:config TodoItem: each setting, its effective
 *  value and the layer it came from (default, file or env). */
#[AsCommand(name: 'todo:config', description: 'Show settings and where each value came from')]
final class ConfigCommand
{
    public function __construct(private SettingsSources $settings)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The owner')] string $owner = 'TodoItem',
    ): int
    {
        try {
            $sources = $this->settings->sources($owner);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return 1;
        }
        foreach ($sources as $key => [$value, $layer]) {
            $io->writeln(sprintf('%-14s %-8s %s', $key, var_export($value, true), $layer));
        }
        return 0;
    }
}
