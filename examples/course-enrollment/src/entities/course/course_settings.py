"""Every Course setting, its default and its rules, declared by the owner.

Overrides live outside src/, in config/Course.toml, because live config
belongs to whoever runs the app and a code update must never overwrite it.
Environment variables win over the file (e.g. COURSE_MAX_COURSES_PER_STUDENT=2).
The loader merges once at startup and builds this frozen object.
"""

from dataclasses import dataclass


@dataclass(frozen=True)
class CourseSettings:
    max_courses_per_student: int = 4
    transfers_open: bool = True

    def __post_init__(self) -> None:
        if self.max_courses_per_student < 1:
            raise ValueError("Course setting max_courses_per_student must be at least 1")
