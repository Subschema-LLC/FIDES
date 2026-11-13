"""Contract: what this app needs in order to find students.

The entity states the need. An integration fulfills it. Nothing in the core
knows whether students live in SQLite, an API or a test fake.
"""

from typing import Protocol

from entities.student.student import Student


class StudentStore(Protocol):
    def find(self, student_id: int) -> Student | None: ...

    def all(self) -> list[Student]: ...
