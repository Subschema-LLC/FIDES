"""Fulfills entities.course.course_store.CourseStore with SQLite.

save() writes the course, its enrollments and its recorded messages in one
transaction (or joins the UnitOfWork's transaction if one is open). The
version check makes the second of two simultaneous saves fail instead of
silently overwriting the first.
"""

from datetime import datetime
from sqlite3 import Row

from entities.course.course import Course
from entities.course.enrollment import Enrollment
from integrations.database.connection import Connection
from integrations.database.outbox import Outbox


class SqlCourseStore:
    def __init__(self, connection: Connection, outbox: Outbox) -> None:
        self._connection = connection
        self._outbox = outbox

    def find(self, code: str) -> Course | None:
        row = self._connection.execute(
            "SELECT * FROM courses WHERE code = ?", (code.strip().upper(),)
        ).fetchone()
        return self._hydrate(row) if row else None

    def all(self) -> list[Course]:
        rows = self._connection.execute("SELECT * FROM courses ORDER BY code").fetchall()
        return [self._hydrate(row) for row in rows]

    def count_for_student(self, student_id: int) -> int:
        row = self._connection.execute(
            "SELECT COUNT(*) AS n FROM enrollments WHERE student_id = ?", (student_id,)
        ).fetchone()
        return int(row["n"])

    def save(self, course: Course) -> None:
        def write() -> None:
            new_version = course.version + 1
            if course.version == 0:
                self._connection.execute(
                    "INSERT INTO courses (code, title, capacity, version) VALUES (?, ?, ?, ?)",
                    (course.code, course.title, course.seats.capacity, new_version),
                )
            else:
                updated = self._connection.execute(
                    "UPDATE courses SET title = ?, capacity = ?, version = ? "
                    "WHERE code = ? AND version = ?",
                    (course.title, course.seats.capacity, new_version, course.code, course.version),
                ).rowcount
                if updated == 0:
                    raise RuntimeError(
                        f"{course.code} was changed by someone else since it was read. Try again."
                    )
            self._connection.execute(
                "DELETE FROM enrollments WHERE course_code = ?", (course.code,)
            )
            for enrollment in course.enrollments:
                self._connection.execute(
                    "INSERT INTO enrollments (course_code, student_id, enrolled_at) "
                    "VALUES (?, ?, ?)",
                    (course.code, enrollment.student_id, enrollment.enrolled_at.isoformat()),
                )
            for message in course.pull_messages():
                self._outbox.append(message.name, message.payload())
            course.mark_saved(new_version)

        self._connection.transaction(write)

    def _hydrate(self, row: Row) -> Course:
        enrollment_rows = self._connection.execute(
            "SELECT student_id, enrolled_at FROM enrollments "
            "WHERE course_code = ? ORDER BY enrolled_at",
            (row["code"],),
        ).fetchall()
        enrollments = [
            Enrollment(r["student_id"], datetime.fromisoformat(r["enrolled_at"]))
            for r in enrollment_rows
        ]
        return Course(row["code"], row["title"], row["capacity"], enrollments, row["version"])
