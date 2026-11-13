"""Feature: move a student from one course to another.

Two courses change, so this is the one feature that asks for a UnitOfWork.
The drop and the enroll commit together or not at all: a student is never
left in neither course, or in both.

The course limit does not apply here, because a transfer keeps the
student's number of courses the same.
"""

from entities.course.course import Course
from entities.course.course_settings import CourseSettings
from entities.course.course_store import CourseStore
from shared.clock import Clock
from shared.unit_of_work import UnitOfWork


class Transfer:
    def __init__(
        self,
        courses: CourseStore,
        settings: CourseSettings,
        unit_of_work: UnitOfWork,
        clock: Clock,
    ) -> None:
        self._courses = courses
        self._settings = settings
        self._unit_of_work = unit_of_work
        self._clock = clock

    def execute(self, student_id: int, from_code: str, to_code: str) -> None:
        if not self._settings.transfers_open:
            raise ValueError("Transfers are closed right now")

        def work() -> None:
            source = self._find(from_code)
            target = self._find(to_code)
            if source.code == target.code:
                raise ValueError("Pick two different courses")
            source.drop(student_id)
            target.enroll(student_id, self._clock.now())
            self._courses.save(source)
            self._courses.save(target)

        self._unit_of_work.run(work)

    def _find(self, code: str) -> Course:
        course = self._courses.find(code)
        if course is None:
            raise LookupError(f"No course {code}")
        return course
