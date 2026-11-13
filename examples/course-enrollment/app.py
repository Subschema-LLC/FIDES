#!/usr/bin/env python3
"""Entry point. Puts src/ on the import path and hands over to CLI delivery.

Run `python3 app.py help` for the commands.
"""

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent
sys.path.insert(0, str(ROOT / "src"))

from delivery.cli.commands import main  # noqa: E402  (needs src/ on the path first)

if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:], ROOT))
