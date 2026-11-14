<?php
/** The only file that knows integrations exist. It builds the object
 *  graph (store, clock, settings, features) and hands delivery the
 *  finished features. Nothing else in the app names an integration. */

use App\Entities\TodoItem\TodoItemSettings;
use App\Features\AddTodo\AddTodo;
use App\Features\CompleteTodo\CompleteTodo;
use App\Features\DeleteTodo\DeleteTodo;
use App\Features\ListTodos\ListTodos;
use App\Features\ReopenTodo\ReopenTodo;
use App\Integrations\Clock\SystemClock;
use App\Integrations\Config\ConfigLoader;
use App\Integrations\Database\SqliteTodoItemStore;

function build_app(string $root, array $env): array
{
    $pdo = new PDO('sqlite:' . $root . '/var/todos.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $store = new SqliteTodoItemStore($pdo);
    $clock = new SystemClock();
    $config = new ConfigLoader($root . '/config', $env);
    $settings = $config->todoItemSettings();

    // The settings report is built here, where integrations may be
    // named, so the CLI door can print it without importing any.
    $defaults = TodoItemSettings::defaults();
    $values = $config->mergedValues('TodoItem', $defaults);
    $origin = $config->provenance('TodoItem', $defaults);
    $report = [];
    foreach ($values as $key => $value) {
        $report[$key] = [$value, $origin[$key]];
    }

    return [
        'list' => new ListTodos($store, $settings),
        'add' => new AddTodo($store, $clock),
        'complete' => new CompleteTodo($store, $clock),
        'reopen' => new ReopenTodo($store),
        'delete' => new DeleteTodo($store),
        'config' => $config,
        'settings' => $settings,
        'todo_settings_report' => $report,
    ];
}
