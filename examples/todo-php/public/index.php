<?php
/** The web door: php -S 127.0.0.1:8080 -t public (or: php app.php serve). */

$root = dirname(__DIR__);
require $root . '/autoload.php';
require $root . '/src/delivery/wiring.php';

$app = build_app($root, getenv());
echo (new App\Delivery\Web\WebApp($app))->handle($_SERVER['REQUEST_METHOD'], $_POST);
