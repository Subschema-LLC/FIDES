<?php
namespace App\Delivery\Web;

/** The web door: one page. GET renders the add form and the list
 *  (the ListTodos feature's own view). POST runs one feature, then
 *  redirects back to the page. Every POST carries a CSRF token and
 *  its handler checks it. No rules live here; delivery only
 *  translates the request into feature calls. */
final class WebApp
{
    public function __construct(private array $app)
    {
    }

    public function handle(string $method, array $post): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $csrf = $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
        $flash = '';

        if ($method === 'POST') {
            if (!hash_equals($csrf, (string) ($post['_csrf'] ?? ''))) {
                return $this->page($csrf, 'That form did not come from this page. Nothing was changed.');
            }
            $action = (string) ($post['action'] ?? '');
            $id = (int) ($post['id'] ?? 0);
            try {
                $flash = match ($action) {
                    'add' => 'Added: ' . $this->app['add']->add((string) ($post['title'] ?? ''))->title,
                    'complete' => $this->app['complete']->complete($id) ? 'Marked done.' : 'No such to-do.',
                    'reopen' => $this->app['reopen']->reopen($id) ? 'Reopened.' : 'No such to-do.',
                    'delete' => $this->app['delete']->delete($id) ? 'Deleted.' : 'No such to-do.',
                    default => '',
                };
            } catch (\InvalidArgumentException $e) {
                $flash = $e->getMessage();
            }
            $_SESSION['flash'] = $flash;
            header('Location: /');
            return '';
        }

        $flash = (string) ($_SESSION['flash'] ?? '');
        unset($_SESSION['flash']);
        return $this->page($csrf, $flash);
    }

    private function page(string $csrf, string $flash): string
    {
        $e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $list = $this->app['list']->view($this->app['list']->items(), $csrf);
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>To-dos</title><style>'
            . '.todos li.done .title{text-decoration:line-through}.todos form{display:inline}'
            . 'body{font-family:system-ui,sans-serif;max-width:40rem;margin:2rem auto;padding:0 1rem}'
            . '.muted,.state{color:#666}</style></head><body>'
            . '<h1>To-dos</h1>'
            . ($flash !== '' ? '<p class="flash">' . $e($flash) . '</p>' : '')
            . '<form method="post"><input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<input type="hidden" name="action" value="add">'
            . '<label>New to-do <input name="title" required maxlength="200"></label> <button>Add</button></form>'
            . $list
            . '</body></html>';
    }
}
