<?php
namespace App\Integrations\Config;

use App\Entities\TodoItem\TodoItemSettings;
use App\Shared\SettingsSources;

/** The one merger. For each entity: the Settings class default, then
 *  overrides/<Entity>.yaml, then the environment variable
 *  (TODOITEM_LIST_LIMIT); later wins. An override key the Settings
 *  class does not declare fails startup, so a typo cannot pass
 *  quietly. Merged values are validated by the Settings class.
 *
 *  The container calls fromEnvironment() each time it builds the app
 *  (every request under PHP-FPM), so an edited override applies on
 *  the next request and nothing is compiled into Symfony's cache. */
final class ConfigLoader implements SettingsSources
{
    private const OWNERS = ['TodoItem' => TodoItemSettings::class];

    public function __construct(private string $configDir, private array $env)
    {
    }

    public static function fromEnvironment(string $configDir): self
    {
        return new self($configDir, getenv());
    }

    public function todoItemSettings(): TodoItemSettings
    {
        $values = $this->merge('TodoItem', TodoItemSettings::defaults());
        return TodoItemSettings::fromMerged($values);
    }

    public function sources(string $owner): array
    {
        $settings = self::OWNERS[$owner] ?? null;
        if ($settings === null) {
            throw new \InvalidArgumentException("No settings are declared for '$owner'.");
        }
        $defaults = $settings::defaults();
        $origin = $this->provenance($owner, $defaults);
        $report = [];
        foreach ($this->merge($owner, $defaults) as $key => $value) {
            $report[$key] = [$value, $origin[$key]];
        }
        return $report;
    }

    /** @return array<string, string> key => where its value came from */
    private function provenance(string $entity, array $defaults): array
    {
        $origin = array_map(fn($v): string => 'default', $defaults);
        $file = $this->configDir . '/' . $entity . '.yaml';
        foreach (YamlReader::readFile($file) as $key => $value) {
            if (array_key_exists($key, $defaults)) {
                $origin[$key] = 'file';
            }
        }
        foreach ($defaults as $key => $value) {
            if (array_key_exists($this->envName($entity, $key), $this->env)) {
                $origin[$key] = 'env';
            }
        }
        return $origin;
    }

    private function merge(string $entity, array $defaults): array
    {
        $values = $defaults;
        foreach (YamlReader::readFile($this->configDir . '/' . $entity . '.yaml') as $key => $value) {
            if (!array_key_exists($key, $defaults)) {
                $file = basename($this->configDir) . "/$entity.yaml";
                throw new \RuntimeException("$file sets '$key', which {$entity}Settings does not declare.");
            }
            $values[$key] = $value;
        }
        foreach ($defaults as $key => $value) {
            $envName = $this->envName($entity, $key);
            if (array_key_exists($envName, $this->env)) {
                $values[$key] = self::envScalar($this->env[$envName]);
            }
        }
        return $values;
    }

    /** Environment values arrive as strings; read them with the
     *  same scalar rules as the YAML files. */
    private static function envScalar(mixed $raw): mixed
    {
        if (!is_string($raw)) {
            return $raw;
        }
        if (preg_match('/^-?\d+$/', $raw)) {
            return (int) $raw;
        }
        return match ($raw) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $raw,
        };
    }

    private function envName(string $entity, string $key): string
    {
        return strtoupper($entity . '_' . $key);
    }
}
