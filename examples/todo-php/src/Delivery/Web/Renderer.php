<?php
namespace App\Delivery\Web;

/** Renders feature views, page templates and the outer layout.
 *  Remembers which features rendered on the page so each feature's
 *  CSS and scripts load automatically in first-render order:
 *  layout first, then each feature, then the page's own. */
final class Renderer
{
    /** @var list<string> */
    private array $usedFeatures = [];
    private string $page = '';

    public function __construct(
        private string $featuresDir,
        private string $webDir,
    ) {
    }

    /** @param array<string, mixed> $values */
    public function feature(string $feature, array $values = [], ?string $template = null): string
    {
        if (!in_array($feature, $this->usedFeatures, true)) {
            $this->usedFeatures[] = $feature;
        }
        $leaf = basename($feature);
        $viewName = $template ?? $leaf . 'View';
        return self::renderFile($this->featuresDir . '/' . $feature . '/' . $viewName . '.php', $values);
    }

    /** @param array<string, mixed> $values */
    public function page(string $page, array $values = []): string
    {
        $pageFolder = ucfirst($page);
        $this->page = $pageFolder;
        $viewName = $pageFolder . 'View';
        return self::renderFile($this->webDir . '/' . $pageFolder . '/' . $viewName . '.php', $values);
    }

    public function layout(string $title, string $flash, string $content, string $currentPath = '/'): string
    {
        return self::renderFile($this->webDir . '/Layout.php', [
            'title' => $title,
            'flash' => $flash,
            'content' => $content,
            'currentPath' => $currentPath,
            'styles' => $this->styles(),
            'scripts' => $this->scripts(),
        ]);
    }

    /** @return list<string> */
    public function styles(): array
    {
        return $this->collectAssets('css');
    }

    /** @return list<string> */
    public function scripts(): array
    {
        return $this->collectAssets('js');
    }

    /** @return array{path: string, type: string}|null */
    public function resolveAsset(string $requestPath): ?array
    {
        if (!preg_match('#^/assets/(?:(Layout)\.(css|js)|(?:Features|features)/([A-Z][A-Za-z0-9]*(?:/[A-Z][A-Za-z0-9]*)?)/([A-Z][A-Za-z0-9]*)\.(css|js)|(?:Pages|pages)/([A-Za-z0-9_]+)/([A-Z][A-Za-z0-9]*)\.(css|js))$#', $requestPath, $m)) {
            return null;
        }
        if (($m[1] ?? '') === 'Layout') {
            $ext = $m[2];
            $file = $this->webDir . '/Layout.' . $ext;
        } elseif (($m[3] ?? '') !== '') {
            $ext = $m[5];
            $file = $this->featuresDir . '/' . $m[3] . '/' . $m[4] . '.' . $ext;
        } else {
            $ext = $m[8];
            $file = $this->webDir . '/' . ucfirst($m[6]) . '/' . $m[7] . '.' . $ext;
        }
        if (!is_file($file)) {
            return null;
        }
        $type = $ext === 'css' ? 'text/css; charset=utf-8' : 'text/javascript; charset=utf-8';
        return ['path' => $file, 'type' => $type];
    }

    /** @return list<string> */
    private function collectAssets(string $ext): array
    {
        $urls = [];
        if (is_file($this->webDir . '/Layout.' . $ext)) {
            $urls[] = '/assets/Layout.' . $ext;
        }
        foreach ($this->usedFeatures as $feature) {
            $leaf = basename($feature);
            if (is_file($this->featuresDir . '/' . $feature . '/' . $leaf . '.' . $ext)) {
                $urls[] = '/assets/Features/' . $feature . '/' . $leaf . '.' . $ext;
            }
        }
        if ($this->page !== '') {
            if (is_file($this->webDir . '/' . $this->page . '/' . $this->page . '.' . $ext)) {
                $urls[] = '/assets/Pages/' . $this->page . '/' . $this->page . '.' . $ext;
            }
        }
        return $urls;
    }

    /** @param array<string, mixed> $values */
    private static function renderFile(string $templatePath, array $values): string
    {
        $e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        return (static function (string $__path, array $__values, callable $e): string {
            extract($__values, EXTR_SKIP);
            ob_start();
            require $__path;
            return (string) ob_get_clean();
        })($templatePath, $values, $e);
    }
}
