"""Entity: a course section, and the rules about it that are true everywhere.

Ownership follows consistency. The number of seats taken must always match
the enrollments, so the Course owns its enrollments and both are saved in one
write. Students are referred to by id, never held as objects.
"""

from datetime import datetime

from entities.course.enrollment import Enrollment
from entities.course.messages import RecordedMessage, StudentDropped, StudentEnrolled
from entities.course.seats import Seats


class Course:
    def __init__(
        self,
        code: str,
        title: str,
        capacity: int,
        enrollments: list[Enrollment] | None = None,
        version: int = 0,
    ) -> None:
        if not code.strip():
            raise ValueError("A course needs a code")
        if not title.strip():
            raise ValueError("A course needs a title")
        self._code = code.strip().upper()
        self._title = title.strip()
        self._capacity = capacity
        self._enrollments = list(enrollments or [])
        self._version = version
        self._messages: list[RecordedMessage] = []
        Seats(capacity, len(self._enrollments))  # checks the starting state

    # Reading is open to everyone. Changing is only done through the actions below.

    @property
    def code(self) -> str:
        return self._code

    @property
    def title(self) -> str:
        return self._title

    @property
    def seats(self) -> Seats:
        return Seats(self._capacity, len(self._enrollments))

    @property
    def enrollments(self) -> tuple[Enrollment, ...]:
        return tuple(self._enrollments)

    @property
    def version(self) -> int:
        return self._version

    def has_student(self, student_id: int) -> bool:
        return any(e.student_id == student_id for e in self._enrollments)

    # Actions

    def enroll(self, student_id: int, now: datetime) -> None:
        if self.has_student(student_id):
            raise ValueError(f"Student {student_id} is already enrolled in {self._code}")
        if self.seats.is_full:
            raise ValueError(f"{self._code} is full")
        self._enrollments.append(Enrollment(student_id, now))
        self._messages.append(StudentEnrolled(self._code, student_id))

    def drop(self, student_id: int) -> None:
        if not self.has_student(student_id):
            raise LookupError(f"Student {student_id} is not enrolled in {self._code}")
        self._enrollments = [e for e in self._enrollments if e.student_id != student_id]
        self._messages.append(StudentDropped(self._code, student_id))

    def pull_messages(self) -> list[RecordedMessage]:
        """Hand recorded messages to the store, once."""
        messages, self._messages = self._messages, []
        return messages

    def mark_saved(self, version: int) -> None:
        """Called by the store after a successful write.

        The version stops two people from taking the last seat at the same
        moment: the second save sees a newer version and fails.
        """
        if version <= self._version:
            raise ValueError("A saved version must be newer than the current one")
        self._version = version
