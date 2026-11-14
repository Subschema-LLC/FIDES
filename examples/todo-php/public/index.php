<?php
/** The web door: php -S 127.0.0.1:8080 -t public public/index.php (or: php app.php serve). */

$root = dirname(__DIR__);
require $root . '/autoload.php';
require $root . '/src/Delivery/Wiring.php';

$app = build_app($root, getenv());
$uri = $_SERVER['REQUEST_URI'] ?? '/';
echo (new App\Delivery\Web\WebApp($app))->handle($_SERVER['REQUEST_METHOD'] ?? 'GET', $_POST, $uri);
