# Inside a framework

FIDES is an approach, not a framework. It does not replace Symfony, Laravel,
Rails, Django, Spring or ASP.NET Core; it fits inside them. The framework
keeps its conventions, and the zones map onto them. The rules stay required.
How each one is met can follow the framework, as long as the rule's *why*
still holds.

This page covers what changes inside a framework: where its pieces go, the
container, the `config/` folder, settings the framework compiles, ORMs,
templates, base classes and traits, and moving in a piece at a time.

## Where the framework's pieces go

| Framework piece | Zone | Note |
|---|---|---|
| Controllers, route handlers, console commands, queue consumers, scheduled jobs | delivery | Thin: read the input, call a feature, shape the output |
| The container's configuration | delivery's wiring | See [Containers](#containers-what-stays-explicit) |
| Templates | feature views and page templates | See [Templates](#templates) |
| Active-record models (Eloquent, ActiveRecord, Django models) | integrations | They inherit from the framework, so they are the store's records, not entities. See [ORMs](#orms) |
| Data-mapper ORM mapping (Doctrine, JPA, Entity Framework) | integrations | See [ORMs](#orms) |
| Framework events and listeners | messages, and the features that react to them | Dispatched after commit, from the outbox ([changes.md](changes.md)) |
| Form types and request validation | delivery | They check the shape of input. Rules that are true everywhere live in entities and values |
| The framework's `config/` folder | not FIDES's `config/` | See [The config/ folder](#the-config-folder) |

The import rules apply to your code, not to the framework's. Delivery and
integrations may use the framework freely. The core may use a framework's
plain utilities (a collection type, a string helper), never its I/O, its
container or its base classes.

## Containers: what stays explicit

Most frameworks build the object graph with a dependency-injection container:
Symfony, Laravel, Spring, ASP.NET Core, NestJS. The container is the wiring.
FIDES does not ask you to replace it with a hand-written file. It asks that
four things stay explicit.

1. **Every contract is bound in one file.** That file names, for each
   contract, the integration that fulfills it: a Symfony services file, a
   Laravel service provider, a Spring `@Configuration` class, an extension
   method on ASP.NET Core's `IServiceCollection`. Bind each contract there
   even when the container could work it out, for example from the only class
   that implements an interface. That binding is what changes when you swap a
   database, so it should be a line a reader can find. Integration class names
   appear in that file and nowhere else in your code, which a check can
   search for.
2. **The core never asks the container for anything.** Entities, features and
   shared get what they need through their constructors. No service locator,
   no `container.get()`, no field or setter injection, no static facades
   (Laravel's `DB::` and `Mail::`). A feature's constructor stays its complete
   list of needs.
3. **The core carries no container markup where you can avoid it.** Register
   core classes in the wiring file rather than annotating them (`@Service`,
   `@Autowired`, `#[Autowire]`), so the core does not depend on the
   container. Autowiring by type is fine: a feature that asks only for
   contracts gets them from the bindings in point 1.
4. **A missing binding stops startup.** Use the container's own check:
   `lint:container` in Symfony's build, Spring's eager startup,
   `ValidateOnBuild` in ASP.NET Core. Where the container resolves only on
   use (Laravel), add a test that resolves every controller and command.

The rest of the container's configuration (bundles, packages, middleware)
belongs to the framework and stays where the framework puts it. Keep the
wiring file where the framework loads container configuration from, or in
delivery if the framework can load it from there.

## The `config/` folder

In FIDES, `config/` holds the operator's overrides: values that belong to
whoever runs the app, which a code update must never overwrite. Symfony,
Laravel and Rails already use `config/` for configuration that ships with the
code: routes, services, framework settings. The two meanings clash.

The rule is about ownership, not the folder's name. Leave the framework's
`config/` to the framework, and give operator overrides a place the code never
ships or writes over:

- **A folder of their own,** named for what it holds, such as `overrides/` at
  the project root, one file per owner. The clearest choice for a new app.
- **A fixed pattern inside the framework's folder,** when installs already
  keep operator files there: `config/<owner>.local.yaml`, or
  `config/<owner>.yaml` shipped only as `<owner>.yaml.example`. Version
  control ignores the live files, and an update never writes them.
- **Outside the app,** such as `/etc/<app>/`, for packaged installs whose
  operators expect it there.

Whichever you pick: one file per owner, named for the owner; the settings
loader reads only those files; and `AGENTS.md` says where they are. An app
that already has installs keeps its operator files where they are. Moving
them breaks every install and changes nothing the rule cares about.

## Settings the framework compiles

Some frameworks compile configuration into a cache for speed: Symfony's
container parameters, Laravel's `config:cache`. A value compiled there changes
only when the cache is rebuilt, which turns an operator's edit into a deploy
step.

The framework's own settings can stay in its cache. Read operator settings
through the FIDES loader at startup instead, so an edited override applies at
the next startup ([configuration.md](configuration.md#what-startup-means)). In
PHP's usual model, that is the next request.

## ORMs

An ORM pulls toward an object graph: entities that hold each other and load
each other on demand (`$course->getStudents()`). FIDES entities refer to each
other by id, so FIDES gives that graph up on purpose:

- **No associations between entities.** Map another entity's id as a plain
  value (`string $courseId`), or as a small id value through the ORM's own
  tools (Doctrine embeddables or a custom DBAL type), never as an entity
  reference (`ManyToOne`, `OneToMany`). Inside one entity's consistency
  boundary, such as a Course and its own enrollments, associations are fine.
- **Foreign keys stay in the database.** The schema still declares them, so
  the database guards referential integrity. Only the ORM mapping treats the
  column as a plain value.
- **The mapping stays in integrations.** Either map the entity itself with the
  ORM's external mapping (XML, PHP or fluent configuration) when its shape
  fits, or map a record class in integrations and let the store copy between
  record and entity. Active-record ORMs always need the record form, because
  the model inherits from the framework.
- **Reads across entities use a read model**
  ([structure.md](structure.md#read-models)), usually one SQL query or a
  database view, outside the ORM's object graph.

You lose lazy loading and walking from one object to the next. In exchange,
each entity loads and saves on its own, and no query hides behind a getter.

## Templates

Template inheritance (`{% extends %}` in Twig and Jinja, `@extends` in Blade)
is inheritance between templates, not between units of code. It stays inside
delivery:

- **A page template may extend the layout.** Both live in delivery.
- **A feature view is a fragment.** It extends nothing and includes only its
  own partials, never another feature's view or a page template. The page
  template includes it (rule 14).
- **Point the template loader at feature folders,** so a view stays beside its
  feature: a Twig namespace (`@Features/ListTodos/ListTodosView.html.twig`), a
  Blade view path, a Django template directory.
- **Escape by default, keep logic out.** Twig, Blade and Jinja escape by
  default but can run logic, so check in the build that a feature view names
  no entity and extends or includes nothing outside its folder (rules 13 and
  14).

## Base classes and traits

Rule 8 allows a framework's required base class or trait at the edge, kept
thin. Before using one, check whether the framework still requires it:

- Symfony controllers never needed `AbstractController`, and since Symfony 7.3
  console commands need not extend `Command`.
- Laravel routes can point at any class; the base `Controller` is optional.
- Spring controllers are plain classes with annotations.
- Rails controllers and Django class-based views do need a base class. Keep
  them thin, in delivery.
- Symfony's kernel needs `BaseKernel` and `MicroKernelTrait`. It is delivery:
  the door the framework starts from. List it in the checker as the one
  allowed parent and trait, with the reason.

A helper a base class used to give you (a redirect, a flash message,
rendering) becomes a small object passed in through the constructor.
Framework attributes for routes, input validation and ORM mapping belong on
delivery and integration classes, never on entities and features.

## Moving in a piece at a time

An existing framework app rarely moves in one step. Add the zones beside the
framework's own folders and move one concept at a time:

- New zones never import the old code. Old code may call into the new zones.
- An integration may wrap an old service as an adapter, behind a contract,
  until that service moves too.
- The checker applies every rule to the new zones and holds the old code to a
  ratchet: its count of violations may go down, never up.
- Start with a concept that is well tested and about to change anyway, so the
  move pays for itself.

[migrating.md](migrating.md) covers proving that nothing else changed.
