# Why FIDES

## Goals

FIDES is judged against five goals, in this order:

1. **Simplicity.** Few concepts, few rules, no machinery you have to learn
   before you can read the code.
2. **Readability.** A use case reads top to bottom in one file. Names say what
   things do.
3. **Encapsulation.** Data is changed only by its owner, through actions with
   names, never poked from outside.
4. **Locality.** Everything about one concept sits in one place. You should
   not have to visit a dozen folders to understand one thing.
5. **Good for humans and AI alike.** The same properties that let a new
   developer find their way in five minutes let an AI agent work in a small,
   correct context, and a mechanical check stops either of them from taking a
   shortcut.

When two goals conflict, the earlier one wins.

## What goes wrong without it

- **Layering scatters concepts.** Organizing by kind of code (controllers,
  services, repositories, DTOs, mappers) spreads one use case across many
  folders. See [structure.md](structure.md#spread-count-the-folders).
- **Inheritance hides behavior.** To know what a method does, you climb a
  chain of parent classes, each of which can override the last.
- **Rules leak everywhere.** When any code can change any data, the rule
  "a full course takes no more students" ends up checked in five places, and
  missed in a sixth.
- **Side effects get tangled with changes.** An email sent in the middle of a
  database transaction cannot be unsent when the transaction rolls back.
- **Agents copy the mess.** An AI agent asked to add a feature imitates the
  nearest code. If that code shortcuts a boundary, so will the agent.

## Where it fits

FIDES is designed for **server-side applications and monoliths**: code that
owns data, enforces rules on it and is reached through several doors (web,
API, CLI, background jobs).

| Good fit | Why |
|---|---|
| Server-rendered web apps and APIs | Every FIDES idea has something to attach to |
| Monoliths and modular monoliths | Feature folders and isolated entities keep a large codebase navigable |
| The backend behind a single-page or mobile app | The SPA is just another delivery client |
| CLIs and workers sharing rules with a web app | Same features, different delivery |
| One service inside a larger system | Each service can be FIDES inside; the outbox carries messages out |
| Apps built on a framework (Symfony, Laravel, Rails, Django, Spring, ASP.NET Core) | FIDES is an approach, not a framework: its zones map onto the framework's conventions ([frameworks.md](frameworks.md)) |

| Poor fit | Why |
|---|---|
| The single-page app itself (React, Vue, Svelte), mobile and desktop clients | Client code is mostly component state and rendering. Its frameworks bring their own structure, and stores, transactions and an outbox have nothing to attach to. Feature folders, composition over inheritance and "views only display" still carry over. |
| Libraries and SDKs | There is no delivery and no data to own |
| Small scripts | The structure costs more than it saves |
| Data pipelines, game engines, performance-critical inner loops | Their shape is driven by data flow or speed, not by ownership of rules |

## What it costs

FIDES is not free. Know the costs before adopting it:

- **More files within each folder.** Values, messages and contracts each get
  a name. The trade is deliberate: many small files in one place beat a few
  large files spread across many places.
- **Explicit wiring.** One file builds every object by hand. It is long and
  boring, which is the point: it is the one place that shows how the app is
  connected. In a framework with a dependency-injection container, the
  container builds the objects and that file shrinks to one line per
  contract, binding it to its integration ([frameworks.md](frameworks.md)).
- **Some repetition.** Because entities never import each other, two entities
  may each declare a small thing (such as a message contract) that a layered
  app would share.
- **A screen is two places.** A page's folder in delivery lays it out with any
  page-specific CSS and JS, and each feature it shows keeps its own view, CSS
  and JS. Finding a button means asking which feature handles it. In exchange,
  nothing on a page is owned twice.
- **Reads across entities need a read model.** A screen that joins entities
  gets its own query contract and one integration query, instead of walking
  from one object to the next. In exchange, reads never bend the rules that
  guard writes.
- **A checker to maintain.** The rules only hold if the build enforces them.
- **New habits.** The outbox and "one write by default" take a little getting
  used to if you are used to sending email from a controller.

## Prior art

FIDES is mostly a recombination of good ideas. Its contribution is choosing a
small, strict set of them, making the set language-agnostic and enforcing it
mechanically.

| Idea | Source | What FIDES takes |
|---|---|---|
| Ports and adapters (hexagonal architecture) | Alistair Cockburn | Contracts in the core, adapters at the edges |
| Clean and onion architectures | Robert C. Martin, Jeffrey Palermo | Dependencies point inward |
| Vertical slices | Jimmy Bogard | Organize by use case, not by layer |
| Aggregates and domain events | Eric Evans, *Domain-Driven Design* | Ownership follows consistency; entities record what happened |
| Transactional outbox | Common distributed-systems pattern | Messages saved with the change, dispatched after commit |
| DCI (Data, Context, Interaction) | Trygve Reenskaug, James Coplien | A use case should read like the scenario it implements |
| Functional core, imperative shell | Gary Bernhardt | A pure core; I/O at the edges |
| Locality of behaviour | Carson Gross | Keep what changes together, together |
| Deep modules | John Ousterhout, *A Philosophy of Software Design* | Small interfaces, a lot hidden behind them |
| Favor composition over inheritance | Gamma, Helm, Johnson, Vlissides, *Design Patterns* | Taken to its conclusion: no inheritance at all |

What is different from those sources:

- **Entities never import each other.** Cross-entity rules always live in a
  feature.
- **No inheritance anywhere,** not just "prefer composition". The one
  allowance is a custom error extending its language's built-in exception,
  where the language requires it.
- **Configuration has an ownership model:** declared by the owner in code,
  overridden by the operator outside the code.
- **Designed for AI agents as well as people:** one-page rules, a predictable
  shape and a build check that agents cannot talk their way around.
