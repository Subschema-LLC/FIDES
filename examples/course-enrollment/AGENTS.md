# AGENTS.md: rules for AI coding agents (and humans) working here

This app follows FIDES. The full spec is in the repository root under `docs/`.
This page is the part you need while changing code. `python3 run_tests.py`
checks most of it and fails the build when a rule is broken.

## Where code goes

| You are adding... | Put it in |
|---|---|
| A rule that is always true for a concept | that concept's folder in `src/entities/` |
| Something a user or system does | a new folder in `src/features/` |
| A new way the outside world reaches the app | `src/delivery/` |
| A new way the app reaches the outside world | `src/integrations/`, behind a contract |
| A contract no single entity owns | `src/shared/` |
| A setting | declared with its default in the owner's settings class |
| An override for a setting | `config/<Owner>.toml` (never inside `src/`) |

If a change needs more than a feature folder, the entities it names and one
integration, stop and ask whether a concept is being spread too far.

## Rules

1. **One owner.** Only the unit that owns data changes it. Others call its actions.
   Reading through properties is fine.
2. **Entities stand alone.** An entity imports only its own folder. Refer to
   another entity by id, never by object. Cross-entity rules live in features.
3. **One use case per feature folder,** readable top to bottom, with its view
   and CSS beside it. Features never import other features. They react to
   messages instead.
4. **The core is pure.** Entities, features and shared never touch the database,
   files, network, environment or clock. Features get time from the `Clock`
   contract and pass it into entities.
5. **Dependencies point inward.** Features import entities and shared.
   Integrations import entities and shared. Delivery imports features and
   shared. Only `delivery/wiring.py` imports integrations.
6. **No inheritance.** Compose small objects. The only base class allowed is
   `Protocol`, for contracts. Integrations fulfill contracts without inheriting.
7. **Messages are recorded, saved, then dispatched.** Entities record them,
   stores save them in the same write, the worker dispatches them after
   commit. Never send email or call out from inside a change. Handlers must be
   safe to run twice.
8. **One write by default.** A store's `save()` is the transaction. Ask for
   `UnitOfWork` only when a feature changes two entities, so it is visible in
   the constructor.
9. **Settings are declared in code, overridden in `config/`, then by the
   environment,** merged once at startup. Unknown keys and invalid values stop
   startup. Secrets come from the environment only. The database holds
   transactional data, never configuration.
10. **Views only display.** Templates have no logic. Values are escaped unless
    they are `Markup`. Pages are composed in delivery.
11. **Keep it close and small.** At most `zone/owner/file` deep, lines up to 100
    characters, files up to 200 lines. Split by concept, never by kind of code.

## Before you finish

```bash
python3 run_tests.py      # architecture check + all tests
```

Coming back after months away? Read this page, then `config/`, then
`src/features/transfer/transfer.py` (the one feature that shows every rule),
then `src/delivery/wiring.py` to see how it all connects.
