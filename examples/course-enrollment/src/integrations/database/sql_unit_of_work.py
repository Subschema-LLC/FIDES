"""Fulfills shared.unit_of_work.UnitOfWork with one SQLite transaction."""

from typing import Callable, TypeVar

from integrations.database.connection import Connection

T = TypeVar("T")


class SqlUnitOfWork:
    def __init__(self, connection: Connection) -> None:
        self._connection = connection

    def run(self, work: Callable[[], T]) -> T:
        return self._connection.transaction(work)
