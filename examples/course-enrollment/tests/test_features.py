"""Features: each use case, tested with in-memory fakes for its contracts."""

import unittest

from entities.course.course import Course
from entities.course.course_settings import CourseSettings
from entities.student.email import Email
from entities.student.student import Student
from fakes import (FakeClassRoster, FakeCourseStore, FakeStudentStore, FixedClock,
                   RecordingMailer, RunNowUnitOfWork)
from features.enroll.enroll import Enroll
from features.list_courses.list_courses import ListCourses
from features.list_roster.list_roster import ListRoster
from features.send_enrollment_confirmation.send_enrollment_confirmation import (
    SendEnrollmentConfirmation,
)
from features.transfer.transfer import Transfer
from shared.class_roster.class_roster import RosterRow

ANA = Student(1, "Ana Ruiz", Email.parse("ana@example.edu"))
DANA = Student(4, "Dana Lopez", Email.parse("dana@example.edu"), active=False)


class EnrollTest(unittest.TestCase):
    def setUp(self) -> None:
        self.courses = FakeCourseStore(Course("CS101", "Intro", 3), Course("ENG105", "Comp", 3))
        self.enroll = Enroll(
            FakeStudentStore(ANA, DANA), self.courses, CourseSettings(max_courses_per_student=1),
            FixedClock(),
        )

    def test_enrolls_with_one_save_and_one_message(self) -> None:
        self.enroll.execute(1, "cs101")
        self.assertEqual(self.courses.saved, ["CS101"])
        self.assertEqual(self.courses.messages, ["StudentEnrolled"])

    def test_rejects_an_inactive_student(self) -> None:
        with self.assertRaises(ValueError):
            self.enroll.execute(4, "CS101")

    def test_enforces_the_course_limit_from_settings(self) -> None:
        self.enroll.execute(1, "CS101")
        with self.assertRaises(ValueError):
            self.enroll.execute(1, "ENG105")

    def test_form_offers_only_active_students(self) -> None:
        self.assertEqual([c.label for c in self.enroll.form().students], ["Ana Ruiz"])


class TransferTest(unittest.TestCase):
    def test_moves_a_student_inside_one_unit_of_work(self) -> None:
        source, target = Course("CS101", "Intro", 3), Course("ENG105", "Comp", 3)
        source.enroll(1, FixedClock().now())
        courses, work = FakeCourseStore(source, target), RunNowUnitOfWork()
        Transfer(courses, CourseSettings(), work, FixedClock()).execute(1, "CS101", "ENG105")
        self.assertEqual(work.runs, 1)
        self.assertFalse(courses.find("CS101").has_student(1))
        self.assertTrue(courses.find("ENG105").has_student(1))

    def test_respects_closed_transfers(self) -> None:
        transfer = Transfer(FakeCourseStore(), CourseSettings(transfers_open=False),
                            RunNowUnitOfWork(), FixedClock())
        with self.assertRaises(ValueError):
            transfer.execute(1, "CS101", "ENG105")


class ListCoursesTest(unittest.TestCase):
    def test_returns_plain_rows_for_the_view(self) -> None:
        full = Course("MATH120", "Algebra", 1)
        full.enroll(1, FixedClock().now())
        rows = ListCourses(FakeCourseStore(full)).execute()
        self.assertEqual((rows[0].code, rows[0].seats_left, rows[0].availability),
                         ("MATH120", 0, "full"))


class ListRosterTest(unittest.TestCase):
    def test_shapes_one_line_per_enrollment_from_the_read_model(self) -> None:
        row = RosterRow("CS101", "Intro", 1, "Ana Ruiz", FixedClock().now())
        line = ListRoster(FakeClassRoster(row)).execute()[0]
        self.assertEqual((line.course, line.student, line.enrolled_on),
                         ("CS101 Intro", "Ana Ruiz (#1)", FixedClock().now().date().isoformat()))


class SendEnrollmentConfirmationTest(unittest.TestCase):
    def test_emails_an_enrolled_student(self) -> None:
        course = Course("CS101", "Intro", 3)
        course.enroll(1, FixedClock().now())
        mailer = RecordingMailer()
        handler = SendEnrollmentConfirmation(FakeStudentStore(ANA), FakeCourseStore(course), mailer)
        handler.handle({"course_code": "CS101", "student_id": 1})
        self.assertEqual(mailer.sent, [("ana@example.edu", "You're enrolled in CS101")])

    def test_skips_a_student_who_already_dropped(self) -> None:
        mailer = RecordingMailer()
        handler = SendEnrollmentConfirmation(
            FakeStudentStore(ANA), FakeCourseStore(Course("CS101", "Intro", 3)), mailer
        )
        handler.handle({"course_code": "CS101", "student_id": 1})
        self.assertEqual(mailer.sent, [])


if __name__ == "__main__":
    unittest.main()
