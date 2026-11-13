"""Contract: what this app needs in order to find and save courses.

save() writes the course, its enrollments and its recorded messages together,
in one transaction. It fails if someone else changed the course since it was
read (see Course.mark_saved).
"""

from typing import Protocol

from entities.course.course import Course


class CourseStore(Protocol):
    def find(self, code: str) -> Course | None: ...

    def all(self) -> list[Course]: ...

    def count_for_student(self, student_id: int) -> int: ...

    def save(self, course: Course) -> None: ...
