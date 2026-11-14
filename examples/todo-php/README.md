# Example: to-do app (PHP)

A very small FIDES app: a to-do list you can add to, complete,
reopen and delete, from the command line or a single web page. It
uses only PHP itself (8.1 or newer, with SQLite), so there is
nothing to install and no framework to learn first.

It exists to show the FIDES shape at the smallest size that still
exercises every rule: one entity, five features, two doors, one
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
php app.php serve                    # web app on http://127.0.0.1:8080
php tests/run.php                    # architecture check + 15 tests
php arch-check.php                   # the architecture check on its own
```

Delete `var/todos.sqlite` to start over with an empty list.

## Map

```
AGENTS.md            # the rules, one page, for AI agents and humans
arch-check.php       # fails the build on rule violations
app.php              # the command-line door
public/index.php     # the web door
config/
  TodoItem.yaml      # overrides only; defaults live in the entity
src/
  shared/            # Clock: the ownerless contract
  entities/TodoItem/ # TodoItem, its store contract, its Settings
  features/          # ListTodos (with its view), AddTodo,
                     # CompleteTodo, ReopenTodo, DeleteTodo
  integrations/      # SQLite store, system clock, config loader
  delivery/          # wiring.php (the only file that names
                     # integrations), the CLI and web doors
tests/run.php        # the test run
var/                 # todos.sqlite lives here (not committed)
```

## How the rules show up

- **One concept, one place.** Everything about a to-do is in
  `entities/TodoItem/`: the title rule (trimmed, 1 to 200
  characters), the done/open state, the store contract and the
  declared settings.
- **One owner, one write.** TodoItem owns its state; a feature
  changes it through a single store save.
- **Dependencies point inward.** The web page and the CLI call
  features; features call the entity's store contract; the SQLite
  integration fulfills it. Only `delivery/wiring.php` knows the
  SQLite store exists.
- **No inheritance.** There is no `extends` anywhere in `src/`,
  and arch-check fails if one appears.
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
