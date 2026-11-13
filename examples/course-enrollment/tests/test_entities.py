"""Entities: the rules that are true everywhere, tested with no fakes at all."""

import unittest
from datetime import datetime, timezone

from entities.course.course import Course
from entities.course.seats import Seats
from entities.student.email import Email

NOW = datetime(2026, 11, 13, 9, 0, tzinfo=timezone.utc)


class EmailTest(unittest.TestCase):
    def test_rejects_an_invalid_address(self) -> None:
        with self.assertRaises(ValueError):
            Email.parse("not-an-email")

    def test_normalizes_a_valid_address(self) -> None:
        self.assertEqual(Email.parse("  Ana@Example.edu ").value, "ana@example.edu")


class SeatsTest(unittest.TestCase):
    def test_counts_seats_left(self) -> None:
        self.assertEqual(Seats(capacity=3, taken=1).left, 2)

    def test_rejects_more_enrollments_than_seats(self) -> None:
        with self.assertRaises(ValueError):
            Seats(capacity=2, taken=3)


class CourseTest(unittest.TestCase):
    def test_enroll_takes_a_seat_and_records_a_message(self) -> None:
        course = Course("cs101", "Intro", capacity=2)
        course.enroll(7, NOW)
        self.assertEqual(course.code, "CS101")
        self.assertTrue(course.has_student(7))
        self.assertEqual(course.seats.left, 1)
        self.assertEqual([m.name for m in course.pull_messages()], ["StudentEnrolled"])
        self.assertEqual(course.pull_messages(), [], "messages are handed over only once")

    def test_cannot_enroll_twice(self) -> None:
        course = Course("CS101", "Intro", capacity=2)
        course.enroll(7, NOW)
        with self.assertRaises(ValueError):
            course.enroll(7, NOW)

    def test_cannot_enroll_in_a_full_course(self) -> None:
        course = Course("CS101", "Intro", capacity=1)
        course.enroll(7, NOW)
        with self.assertRaises(ValueError):
            course.enroll(8, NOW)

    def test_cannot_drop_a_student_who_is_not_enrolled(self) -> None:
        with self.assertRaises(LookupError):
            Course("CS101", "Intro", capacity=1).drop(7)

    def test_saved_version_must_move_forward(self) -> None:
        course = Course("CS101", "Intro", capacity=1, version=3)
        with self.assertRaises(ValueError):
            course.mark_saved(3)


if __name__ == "__main__":
    unittest.main()
