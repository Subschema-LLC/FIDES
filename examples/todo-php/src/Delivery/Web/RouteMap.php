<?php
namespace App\Delivery\Web;

/** Maps HTTP paths to their delivery targets (dashboard page, todo
 *  page, JSON API endpoint, or static asset). */
final class RouteMap
{
    public static function resolve(string $rawPath): string
    {
        $path = $rawPath === '/' ? '/' : rtrim($rawPath, '/');
        if (str_starts_with($path, '/assets/')) {
            return 'asset';
        }
        return match ($path) {
            '/', '/dashboard' => 'dashboard',
            '/todo', '/todos' => 'todo',
            '/api/todos' => 'api.todos',
            default => 'not_found',
        };
    }

    public static function redirectTarget(string $returnTo): string
    {
        return in_array($returnTo, ['/', '/dashboard', '/todo', '/todos'], true)
            ? $returnTo
            : '/todo';
    }
}
