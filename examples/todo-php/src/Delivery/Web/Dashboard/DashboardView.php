<?php
/** Page template for the landing page / dashboard.
 *  Places summary metrics and the ListTodos feature view.
 *  @var callable(string): string $e
 *  @var \App\Features\TodoManagement\ListTodos\TodoSummary $summary
 *  @var string $todoList
 */
?>
<section class="dashboard-header">
  <div>
    <h1>Dashboard</h1>
    <p class="dashboard-subtitle">Overview of your to-dos (showing up to <?= $summary->limit ?> newest items).</p>
  </div>
  <a class="dashboard-cta" href="/todo">Manage to-dos &rarr;</a>
</section>

<section class="dashboard-stats" aria-label="To-do summary">
  <button type="button" class="stat-card" data-stat-filter="open">
    <span class="stat-value"><?= $summary->open ?></span>
    <span class="stat-label">Open</span>
  </button>
  <button type="button" class="stat-card" data-stat-filter="done">
    <span class="stat-value"><?= $summary->done ?></span>
    <span class="stat-label">Completed</span>
  </button>
  <button type="button" class="stat-card" data-stat-filter="all">
    <span class="stat-value"><?= $summary->total ?></span>
    <span class="stat-label">Total</span>
  </button>
</section>

<section class="dashboard-recent">
  <h2>Current to-dos</h2>
<?= $todoList ?>
</section>
