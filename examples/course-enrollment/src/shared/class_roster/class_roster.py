"""Read model: who is enrolled in which course, across courses and students.

No entity owns this view, and entities never hold each other, so it is a
contract of its own in shared. It only reads: every change still goes through
the Course entity and its store. Rows hold plain values, never entities.
"""

from dataclasses import dataclass
from datetime import datetime
from typing import Protocol


@dataclass(frozen=True)
class RosterRow:
    course_code: str
    course_title: str
    student_id: int
    student_name: str
    enrolled_at: datetime


class ClassRoster(Protocol):
    def rows(self) -> list[RosterRow]: ...
