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
- its **read rows**, if a store returns some: small typed, read-only records
  for lists and screens that do not need the whole entity. Never a raw array
  or dictionary from the database driver, so the type checker sees every
  field a feature reads.

An entity imports only its own folder. It refers to other entities by
identity (an id or key), never by holding them.

### Features

One use case per folder, named as an action: `enroll`, `transfer`,
`list_courses`. The folder holds the use case, plus its views, CSS and
scripts if it shows anything. The use case reads top to bottom. A feature
with a view also shapes the plain values that view prints.

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

Each integration talks to its system one way. If the database stores use a
small set of query helpers, every store uses them and none reaches past them
to the driver. Two styles side by side double what a reader has to know.

### Delivery

How the world reaches the app: web routes, API endpoints, CLI commands, the
worker that dispatches messages. Delivery translates input into feature calls
and feature results into output. It holds no business rules.

One file in delivery, **wiring**, builds the whole object graph at startup.
It is the only file that knows integrations exist.

#### Pages

A web delivery with more than one or two pages gives each page a folder. The
page is the unit of web delivery, the way a use case is the unit of features.

```
delivery/web/
  layout              # the frame every page sits in, with its CSS and scripts
  renderer            # escapes by default, loads feature assets
  routes              # paths to pages
  board/
    board             # page code: reads the request, calls features, responds
    board_view        # page template: lays out the page, places feature views
    board_view.css    # only for markup the page template prints itself
features/
  show_board/
    show_board        # the use case, and the plain values its views print
    board_view        # a feature view
    show_board.css    # loads whenever one of this feature's views renders
  manage_board_items/
    manage_board_items
    add_card_form     # the form belongs to the feature that handles it
```

How a page is put together:

- **The page template places feature views.** It prints a heading and some
  structure, then hands each area to the feature that owns it. Read it to
  know what is on the page.
- **A feature view displays one feature's values.** It never renders another
  feature's view or a page template. Once a template language can run code,
  this needs a check in the build.
- **A form belongs to the feature that handles its submission.** Its fields,
  labels and choices live in that feature's view, so the code that reads the
  form and the markup that sends it sit in one folder.
- **Slots.** When one feature's area holds another feature's form (an edit
  form inside a board card), the page renders that form and hands it to the
  first view as ready-made, already escaped HTML. The view prints the slot
  where it belongs and knows nothing about what is inside.
- **One-button actions can sit in any view.** A button that posts nothing but
  an id (mark done, delete) has no fields for a feature to own. A slot per
  button would cost more than it saves.
- **Assets load with their views.** A feature's CSS and scripts load when one
  of its views renders on the page: the layout's first, then each feature's
  in the order they first rendered, then the page's own. Delivery never keeps
  a list of which page needs which stylesheet, so a feature cannot appear
  without its styling.
- **Page CSS styles only what the page template prints.** If a rule targets a
  feature's markup, it belongs in that feature's CSS.

Serve feature assets from their folders (through the app, or copied by a
build step) rather than inlining them. Separate files keep a strict
Content-Security-Policy possible.

The cost is honest: a screen is now two places, its page folder and the
features it shows. In exchange, each part of the screen is found by asking
who handles it, and nothing on the page is owned twice.

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

Run the language's type checker or static analyser in the same build, at a
strict setting. It catches what the import check cannot: a field that does
not exist, a variable used before it is set, a branch that can never run.
Typed read rows and feature-shaped view values matter here, because a checker
only sees as far as the types reach.

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

- **Shallow.** Never deeper than `zone/owner/file`. The one exception is a
  page in delivery: `delivery/web/<page>/file`.
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
| Feature view | A template in a feature's folder that displays that feature's values |
| Page | One screen of a web delivery, in its own folder in delivery: page code plus a page template |
| Page template | The template that lays out a page and places feature views on it |
| Slot | Ready-made HTML a page hands into a feature view, such as another feature's form |
| Read row | A small typed, read-only record a store returns for lists and screens |
| Settings | An owner's tunable values, declared in code with defaults and rules |
| Wiring | The one file that builds the app from its parts |
