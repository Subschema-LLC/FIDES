<?php
/** Page template for the To-dos page.
 *  Places AddTodo's form view and ListTodos's list view.
 *  @var callable(string): string $e
 *  @var \App\Features\TodoManagement\ListTodos\TodoSummary $summary
 *  @var string $addForm
 *  @var string $todoList
 */
?>
<section class="todo-page-header">
  <div>
    <h1>To-dos</h1>
    <p class="todo-page-subtitle"><?= $summary->open ?> open, <?= $summary->done ?> done <span class="todo-shortcut-hint" data-todo-shortcut-hint>(press <kbd>/</kbd> to focus input)</span></p>
  </div>
</section>

<?= $addForm ?>
<?= $todoList ?>
