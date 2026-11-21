# Standard library only

This example uses nothing outside Python's standard library (3.11 or newer).

**Why:** it runs anywhere `python3` runs, with nothing to install, so the
FIDES rules are the only new thing a reader meets.

**Costs:** `string.Template` instead of a template engine, the `wsgiref`
development server instead of a production one, and a mailer that writes to
`var/mail.log`. The type check runs only where mypy is installed.

**Revisit when:** never for this example. A real app picks its own tools
(rule 17); only the rules carry over.
