"""Feature: email a student once they are enrolled.

It reacts to the StudentEnrolled message. The worker calls it after the
enrollment has committed, so a failed email never undoes an enrollment.

The worker can deliver the same message twice (for example after a crash
between sending and marking it dispatched), so a handler must be safe to
run twice. A duplicate confirmation email is acceptable here. For anything
that is not, check first and skip.
"""

from typing import Any

from entities.course.course_store import CourseStore
from entities.student.student_store import StudentStore
from shared.mailer import Mailer


class SendEnrollmentConfirmation:
    def __init__(self, students: StudentStore, courses: CourseStore, mailer: Mailer) -> None:
        self._students = students
        self._courses = courses
        self._mailer = mailer

    def handle(self, payload: dict[str, Any]) -> None:
        student = self._students.find(int(payload["student_id"]))
        course = self._courses.find(str(payload["course_code"]))
        if student is None or course is None or not course.has_student(student.id):
            return  # they dropped before we got here, so there is nothing to confirm

        self._mailer.send(
            to=student.email.value,
            subject=f"You're enrolled in {course.code}",
            body=(
                f"Hi {student.name},\n\n"
                f"You are enrolled in {course.code}: {course.title}.\n"
                f"Seats left in the course: {course.seats.left}.\n"
            ),
        )
