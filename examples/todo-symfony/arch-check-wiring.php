<?php
/** The wiring part of arch-check.php. Symfony's container could bind a
 *  contract on its own when exactly one class implements it, which hides
 *  the binding. FIDES binds each contract in one file, so the build checks
 *  that every interface in src/Entities and src/Shared is a service key in
 *  config/wiring.yaml. */

/** @return list<string> */
function wiringProblems(string $root, string $wiringFile): array
{
    $wiring = is_file($wiringFile) ? (string) file_get_contents($wiringFile) : '';
    $problems = [];
    foreach (['Entities', 'Shared'] as $zone) {
        if (!is_dir("$root/$zone")) {
            continue;
        }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$zone"));
        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $code = (string) file_get_contents($file->getPathname());
            $isContract = preg_match('/^interface\s+(\w+)/m', $code, $interface);
            $hasNamespace = preg_match('/^namespace\s+([^;]+);/m', $code, $namespace);
            if (!$isContract || !$hasNamespace) {
                continue;
            }
            $contract = $namespace[1] . '\\' . $interface[1];
            if (!preg_match('/^\s+' . preg_quote($contract, '/') . ':/m', $wiring)) {
                $problems[] = "$contract is not bound in config/wiring.yaml";
            }
        }
    }
    return $problems;
}
