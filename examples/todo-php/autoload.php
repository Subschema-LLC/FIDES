<?php
/** A tiny autoloader: App\<Zone>\<...> maps to src/<zone>/<...>.php.
 *  The zone folder is lowercased. Entity and feature folders keep
 *  their case (TodoItem, ListTodos); integration and delivery
 *  subfolders are lowercase (database, cli), as on disk.
 *  No Composer, nothing to install. */
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $parts = explode('\\', substr($class, 4));
    $zone = strtolower((string) array_shift($parts));
    if (($zone === 'integrations' || $zone === 'delivery') && $parts !== []) {
        $parts[0] = strtolower($parts[0]);
    }
    $path = __DIR__ . '/src/' . $zone . '/' . implode('/', $parts) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
