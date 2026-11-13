"""Value: an email address that checks itself.

If an Email exists, it is valid. No other code ever needs to re-check it.
"""

import re
from dataclasses import dataclass

_PATTERN = re.compile(r"[^@\s]+@[^@\s]+\.[^@\s]+")


@dataclass(frozen=True)
class Email:
    value: str

    def __post_init__(self) -> None:
        if not _PATTERN.fullmatch(self.value):
            raise ValueError(f"Not a valid email address: {self.value!r}")

    @staticmethod
    def parse(raw: str) -> "Email":
        return Email(raw.strip().lower())
