# AGENTS.md — rules for AI agents and people working in this app

This app follows FIDES. Run `php arch-check.php` and
`php tests/run.php` before you finish.

## Where code goes

- A rule always true for a concept → its folder in `src/entities/`
- Something a user or system does → a new folder in `src/features/`
- A new way the world reaches the app → `src/delivery/`
- A new way the app reaches the world → `src/integrations/`, behind a contract
- A contract no single entity owns → `src/shared/`
- Setting overrides → `config/`, one YAML file per entity

## The rules

1. **No inheritance.** Never use `extends` in `src/`. Compose small
   objects instead. Classes are `final`; contracts are interfaces.
2. **Entities hold rules that are true everywhere.** An entity
   imports only its own folder. It owns what it must keep
   consistent and saves in a single store write. (TodoItem owns its
   title rule and its done/open state.)
3. **Features hold one use case per folder**, readable top to
   bottom, with the view colocated (see ListTodos/view.php).
   Features import entities and `src/shared/` contracts only:
   never integrations, never delivery, never another feature.
4. **Integrations fulfill contracts** (the SQLite store, the
   system clock, the config loader). They import entities and
   shared contracts only, never each other.
5. **Delivery reaches features only.** `src/delivery/wiring.php`
   is the only file allowed to import integrations; it builds the
   object graph and hands finished features to the doors.
6. **Time is passed in.** Features get the time from the shared
   Clock contract and pass it into entities. Entities never read
   a clock.
7. **Configuration is declared, then overridden.** Each entity
   declares its settings, defaults and validation in a Settings
   class in its own folder (`TodoItemSettings`). `config/` holds
   overrides only. The merger runs default, then config file,
   then environment variable; an undeclared override key fails
   startup. The database holds transactional data only, never
   configuration. Secrets, if this app ever has any, come from
   the environment only.
8. Escape every value you print. Every POST form carries the CSRF
   token and its handler checks it.
