<?php
/** The outer frame every page sits in. Loads stylesheets and scripts
 *  in FIDES order: layout first, then rendered features, then page.
 *  @var callable(string): string $e
 *  @var string $title
 *  @var string $flash
 *  @var string $content
 *  @var string $currentPath
 *  @var list<string> $styles
 *  @var list<string> $scripts
 */
$isDashboard = $currentPath === '/' || $currentPath === '/dashboard';
$isTodo = $currentPath === '/todo' || $currentPath === '/todos';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $e($title) ?></title>
<?php foreach ($styles as $href): ?>
  <link rel="stylesheet" href="<?= $e($href) ?>">
<?php endforeach; ?>
</head>
<body>
  <header class="site-header">
    <a class="site-brand" href="/">FIDES To-dos</a>
    <nav class="site-nav" aria-label="Main navigation">
      <a href="/"<?= $isDashboard ? ' aria-current="page"' : '' ?>>Dashboard</a>
      <a href="/todo"<?= $isTodo ? ' aria-current="page"' : '' ?>>To-dos</a>
    </nav>
  </header>
<?php if ($flash !== ''): ?>
  <p class="flash" data-flash role="status"><?= $e($flash) ?></p>
<?php endif; ?>
  <main class="site-main">
<?= $content ?>
  </main>
<?php foreach ($scripts as $src): ?>
  <script src="<?= $e($src) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
