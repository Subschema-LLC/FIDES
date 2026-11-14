<?php
/** A tiny PSR-4 autoloader: App\<Zone>\<...> maps directly to
 *  src/<Zone>/<...>.php, matching PascalCase folders and files 1:1.
 *  No Composer, nothing to install. */
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, 4));
    $path = __DIR__ . '/src/' . $relative . '.php';
    if (is_file($path)) {
        require $path;
    }
});
