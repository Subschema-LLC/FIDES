"""Value: how many seats a course has and how many are taken."""

from dataclasses import dataclass


@dataclass(frozen=True)
class Seats:
    capacity: int
    taken: int

    def __post_init__(self) -> None:
        if self.capacity < 1:
            raise ValueError("A course needs at least one seat")
        if not 0 <= self.taken <= self.capacity:
            raise ValueError(f"{self.taken} enrollments do not fit in {self.capacity} seats")

    @property
    def left(self) -> int:
        return self.capacity - self.taken

    @property
    def is_full(self) -> bool:
        return self.left == 0
