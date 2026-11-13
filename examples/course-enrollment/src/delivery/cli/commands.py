"""CLI delivery: the same features as the web, reached from a terminal.

Usage: python3 app.py <command> [arguments]

  courses                                list courses and seats
  students                               list students who can enroll
  enroll   <student_id> <course_code>    enroll a student
  drop     <student_id> <course_code>    drop a student from a course
  transfer <student_id> <from> <to>      move a student between courses
  work                                   send pending messages (confirmation emails)
  config   <app|Course>                  show effective settings and where each came from
  serve    [port]                        start the web app (default port 8080)
"""

import sys
from pathlib import Path
from typing import Any

from delivery.web.app import serve
from delivery.wiring import Wiring, build

USER_ERRORS = (LookupError, ValueError, RuntimeError)


def main(argv: list[str], root: Path) -> int:
    if not argv or argv[0] in ("-h", "--help", "help"):
        print(__doc__)
        return 0
    try:
        wiring = build(root)
    except ValueError as error:  # bad config stops startup, with a clear reason
        print(f"Startup failed: {error}", file=sys.stderr)
        return 2
    try:
        return _run(wiring, argv[0], argv[1:])
    except USER_ERRORS as error:
        print(f"Error: {error}", file=sys.stderr)
        return 1


def _run(wiring: Wiring, command: str, args: list[str]) -> int:
    if command == "courses":
        for row in wiring.list_courses.execute():
            print(f"{row.code:<8} {row.title:<24} {row.seats_taken}/{row.capacity} taken")
    elif command == "students":
        for choice in wiring.enroll.form().students:
            print(f"{choice.value:>3}  {choice.label}")
    elif command == "enroll" and len(args) == 2:
        wiring.enroll.execute(_whole_number(args[0]), args[1])
        print("Enrolled. Run `python3 app.py work` to send the confirmation email.")
    elif command == "drop" and len(args) == 2:
        wiring.drop.execute(_whole_number(args[0]), args[1])
        print("Dropped.")
    elif command == "transfer" and len(args) == 3:
        wiring.transfer.execute(_whole_number(args[0]), args[1], args[2])
        print("Transferred.")
    elif command == "work":
        report = wiring.worker.run()
        print(f"Dispatched {report.dispatched} message(s). Mail is in var/.")
        for failure in report.failures:
            print(f"Still pending: {failure}", file=sys.stderr)
    elif command == "config" and len(args) == 1 and args[0] in wiring.settings:
        _show_config(args[0], wiring.settings[args[0]].values, wiring.settings[args[0]].provenance)
    elif command == "serve" and len(args) <= 1:
        serve(wiring, _whole_number(args[0]) if args else 8080)
    else:
        print(__doc__, file=sys.stderr)
        return 2
    return 0


def _show_config(owner: str, values: dict[str, Any], provenance: dict[str, str]) -> None:
    print(f"{owner} settings (later wins: default, then config/{owner}.toml, then env)")
    for path, layer in sorted(provenance.items()):
        value: Any = values
        for part in path.split("."):
            value = value[part]
        print(f"  {path:<26} {value!s:<24} {layer}")


def _whole_number(text: str) -> int:
    if not text.strip().isdigit():
        raise ValueError(f"Expected a number, got {text!r}")
    return int(text)
