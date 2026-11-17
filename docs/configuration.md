# Configuration

Settings are **declared in code, overridden in `config/`, and overridden again
by the environment.** This is merging data, not inheritance: nothing extends
anything.

## Three layers, later wins

| Layer | Lives in | Owned by |
|---|---|---|
| 1. Default | the owner's settings unit, in `src/` | the code |
| 2. Override file | `config/<Owner>`, outside `src/` | whoever runs the app |
| 3. Environment variable | the process environment | the deployment |

**Defaults are declared by the owner.** Every setting an entity has is
declared in that entity's folder, with its default and its rules:

```
entities/course/course_settings
  max_courses_per_student = 4     # must be at least 1
  transfers_open          = true
```

Ownerless settings (an app title, a sender address) are declared in
`shared/`.

**Overrides live outside the source tree,** one file per owner, named after it
in the same casing as `src/` (`snake_case` or `PascalCase`), holding only what
differs:

```
config/
  app            # overrides the ownerless app settings (or App in PascalCase)
  course         # overrides course_settings (or Course in PascalCase)
```

Why outside `src/`: live configuration belongs to whoever runs the app, and a
code update must never overwrite it. The owner owns the *declaration*, which
ships with the code. The config folder holds the *operator's values*. The
file name says who owns them.

Symfony, Laravel and Rails already use `config/` for configuration that ships
with the code. In those apps, operator overrides get a folder of their own:
see [frameworks.md](frameworks.md#the-config-folder).

**The environment wins over the file.** The variable name is the owner's
prefix plus the key, in capitals: `COURSE_MAX_COURSES_PER_STUDENT=2`,
`APP_TITLE="Fall Registration"`.

## Merging rules

- **Once per startup.** One loader (an integration) merges every owner's
  settings and builds a frozen settings object. Wiring hands it to the
  features and stores that need it. Nothing reads config files or environment
  variables anywhere else. When startup happens depends on the runtime: see
  [What startup means](#what-startup-means).
- **Scalars override, maps merge key by key, lists replace whole.**
- **Unknown keys stop startup,** naming the key and the file. A typo never
  passes silently.
- **Invalid values stop startup.** The settings unit checks the merged values
  when it is built. Bad config fails at boot, not at 2 a.m. in the middle of
  a request.
- **Frozen.** Settings never change during a unit of work: a request, a
  command, a message being handled. A new value applies from the next startup.
- **Secrets never live in files.** Passwords, keys and tokens come from the
  environment (or a secret store) only.

## What startup means

Startup is whenever wiring builds the app. The runtime decides how often that
happens:

| Runtime | Startup happens | A changed override applies |
|---|---|---|
| A long-running server or worker (most Python, Node, Java, .NET and Go apps) | once, when the process starts | after a restart or reload |
| PHP under PHP-FPM, mod_php or CGI | on every request: PHP builds the app afresh each time | on the next request |
| A PHP server that keeps the app in memory (FrankenPHP worker mode, RoadRunner, Swoole) | once per process, unless the app resets between requests | after a reset or restart |
| A CLI command | on each run | on the next run |

The rule is the same in every row: one merged, checked, frozen settings object
per unit of work. Build it once, hand it on, and never read the file or the
environment again partway through. A request that saw two values of one
setting cannot be reproduced.

A long-running process may reload settings without a restart, on a signal or
when the file changes. It builds a new settings object between units of work
and swaps it in whole. The old object is never changed in place, so work
already running finishes with the values it started with.

## Settings edited through the app

Some apps let operators change settings on an admin screen as well as in the
override file. That fits FIDES when:

- **The screen writes the override file,** through a contract, the same file
  the operator edits by hand. Never the database (rule 12).
- **The new values pass the startup checks before they are written,** so the
  screen can never save a file that would stop the next startup.
- **The change applies at the next startup,** never to the request that saved
  it. In PHP's usual model that is the next request, so no cache needs
  clearing.
- **Secrets stay out of the file.** A screen may show whether a secret is set,
  and may write one only to a secret store.

## Show where every value came from

Give the app a command that prints each setting, its effective value and the
layer it came from:

```
$ app config Course
max_courses_per_student   3      file
transfers_open            false  env
```

This answers "why is this 3?" in seconds, for people and agents alike.

## Configuration or data?

Ask who changes it, how, and how often.

| It is... | It goes in... |
|---|---|
| A tunable value with a sensible default | the owner's settings, overridden in `config/` |
| Curated by whoever runs the app, with no meaningful default (a staff roster, a list of systems to monitor) | `config/`, in the owning entity's file, under its own key, read through a store contract |
| Created or edited by the app's users through the app | the database: it is transactional data |
| A record of what happened (orders, enrollments, check-ins, history) | the database |

The database never holds configuration (rule 12).

## Format and tooling

The file format is a tool choice, not a rule. YAML, TOML and JSON all work.
Pick the one your language reads most easily; the example uses TOML because
Python reads it with no dependencies.

Avoid templating inside config files (Helm-style or Jinja-in-YAML). It turns
configuration into a second programming language whose errors only appear
after rendering. If a value has to be calculated, the settings unit
calculates it.

Optional extras that make config easy to manage:

- generate a JSON Schema from the settings units, so editors autocomplete and
  check the override files as you type
- validate every environment's config in the build, before deploy
