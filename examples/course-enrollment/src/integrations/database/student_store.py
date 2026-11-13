"""Fulfills entities.student.student_store.StudentStore with SQLite."""

from sqlite3 import Row

from entities.student.email import Email
from entities.student.student import Student
from integrations.database.connection import Connection


class SqlStudentStore:
    def __init__(self, connection: Connection) -> None:
        self._connection = connection

    def find(self, student_id: int) -> Student | None:
        row = self._connection.execute(
            "SELECT * FROM students WHERE id = ?", (student_id,)
        ).fetchone()
        return _hydrate(row) if row else None

    def all(self) -> list[Student]:
        rows = self._connection.execute("SELECT * FROM students ORDER BY name").fetchall()
        return [_hydrate(row) for row in rows]


def _hydrate(row: Row) -> Student:
    return Student(row["id"], row["name"], Email.parse(row["email"]), bool(row["active"]))
