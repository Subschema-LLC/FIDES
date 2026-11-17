<?php
/** The web door: php -S 127.0.0.1:8080 -t public public/index.php */

use App\Delivery\Kernel;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

$env = getenv('APP_ENV') ?: 'dev';
$kernel = new Kernel($env, $env !== 'prod');
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
