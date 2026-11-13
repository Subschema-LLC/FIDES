"""Value: one student in one course. It only exists inside its Course."""

from dataclasses import dataclass
from datetime import datetime


@dataclass(frozen=True)
class Enrollment:
    student_id: int
    enrolled_at: datetime
