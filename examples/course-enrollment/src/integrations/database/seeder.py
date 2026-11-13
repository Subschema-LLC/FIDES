"""Demo data, written on first run only.

The seeder writes rows directly, which skips the entities' rules. That is
acceptable for fixed demo data and nowhere else. Delete var/app.sqlite to
start over.
"""

from integrations.database.connection import Connection

STUDENTS = [
    (1, "Ana Ruiz", "ana.ruiz@example.edu", 1),
    (2, "Ben Okafor", "ben.okafor@example.edu", 1),
    (3, "Chen Wei", "chen.wei@example.edu", 1),
    (4, "Dana Lopez", "dana.lopez@example.edu", 0),
]

COURSES = [
    ("CS101", "Intro to Programming", 3),
    ("ENG105", "College Composition", 25),
    ("MATH120", "College Algebra", 2),
]

ENROLLMENTS = [
    ("CS101", 1),
    ("CS101", 2),
    ("MATH120", 2),
]


def seed_if_empty(connection: Connection) -> None:
    if connection.execute("SELECT COUNT(*) AS n FROM students").fetchone()["n"]:
        return

    def write() -> None:
        for student in STUDENTS:
            connection.execute(
                "INSERT INTO students (id, name, email, active) VALUES (?, ?, ?, ?)", student
            )
        for code, title, capacity in COURSES:
            connection.execute(
                "INSERT INTO courses (code, title, capacity, version) VALUES (?, ?, ?, 1)",
                (code, title, capacity),
            )
        for code, student_id in ENROLLMENTS:
            connection.execute(
                "INSERT INTO enrollments (course_code, student_id, enrolled_at) VALUES (?, ?, ?)",
                (code, student_id, "2026-08-24T09:00:00+00:00"),
            )

    connection.transaction(write)
