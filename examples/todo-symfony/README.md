# Example: to-do app (Symfony)

The [PHP to-do example](../todo-php) again, inside Symfony 7.4 with
Doctrine ORM and Twig. It answers the first question a Symfony developer
asks about FIDES: what happens to the container, the ORM, the templates
and `config/`?

The answer it shows: **the entities and features do not change.**
`src/Entities`, the feature use cases and their CSS and JS, and
`src/Shared/Clock.php` are byte for byte the PHP example's, and the tests
check that. Only integrations, delivery and the view templates change when
the framework does. See [docs/frameworks.md](../../docs/frameworks.md) for
the rules this example puts into code.

It has one web page and a command line. The PHP example also has a
dashboard page and a JSON API; they would map onto Symfony the same way.

## Run it

PHP 8.2 or newer with `pdo_sqlite`, and Composer.

```bash
composer install
php bin/console doctrine:schema:create     # var/todos.sqlite
php bin/console todo:add "Write the spec"
php bin/console todo:list
php bin/console todo:done 1
php bin/console todo:config                # each setting and where its value came from
php -S 127.0.0.1:8080 -t public public/index.php
php tests/run.php                          # architecture check + 19 tests
php arch-check.php                         # the architecture check on its own
php bin/console lint:container             # Symfony's check that every service resolves
```

Set `APP_SECRET` in the environment before running it anywhere real.
`APP_ENV=prod` caches the container; settings still apply on the next
request, because they are not in that cache.

## Map

```
AGENTS.md                      # the rules, one page
arch-check.php                 # imports, size, pages, edge exceptions ($edge)
arch-check-inheritance.php     # extends and traits, read from PHP tokens
arch-check-twig.php            # feature views are fragments; no entity in a view
arch-check-wiring.php          # every contract is bound in config/wiring.yaml
bin/console                    # the command-line door
public/index.php               # the web door
config/                        # Symfony's, and it ships with the code
  bundles.php
  packages/                    # framework, Twig (feature-folder namespace), Doctrine
  routes.yaml                  # routes are attributes on delivery classes
  services.yaml                # autowires features and delivery; excludes integrations
  wiring.yaml                  # the wiring: names integrations, binds every contract
overrides/
  TodoItem.yaml                # the operator's overrides; no update writes here
src/
  Shared/
    Clock.php                  # same as todo-php
    SettingsSources.php        # where each setting came from, for todo:config
  Entities/TodoItem/           # same as todo-php: entity, settings, store contract
  Features/TodoManagement/     # same use cases; views are Twig fragments
    AddTodo/                   #   AddTodo.php, AddTodoView.html.twig, AddTodo.css
    ListTodos/                 #   ListTodos.php, TodoRow, TodoSummary, view, CSS, JS
    CompleteTodo/  ReopenTodo/  DeleteTodo/
  Integrations/
    Clock/SystemClock.php
    Config/                    # ConfigLoader (built per request), YamlReader
    Database/
      TodoItemRecord.php       # the Doctrine-mapped row; no associations
      DoctrineTodoItemStore.php# fulfills TodoItemStore, copying record <-> entity
  Delivery/
    Kernel.php                 # extends Symfony's Kernel: the one listed edge exception
    Cli/                       # invokable commands: todo:add, todo:list, todo:done, todo:config
    Web/
      Layout.html.twig  Layout.css  Layout.js
      Renderer.php             # feature views, page, layout; assets in FIDES order
      Assets.php               # serves CSS and JS from the folders that own them
      Todo/
        Todo.php               # the page's controller: a plain class, CSRF checked
        TodoView.html.twig     # places the AddTodo form and the ListTodos list
tests/
  run.php                      # architecture check, core match, store, settings, web, CLI
```

## How FIDES fits Symfony here

- **Container.** Features and delivery are autowired by type.
  Integrations are registered only in `config/wiring.yaml`, which binds
  each contract to one, even where Symfony could bind it on its own.
  `arch-check-wiring.php` fails if a contract is missing.
- **No base classes.** The controller is a plain class with
  `#[AsController]`; commands are invokable (Symfony 7.3+). The kernel is
  the one class that must extend Symfony's, so `arch-check.php` lists it.
- **Doctrine.** The entity has no mapping. `TodoItemRecord` is the mapped
  row and `DoctrineTodoItemStore` copies between record and entity. The
  record is not `final` because Doctrine's lazy proxies extend it on PHP
  8.3 and older; the framework extends it, it extends nothing.
- **Twig.** `@Features` points at `src/Features`, so each view sits with
  its feature. Feature views are fragments. The page code renders them,
  then the page template and the layout, so `Renderer` can load each
  feature's CSS and JS in render order.
- **Settings.** `config/` is Symfony's. Operator overrides live in
  `overrides/`. The container builds the config loader for each request,
  so nothing the operator sets is compiled into Symfony's cache.
- **Resets.** `Renderer` implements Symfony's `ResetInterface`, so a server
  that keeps the app in memory between requests (FrankenPHP worker mode,
  RoadRunner) starts each request with no assets from the last.

## What it costs

- **Copying between record and entity.** Five lines in each direction.
  In exchange the entity knows nothing of Doctrine, and a record cannot
  load another entity behind a getter.
- **The wiring file lists every integration.** Symfony would find them on
  its own. The list is the one place that shows what fulfills what.
- **CSRF by hand.** The page keeps a token in the session and checks it,
  as the PHP example does, rather than adding `symfony/security-csrf`.
