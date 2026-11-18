<?php
/** The Twig part of arch-check.php. Twig escapes by default but can run
 *  logic and pull in other templates, so the build checks the view rules:
 *  - No view (*View.html.twig, Layout.html.twig) names an entity.
 *  - A feature view is a fragment: it extends and embeds nothing, and
 *    includes, imports or sources only templates in its own folder. A
 *    page template may extend the layout; both live in delivery. */

/** @return list<string> */
function twigViewProblems(string $rel, string $code): array
{
    $isView = str_ends_with($rel, 'View.html.twig') || basename($rel) === 'Layout.html.twig';
    if (!$isView) {
        return [];
    }
    $problems = [];
    if (preg_match('/App\\\\+Entities\\\\+/', $code)) {
        $problems[] = 'view template must not reference an entity';
    }
    if (!str_starts_with($rel, 'Features/')) {
        return $problems;
    }
    if (preg_match('/\{%-?\s*(extends|embed)\b/', $code, $m)) {
        $problems[] = "feature view must not use {$m[1]}: it is a fragment the page places";
    }
    $ownFolder = '@' . dirname($rel) . '/';
    $tag = '\{%-?\s*(?:include|import|from|use)';
    $function = '\b(?:include|source)\s*\(';
    $pattern = "/(?:$tag|$function)\\s*['\"]([^'\"]+)['\"]/";
    preg_match_all($pattern, $code, $refs);
    foreach ($refs[1] as $template) {
        if (!str_starts_with($template, $ownFolder)) {
            $problems[] = "feature view may only include its own folder's templates, not $template";
        }
    }
    return $problems;
}
