"""Feature: list every course with its seats.

A read-only use case. It returns plain rows shaped for this feature's view,
so the view only displays values and never touches an entity.
"""

from dataclasses import dataclass

from entities.course.course_store import CourseStore


@dataclass(frozen=True)
class CourseRow:
    code: str
    title: str
    seats_taken: int
    capacity: int
    seats_left: int
    availability: str  # "open" or "full"; the view uses it as a CSS class


class ListCourses:
    def __init__(self, courses: CourseStore) -> None:
        self._courses = courses

    def execute(self) -> list[CourseRow]:
        rows = []
        for course in self._courses.all():
            seats = course.seats
            rows.append(
                CourseRow(
                    code=course.code,
                    title=course.title,
                    seats_taken=seats.taken,
                    capacity=seats.capacity,
                    seats_left=seats.left,
                    availability="full" if seats.is_full else "open",
                )
            )
        return rows
