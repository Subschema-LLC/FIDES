<?php
namespace App\Delivery\Web;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Environment;

/** Renders feature views, page templates and the layout with Twig, and
 *  remembers which features rendered so their CSS and JS load in FIDES
 *  order: layout first, then each feature in first-render order, then
 *  the page's own. Symfony resets it between requests (ResetInterface),
 *  so a server that keeps the app in memory never carries one page's
 *  assets into the next. */
final class Renderer implements ResetInterface
{
    /** @var list<string> */
    private array $features = [];
    private string $page = '';

    public function __construct(
        private Environment $twig,
        #[Autowire('%kernel.project_dir%/src')] private string $src,
    ) {
    }

    /** @param array<string, mixed> $values */
    public function feature(string $feature, array $values): string
    {
        if (!in_array($feature, $this->features, true)) {
            $this->features[] = $feature;
        }
        $leaf = basename($feature);
        return $this->twig->render("@Features/$feature/{$leaf}View.html.twig", $values);
    }

    /** @param array<string, mixed> $values */
    public function page(string $page, array $values): string
    {
        $this->page = $page;
        return $this->twig->render("$page/{$page}View.html.twig", $values);
    }

    public function layout(string $title, string $flash, string $content): string
    {
        return $this->twig->render('Layout.html.twig', [
            'title' => $title,
            'flash' => $flash,
            'content' => $content,
            'styles' => $this->assets('css'),
            'scripts' => $this->assets('js'),
        ]);
    }

    public function reset(): void
    {
        $this->features = [];
        $this->page = '';
    }

    /** @return list<string> */
    private function assets(string $ext): array
    {
        $candidates = ["Layout.$ext" => "Delivery/Web/Layout.$ext"];
        foreach ($this->features as $feature) {
            $file = "Features/$feature/" . basename($feature) . ".$ext";
            $candidates[$file] = $file;
        }
        if ($this->page !== '') {
            $candidates["Pages/{$this->page}/{$this->page}.$ext"]
                = "Delivery/Web/{$this->page}/{$this->page}.$ext";
        }
        $urls = [];
        foreach ($candidates as $url => $file) {
            if (is_file("{$this->src}/$file")) {
                $urls[] = "/assets/$url";
            }
        }
        return $urls;
    }
}
