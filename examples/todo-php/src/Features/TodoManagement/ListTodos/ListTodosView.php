<?php
/** Feature view for ListTodos. Displays shaped TodoRow items only.
 *  @var callable(string): string $e
 *  @var list<\App\Features\TodoManagement\ListTodos\TodoRow> $rows
 *  @var string $csrf
 *  @var string $actionPath
 *  @var string $returnTo
 */
?>
<section class="todo-list-section" data-todo-list>
<?php if ($rows === []): ?>
  <p class="muted">Nothing here yet. Add your first to-do to get started.</p>
<?php else: ?>
  <div class="todo-filters" role="toolbar" aria-label="Filter to-dos">
    <button type="button" class="filter-btn active" data-todo-filter="all">All (<?= count($rows) ?>)</button>
    <button type="button" class="filter-btn" data-todo-filter="open">Open</button>
    <button type="button" class="filter-btn" data-todo-filter="done">Done</button>
  </div>
  <ul class="todos">
  <?php foreach ($rows as $row): ?>
    <li class="<?= $e($row->status) ?>" data-status="<?= $e($row->status) ?>">
      <span class="title"><?= $e($row->title) ?></span>
      <span class="todo-meta">
        <?php if ($row->done): ?>
          <span class="state">done <?= $e($row->completedDate) ?></span>
          <form method="post" action="<?= $e($actionPath) ?>">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="return_to" value="<?= $e($returnTo) ?>">
            <input type="hidden" name="action" value="reopen">
            <input type="hidden" name="id" value="<?= $row->id ?>">
            <button type="submit">Reopen</button>
          </form>
        <?php else: ?>
          <span class="state">added <?= $e($row->createdDate) ?></span>
          <form method="post" action="<?= $e($actionPath) ?>">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="return_to" value="<?= $e($returnTo) ?>">
            <input type="hidden" name="action" value="complete">
            <input type="hidden" name="id" value="<?= $row->id ?>">
            <button type="submit">Done</button>
          </form>
        <?php endif; ?>
        <form method="post" action="<?= $e($actionPath) ?>">
          <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
          <input type="hidden" name="return_to" value="<?= $e($returnTo) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $row->id ?>">
          <button type="submit" class="btn-danger">Delete</button>
        </form>
      </span>
    </li>
  <?php endforeach; ?>
  </ul>
<?php endif; ?>
</section>
