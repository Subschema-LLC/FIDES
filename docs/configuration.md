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

**The environment wins over the file.** The variable name is the owner's
prefix plus the key, in capitals: `COURSE_MAX_COURSES_PER_STUDENT=2`,
`APP_TITLE="Fall Registration"`.

## Merging rules

- **Once, at startup.** One loader (an integration) merges every owner's
  settings and builds a frozen settings object. Wiring hands it to the
  features and stores that need it. Nothing reads config files or environment
  variables anywhere else.
- **Scalars override, maps merge key by key, lists replace whole.**
- **Unknown keys stop startup,** naming the key and the file. A typo never
  passes silently.
- **Invalid values stop startup.** The settings unit checks the merged values
  when it is built. Bad config fails at boot, not at 2 a.m. in the middle of
  a request.
- **Frozen.** Settings cannot change while the app runs.
- **Secrets never live in files.** Passwords, keys and tokens come from the
  environment (or a secret store) only.

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
