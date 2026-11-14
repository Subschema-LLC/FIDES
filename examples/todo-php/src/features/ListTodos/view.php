<?php
/** The list's HTML. $items are TodoItem, $csrf the form token.
 *  Every printed value is escaped. */
$e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<?php if ($items === []): ?>
<p class="muted">Nothing here yet. Add the first to-do above.</p>
<?php else: ?>
<ul class="todos">
<?php foreach ($items as $item): ?>
  <li class="<?= $item->done ? 'done' : 'open' ?>">
    <span class="title"><?= $e($item->title) ?></span>
    <?php if ($item->done): ?>
    <span class="state">done <?= $e($item->completedAt?->format('Y-m-d') ?? '') ?></span>
    <form method="post"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
      <input type="hidden" name="action" value="reopen"><input type="hidden" name="id" value="<?= (int) $item->id ?>">
      <button>Reopen</button></form>
    <?php else: ?>
    <form method="post"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
      <input type="hidden" name="action" value="complete"><input type="hidden" name="id" value="<?= (int) $item->id ?>">
      <button>Done</button></form>
    <?php endif; ?>
    <form method="post"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
      <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $item->id ?>">
      <button>Delete</button></form>
  </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
