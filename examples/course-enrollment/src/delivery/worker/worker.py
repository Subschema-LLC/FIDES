"""Delivery: dispatches saved messages after commit.

Each message goes to the feature that reacts to it. A handler that fails
leaves its message pending, so the next run retries it. The change that
recorded the message is never undone.
"""

from dataclasses import dataclass, field
from typing import Any, Callable

from shared.message_bus import MessageBus

Handler = Callable[[dict[str, Any]], None]


@dataclass(frozen=True)
class WorkReport:
    dispatched: int
    failures: list[str] = field(default_factory=list)


class Worker:
    def __init__(self, bus: MessageBus, handlers: dict[str, Handler]) -> None:
        self._bus = bus
        self._handlers = handlers

    def run(self) -> WorkReport:
        dispatched = 0
        failures = []
        for message in self._bus.pending():
            handler = self._handlers.get(message.name)
            try:
                if handler is not None:  # no handler means nobody needs to react
                    handler(message.payload)
            except Exception as error:  # keep going; this message stays pending
                failures.append(f"#{message.id} {message.name}: {error}")
                continue
            self._bus.mark_dispatched(message.id)
            dispatched += 1
        return WorkReport(dispatched, failures)
