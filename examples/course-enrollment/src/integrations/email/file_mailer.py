"""Fulfills shared.mailer.Mailer by appending to a log file instead of sending.

A real app would swap in an SMTP or API mailer here, in wiring, without any
feature noticing.
"""

from pathlib import Path


class FileMailer:
    def __init__(self, log_path: Path, sender: str) -> None:
        self._log_path = log_path
        self._sender = sender

    def send(self, to: str, subject: str, body: str) -> None:
        with self._log_path.open("a", encoding="utf-8") as log:
            log.write(f"From: {self._sender}\nTo: {to}\nSubject: {subject}\n\n{body}\n{'-' * 40}\n")
