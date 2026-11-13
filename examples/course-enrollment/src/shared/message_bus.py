"""Contract: the dispatch side of the outbox.

Messages travel in three steps:
  1. RECORD:   an entity records what happened (e.g. StudentEnrolled).
  2. SAVE:     the entity's store saves those messages in the same write
               as the entity itself.
  3. DISPATCH: after commit, the worker reads them back through this
               contract and hands each one to the feature that reacts to it.

Nothing is ever published inline, so a failed email can never undo an
enrollment, and an enrollment that rolled back never sends an email.
"""

from dataclasses import dataclass
from typing import Any, Protocol


@dataclass(frozen=True)
class SavedMessage:
    id: int
    name: str
    payload: dict[str, Any]


class MessageBus(Protocol):
    def pending(self) -> list[SavedMessage]: ...

    def mark_dispatched(self, message_id: int) -> None: ...
