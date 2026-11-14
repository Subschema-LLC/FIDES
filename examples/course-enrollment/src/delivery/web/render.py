"""Logic-less rendering, escaped by default.

Templates are plain HTML files with $placeholders (Python's string.Template).
They cannot loop, branch or call code, so a view can only display what it is
handed. Every value is HTML-escaped unless it is Markup, and Markup only comes
from rendered templates or developer-written files. Lists are rendered by
repeating a row template, here in delivery, never inside the template.

A Renderer serves one response. It renders feature views from their feature
folders and the page template from the page's folder, and it remembers which
features rendered, so each feature's CSS loads with its view. Nobody keeps a
list of which page needs which stylesheet.
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
    is_record = is_dataclass(values) and not isinstance(values, type)
    data = asdict(values) if is_record else dict(values)
    safe = {k: v.html if isinstance(v, Markup) else html.escape(str(v)) for k, v in data.items()}
    return Markup(Template(template.read_text(encoding="utf-8")).substitute(safe))


def render_each(template: Path, items: Iterable[Any]) -> Markup:
    return Markup("".join(render(template, item).html for item in items))


def trusted_file(path: Path) -> Markup:
    """For developer-written files such as a feature's CSS. Never for user input."""
    return Markup(path.read_text(encoding="utf-8"))


class Renderer:
    def __init__(self, features: Path, pages: Path) -> None:
        self._features = features
        self._pages = pages
        self._used: list[str] = []  # features, in the order they first rendered
        self._page = ""

    def feature(self, name: str, template: str, values: Any) -> Markup:
        """One of a feature's views, from src/features/<name>/<template>.html."""
        return render(self._feature_template(name, template), values)

    def feature_each(self, name: str, template: str, items: Iterable[Any]) -> Markup:
        return render_each(self._feature_template(name, template), items)

    def page(self, name: str, values: Any) -> Markup:
        """The page template, from delivery/web/<name>/<name>.html."""
        self._page = name
        return render(self._pages / name / f"{name}.html", values)

    def styles(self) -> Markup:
        """Feature CSS in first-render order, then the page's own. Layout CSS comes first."""
        files = [self._features / name / f"{name}.css" for name in self._used]
        if self._page:
            files.append(self._pages / self._page / f"{self._page}.css")
        css = [trusted_file(path) for path in files if path.is_file()]
        return Markup("".join(markup.html for markup in css))

    def _feature_template(self, name: str, template: str) -> Path:
        if name not in self._used:
            self._used.append(name)
        return self._features / name / f"{template}.html"
