"""Fulfills shared.clock.Clock with the real time, in UTC."""

from datetime import datetime, timezone


class SystemClock:
    def now(self) -> datetime:
        return datetime.now(timezone.utc)
