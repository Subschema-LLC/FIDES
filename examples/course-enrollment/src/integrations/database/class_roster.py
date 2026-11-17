"""Fulfills shared.class_roster.class_roster.ClassRoster with one SQLite join."""

from datetime import datetime

from integrations.database.connection import Connection
from shared.class_roster.class_roster import RosterRow

QUERY = """
SELECT c.code, c.title, s.id, s.name, e.enrolled_at
FROM enrollments e
JOIN courses c ON c.code = e.course_code
JOIN students s ON s.id = e.student_id
ORDER BY c.code, s.name
"""


class SqlClassRoster:
    def __init__(self, connection: Connection) -> None:
        self._connection = connection

    def rows(self) -> list[RosterRow]:
        return [
            RosterRow(row["code"], row["title"], row["id"], row["name"],
                      datetime.fromisoformat(row["enrolled_at"]))
            for row in self._connection.execute(QUERY).fetchall()
        ]
