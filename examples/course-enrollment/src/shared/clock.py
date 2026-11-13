"""Contract: the current time.

Features ask the Clock and pass the time into entities. Entities never read a
clock themselves, which keeps them deterministic and easy to test.
"""

from datetime import datetime
from typing import Protocol


class Clock(Protocol):
    def now(self) -> datetime: ...
