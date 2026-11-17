<?php
namespace App\Delivery\Web;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/** Serves CSS and JS from the folders that own them: the layout's from
 *  Delivery/Web, a feature's from its feature folder, a page's from its
 *  page folder. Only those three shapes of path are answered. */
#[AsController]
final class Assets
{
    private const PATTERN = '#^(?:(Layout)\.(css|js)'
        . '|Features/([A-Z][A-Za-z0-9]*(?:/[A-Z][A-Za-z0-9]*)?)/([A-Z][A-Za-z0-9]*)\.(css|js)'
        . '|Pages/([A-Z][A-Za-z0-9]*)/([A-Z][A-Za-z0-9]*)\.(css|js))$#';

    public function __construct(#[Autowire('%kernel.project_dir%/src')] private string $src)
    {
    }

    #[Route('/assets/{path}', name: 'asset', requirements: ['path' => '.+'], methods: ['GET'])]
    public function __invoke(string $path): Response
    {
        if (!preg_match(self::PATTERN, $path, $m)) {
            return new Response('Not found', 404);
        }
        [$file, $ext] = match (true) {
            ($m[1] ?? '') !== '' => ["Delivery/Web/Layout.$m[2]", $m[2]],
            ($m[3] ?? '') !== '' => ["Features/$m[3]/$m[4].$m[5]", $m[5]],
            default => ["Delivery/Web/$m[6]/$m[7].$m[8]", $m[8]],
        };
        if (!is_file("{$this->src}/$file")) {
            return new Response('Not found', 404);
        }
        $type = $ext === 'css' ? 'text/css; charset=utf-8' : 'text/javascript; charset=utf-8';
        return new BinaryFileResponse("{$this->src}/$file", 200, ['Content-Type' => $type]);
    }
}
