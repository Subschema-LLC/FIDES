"""Feature: drop a student from a course.

One entity changes, so one store save is the whole transaction: the course,
its enrollments and the StudentDropped message are written together.
"""

from entities.course.course_store import CourseStore


class Drop:
    def __init__(self, courses: CourseStore) -> None:
        self._courses = courses

    def execute(self, student_id: int, course_code: str) -> None:
        course = self._courses.find(course_code)
        if course is None:
            raise LookupError(f"No course {course_code}")
        course.drop(student_id)
        self._courses.save(course)
