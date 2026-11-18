"""Feature: list who is enrolled in each course.

It needs courses and students at once, so it reads the ClassRoster read model
instead of loading each entity and joining them here. It shapes plain lines
for its output and changes nothing.
"""

from dataclasses import dataclass

from shared.class_roster.class_roster import ClassRoster


@dataclass(frozen=True)
class RosterLine:
    course: str
    student: str
    enrolled_on: str  # YYYY-MM-DD


class ListRoster:
    def __init__(self, roster: ClassRoster) -> None:
        self._roster = roster

    def execute(self) -> list[RosterLine]:
        return [
            RosterLine(
                course=f"{row.course_code} {row.course_title}",
                student=f"{row.student_name} (#{row.student_id})",
                enrolled_on=row.enrolled_at.date().isoformat(),
            )
            for row in self._roster.rows()
        ]
