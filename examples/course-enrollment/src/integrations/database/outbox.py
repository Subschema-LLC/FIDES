"""Both sides of the outbox table.

append() is used by stores, inside their own write, so a message is saved if
and only if the change that caused it is saved.

pending() and mark_dispatched() fulfill shared.message_bus.MessageBus for the
worker, which dispatches messages after commit.
"""

import json
from datetime import datetime, timezone
from typing import Any

from integrations.database.connection import Connection
from shared.message_bus import SavedMessage


class Outbox:
    def __init__(self, connection: Connection) -> None:
        self._connection = connection

    def append(self, name: str, payload: dict[str, Any]) -> None:
        self._connection.execute(
            "INSERT INTO outbox (name, payload, created_at) VALUES (?, ?, ?)",
            (name, json.dumps(payload), _now()),
        )

    def pending(self) -> list[SavedMessage]:
        rows = self._connection.execute(
            "SELECT id, name, payload FROM outbox WHERE dispatched_at IS NULL ORDER BY id"
        ).fetchall()
        return [SavedMessage(row["id"], row["name"], json.loads(row["payload"])) for row in rows]

    def mark_dispatched(self, message_id: int) -> None:
        self._connection.execute(
            "UPDATE outbox SET dispatched_at = ? WHERE id = ?", (_now(), message_id)
        )


def _now() -> str:
    return datetime.now(timezone.utc).isoformat()
