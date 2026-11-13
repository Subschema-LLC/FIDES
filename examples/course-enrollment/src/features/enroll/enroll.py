"""Feature: enroll one student in one course.

Read it top to bottom. This file is the whole use case.

Cross-entity rules (is the student active? are they already at their course
limit?) live here, in the feature, because no single entity owns them. The
Course only ever learns the student's id.
"""

from dataclasses import dataclass

from entities.course.course_settings import CourseSettings
from entities.course.course_store import CourseStore
from entities.student.student_store import StudentStore
from shared.clock import Clock


@dataclass(frozen=True)
class Choice:
    value: str
    label: str


@dataclass(frozen=True)
class EnrollForm:
    students: list[Choice]
    courses: list[Choice]


class Enroll:
    def __init__(
        self,
        students: StudentStore,
        courses: CourseStore,
        settings: CourseSettings,
        clock: Clock,
    ) -> None:
        self._students = students
        self._courses = courses
        self._settings = settings
        self._clock = clock

    def execute(self, student_id: int, course_code: str) -> None:
        student = self._students.find(student_id)
        if student is None:
            raise LookupError(f"No student with id {student_id}")
        course = self._courses.find(course_code)
        if course is None:
            raise LookupError(f"No course {course_code}")

        if not student.is_active:
            raise ValueError(f"{student.name} is not an active student")
        limit = self._settings.max_courses_per_student
        if self._courses.count_for_student(student.id) >= limit:
            raise ValueError(f"{student.name} already has {limit} courses, the most allowed")

        course.enroll(student.id, self._clock.now())
        self._courses.save(course)  # one entity changed, so one write is enough

    def form(self) -> EnrollForm:
        """Plain values for this feature's view. The view never sees an entity."""
        students = [Choice(str(s.id), s.name) for s in self._students.all() if s.is_active]
        courses = [
            Choice(c.code, f"{c.code}: {c.title} ({c.seats.left} left)")
            for c in self._courses.all()
            if not c.seats.is_full
        ]
        return EnrollForm(students, courses)
