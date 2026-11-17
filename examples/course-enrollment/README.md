# Example: course enrollment (Python)

A small FIDES app: students enroll in courses, drop them and transfer between
them. It uses only the Python standard library (3.11 or newer), so there is
nothing to install. Install mypy if you want `run_tests.py` to type-check
`src/` as well.

It exists to show every FIDES rule working in real code. Being in Python, it
also shows that the rules do not depend on any one language.

## Run it

```bash
python3 app.py courses                    # courses and seats
python3 app.py students                   # who can enroll
python3 app.py enroll 3 CS101             # Chen Wei takes the last CS101 seat
python3 app.py transfer 2 MATH120 ENG105  # two courses change in one UnitOfWork
python3 app.py work                       # send confirmation emails (to var/mail.log)
python3 app.py config Course              # each setting and where its value came from
python3 app.py serve                      # web app on http://127.0.0.1:8080
python3 run_tests.py                      # architecture check, type check, 35 tests
```

Delete `var/app.sqlite` to start over with the demo data.

## Map

```
AGENTS.md          # the rules, one page, for AI agents and humans
arch_check.py      # fails the build on rule violations (run by run_tests.py)
config/            # overrides only, one file per owner; belongs to whoever runs the app
  app.toml         #   overrides AppSettings
  Course.toml      #   overrides CourseSettings
src/
  shared/          # contracts no entity owns: UnitOfWork, Clock, Mailer, MessageBus
                   #   plus AppSettings (ownerless settings)
  entities/
    student/       # Student, Email (checks itself), StudentStore (contract)
    course/        # Course (owns its enrollments), Seats, Enrollment,
                   #   StudentEnrolled / StudentDropped, CourseSettings, CourseStore
  features/
    enroll/        # use case + view.html + option.html + enroll.css
    drop/          # one entity, one write
    transfer/      # two entities, so it asks for a UnitOfWork
    list_courses/  # read-only, returns plain rows for its view
    send_enrollment_confirmation/  # reacts to StudentEnrolled after commit
  integrations/
    database/      # SQLite stores, outbox, SqlUnitOfWork, demo seeder
    config/        # ConfigLoader: default, then file, then environment
    email/         # FileMailer (writes to var/mail.log)
    clock/         # SystemClock
  delivery/
    web/           # WSGI routes, layout, escape-by-default rendering
      courses/     #   one folder per page: courses.py (request) + courses.html (layout)
    cli/           # the same features from a terminal
    worker/        # dispatches saved messages after commit
    wiring.py      # the only file that knows integrations exist
tests/             # entities with no fakes, features with fakes, integrations with SQLite
```

## One change, start to finish: a transfer

1. `delivery/cli/commands.py` hands `transfer 2 MATH120 ENG105` to the
   Transfer feature.
2. Transfer checks `CourseSettings.transfers_open`, then asks its UnitOfWork
   to run the change, because two courses change at once.
3. Inside it: MATH120 drops the student and ENG105 enrolls them. Each Course
   checks its own rules (enrolled? full?) and records a message.
4. Each `CourseStore.save()` writes the course, its enrollments and its
   messages. Both saves join the UnitOfWork's transaction, so they commit
   together or not at all. A version check stops two people from taking the
   last seat at the same moment.
5. Later, `python3 app.py work` runs the worker. It hands StudentEnrolled to
   SendEnrollmentConfirmation, which emails the student. If the email fails,
   the message stays pending and the transfer stands.

## Where Python needed a decision

FIDES rules are the same in every language. These are the Python choices:

- **Contracts** are `typing.Protocol` classes. Integrations fulfill them by
  shape, without inheriting, which keeps the no-inheritance rule absolute.
- **Values** are frozen dataclasses that check themselves in `__post_init__`.
- **Privacy** is by convention (a leading underscore), because Python cannot
  enforce it. Rule 1 relies on review here, so it is written down plainly.
- **Views** use `string.Template`, which cannot loop or branch. Lists are
  rendered by repeating a row template in delivery. Because a template cannot
  run code, it cannot name an entity or render another feature's view, so
  those two rules need no check here.
- **Pages** are folders in `delivery/web/`. The `Renderer` renders feature
  views from their feature folders and remembers which features rendered, so
  each feature's CSS reaches the page without anyone listing it.
- **Config** is TOML because Python can read it with no dependencies. The
  format is a tool choice, not a FIDES rule.
- **The web server** is a plain WSGI function, so no framework class has to
  be subclassed.
