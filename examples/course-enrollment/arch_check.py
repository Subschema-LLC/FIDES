#!/usr/bin/env python3
"""Checks the FIDES rules that a machine can check. Exit code 1 on any violation.

Run it in the build so a person or an AI agent cannot quietly break a rule.

  Imports      shared        -> shared
               entities/X    -> entities/X only (entities never import each other)
               features/X    -> entities, shared, features/X
               integrations/X-> entities, shared, integrations/X
               delivery      -> features, shared, delivery (wiring.py may import anything)
  Core is pure entities, features and shared never import I/O modules or read the clock
  No inheritance  classes may only subclass Protocol (a contract)
  Spread       at most zone/owner/file deep, 100-character lines, 200-line files
"""

import ast
import sys
from pathlib import Path

ZONES = ("shared", "entities", "features", "integrations", "delivery")
CORE = ("shared", "entities", "features")
IO_MODULES = {"os", "sys", "io", "pathlib", "shutil", "subprocess", "socket", "sqlite3",
              "http", "urllib", "smtplib", "tomllib", "wsgiref", "requests"}
CLOCK_CALLS = ("datetime.now", "datetime.utcnow", "datetime.today", "date.today",
               "time.time", "time.time_ns", "time.monotonic")
MAX_LINE, MAX_LINES, MAX_DEPTH = 100, 200, 3


def check(src: Path) -> list[str]:
    problems = []
    for path in sorted(src.rglob("*")):
        if path.is_file() and path.suffix in (".py", ".html", ".css"):
            problems += check_spread(src, path)
            if path.suffix == ".py":
                problems += check_python(src, path)
    return problems


def check_spread(src: Path, path: Path) -> list[str]:
    rel = path.relative_to(src)
    problems = []
    if len(rel.parts) > MAX_DEPTH:
        problems.append(f"{rel}: deeper than zone/owner/file")
    lines = path.read_text(encoding="utf-8").splitlines()
    if len(lines) > MAX_LINES:
        problems.append(f"{rel}: {len(lines)} lines (max {MAX_LINES}); a concept is hiding in it")
    for number, line in enumerate(lines, start=1):
        if len(line) > MAX_LINE:
            problems.append(f"{rel}:{number}: {len(line)} characters (max {MAX_LINE})")
    return problems


def check_python(src: Path, path: Path) -> list[str]:
    rel = path.relative_to(src)
    zone = rel.parts[0]
    owner = rel.parts[1] if len(rel.parts) > 2 else ""
    tree = ast.parse(path.read_text(encoding="utf-8"), filename=str(rel))
    problems = []
    for node in ast.walk(tree):
        if isinstance(node, ast.ImportFrom) and node.level > 0:
            problems.append(f"{rel}:{node.lineno}: relative import; use the full path")
        for module in imported_modules(node):
            reason = import_problem(zone, owner, rel, module)
            if reason:
                problems.append(f"{rel}:{node.lineno}: imports {module}, but {reason}")
        if isinstance(node, ast.ClassDef) and not all(is_protocol(b) for b in node.bases):
            problems.append(f"{rel}:{node.lineno}: {node.name} inherits; compose instead")
        if zone in CORE and isinstance(node, ast.Call) and reads_clock(node):
            problems.append(f"{rel}:{node.lineno}: reads the clock; pass the time in")
    return problems


def imported_modules(node: ast.AST) -> list[str]:
    if isinstance(node, ast.Import):
        return [alias.name for alias in node.names]
    if isinstance(node, ast.ImportFrom) and node.level == 0 and node.module:
        return [node.module]
    return []


def import_problem(zone: str, owner: str, rel: Path, module: str) -> str:
    parts = module.split(".")
    target, target_owner = parts[0], (parts[1] if len(parts) > 1 else "")
    if target not in ZONES:
        if zone in CORE and target in IO_MODULES:
            return f"{zone} must not touch the outside world"
        return ""
    own_folder = target == zone and target_owner == owner
    if zone == "shared":
        allowed = target == "shared"
    elif zone == "entities":
        allowed = own_folder
    elif zone in ("features", "integrations"):
        allowed = target in ("entities", "shared") or own_folder
    else:  # delivery
        is_wiring = rel.as_posix() == "delivery/wiring.py"
        allowed = is_wiring or target in ("features", "shared", "delivery")
    return "" if allowed else f"{zone}/{owner or rel.name} may not import from {target}"


def is_protocol(base: ast.expr) -> bool:
    if isinstance(base, ast.Subscript):
        base = base.value
    return isinstance(base, ast.Name) and base.id == "Protocol"


def reads_clock(call: ast.Call) -> bool:
    return dotted_name(call.func).endswith(CLOCK_CALLS)


def dotted_name(node: ast.expr) -> str:
    if isinstance(node, ast.Name):
        return node.id
    if isinstance(node, ast.Attribute):
        return f"{dotted_name(node.value)}.{node.attr}"
    return ""


if __name__ == "__main__":
    root = Path(__file__).resolve().parent
    found = check(root / "src")
    if found:
        print("arch_check FAILED:\n  " + "\n  ".join(found), file=sys.stderr)
        raise SystemExit(1)
    print("arch_check OK: imports, purity, no inheritance, spread")
