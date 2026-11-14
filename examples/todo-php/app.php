<?php
/** The command-line door. Run: php app.php list
 *  Serve the web door with: php app.php serve */

$root = __DIR__;
require $root . '/autoload.php';
require $root . '/src/delivery/wiring.php';

$args = array_slice($argv, 1);
if (($args[0] ?? '') === 'serve') {
    echo "Web app on http://127.0.0.1:8080 (Ctrl+C to stop)\n";
    passthru('php -S 127.0.0.1:8080 -t ' . escapeshellarg($root . '/public'));
    exit(0);
}

$app = build_app($root, getenv());
exit((new App\Delivery\Cli\CliApp($app))->run($args));
