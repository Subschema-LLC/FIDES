# AGENTS.md: rules for AI agents and people working in this app

This app follows FIDES inside Symfony 7.4. Run `php arch-check.php` and
`php tests/run.php` before you finish.

## Where code goes

- A rule always true for a concept: its folder in `src/Entities/`
- Something a user or system does: a new folder in `src/Features/TodoManagement/`
- A new way the world reaches the app: `src/Delivery/` (a controller in
  `src/Delivery/Web/<Page>/`, a command in `src/Delivery/Cli/`)
- A new way the app reaches the world: `src/Integrations/`, behind a
  contract, registered and bound in `config/wiring.yaml`
- A contract no single entity owns: `src/Shared/`
- An operator's setting override: `overrides/<Entity>.yaml`. Symfony owns
  `config/`; `overrides/` belongs to whoever runs the app.

## The rules

1. **No inheritance.** No `extends` and no traits in `src/`. The one edge
   exception is `src/Delivery/Kernel.php`, which Symfony requires to extend
   its Kernel and use `MicroKernelTrait`; `arch-check.php` lists it with the
   reason. Controllers are plain classes (no `AbstractController`) and
   commands are invokable (no `Command` base class).
2. **The core is the same as the PHP example's.** `src/Entities`,
   `src/Features/**/*.php` and `src/Shared/Clock.php` match
   `examples/todo-php` byte for byte, and `tests/run.php` checks it. They
   import nothing from Symfony or Doctrine.
3. **Wiring is one file.** `config/wiring.yaml` registers every integration
   and binds every contract to one, even where the container could guess.
   No PHP file names an integration. `arch-check.php` fails on an unbound
   contract.
4. **Doctrine stays in integrations.** `TodoItemRecord` is the mapped row;
   `DoctrineTodoItemStore` copies between it and the `TodoItem` entity. No
   associations between entities: another entity is an id.
5. **Views only display.** Features shape plain values (`TodoRow`,
   `TodoSummary`). A feature view (`*View.html.twig` in its feature folder)
   is a fragment: it extends and embeds nothing and includes only its own
   folder's templates. Twig escapes by default; `|raw` is only for HTML the
   app rendered itself.
6. **Pages compose features.** `src/Delivery/Web/<Page>/` holds `<Page>.php`
   (the controller) and `<Page>View.html.twig`. `Renderer` loads CSS and JS
   in FIDES order: layout, then each rendered feature, then the page.
7. **Settings are read when the app is built,** never compiled into
   Symfony's cache: the container calls `ConfigLoader::fromEnvironment()`
   for each request (or command). Defaults live in the entity's Settings
   class, overrides in `overrides/`, and the environment wins. Secrets
   (`APP_SECRET`, a database password in `DATABASE_URL`) come from the
   environment only.
8. Every POST form carries the CSRF token and its handler checks it.
