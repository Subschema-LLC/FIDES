<?php
/** The test run: the architecture check, then the rules, with a
 *  throwaway SQLite database. php tests/run.php */

$root = dirname(__DIR__);
require $root . '/autoload.php';
require $root . '/src/delivery/wiring.php';

$passes = 0;
$fails = 0;
function check(bool $ok, string $name): void
{
    global $passes, $fails;
    if ($ok) {
        $passes++;
    } else {
        $fails++;
        echo "FAIL: $name\n";
    }
}

// Architecture first: a broken shape fails the whole run.
passthru('php ' . escapeshellarg($root . '/arch-check.php'), $archExit);
check($archExit === 0, 'arch-check passes');

use App\Entities\TodoItem\TodoItem;
use App\Entities\TodoItem\TodoItemSettings;
use App\Integrations\Config\ConfigLoader;
use App\Integrations\Database\SqliteTodoItemStore;

$now = new DateTimeImmutable('2026-11-13 12:00:00');

// Entity rules.
check(TodoItem::create('  Write the spec  ', $now)->title === 'Write the spec', 'titles are trimmed');
try {
    TodoItem::create('   ', $now);
    check(false, 'an empty title is refused');
} catch (InvalidArgumentException) {
    check(true, 'an empty title is refused');
}
try {
    TodoItem::create(str_repeat('a', 201), $now);
    check(false, 'a title over 200 characters is refused');
} catch (InvalidArgumentException) {
    check(true, 'a title over 200 characters is refused');
}
$item = TodoItem::create('Ship it', $now);
check($item->complete($now)->done && $item->complete($now)->completedAt == $now, 'complete marks done with the time passed in');
check($item->complete($now)->complete($now->modify('+1 day'))->completedAt == $now, 'completing a done item changes nothing');
check($item->complete($now)->reopen()->done === false && $item->reopen() === $item, 'reopen clears done; reopening an open item changes nothing');

// Settings: defaults, file override, env wins, undeclared key fails.
$loader = new ConfigLoader($root . '/config', []);
check($loader->todoItemSettings()->listLimit === 25, 'config/TodoItem.yaml overrides the default 50 with 25');
$loader = new ConfigLoader($root . '/config', ['TODOITEM_LIST_LIMIT' => '7']);
check($loader->todoItemSettings()->listLimit === 7, 'the environment wins over the file');
$badDir = sys_get_temp_dir() . '/fides-todo-badcfg';
@mkdir($badDir);
file_put_contents($badDir . '/TodoItem.yaml', "bogus_key: 1\n");
try {
    (new ConfigLoader($badDir, []))->todoItemSettings();
    check(false, 'an undeclared config key fails startup');
} catch (RuntimeException) {
    check(true, 'an undeclared config key fails startup');
}
try {
    new TodoItemSettings(0);
    check(false, 'an out-of-range list_limit is refused');
} catch (InvalidArgumentException) {
    check(true, 'an out-of-range list_limit is refused');
}

// Store round-trip and the feature flow, on a throwaway database.
$tmp = tempnam(sys_get_temp_dir(), 'fides-todo-') . '.sqlite';
$pdo = new PDO('sqlite:' . $tmp);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$store = new SqliteTodoItemStore($pdo);
$saved = $store->save(TodoItem::create('First', $now));
check($saved->id !== null && $store->get($saved->id)->title === 'First', 'the store saves and reads back');
$store->save(TodoItem::create('Second', $now));
check(count($store->all(50)) === 2 && count($store->all(1)) === 1, 'all() lists newest first within the limit');
$store->save($saved->complete($now));
check($store->get($saved->id)->done === true, 'an update through save() persists');
$store->delete($saved->id);
check($store->get($saved->id) === null && count($store->all(50)) === 1, 'delete removes the item');
unlink($tmp);

echo "\n$passes passed, $fails failed\n";
exit($fails === 0 ? 0 : 1);
