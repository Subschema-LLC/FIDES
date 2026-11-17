<?php
namespace App\Delivery\Web\Todo;

use App\Delivery\Web\Renderer;
use App\Features\TodoManagement\AddTodo\AddTodo;
use App\Features\TodoManagement\CompleteTodo\CompleteTodo;
use App\Features\TodoManagement\DeleteTodo\DeleteTodo;
use App\Features\TodoManagement\ListTodos\ListTodos;
use App\Features\TodoManagement\ReopenTodo\ReopenTodo;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/** The to-dos page: places AddTodo's form and ListTodos's list on
 *  TodoView.html.twig, and handles the POST actions. A plain class:
 *  Symfony's AbstractController is not needed. Every POST carries a
 *  CSRF token, and its handler checks it before calling a feature. */
#[AsController]
final class Todo
{
    public function __construct(
        private ListTodos $listTodos,
        private AddTodo $addTodo,
        private CompleteTodo $completeTodo,
        private ReopenTodo $reopenTodo,
        private DeleteTodo $deleteTodo,
        private Renderer $view,
    ) {
    }

    #[Route('/', name: 'todo', methods: ['GET'])]
    public function show(Request $request): Response
    {
        $session = $request->getSession();
        $csrf = self::csrfToken($session);
        $items = $this->listTodos->items();
        $summary = $this->listTodos->summary($items);

        $addForm = $this->view->feature(
            'TodoManagement/AddTodo',
            $this->addTodo->formValues($csrf, '/', '/'),
        );
        $todoList = $this->view->feature('TodoManagement/ListTodos', [
            'rows' => $this->listTodos->rows($items),
            'csrf' => $csrf,
            'actionPath' => '/',
            'returnTo' => '/',
        ]);
        $page = $this->view->page('Todo', [
            'open' => $summary->open,
            'done' => $summary->done,
            'addForm' => $addForm,
            'todoList' => $todoList,
        ]);
        $flash = (string) $session->remove('flash');
        return new Response($this->view->layout('To-dos', $flash, $page));
    }

    #[Route('/', name: 'todo_action', methods: ['POST'])]
    public function act(Request $request): Response
    {
        $session = $request->getSession();
        $post = $request->request;
        if (!hash_equals(self::csrfToken($session), (string) $post->get('_csrf'))) {
            $session->set('flash', 'The form expired. Please try again.');
            return new RedirectResponse('/', Response::HTTP_SEE_OTHER);
        }
        $id = (int) $post->get('id');
        $missing = 'No such to-do.';
        try {
            $message = match ((string) $post->get('action')) {
                'add' => 'Added: ' . $this->addTodo->add((string) $post->get('title'))->title,
                'complete' => $this->completeTodo->complete($id) ? 'Marked done.' : $missing,
                'reopen' => $this->reopenTodo->reopen($id) ? 'Reopened.' : $missing,
                'delete' => $this->deleteTodo->delete($id) ? 'Deleted.' : $missing,
                default => 'Unknown action.',
            };
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
        }
        $session->set('flash', $message);
        return new RedirectResponse('/', Response::HTTP_SEE_OTHER);
    }

    private static function csrfToken(SessionInterface $session): string
    {
        if (!$session->has('csrf')) {
            $session->set('csrf', bin2hex(random_bytes(16)));
        }
        return (string) $session->get('csrf');
    }
}
