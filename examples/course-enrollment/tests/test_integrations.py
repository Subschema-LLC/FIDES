"""Integrations and delivery edges: SQLite, config loading, the worker, rendering."""

import tempfile
import unittest
from datetime import datetime, timezone
from pathlib import Path

from delivery.web.render import Markup, render
from delivery.worker.worker import Worker
from entities.course.course import Course
from entities.course.course_settings import CourseSettings
from integrations.config.config_loader import ConfigLoader, merge
from integrations.database.connection import Connection
from integrations.database.course_store import SqlCourseStore
from integrations.database.outbox import Outbox
from integrations.database.seeder import seed_if_empty
from integrations.database.sql_unit_of_work import SqlUnitOfWork

NOW = datetime(2026, 11, 13, 9, 0, tzinfo=timezone.utc)


class DatabaseTest(unittest.TestCase):
    def setUp(self) -> None:
        self.connection = Connection(":memory:")
        seed_if_empty(self.connection)
        self.outbox = Outbox(self.connection)
        self.courses = SqlCourseStore(self.connection, self.outbox)

    def test_saves_the_course_and_its_message_in_one_write(self) -> None:
        course = self.courses.find("ENG105")
        course.enroll(3, NOW)
        self.courses.save(course)
        self.assertTrue(self.courses.find("ENG105").has_student(3))
        self.assertEqual([m.name for m in self.outbox.pending()], ["StudentEnrolled"])

    def test_second_of_two_simultaneous_saves_fails(self) -> None:
        first, second = self.courses.find("CS101"), self.courses.find("CS101")
        first.enroll(3, NOW)
        second.enroll(4, NOW)
        self.courses.save(first)
        with self.assertRaises(RuntimeError):
            self.courses.save(second)  # would otherwise take the last seat twice

    def test_unit_of_work_rolls_back_every_save_inside_it(self) -> None:
        def half_a_transfer() -> None:
            source = self.courses.find("CS101")
            source.drop(1)
            self.courses.save(source)
            raise ValueError("the second course turned out to be full")

        with self.assertRaises(ValueError):
            SqlUnitOfWork(self.connection).run(half_a_transfer)
        self.assertTrue(self.courses.find("CS101").has_student(1))
        self.assertEqual(self.outbox.pending(), [])


class ConfigLoaderTest(unittest.TestCase):
    def load(self, toml: str, environ: dict[str, str] | None = None):
        with tempfile.TemporaryDirectory() as folder:
            path = Path(folder) / "Course.toml"
            path.write_text(toml, encoding="utf-8")
            return ConfigLoader(environ or {}).load(CourseSettings, path, "COURSE")

    def test_layers_default_then_file_then_environment(self) -> None:
        loaded = self.load("max_courses_per_student = 3\n", {"COURSE_TRANSFERS_OPEN": "false"})
        self.assertEqual(loaded.settings, CourseSettings(3, False))
        self.assertEqual(loaded.provenance,
                         {"max_courses_per_student": "file", "transfers_open": "env"})

    def test_unknown_key_stops_startup(self) -> None:
        with self.assertRaisesRegex(ValueError, "max_course_per_student"):
            self.load("max_course_per_student = 3\n")  # typo

    def test_invalid_value_stops_startup(self) -> None:
        with self.assertRaises(ValueError):
            self.load("max_courses_per_student = 0\n")

    def test_wrong_type_stops_startup(self) -> None:
        with self.assertRaises(ValueError):
            self.load('max_courses_per_student = "three"\n')

    def test_missing_file_means_defaults(self) -> None:
        loaded = ConfigLoader({}).load(CourseSettings, Path("/nowhere/Course.toml"), "COURSE")
        self.assertEqual(loaded.settings, CourseSettings())

    def test_maps_merge_by_key_and_lists_replace_whole(self) -> None:
        base = {"limits": {"low": 1, "high": 9}, "tags": ["a", "b"]}
        merged = merge(base, {"limits": {"high": 5}, "tags": ["c"]})
        self.assertEqual(merged, {"limits": {"low": 1, "high": 5}, "tags": ["c"]})


class WorkerTest(unittest.TestCase):
    def test_failed_handler_leaves_the_message_pending(self) -> None:
        connection = Connection(":memory:")
        outbox = Outbox(connection)
        outbox.append("StudentEnrolled", {"course_code": "CS101", "student_id": 1})

        def broken(payload: dict) -> None:
            raise ConnectionError("mail server down")

        report = Worker(outbox, {"StudentEnrolled": broken}).run()
        self.assertEqual((report.dispatched, len(report.failures)), (0, 1))
        self.assertEqual(len(outbox.pending()), 1)


class RenderTest(unittest.TestCase):
    def test_escapes_by_default_and_trusts_only_markup(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            template = Path(folder) / "t.html"
            template.write_text("<p>$text</p>$extra", encoding="utf-8")
            page = render(template, {"text": "<script>", "extra": Markup("<b>ok</b>")})
        self.assertEqual(page.html, "<p>&lt;script&gt;</p><b>ok</b>")


if __name__ == "__main__":
    unittest.main()
