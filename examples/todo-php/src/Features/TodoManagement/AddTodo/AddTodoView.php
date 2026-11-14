<?php
/** Feature view for AddTodo: the form belongs to the feature that handles it.
 *  @var callable(string): string $e
 *  @var string $csrf
 *  @var string $actionPath
 *  @var string $returnTo
 *  @var int $maxLength
 */
?>
<section class="add-todo-section">
  <form method="post" action="<?= $e($actionPath) ?>" class="add-todo-form" data-add-todo-form>
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <input type="hidden" name="return_to" value="<?= $e($returnTo) ?>">
    <input type="hidden" name="action" value="add">
    <label class="add-todo-label">
      <span>New to-do</span>
      <input
        type="text"
        name="title"
        required
        maxlength="<?= $maxLength ?>"
        placeholder="What needs to be done?"
        data-todo-title-input
      >
    </label>
    <button type="submit">Add</button>
  </form>
</section>
