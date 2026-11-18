"""The one place that knows integrations exist. It builds the object graph.

Startup, in order:
  1. Load each settings class once: declared default, then config/<Owner>.toml,
     then environment variables.
  2. Build the integrations that fulfill the contracts.
  3. Hand settings and integrations to features through their constructors.

Nothing else reads files, environment variables or the database directly.
"""

from dataclasses import dataclass
from pathlib import Path
from typing import Mapping

from delivery.worker.worker import Worker
from entities.course.course_settings import CourseSettings
from features.drop.drop import Drop
from features.enroll.enroll import Enroll
from features.list_courses.list_courses import ListCourses
from features.list_roster.list_roster import ListRoster
from features.send_enrollment_confirmation.send_enrollment_confirmation import (
    SendEnrollmentConfirmation,
)
from features.transfer.transfer import Transfer
from integrations.clock.system_clock import SystemClock
from integrations.config.config_loader import ConfigLoader, LoadedSettings
from integrations.database.class_roster import SqlClassRoster
from integrations.database.connection import Connection
from integrations.database.course_store import SqlCourseStore
from integrations.database.outbox import Outbox
from integrations.database.seeder import seed_if_empty
from integrations.database.sql_unit_of_work import SqlUnitOfWork
from integrations.database.student_store import SqlStudentStore
from integrations.email.file_mailer import FileMailer
from shared.app_settings import AppSettings


@dataclass(frozen=True)
class Wiring:
    app: AppSettings
    settings: dict[str, LoadedSettings]  # by owner, for `python3 app.py config <owner>`
    list_courses: ListCourses
    list_roster: ListRoster
    enroll: Enroll
    drop: Drop
    transfer: Transfer
    worker: Worker


def build(root: Path, environ: Mapping[str, str] | None = None) -> Wiring:
    loader = ConfigLoader(environ)
    config = root / "config"
    settings = {
        "app": loader.load(AppSettings, config / "app.toml", "APP"),
        "Course": loader.load(CourseSettings, config / "Course.toml", "COURSE"),
    }
    app: AppSettings = settings["app"].settings
    course_settings: CourseSettings = settings["Course"].settings

    var = root / "var"
    var.mkdir(exist_ok=True)
    connection = Connection(str(var / app.database_file))
    seed_if_empty(connection)
    outbox = Outbox(connection)
    courses = SqlCourseStore(connection, outbox)
    students = SqlStudentStore(connection)
    clock = SystemClock()
    mailer = FileMailer(var / app.mail_log, app.mail_from)
    confirm = SendEnrollmentConfirmation(students, courses, mailer)

    return Wiring(
        app=app,
        settings=settings,
        list_courses=ListCourses(courses),
        list_roster=ListRoster(SqlClassRoster(connection)),
        enroll=Enroll(students, courses, course_settings, clock),
        drop=Drop(courses),
        transfer=Transfer(courses, course_settings, SqlUnitOfWork(connection), clock),
        worker=Worker(outbox, {"StudentEnrolled": confirm.handle}),
    )
