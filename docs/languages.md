# Language notes

The rules are the same in every language. What changes is how each language
expresses a unit, a contract and a value, and how strongly it can enforce
them. Where a language cannot enforce a rule, write the rule down plainly and
rely on review and the build check.

| Concept | What it needs |
|---|---|
| Unit | Something that can hide its data and expose actions |
| Contract | Something that describes what a unit can do, without saying how |
| Value | Something that checks itself when created and never changes |
| No inheritance | A way to share behavior by composing, not extending |

## PHP

- **Unit:** a `final class` with `private` properties.
- **Contract:** an `interface`. Implementations use `implements`, never `extends`.
- **Value:** a `final readonly class` that validates in its constructor.
- **Enforcement:** Deptrac, or a short script that reads `use` statements and
  fails on any `extends`. Run PHPStan or Psalm in the same build, level 5 or
  higher.
- **Views:** Mustache is logic-less. Twig and Blade escape by default but
  allow logic, so keep it out by review. Plain PHP templates need no
  dependency and every PHP developer reads them, but they can run any code
  and every value must be escaped by hand, so one missed call is a security
  hole. If you choose them, keep them close to logic-less: a short escape
  helper on every value, loops and conditions only over values the feature
  already shaped, and a check in the build that fails when a template names an
  entity or renders another feature's view. Give each template's values a
  `@var` type so the static analyser reads them; an untyped value is a blind
  spot. A production app moved onto FIDES used plain PHP templates, and its
  one rendering regression was a template reading a field that no longer
  existed.
- **Config:** PHP has no built-in YAML parser. Use JSON, PHP arrays, or a
  small reader for the subset of YAML you allow.

## Python

- **Unit:** a class with underscore-prefixed attributes and read-only
  properties. Python cannot enforce privacy, so rule 1 relies on convention
  and review.
- **Contract:** `typing.Protocol`. Implementations match it by shape and do not
  inherit from it, which keeps the no-inheritance rule absolute.
- **Value:** `@dataclass(frozen=True)` that checks itself in `__post_init__`.
- **Enforcement:** import-linter, or a short `ast` script (the example's
  `arch_check.py` is about 150 lines). Add mypy or pyright in strict mode.
- **Views:** `string.Template` is logic-less and in the standard library.
- **Web:** a plain WSGI function avoids subclassing a framework's handler.

See [the example app](../examples/course-enrollment).

## TypeScript and JavaScript (server side)

- **Unit:** a class with `#private` fields (truly private at runtime), or a
  module that exports only functions.
- **Contract:** an `interface`. TypeScript matches by shape, so implementations
  need no `extends` and no `implements`, though `implements` documents intent.
- **Value:** a class with `readonly` fields, frozen with `Object.freeze`.
- **Enforcement:** dependency-cruiser or eslint-plugin-boundaries, plus
  `tsc --strict`.
- **Note:** FIDES fits the Node backend. For the browser app itself, see
  [why.md](why.md#where-it-fits).

## Java and Kotlin

- **Unit:** a `final` class (Kotlin classes are final by default) with
  `private` fields.
- **Contract:** an `interface`.
- **Value:** a `record` (Java) or `data class` (Kotlin) that checks itself in
  its constructor.
- **Enforcement:** ArchUnit or Konsist. Stronger: one Gradle or Maven module
  per zone, so the compiler enforces the direction.
- **Frameworks:** organize packages by feature, not by layer. Keep framework
  annotations and base classes in delivery and integrations. A
  `TransactionTemplate` can fulfill the `UnitOfWork` contract.

## C\#

- **Unit:** a `sealed` class with `private` fields.
- **Contract:** an `interface`.
- **Value:** a `record` that validates in its constructor.
- **Enforcement:** NetArchTest or ArchUnitNET, or one project per zone with
  project references pointing inward.

## Go

- **Unit:** a package. Unexported fields keep data private to the package.
- **Contract:** an `interface`. Go satisfies interfaces implicitly, which
  matches FIDES naturally.
- **Value:** a struct with unexported fields and a constructor function that
  validates.
- **No inheritance:** the language has none. Embedding is composition, but do
  not use it to imitate inheritance.
- **Enforcement:** `internal/` directories plus go-arch-lint.
- **Naming:** Go package names are short and lowercase (`course`, `enroll`).

## Rust

- **Unit:** a struct with private fields inside a module.
- **Contract:** a `trait`.
- **Value:** a struct with private fields and a constructor that returns a
  `Result`.
- **No inheritance:** the language has none.
- **Enforcement:** make each zone a crate in a workspace. Cargo will refuse a
  dependency that points the wrong way.
