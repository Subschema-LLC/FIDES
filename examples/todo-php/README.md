# Example: to-do app (PHP)

A very small FIDES app: a to-do list you can add to, complete,
reopen and delete, from the command line or a two-page web app
(a landing page / dashboard at `/` and a to-dos page at `/todo`).
It uses only PHP itself (8.1 or newer, with SQLite), so there is
nothing to install and no framework to learn first.

It exists to show the FIDES shape at the smallest size that still
exercises every rule: one entity, five features, two doors, two
web pages that compose feature views with colocated CSS and JS, one
store contract, declared settings with overrides, and an
architecture check that fails the build when the shape breaks.
The course-enrollment example shows the same rules at larger
scale, in Python.

## Run it

```bash
php app.php add "Write the spec"     # add a to-do
php app.php list                     # the list, newest first
php app.php done 1                   # mark #1 done
php app.php reopen 1                 # put it back
php app.php delete 1                 # remove it
php app.php config TodoItem          # each setting and where its value came from
php app.php serve                    # web app on http://127.0.0.1:8080 (/ and /todo)
php tests/run.php                    # architecture check + 23 tests
php arch-check.php                   # the architecture check on its own
```

Delete `var/todos.sqlite` to start over with an empty list.

## Map

**Naming convention:** Every directory and file in `src/` and `config/` —
including zone folders, feature groups, page folders, PHP classes, view
templates, stylesheets (`.css`), and scripts (`.js`) — uses `PascalCase`
matching PSR-4 namespaces 1:1 on disk (`Entities/TodoItem/TodoItem.php`,
`Features/TodoManagement/ListTodos/ListTodos.php`, `ListTodosView.php`,
`ListTodos.css`, `ListTodos.js`, `Delivery/Web/Dashboard/Dashboard.php`,
`DashboardView.php`, `Dashboard.css`, `Dashboard.js`, `Delivery/Wiring.php`).

```
AGENTS.md                                  # the rules, one page, for AI agents and humans
arch-check.php                             # fails the build on rule violations
arch-check-inheritance.php                 # its extends and trait checks, read from PHP tokens
app.php                                    # the command-line door
autoload.php                               # PSR-4 autoloader for App\* -> src/*
public/
  index.php                                # the web door
config/
  TodoItem.yaml                            # overrides only; defaults live in the entity
src/
  Shared/
    Clock.php                              # ownerless time contract
  Entities/
    TodoItem/
      TodoItem.php                         # entity and its title/state rules
      TodoItemSettings.php                 # declared settings, defaults and validation
      TodoItemStore.php                    # persistence contract
  Features/
    TodoManagement/
      AddTodo/
        AddTodo.php                        # use case: add a to-do
        AddTodoView.php                    # feature view: the add-to-do form
        AddTodo.css                        # styles for the add-to-do form
      CompleteTodo/
        CompleteTodo.php                   # use case: mark a to-do done
      DeleteTodo/
        DeleteTodo.php                     # use case: delete a to-do
      ListTodos/
        ListTodos.php                      # use case: list to-dos and shape view data
        TodoRow.php                        # plain view record for a single to-do
        TodoSummary.php                    # plain view record for summary counts
        ListTodosView.php                  # feature view: filterable list of to-dos
        ListTodos.css                      # styles for the to-do list
        ListTodos.js                       # client-side status filter (All / Open / Done)
      ReopenTodo/
        ReopenTodo.php                     # use case: reopen a completed to-do
  Integrations/
    Clock/
      SystemClock.php                      # fulfills Clock
    Config/
      ConfigLoader.php                     # merges defaults, YAML and environment
      YamlReader.php                       # reads config/*.yaml
    Database/
      SqliteTodoItemStore.php              # fulfills TodoItemStore on SQLite
  Delivery/
    Wiring.php                             # the only file that names integrations
    Api/
      ReadTodos.php                        # JSON API endpoint (GET /api/todos)
    Cli/
      CliApp.php                           # CLI commands
    Web/
      WebApp.php                           # HTTP entrypoint, CSRF check, asset serving
      RouteMap.php                         # maps HTTP paths to page/API targets
      Renderer.php                         # renders views and collects CSS/JS in render order
      Layout.php                           # outer HTML frame and navigation (/ and /todo)
      Layout.css                           # frame styles
      Layout.js                            # flash message dismissal
      Dashboard/
        Dashboard.php                      # landing page code (GET /)
        DashboardView.php                  # landing page template (summary + ListTodos view)
        Dashboard.css                      # styles for dashboard-owned markup
        Dashboard.js                       # links stat cards to list filters
      Todo/
        Todo.php                           # to-dos page code (GET/POST /todo)
        TodoView.php                       # to-dos page template (AddTodo + ListTodos views)
        Todo.css                           # styles for todo-page-owned markup
        Todo.js                            # keyboard shortcut (/) to focus the new to-do input
tests/
  run.php                                  # architecture check + unit, config, store & web tests
  arch-check-cases.php                     # runs the check on a throwaway tree of rule breaks
var/                                       # todos.sqlite lives here (not committed)
```

## How the rules show up

- **One concept, one place.** Everything about a to-do is in
  `Entities/TodoItem/`: the title rule (trimmed, 1 to 200
  characters), the done/open state, the store contract and the
  declared settings.
- **One owner, one write.** TodoItem owns its state; a feature
  changes it through a single store save.
- **Dependencies point inward.** The web page and the CLI call
  features; features call the entity's store contract; the SQLite
  integration fulfills it. Only `Delivery/Wiring.php` knows the
  SQLite store exists.
- **No inheritance.** There is no `extends` anywhere in `src/`,
  and arch-check fails if one appears. The one `extends` it
  allows is a custom exception extending a built-in exception,
  because PHP only throws `Throwable`s; this app needs none. A
  trait in a class body fails the check too.
- **Time is passed in.** Features take the time from the Clock
  contract and hand it to the entity, so a test can state exactly
  what "now" means.
- **Configuration is declared, then overridden.** TodoItem
  declares `list_limit` (default 50) in `TodoItemSettings`;
  `config/TodoItem.yaml` overrides it to 25; the environment
  variable `TODOITEM_LIST_LIMIT` wins over both. Run
  `php app.php config TodoItem` to see each value and its source.
  An override key the Settings class does not declare fails
  startup. The database stores to-dos, never configuration.
