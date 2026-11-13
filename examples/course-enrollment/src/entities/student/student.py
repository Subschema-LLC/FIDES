"""Entity: a student, and the rules about a student that are true everywhere.

Other entities never hold a Student object. They refer to a student by id.
"""

from entities.student.email import Email


class Student:
    def __init__(self, student_id: int, name: str, email: Email, active: bool = True) -> None:
        if not name.strip():
            raise ValueError("A student needs a name")
        self._id = student_id
        self._name = name.strip()
        self._email = email
        self._active = active

    @property
    def id(self) -> int:
        return self._id

    @property
    def name(self) -> str:
        return self._name

    @property
    def email(self) -> Email:
        return self._email

    @property
    def is_active(self) -> bool:
        return self._active
