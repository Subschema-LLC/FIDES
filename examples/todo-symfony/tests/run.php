<?php
/** The test run: the architecture check, the core it shares with the PHP
 *  example, the Doctrine store, settings, the web page and the CLI, on a
 *  throwaway SQLite database (var/test.sqlite). php tests/run.php */

use App\Delivery\Kernel;
use App\Entities\TodoItem\TodoItem;
use App\Entities\TodoItem\TodoItemStore;
use App\Features\TodoManagement\ListTodos\ListTodos;
use App\Integrations\Config\ConfigLoader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$passes = 0;
$fails = 0;
function check(bool $ok, string $name): void
{
    global $passes, $fails;
    $ok ? $passes++ : $fails++;
    if (!$ok) {
        echo "FAIL: $name\n";
    }
}

// Architecture first: a broken shape fails the whole run.
passthru('php ' . escapeshellarg($root . '/arch-check.php'), $archExit);
check($archExit === 0, 'arch-check passes');

// The core is the PHP example's, byte for byte. Only integrations and
// delivery change when the framework does.
$plain = dirname($root) . '/todo-php/src';
$core = array_merge(
    glob("$root/src/Entities/*/*.php") ?: [],
    glob("$root/src/Features/*/*/*.{php,css,js}", GLOB_BRACE) ?: [],
    ["$root/src/Shared/Clock.php"],
);
$differ = array_filter(
    $core,
    static fn(string $f): bool => @file_get_contents($f)
        !== @file_get_contents($plain . substr($f, strlen("$root/src"))),
);
check(count($core) > 10 && $differ === [], 'entities, features and Clock match todo-php');

// A fresh test database, created from the Doctrine mapping.
@unlink("$root/var/test.sqlite");
$kernel = new Kernel('test', false);
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
$em = $container->get(EntityManagerInterface::class);
$em->getConfiguration()->setUseSchemaEditorApi(true);
(new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());

// The store, through its contract.
$store = $container->get(TodoItemStore::class);
$now = new DateTimeImmutable('2026-11-17 12:00:00');
$saved = $store->save(TodoItem::create('  Write the spec  ', $now));
$read = $store->get((int) $saved->id);
check($read?->title === 'Write the spec', 'the Doctrine store saves and reads');
$store->save($saved->complete($now));
check($store->get((int) $saved->id)?->done === true, 'a change is saved');
$store->delete((int) $saved->id);
check($store->get((int) $saved->id) === null, 'delete removes the to-do');

// Settings: overrides/ beats the default, the environment beats the file.
$limit = $container->get(ListTodos::class)->summary([])->limit;
check($limit === 25, 'overrides/TodoItem.yaml wins over the default');
putenv('TODOITEM_LIST_LIMIT=7');
$fromEnv = ConfigLoader::fromEnvironment("$root/overrides")->todoItemSettings()->listLimit;
putenv('TODOITEM_LIST_LIMIT');
check($fromEnv === 7, 'the environment wins, read each time the app is built');

// Web, through Symfony's test client.
$client = $container->get('test.client');
$client->request('GET', '/');
$html = (string) $client->getResponse()->getContent();
check(str_contains($html, 'data-add-todo-form'), 'GET / shows the page with the AddTodo form');
preg_match('/name="_csrf" value="([^"]+)"/', $html, $token);
$form = ['_csrf' => $token[1] ?? '', 'action' => 'add', 'title' => 'Ship it'];
$client->request('POST', '/', $form);
check($client->getResponse()->getStatusCode() === 303, 'POST add redirects');
$client->request('GET', '/');
$html = (string) $client->getResponse()->getContent();
check(str_contains($html, 'Added: Ship it'), 'the flash says what happened');
check(str_contains($html, '<span class="title">Ship it</span>'), 'the new to-do is listed');
$client->request('POST', '/', ['_csrf' => 'wrong', 'action' => 'add', 'title' => 'Sneaky']);
$client->request('GET', '/');
$html = (string) $client->getResponse()->getContent();
check(!str_contains($html, 'Sneaky'), 'no token, no change');
$order = array_map(static fn(string $asset): int|false => strpos($html, $asset), [
    '/assets/Layout.css',
    '/assets/Features/TodoManagement/AddTodo/AddTodo.css',
    '/assets/Features/TodoManagement/ListTodos/ListTodos.css',
]);
$inOrder = !in_array(false, $order, true) && $order[0] < $order[1] && $order[1] < $order[2];
check($inOrder, 'CSS loads layout, then features in render order');
$client->request('GET', '/assets/Features/TodoManagement/ListTodos/ListTodos.css');
$type = (string) $client->getResponse()->headers->get('Content-Type');
check(str_starts_with($type, 'text/css'), 'a feature serves its own CSS');
$client->request('GET', '/assets/Features/../../composer.json');
check($client->getResponse()->getStatusCode() === 404, 'paths outside the owners are refused');

// The command line, and Symfony's own check that every service resolves.
$console = new Application($kernel);
$console->setAutoExit(false);
$cli = new ApplicationTester($console);
$cli->run(['command' => 'todo:add', 'title' => 'From the CLI']);
check($cli->getStatusCode() === 0, 'todo:add adds');
$cli->run(['command' => 'todo:list']);
check(str_contains($cli->getDisplay(), 'From the CLI'), 'todo:list lists it');
$cli->run(['command' => 'todo:add', 'title' => '   ']);
check($cli->getStatusCode() === 1, 'the entity refuses an empty title');
$cli->run(['command' => 'todo:config']);
$shown = preg_match('/list_limit\s+25\s+file/', $cli->getDisplay());
check($shown === 1, 'todo:config shows each source');
$cli->run(['command' => 'lint:container']);
check($cli->getStatusCode() === 0, 'lint:container: every constructor gets what it asks for');

echo "\n$passes passed, $fails failed\n";
exit($fails === 0 ? 0 : 1);
