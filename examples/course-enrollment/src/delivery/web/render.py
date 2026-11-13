"""Logic-less rendering, escaped by default.

Templates are plain HTML files with $placeholders (Python's string.Template).
They cannot loop, branch or call code, so a view can only display what it is
handed. Every value is HTML-escaped unless it is Markup, and Markup only comes
from rendered templates or developer-written files. Lists are rendered by
repeating a row template, here in delivery, never inside the template.
"""

import html
from dataclasses import asdict, dataclass, is_dataclass
from pathlib import Path
from string import Template
from typing import Any, Iterable


@dataclass(frozen=True)
class Markup:
    html: str

    def __add__(self, other: "Markup") -> "Markup":
        return Markup(self.html + other.html)


def render(template: Path, values: Any) -> Markup:
    data = asdict(values) if is_dataclass(values) else dict(values)
    safe = {k: v.html if isinstance(v, Markup) else html.escape(str(v)) for k, v in data.items()}
    return Markup(Template(template.read_text(encoding="utf-8")).substitute(safe))


def render_each(template: Path, items: Iterable[Any]) -> Markup:
    return Markup("".join(render(template, item).html for item in items))


def trusted_file(path: Path) -> Markup:
    """For developer-written files such as a feature's CSS. Never for user input."""
    return Markup(path.read_text(encoding="utf-8"))
