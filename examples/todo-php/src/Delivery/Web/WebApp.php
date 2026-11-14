<?php
namespace App\Delivery\Web;

use App\Delivery\Api\ReadTodos;
use App\Delivery\Web\Dashboard\Dashboard;
use App\Delivery\Web\Todo\Todo;

/** The web door: maps paths via RouteMap to page folders (Dashboard/
 *  and Todo/) or the JSON API (Api/ReadTodos), wraps pages in
 *  Layout.php via Renderer, and serves colocated CSS and JS assets.
 *  Every POST carries a CSRF token and its handler checks it. */
final class WebApp
{
    public function __construct(private array $app)
    {
    }

    /** @param array<string, mixed> $post */
    public function handle(string $method, array $post, ?string $uri = null): string
    {
        $rawUri = $uri ?? (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($rawUri, PHP_URL_PATH) ?: '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $route = RouteMap::resolve($path);
        $view = new Renderer(dirname(__DIR__, 2) . '/Features', __DIR__);

        if ($method === 'GET' && $route === 'asset') {
            $asset = $view->resolveAsset($path);
            if ($asset === null) {
                http_response_code(404);
                return 'Not Found';
            }
            if (!headers_sent()) {
                header('Content-Type: ' . $asset['type']);
            }
            return (string) file_get_contents($asset['path']);
        }

        if ($method === 'GET' && $route === 'api.todos') {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            return (new ReadTodos($this->app['list']))->handle();
        }

        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        $csrf = (string) ($_SESSION['csrf'] ??= bin2hex(random_bytes(16)));

        $dashboard = new Dashboard($this->app['list']);
        $todo = new Todo(
            $this->app['list'],
            $this->app['add'],
            $this->app['complete'],
            $this->app['reopen'],
            $this->app['delete'],
        );

        if ($method === 'POST') {
            if (!hash_equals($csrf, (string) ($post['_csrf'] ?? ''))) {
                $msg = 'That form did not come from this page. Nothing was changed.';
                return $this->renderPage($route, $path, $view, $dashboard, $todo, $csrf, $msg);
            }
            $_SESSION['flash'] = $todo->handlePost($post);
            $returnTo = RouteMap::redirectTarget((string) ($post['return_to'] ?? '/todo'));
            if (!headers_sent()) {
                header('Location: ' . $returnTo);
            }
            return '';
        }

        $flash = (string) ($_SESSION['flash'] ?? '');
        unset($_SESSION['flash']);
        return $this->renderPage($route, $path, $view, $dashboard, $todo, $csrf, $flash);
    }

    private function renderPage(
        string $route,
        string $path,
        Renderer $view,
        Dashboard $dashboard,
        Todo $todo,
        string $csrf,
        string $flash,
    ): string {
        return match ($route) {
            'dashboard' => $view->layout('Dashboard — To-dos', $flash, $dashboard->show($view, $csrf), $path),
            'todo' => $view->layout('To-dos', $flash, $todo->show($view, $csrf), $path),
            default => $this->notFound($view, $flash, $path),
        };
    }

    private function notFound(Renderer $view, string $flash, string $path): string
    {
        http_response_code(404);
        return $view->layout('Not Found', $flash, '<p>Page not found.</p>', $path);
    }
}
