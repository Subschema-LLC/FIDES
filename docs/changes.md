# Changes and messages

How FIDES keeps data correct when something changes, and how it reacts to
changes without tangling side effects into them.

## One write by default

Most use cases change one entity. Its store's `save()` is the whole
transaction: the entity, its children and its recorded messages are written
together or not at all.

```
Drop(student, course):
  course = courses.find(course)
  course.drop(student)        # the Course checks its own rules
  courses.save(course)        # course + enrollments + StudentDropped, one write
```

Before reaching for a transaction across entities, ask whether one entity
should own the data instead (rule 2, ownership follows consistency). A Course
owns its enrollments, so enrolling is one write, not two.

## The visible exception: UnitOfWork

When a use case truly changes two entities, the feature asks for a
`UnitOfWork` in its constructor and runs the change inside it:

```
Transfer(student, from, to):
  work.run:
    source = courses.find(from)
    target = courses.find(to)
    source.drop(student)
    target.enroll(student, clock.now())
    courses.save(source)      # joins the open transaction
    courses.save(target)      # joins the open transaction
```

Stores never start or commit a transaction when one is already open; they
join it. The UnitOfWork decides when everything commits.

Because the UnitOfWork appears in the constructor, anyone reading the feature
sees at a glance that it is the exception.

## Messages: record, save, dispatch

```
1. RECORD    the entity notes what happened       course.enroll() -> StudentEnrolled
2. SAVE      the store writes it with the change  same transaction, outbox table
3. DISPATCH  after commit, a worker hands it on   SendEnrollmentConfirmation.handle()
```

Never publish a message, send an email or call another system from inside a
change. If the change rolls back, the email has already gone. If the email
fails, it must not roll back the change.

The outbox (a table, or whatever your store uses) holds saved messages until
the worker dispatches them. The worker is a delivery: it reaches the app from
outside, on a schedule or a loop.

### Failures and retries

- A handler that fails leaves its message pending. The next run retries it.
  The change that recorded it stands.
- A message can be delivered more than once (for example, after a crash
  between handling it and marking it done). **Every handler must be safe to
  run twice.** Check whether the work is already done, or make doing it twice
  harmless.
- A handler should re-read current state rather than trust the message
  blindly. The student may have dropped the course before the confirmation
  went out.

### Who reacts

A message is named for what happened (`StudentEnrolled`), not for what should
happen next (`SendEmail`). Any number of features can react to it, and the
feature that recorded it never knows who they are. This is how features avoid
calling each other (rule 5).

## Concurrency: guard the last seat

A transaction alone does not stop two people from reading "one seat left" at
the same moment and both enrolling. FIDES stores guard against this with a
version number:

1. The entity carries the version it was read at.
2. The store saves only if the stored version still matches, and bumps it.
3. If someone else saved first, the second save fails with a clear error and
   the caller can retry.

Other mechanisms (row locks, unique constraints) are fine. The rule is that
the store, not the feature, makes simultaneous changes safe.

## Testing changes

- **Entities** are tested directly, with no fakes: call an action, check the
  state and the recorded messages.
- **Features** are tested with in-memory fakes for their contracts, including
  a UnitOfWork that just runs the work.
- **Integrations** are tested against a real (often in-memory) database,
  including a rollback test: start a UnitOfWork, save, raise, and check that
  nothing was written.
