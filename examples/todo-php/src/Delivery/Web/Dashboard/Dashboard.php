<?php
namespace App\Delivery\Web\Dashboard;

use App\Delivery\Web\Renderer;
use App\Features\TodoManagement\ListTodos\ListTodos;

/** The landing / dashboard page. Calls ListTodos for the summary
 *  and recent items, renders the ListTodos view, and places them on
 *  DashboardView.php. */
final class Dashboard
{
    public function __construct(private ListTodos $listTodos)
    {
    }

    public function show(Renderer $view, string $csrf): string
    {
        $items = $this->listTodos->items();
        $summary = $this->listTodos->summary($items);
        $rows = $this->listTodos->rows($items);

        $todoList = $view->feature('TodoManagement/ListTodos', [
            'rows' => $rows,
            'csrf' => $csrf,
            'actionPath' => '/todo',
            'returnTo' => '/',
        ]);

        return $view->page('Dashboard', [
            'summary' => $summary,
            'todoList' => $todoList,
        ]);
    }
}
