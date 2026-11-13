"""Ownerless settings: values no single entity owns.

Every setting is declared here with its default and its rules. The config
loader merges overrides once at startup (default, then config/app.toml, then
environment variables such as APP_TITLE) and builds this object. It is frozen,
so nothing can change it while the app runs.

Secrets are never settings in a file. They come from the environment only.
"""

from dataclasses import dataclass


@dataclass(frozen=True)
class AppSettings:
    title: str = "Course Enrollment"
    mail_from: str = "no-reply@example.edu"
    database_file: str = "app.sqlite"
    mail_log: str = "mail.log"

    def __post_init__(self) -> None:
        for key in ("title", "mail_from", "database_file", "mail_log"):
            if not str(getattr(self, key)).strip():
                raise ValueError(f"App setting {key} must not be empty")
        for key in ("database_file", "mail_log"):
            value = getattr(self, key)
            if "/" in value or "\\" in value:
                raise ValueError(f"App setting {key} must be a plain file name (it lives in var/)")
