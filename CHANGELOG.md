# Changelog

## 0.2 (2026-11-13)

Findings from moving a production PHP app onto FIDES: about 30 features,
16 web pages and 46 form actions, with plain PHP templates and MariaDB. Every
page, form action, layout and script was compared against the running
version before and after each step.

### Rules

- **Rule 13, views only display,** now says where shaping happens (in the
  feature's code, where tests and the type checker see it) and that a
  template never names an entity. It is checkable when templates can run
  code.
- **New rule 14, pages compose features.** One folder per page in delivery. A
  page template places feature views. A feature's view never renders another
  feature's view. A form belongs to the feature that handles it, and the page
  hands one feature's form into another feature's area as a slot. CSS and JS
  follow three owners: global frame assets with the layout, feature-specific
  assets in the feature folder, and page-specific assets in the page folder,
  loaded in that order when views render.
- Rules 14 to 17 are now 15 to 18.
- **Rule 16, small and shallow,** allows one extra level for pages:
  `delivery/web/<page>/file`.
- **Rule 17, rules are required, tools are examples,** recommends picking
  either `snake_case` (module-based languages) or `PascalCase` (where
  namespaces and classes map 1:1 to folders and files) and keeping that single
  casing consistent across folders, code, views, `.css`, `.js` and `config/`.
- **Rule 18, enforce mechanically,** now includes the language's type checker.

### Docs

- `structure.md`: a Pages section (folders, slots, one-button actions, three
  levels of CSS and JS across layout, features and page folders, and render
  order), `snake_case` vs. `PascalCase` consistency across folders and
  colocated files, typed read rows beside the store contract, one way to talk
  to each system per integration, new glossary terms.
- `migrating.md` (new): moving an existing app and proving nothing changed.
- `ai.md`: page rules and "comments say what is true now" in the AGENTS.md
  template; add the checks on day one; prompting tips.
- `languages.md`: `snake_case` and `PascalCase` naming guidance (including
  PSR-4 `PascalCase` in PHP), what plain PHP templates need, and a type
  checker for PHP, Python and TypeScript.
- `why.md`: the cost that a screen is now two places.

### Example

- The home page is now a page folder, `delivery/web/courses/`, with its own
  code and template.
- A `Renderer` loads each feature's CSS when its view renders, replacing the
  hand-written stylesheet list.
- `arch_check.py` checks page folders and the delivery depth exception.
- `run_tests.py` runs mypy in strict mode over `src/` when it is installed.
  Two type errors it found are fixed.
- Three web tests and three new checker cases (34 tests, from 31).

### What the app taught

- **Unchecked rules drift.** The size limits were written down and never
  checked. About 2,000 lines of PHP ran past 100 characters, and nine files
  ran past 200 lines, the largest a 1,217-line global stylesheet.
- **A type checker earns its place on the first run.** It found a flash
  message printing an undefined variable and a redirect that could never run.
- **Templates that can run code need checks, not trust.** The one rendering
  regression was a template reading a field that no longer existed. Shaping
  values in typed feature code, and an integration test for that state,
  closed it.
- **Page-sized templates hide owners.** The first template pass gave each page
  one big template. Splitting it into feature views put each form's markup
  beside the code that handles it, and left the page template a short list of
  what is on the page.
- **Comments drift toward history.** Notes about the move ("replaces the old
  function", "phase 2") had to be removed once it was done.

## 0.1

First draft: five zones, seventeen rules, a Python example.
