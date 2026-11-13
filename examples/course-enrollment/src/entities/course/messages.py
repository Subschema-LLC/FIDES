"""Messages a Course records about what happened to it.

The Course records them. CourseStore saves them in the same write as the
course. The worker dispatches them after commit. Features that react to a
message (like SendEnrollmentConfirmation) never call the feature that caused it.
"""

from dataclasses import dataclass
from typing import Any, ClassVar, Protocol


class RecordedMessage(Protocol):
    name: ClassVar[str]

    def payload(self) -> dict[str, Any]: ...


@dataclass(frozen=True)
class StudentEnrolled:
    name: ClassVar[str] = "StudentEnrolled"
    course_code: str
    student_id: int

    def payload(self) -> dict[str, Any]:
        return {"course_code": self.course_code, "student_id": self.student_id}


@dataclass(frozen=True)
class StudentDropped:
    name: ClassVar[str] = "StudentDropped"
    course_code: str
    student_id: int

    def payload(self) -> dict[str, Any]:
        return {"course_code": self.course_code, "student_id": self.student_id}
