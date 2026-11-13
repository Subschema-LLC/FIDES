#!/usr/bin/env python3
"""Runs the architecture check, then every test. Exit code 1 on any failure.

This is what the build runs. Run it before you finish any change.
"""

import sys
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parent
sys.path[:0] = [str(ROOT / "src"), str(ROOT / "tests"), str(ROOT)]

from arch_check import check  # noqa: E402

problems = check(ROOT / "src")
if problems:
    print("arch_check FAILED:\n  " + "\n  ".join(problems), file=sys.stderr)
    raise SystemExit(1)
print("arch_check OK")

suite = unittest.defaultTestLoader.discover(str(ROOT / "tests"), top_level_dir=str(ROOT / "tests"))
result = unittest.TextTestRunner(verbosity=1).run(suite)
raise SystemExit(0 if result.wasSuccessful() else 1)
