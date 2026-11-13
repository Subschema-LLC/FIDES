# Structure

## The zones

Every file in `src/` belongs to exactly one zone. The zone decides what the
file may hold and what it may import.

### Entities

The rules that are true everywhere, grouped by the concept that owns them.
One folder per entity holds:

- the **entity** itself: identity plus actions that enforce its rules
- its **values**: small immutable things that check themselves (`Email`, `Seats`)
- its **messages**: records of what happened (`StudentEnrolled`)
- its **settings**: every tunable value, with its default and its rules
- its **store contract**: what the app needs in order to find and save it

An entity imports only its own folder. It refers to other entities by
identity (an id or key), never by holding them.

### Features

One use case per folder, named as an action: `enroll`, `transfer`,
`list_courses`. The folder holds the use case, plus its view, CSS and
scripts if it has a screen. The use case reads top to bottom.

Features import entities and shared. They never import another feature,
an integration or delivery. Rules that involve two entities ("an inactive
student cannot enroll") live here, because neither entity owns them.

Some features react to messages instead of to people
(`send_enrollment_confirmation` reacts to `StudentEnrolled`).

### Shared

Contracts that no single entity owns: `UnitOfWork`, `Clock`, `Mailer`,
`MessageBus`, plus ownerless settings (`AppSettings`). Contracts and small
values only, never logic, or it turns into a junk drawer. Shared imports
nothing else from the app.

### Integrations

How the app reaches the world. Each integration fulfills a contract that an
entity or shared declares: a database store, a mailer, a clock, the config
loader. Integrations import entities and shared, never features or delivery.

### Delivery

How the world reaches the app: web routes, API endpoints, CLI commands, the
worker that dispatches messages. Delivery translates input into feature calls
and feature results into output. It holds no business rules.

One file in delivery, **wiring**, builds the whole object graph at startup.
It is the only file that knows integrations exist.

### Outside `src/`

| Path | Holds |
|---|---|
| `config/` | Setting overrides, one file per owner, owned by whoever runs the app |
| `tests/` | Entity tests (no fakes), feature tests (with fakes), integration tests |
| `AGENTS.md` | The rules on one page, for AI agents and people |
| the checker | A script that fails the build when a rule is broken |
| `var/` | Runtime files (local database, logs), never committed |

## Import direction

| Zone | May import |
|---|---|
| shared | nothing else from the app |
| entities/X | only entities/X |
| features/X | entities, shared, and features/X itself |
| integrations/X | entities, shared, and integrations/X itself |
| delivery | features, shared, delivery |
| delivery/wiring | anything |

**Never:** a feature importing another feature, a feature importing an
integration, an entity importing another entity, or anything importing
delivery (except delivery itself).

The core (entities, features, shared) also never imports I/O modules
(database drivers, HTTP, file system, environment) and never reads the clock.

### Enforce it

Write the import rule into the build so it cannot erode. Existing tools:

| Language | Tools |
|---|---|
| PHP | Deptrac, or a short script over `use` statements |
| Python | import-linter, or a short `ast` script (see the example's `arch_check.py`) |
| JavaScript / TypeScript | dependency-cruiser, eslint-plugin-boundaries |
| Java / Kotlin | ArchUnit, Konsist, or one Gradle module per zone |
| C# | NetArchTest, ArchUnitNET, or one project per zone |
| Go | `internal/` packages, go-arch-lint |
| Rust | one crate per zone in a workspace; Cargo enforces the direction |

Where the build system can make each zone its own module (Gradle modules,
.NET projects, Rust crates), the compiler enforces the direction for free.

## Spread: count the folders

The question FIDES asks of any structure is: **how many folders do you open
to understand or change one thing?**

A layered app commonly splits "enroll a student" across something like:

```
controller/   dto/   mapper/   validator/   service/   service/impl/
repository/   repository/impl/   entity/   event/   listener/
config/   templates/   static/css/
```

That is fourteen places for one concept, and every new developer and every
AI agent has to rediscover the route between them.

In FIDES the same work lives in:

```
features/enroll/          # the use case, its view, its CSS
entities/course/          # the rules, values, messages, settings, contract
integrations/database/    # one store file
delivery/wiring           # one line that connects them
```

Four places, and most days you only open the first two.

FIDES keeps exactly one split that layered apps also have: a contract lives
with its entity and its implementation lives in integrations. That split is
what lets the core be tested without a database and swapped to a new one
without touching a rule.

**The spread test:** a change should touch its feature folder, the entities
that feature names, at most one integration, and wiring. If it needs more, a
concept is spread too far.

### Depth and size

Spread has a vertical side too:

- **Shallow.** Never deeper than `zone/owner/file`.
- **Small files.** A file past about 200 lines is hiding another concept.
  Give that concept its own unit.
- **Short lines.** About 100 characters, one statement per line, so code
  reads down the page instead of across it.
- **Split by concept, never by kind of code.** A folder of "helpers" or
  "utils" is a sign a concept has no owner yet.

## Glossary

| Term | Meaning |
|---|---|
| Unit | The smallest thing that owns data: a class, a package or a module, depending on the language |
| Entity | A unit with identity and rules that are true everywhere |
| Value | A unit with no identity that checks itself and never changes |
| Contract | A promise one side needs and the other side keeps: an interface, protocol or trait |
| Store | The contract for finding and saving an entity, fulfilled by an integration |
| Message | A record of something that happened, saved with the change that caused it |
| Feature | One use case, in one folder |
| Settings | An owner's tunable values, declared in code with defaults and rules |
| Wiring | The one file that builds the app from its parts |
