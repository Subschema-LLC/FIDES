# Comments and decisions

Code says what happens. Names say what each part is. Comments say what the
code cannot: why. FIDES gives every rule one owner, so it can give every
explanation one owner too. The reason for a rule sits beside the rule.

**The test:** open any one file and learn what it does and why, without
opening another.

## Where each explanation goes

| You need to explain... | Put it in |
|---|---|
| What a unit does | its name, then the header at the top of its file |
| Why a rule is the way it is | the header or a comment in the rule's owner |
| Why a line that looks wrong is right | a comment on that line |
| What a contract promises beyond its types | the contract's header |
| How a behavior looks with real values | a test named as a sentence |
| A decision no unit in `src/` owns | a record in `docs/decisions/` |
| What changed, and why | the commit message |

Most explanations have an owner in `src/`, because most rules do. A rule that
is true everywhere lives in an entity, a rule across two entities lives in a
feature, and a setting's rules live in its owner's settings. The reason lives
in the same place.

## The header

Every code file in `src/` opens with a short comment in the language's
documentation form (a docstring, `/** */`, `///`), so editors, doc tools and
agents show it first. **(checkable)**

- **The first sentence says what the unit does.** Someone who sees only that
  line, in a file list, a search result or an editor tooltip, still knows.
- **Then the reasons the code cannot show:** which rule it keeps, what it
  leaves out on purpose, what it promises its callers.
- **In a feature folder, the file named for the folder speaks for it.**
  Views, CSS and JS get a comment only when something in them would surprise
  a reader.

From the example app:

```python
"""Feature: move a student from one course to another.

Two courses change, so this is the one feature that asks for a UnitOfWork.
The drop and the enroll commit together or not at all: a student is never
left in neither course, or in both.

The course limit does not apply here, because a transfer keeps the
student's number of courses the same.
"""
```

The last paragraph does the most work. Without it, a careful reader or an
agent adds the course limit to "fix" the transfer, and breaks it.

A header that needs more than a short paragraph or two is usually describing
two concepts. Split the unit and give each part its own header.

A feature folder gets no README. It would say what the header says, in a
second place, and the two would drift apart.

## Comments in the code

- **Say why, not what.** A comment that repeats the line goes. If a line
  needs a comment to be understood at all, rename something first.

  ```python
  # Repeats the code. Delete it.
  count += 1  # add one to count

  # Says what the code cannot. Keep it.
  Seats(capacity, len(self._enrollments))  # checks the starting state
  ```

- **Explain anything that looks like a mistake and is not:** a check that is
  skipped, an unusual order, a value left unescaped on purpose. Agents "fix"
  what looks wrong. A reason on the line is what stops them.
- **Say what is true now.** "Now uses X", "moved from the old routes file"
  and "phase 2" are history, and history goes in the commit message
  ([ai.md](ai.md#prompting-tips)).
- **No commented-out code.** Version control keeps every old line.
- **A known gap names the issue that owns it:** `TODO(#142): add a waitlist`.
  A TODO with no issue is a promise nobody keeps. The checker can check the
  form, not whether the issue is still open. **(checkable)**

## Decisions no unit owns

A few decisions belong to no entity, feature or setting: why the app runs on
SQLite, why records are never soft-deleted, why a framework convention was
kept. Each gets one short file in `docs/decisions/`, named for the decision,
in the project's casing:

```
docs/decisions/
  no_soft_deletes.md
  sqlite_until_second_server.md
```

```markdown
# No soft deletes

Deleting a record removes it. No table has a `deleted_at` column.

**Why:** every query would need a filter, and the first one that forgets it
shows deleted data. The audit log keeps what was removed and by whom.

**Costs:** undoing a delete needs the audit log, not a flag.

**Revisit when:** people need to restore deleted records themselves.
```

Code that depends on a decision says so in a few words, then names it:

```python
# Removes the row for good (Decision: no_soft_deletes).
```

- **A reference adds to a comment and never replaces it.** The line still
  says what is true here. The record holds the full case for whoever wants
  it.
- **Name it, never number it.** `see note 47` tells the reader nothing until
  they open another file, and the number outlives the note.
- **Every reference resolves.** The checker fails the build when a comment
  names a decision, or a file in `docs/`, that does not exist.
  **(checkable)**
- **A record says what is true now.** When a decision changes, edit or
  delete the record and the comments that name it. The commit message keeps
  the history.
- **A decision about one unit goes in that unit's header,** not here.

Keeping only what is true now is where these records differ from classic
architecture decision records (ADRs), which keep every decision ever made,
the superseded ones included. A
superseded record reads exactly like a current one, so an agent that finds
forty of them has to work out which still hold. Version control already keeps
the history.

## Contracts and tests

- **A contract's names and types are most of its documentation.** Its header
  adds what the types cannot say, such as what one call writes together and
  when a save fails (see the example's `CourseStore`).
- **Tests are examples that fail when they go stale.** Name each one as a
  sentence about behavior, like `test_cannot_enroll_in_a_full_course`, so the
  list of test names reads as a list of rules.

## Why this suits agents

- **An agent opens a file, not a folder of docs.** The header arrives with
  the code, so the reasons are in context without a search.
- **Agents copy what they find.** When the comments around them explain why,
  theirs will. When they narrate history, theirs will too.
- **Agents "fix" what looks wrong.** A reason on the line costs one sentence
  and saves a regression.
- **Pointers cost context.** Every reference an agent follows is one more
  file read, so most get skipped. A sentence in place is read every time.
- **Overviews help less than people expect.** A 2026 study found that
  repository context files such as `AGENTS.md` did not raise agents' success
  on real tasks and added over 20% to their cost, and that repository
  overviews in them did not help. Where the files did help was in stating
  non-standard practices
  ([Gloaguen et al.](https://arxiv.org/abs/2602.11988)). FIDES keeps
  `AGENTS.md` to its rules, which are exactly that, and puts every
  explanation next to the code it explains.

## What a machine can check

| Part | Checked by |
|---|---|
| Every code file in `src/` opens with a header | the checker |
| Every decision or `docs/` file a comment names exists | the checker |
| Every TODO names an issue | the checker |
| A comment says why, not what | review |
| A comment says what is true now | review |

The example's `arch_check.py` does the first three in about twenty lines.

Before the check, the example itself showed why it matters: five zone files
said "See docs/02-zones.md", a file that does not exist. Nothing failed, so
nobody noticed. The check now catches all five, and the zone files say what
their zone may import, which needs no other file.
