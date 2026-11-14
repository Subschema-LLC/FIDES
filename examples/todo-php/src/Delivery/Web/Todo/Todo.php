<?php
namespace App\Delivery\Web\Todo;

use App\Delivery\Web\Renderer;
use App\Features\TodoManagement\AddTodo\AddTodo;
use App\Features\TodoManagement\CompleteTodo\CompleteTodo;
use App\Features\TodoManagement\DeleteTodo\DeleteTodo;
use App\Features\TodoManagement\ListTodos\ListTodos;
use App\Features\TodoManagement\ReopenTodo\ReopenTodo;

/** The To-dos page: places AddTodo's form and ListTodos's list on
 *  TodoView.php, and handles POST actions for adding, completing,
 *  reopening and deleting to-dos. */
final class Todo
{
    public function __construct(
        private ListTodos $listTodos,
        private AddTodo $addTodo,
        private CompleteTodo $completeTodo,
        private ReopenTodo $reopenTodo,
        private DeleteTodo $deleteTodo,
    ) {
    }

    public function show(Renderer $view, string $csrf): string
    {
        $items = $this->listTodos->items();
        $summary = $this->listTodos->summary($items);
        $rows = $this->listTodos->rows($items);

        $addForm = $view->feature('TodoManagement/AddTodo', $this->addTodo->formValues($csrf, '/todo', '/todo'));
        $todoList = $view->feature('TodoManagement/ListTodos', [
            'rows' => $rows,
            'csrf' => $csrf,
            'actionPath' => '/todo',
            'returnTo' => '/todo',
        ]);

        return $view->page('Todo', [
            'summary' => $summary,
            'addForm' => $addForm,
            'todoList' => $todoList,
        ]);
    }

    /** @param array<string, mixed> $post */
    public function handlePost(array $post): string
    {
        $action = (string) ($post['action'] ?? '');
        $id = (int) ($post['id'] ?? 0);
        try {
            return match ($action) {
                'add' => 'Added: ' . $this->addTodo->add((string) ($post['title'] ?? ''))->title,
                'complete' => $this->completeTodo->complete($id) ? 'Marked done.' : 'No such to-do.',
                'reopen' => $this->reopenTodo->reopen($id) ? 'Reopened.' : 'No such to-do.',
                'delete' => $this->deleteTodo->delete($id) ? 'Deleted.' : 'No such to-do.',
                default => '',
            };
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }
    }
}
