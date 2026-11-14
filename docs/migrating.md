# Moving an existing app to FIDES

Most apps that adopt FIDES already exist and already work. The job is to
change the structure and nothing else, and to be able to show that nothing
else changed. "The tests pass" is not enough: an app worth moving usually has
behaviour no test covers.

This page comes from moving a production PHP app onto FIDES: about 30
features, 16 web pages and 46 form actions, compared against the running
version at every step.

## Keep a reference

- Keep the current version runnable for the whole move. Take a snapshot of
  its database.
- Build the FIDES version beside it (a branch or a new tree) and point both at
  copies of the same snapshot.
- Restore the snapshot before every comparison that writes, so both versions
  start from the same data.

## Add the checks first

Put the architecture check, the type checker and the size limits into the
build before moving any code. The architecture check will fail at first; its
output is the to-do list. Adding them late is more expensive: the production
app added its static analyser partway through and the first run found real
bugs, and it never checked the size limits, so they were never kept.

## Prove nothing changed

Four comparisons, each automated, each run against both versions on the same
snapshot:

| Check | How | Catches |
|---|---|---|
| Pages | Fetch every page from both versions, collapse runs of whitespace, diff the HTML | Missing fields, wrong values, changed markup |
| Writes | For each form: restore the snapshot, post the same input to each version, dump the database, diff | A write that changed, went missing or happened twice |
| Looks | In a headless browser, load every page at a desktop and a phone width, record each element's computed style, diff | CSS that moved and now wins or loses a fight it did not before |
| Scripts | Drive each script (click, type, choose) and compare the page and the requests it sends | Behaviour that broke when scripts were split up |

Collapse whitespace before comparing HTML. Moving markup into templates
changes indentation and nothing else, and those differences bury the real
ones.

## Reach every branch

A snapshot shows the states the app is usually in. The bugs are in the states
it is rarely in: an overdue invoice, a failed sync, an item waiting on
someone. Write small scripts that put the data into each rare state, and run
the comparisons again for each one.

The one regression that slipped into the production app's move sat in a
state its base data never reached. Every comparison passed until a variant
put an item in that state.

When a variant finds something, turn it into an integration test. The
comparisons are scaffolding for the move; the tests are what stays.

## Split one big stylesheet carefully

Older apps often have one global stylesheet. FIDES puts each rule with the
markup it styles, which changes the order rules load in.

- **Find where each rule applies.** Load every page and every variant in a
  real browser and record which selectors match.
- **Move each rule to the owner of its markup.** A feature's view gets the
  feature's CSS. The page template gets the page's CSS. Rules for the frame,
  or matched on many pages by shared markup, stay in the layout.
- **Watch the cascade.** Feature files load after the layout. A rule that used
  to come earlier and lose can now come later and win. In the production app,
  a rule for bulk actions moved into a feature file and started overriding the
  action bar it had always lost to.
- **Delete what never wins.** A rule that is overridden on every page where it
  matches is dead code. Remove it instead of moving it.
- **Clean up the split.** Look for empty media queries and selectors that no
  longer match anything.
- **Compare looks after each batch of moves,** not once at the end.

## Name every difference

Some differences are fixes. In the production app, options with the same name
had been listed in whatever order the database returned them; the new version
sorts them by id. Decide each difference on purpose and list it in the commit
or pull request. A difference nobody named is a regression.

## Clean up after the move

- Delete comments about the move itself ("replaces the old `handleEdit()`",
  "phase 2", "kept for parity"). They help during the move and mislead after
  it. Comments say what is true now (see [ai.md](ai.md#prompting-tips)).
- Keep the integration tests the variants produced.
- Keep the architecture check and the type checker in the build.
