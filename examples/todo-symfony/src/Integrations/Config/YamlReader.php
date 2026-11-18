<?php
namespace App\Integrations\Config;

/** The small slice of YAML the config tree uses: flat "key: value"
 *  lines, comments with #, plain or quoted scalars, ints, bools and
 *  null. That is all an override file needs. */
final class YamlReader
{
    /** @return array<string, mixed> */
    public static function readFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!preg_match('/^([a-z0-9_]+):\s*(.*)$/', $line, $m)) {
                throw new \RuntimeException("Cannot read config line: $line");
            }
            $values[$m[1]] = self::scalar(trim($m[2]));
        }
        return $values;
    }

    private static function scalar(string $raw): mixed
    {
        if ($raw === '' || $raw === 'null' || $raw === '~') {
            return null;
        }
        if ($raw === 'true') {
            return true;
        }
        if ($raw === 'false') {
            return false;
        }
        if (preg_match('/^-?\d+$/', $raw)) {
            return (int) $raw;
        }
        if (strlen($raw) >= 2 && ($raw[0] === '"' || $raw[0] === "'") && $raw[strlen($raw) - 1] === $raw[0]) {
            return substr($raw, 1, -1);
        }
        return $raw;
    }
}
