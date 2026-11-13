"""Contract: run several writes as one all-or-nothing change.

Most features never need this. A single store save already wraps one entity,
its children and its messages in one write. Ask for a UnitOfWork only when a
feature changes two entities at once, so the exception is visible in the
feature's constructor.
"""

from typing import Callable, Protocol, TypeVar

T = TypeVar("T")


class UnitOfWork(Protocol):
    def run(self, work: Callable[[], T]) -> T:
        """Run `work`. Commit if it returns, roll everything back if it raises."""
        ...
