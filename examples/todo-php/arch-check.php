<?php
/** The architecture check: fails (exit 1) when a file breaks the
 *  import rules, uses inheritance, exceeds depth/size limits, or
 *  violates page and view boundaries. Run it before finishing:
 *  php arch-check.php
 *
 *  Rules:
 *  - Nothing in src/ uses `extends`, except a custom exception that
 *    extends one built-in exception (PHP only throws Throwables), and
 *    nothing uses a trait (arch-check-inheritance.php).
 *  - Entities/<Name>/ imports only its own folder.
 *  - Features/<Group>/<Name>/ (or Features/<Name>/) imports Entities,
 *    Shared, and its own feature folder only.
 *  - Shared/ imports nothing from the app.
 *  - Integrations/<Name>/ imports Entities, Shared, and its own folder.
 *  - Delivery/ imports Features, Shared, and Delivery; only Wiring.php
 *    may also import Integrations and the entities' Settings classes.
 *  - Every directory and file in src/ (.php, .css, .js) uses PascalCase,
 *    and every PHP namespace matches its folder path 1:1.
 *  - Views (*View.php) never reference entities; feature views never
 *    render another feature's view or a page template.
 *  - Each page folder in Delivery/Web/<Page>/ has <Page>.php and
 *    <Page>View.php, and all files in the folder are named for the page.
 *
 *  Pass a folder to check another tree: php arch-check.php <folder> */

require __DIR__ . '/arch-check-inheritance.php';

$root = $argv[1] ?? __DIR__ . '/src';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$violations = [];

foreach ($files as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $ext = $file->getExtension();
    if (!in_array($ext, ['php', 'css', 'js'], true)) {
        continue;
    }
    $path = $file->getPathname();
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    $partsPath = explode('/', $rel);
    $zone = strtolower($partsPath[0]);
    $maxDepth = in_array($zone, ['delivery', 'features'], true) ? 4 : 3;
    if (count($partsPath) > $maxDepth) {
        $violations[] = "$rel: deeper than allowed folder depth ($maxDepth)";
    }
    foreach (array_slice($partsPath, 0, -1) as $dirSegment) {
        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $dirSegment)) {
            $violations[] = "$rel: directory '$dirSegment' must be PascalCase";
        }
    }
    if (!preg_match('/^[A-Z][A-Za-z0-9]*\.(php|css|js)$/', $file->getFilename())) {
        $violations[] = "$rel: file name must be PascalCase";
    }

    $code = (string) file_get_contents($path);
    $lineCount = substr_count($code, "\n") + 1;
    if ($lineCount > 200) {
        $violations[] = "$rel: $lineCount lines (max 200)";
    }

    if ($ext !== 'php') {
        continue;
    }

    foreach (inheritanceProblems($code) as $problem) {
        $violations[] = "$rel: $problem";
    }

    $ownDir = implode('\\', array_slice($partsPath, 0, -1));
    $ownFolder = $partsPath[1] ?? null;
    $isWiring = $rel === 'Delivery/Wiring.php';
    $isView = str_ends_with($rel, 'View.php') || basename($rel) === 'Layout.php';

    if (preg_match('/^namespace\s+([^;]+);/m', $code, $nsMatch)) {
        $expectedNs = 'App\\' . $ownDir;
        if ($nsMatch[1] !== $expectedNs) {
            $violations[] = "$rel: namespace {$nsMatch[1]} must match folder path ($expectedNs)";
        }
    }

    if ($isView && preg_match('/App\\\\Entities\\\\/', $code)) {
        $violations[] = "$rel: view template must not reference an entity";
    }
    if ($zone === 'features' && $isView && preg_match('/->\s*(feature|page)\s*\(/', $code)) {
        $violations[] = "$rel: feature view must not render another feature view or page template";
    }

    foreach (preg_match_all('/^use\s+(App\\\\[A-Za-z0-9_\\\\]+);/m', $code, $m) ? $m[1] : [] as $import) {
        $parts = explode('\\', $import); // App, Zone, ...
        $importZone = strtolower($parts[1] ?? '');
        $importDir = implode('\\', array_slice($parts, 1, -1));
        $allowed = match ($zone) {
            'shared' => false,
            'entities' => $importZone === 'entities' && $importDir === $ownDir,
            'features' => in_array($importZone, ['entities', 'shared'], true)
                || ($importZone === 'features' && $importDir === $ownDir),
            'integrations' => in_array($importZone, ['entities', 'shared'], true)
                || ($importZone === 'integrations' && $importDir === $ownDir),
            'delivery' => in_array($importZone, ['features', 'shared', 'delivery'], true)
                || ($isWiring && in_array($importZone, ['integrations', 'entities'], true)),
            default => false,
        };
        if (!$allowed) {
            $violations[] = "$rel: may not import $import";
        }
    }

    // Fully-qualified references count as imports too. Ignore the
    // file's own namespace declaration and @var docblocks; flag a
    // reference only when it crosses into another zone or entity.
    $body = (string) preg_replace('#/\*\*.*?\*/#s', '', (string) preg_replace('/^namespace\s+[^;]+;/m', '', $code));
    if (preg_match_all('/\\\\?App\\\\(Entities|Features|Shared|Integrations|Delivery)\\\\(?:([A-Za-z0-9_]+)\\\\?)?/', $body, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $hit) {
            $refZone = strtolower($hit[1]);
            $refName = $hit[2] ?? '';
            $crossZone = $refZone !== $zone;
            $crossEntity = $zone === 'entities' && $refZone === 'entities' && $refName !== '' && $refName !== $ownFolder;
            $hasUse = str_contains($code, 'use App\\' . $hit[1] . '\\' . $refName);
            if (($crossZone || $crossEntity) && !$hasUse) {
                $violations[] = "$rel: references {$hit[0]} without a use import";
            }
        }
    }
}

$webRoot = $root . '/Delivery/Web';
if (is_dir($webRoot)) {
    foreach (scandir($webRoot) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $pageDir = $webRoot . '/' . $entry;
        if (!is_dir($pageDir)) {
            continue;
        }
        $pageFiles = array_values(array_filter(
            scandir($pageDir) ?: [],
            static fn(string $f): bool => $f !== '.' && $f !== '..' && is_file($pageDir . '/' . $f)
        ));
        $pagePrefix = ucfirst($entry);
        $expectedClass = $pagePrefix . '.php';
        $expectedView = $pagePrefix . 'View.php';
        if (!in_array($expectedClass, $pageFiles, true)) {
            $violations[] = "Delivery/Web/$entry: page folder needs $expectedClass";
        }
        if (!in_array($expectedView, $pageFiles, true)) {
            $violations[] = "Delivery/Web/$entry: page folder needs $expectedView";
        }
        foreach ($pageFiles as $name) {
            $stem = (string) explode('.', $name)[0];
            if ($stem !== $pagePrefix && $stem !== $pagePrefix . 'View') {
                $violations[] = "Delivery/Web/$entry/$name: page files must be named for the page ($pagePrefix)";
            }
        }
    }
}

if ($violations !== []) {
    echo "arch-check FAILED:\n";
    foreach ($violations as $v) {
        echo "  - $v\n";
    }
    exit(1);
}
echo "arch-check OK: imports, no inheritance, views, pages, and spread.\n";
