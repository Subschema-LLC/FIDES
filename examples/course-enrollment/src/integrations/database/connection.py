"""SQLite connection with one rule: a transaction inside a transaction joins it.

A store's save() opens a transaction. If a UnitOfWork already opened one, the
save joins it instead, so the UnitOfWork decides when everything commits.
"""

import sqlite3
from typing import Any, Callable, TypeVar

T = TypeVar("T")

SCHEMA = """
CREATE TABLE IF NOT EXISTS students (
    id      INTEGER PRIMARY KEY,
    name    TEXT    NOT NULL,
    email   TEXT    NOT NULL,
    active  INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS courses (
    code     TEXT    PRIMARY KEY,
    title    TEXT    NOT NULL,
    capacity INTEGER NOT NULL,
    version  INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS enrollments (
    course_code TEXT    NOT NULL REFERENCES courses(code),
    student_id  INTEGER NOT NULL REFERENCES students(id),
    enrolled_at TEXT    NOT NULL,
    PRIMARY KEY (course_code, student_id)
);
CREATE TABLE IF NOT EXISTS outbox (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT NOT NULL,
    payload       TEXT NOT NULL,
    created_at    TEXT NOT NULL,
    dispatched_at TEXT
);
"""


class Connection:
    def __init__(self, path: str) -> None:
        # isolation_level=None: this class decides where transactions begin and end.
        self._db = sqlite3.connect(path, isolation_level=None)
        self._db.row_factory = sqlite3.Row
        self._db.execute("PRAGMA foreign_keys = ON")
        self._db.executescript(SCHEMA)

    def execute(self, sql: str, params: tuple[Any, ...] = ()) -> sqlite3.Cursor:
        return self._db.execute(sql, params)

    def transaction(self, work: Callable[[], T]) -> T:
        if self._db.in_transaction:
            return work()
        self._db.execute("BEGIN IMMEDIATE")
        try:
            result = work()
        except BaseException:
            self._db.execute("ROLLBACK")
            raise
        self._db.execute("COMMIT")
        return result
