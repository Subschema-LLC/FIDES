<?php
/** The architecture check: fails (exit 1) when a file breaks the
 *  import rules or uses inheritance. Run it before finishing:
 *  php arch-check.php
 *
 *  Rules:
 *  - Nothing in src/ uses `extends`.
 *  - entities/<Name>/ imports only its own folder.
 *  - features/ import entities and shared only.
 *  - shared/ imports nothing from the app.
 *  - integrations/ import entities and shared only, never each other.
 *  - delivery/ imports features and shared; only wiring.php may
 *    also import integrations and the entities' Settings classes
 *    it builds. */

$root = __DIR__ . '/src';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$violations = [];

foreach ($files as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $rel = substr($path, strlen($root) + 1);
    $code = (string) file_get_contents($path);

    if (preg_match('/\bextends\s+[A-Za-z]/', $code)) {
        $violations[] = "$rel: uses extends (no inheritance)";
    }

    $zone = explode('/', $rel)[0];
    $ownEntity = $zone === 'entities' ? explode('/', $rel)[1] : null;
    $isWiring = $rel === 'delivery/wiring.php';

    foreach (preg_match_all('/^use\s+(App\\\\[A-Za-z0-9_\\\\]+);/m', $code, $m) ? $m[1] : [] as $import) {
        $parts = explode('\\', $import); // App, Zone, ...
        $importZone = strtolower($parts[1] ?? '');
        $allowed = match ($zone) {
            'shared' => false,
            'entities' => $importZone === 'entities' && ($parts[2] ?? null) === $ownEntity,
            'features' => in_array($importZone, ['entities', 'shared'], true),
            'integrations' => in_array($importZone, ['entities', 'shared'], true),
            'delivery' => in_array($importZone, ['features', 'shared'], true)
                || ($isWiring && in_array($importZone, ['integrations', 'entities'], true)),
            default => false,
        };
        if (!$allowed) {
            $violations[] = "$rel: may not import $import";
        }
    }

    // Fully-qualified references count as imports too. Ignore the
    // file's own namespace declaration; flag a reference only when
    // it crosses into another zone, or into another entity.
    $body = preg_replace('/^namespace\s+[^;]+;/m', '', $code);
    if (preg_match_all('/\\\\?App\\\\(Entities|Features|Shared|Integrations|Delivery)\\\\(?:([A-Za-z0-9_]+)\\\\?)?/', $body, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $hit) {
            $refZone = strtolower($hit[1]);
            $refName = $hit[2] ?? '';
            $crossZone = $refZone !== $zone;
            $crossEntity = $zone === 'entities' && $refZone === 'entities' && $refName !== '' && $refName !== $ownEntity;
            $hasUse = str_contains($code, 'use App\\' . $hit[1] . '\\' . $refName);
            if (($crossZone || $crossEntity) && !$hasUse) {
                $violations[] = "$rel: references {$hit[0]} without a use import";
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
echo "arch-check OK: no bad imports, no extends.\n";
