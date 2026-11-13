"""In-memory stand-ins for the contracts. Features are tested with these.

Each one fulfills a contract structurally, without inheriting from it.
"""

from datetime import datetime, timezone
from typing import Callable, TypeVar

from entities.course.course import Course
from entities.student.student import Student

T = TypeVar("T")


class FakeCourseStore:
    def __init__(self, *courses: Course) -> None:
        self.courses = {c.code: c for c in courses}
        self.saved: list[str] = []
        self.messages: list[str] = []

    def find(self, code: str) -> Course | None:
        return self.courses.get(code.strip().upper())

    def all(self) -> list[Course]:
        return sorted(self.courses.values(), key=lambda c: c.code)

    def count_for_student(self, student_id: int) -> int:
        return sum(1 for c in self.courses.values() if c.has_student(student_id))

    def save(self, course: Course) -> None:
        self.messages += [m.name for m in course.pull_messages()]
        course.mark_saved(course.version + 1)
        self.courses[course.code] = course
        self.saved.append(course.code)


class FakeStudentStore:
    def __init__(self, *students: Student) -> None:
        self.students = {s.id: s for s in students}

    def find(self, student_id: int) -> Student | None:
        return self.students.get(student_id)

    def all(self) -> list[Student]:
        return list(self.students.values())


class FixedClock:
    def __init__(self, at: datetime = datetime(2026, 11, 13, 9, 0, tzinfo=timezone.utc)) -> None:
        self.at = at

    def now(self) -> datetime:
        return self.at


class RunNowUnitOfWork:
    def __init__(self) -> None:
        self.runs = 0

    def run(self, work: Callable[[], T]) -> T:
        self.runs += 1
        return work()


class RecordingMailer:
    def __init__(self) -> None:
        self.sent: list[tuple[str, str]] = []

    def send(self, to: str, subject: str, body: str) -> None:
        self.sent.append((to, subject))
