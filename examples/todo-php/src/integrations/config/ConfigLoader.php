<?php
namespace App\Integrations\Config;

use App\Entities\TodoItem\TodoItemSettings;

/** The one merger. For each entity: the Settings class default,
 *  then config/<Entity>.yaml, then the environment variable
 *  (TODOITEM_LIST_LIMIT); later wins. An override key the Settings
 *  class does not declare fails startup, so a typo cannot pass
 *  quietly. Merged values are validated by the Settings class.
 *  Secrets never come from these files; there are none here. */
final class ConfigLoader
{
    public function __construct(private string $configDir, private array $env)
    {
    }

    public function todoItemSettings(): TodoItemSettings
    {
        $values = $this->merge('TodoItem', TodoItemSettings::defaults());
        return TodoItemSettings::fromMerged($values);
    }

    /** @return array<string, string> key => where its value came from */
    public function provenance(string $entity, array $defaults): array
    {
        $origin = array_map(fn($v): string => 'default', $defaults);
        foreach (YamlReader::readFile($this->configDir . '/' . $entity . '.yaml') as $key => $value) {
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

    /** @return array<string, mixed> */
    public function mergedValues(string $entity, array $defaults): array
    {
        return $this->merge($entity, $defaults);
    }

    private function merge(string $entity, array $defaults): array
    {
        $values = $defaults;
        foreach (YamlReader::readFile($this->configDir . '/' . $entity . '.yaml') as $key => $value) {
            if (!array_key_exists($key, $defaults)) {
                throw new \RuntimeException("config/$entity.yaml sets '$key', which {$entity}Settings does not declare.");
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
