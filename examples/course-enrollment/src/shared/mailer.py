"""Contract: send one email. Who it is from is the integration's business."""

from typing import Protocol


class Mailer(Protocol):
    def send(self, to: str, subject: str, body: str) -> None: ...
