"""Web delivery: HTTP in, feature calls, HTML out. No business rules here.

This file maps paths to pages and wraps each page in the layout. Each page has
its own folder beside this file (courses/courses.py and courses/courses.html).
A page places feature views; a feature's view never pulls in another feature's.
"""

from pathlib import Path
from typing import Any, Callable, Iterable
from urllib.parse import parse_qs, urlencode
from wsgiref.simple_server import make_server

from delivery.web.courses import courses
from delivery.web.render import Markup, Renderer, render
from delivery.wiring import Wiring

FEATURES = Path(__file__).resolve().parents[2] / "features"
WEB = Path(__file__).resolve().parent

StartResponse = Callable[[str, list[tuple[str, str]]], Any]


def make_app(wiring: Wiring) -> Callable[[dict[str, Any], StartResponse], Iterable[bytes]]:
    def application(environ: dict[str, Any], start: StartResponse) -> Iterable[bytes]:
        method = environ["REQUEST_METHOD"]
        path = environ.get("PATH_INFO") or "/"
        query = {k: v[0] for k, v in parse_qs(environ.get("QUERY_STRING", "")).items()}
        view = Renderer(FEATURES, WEB)

        if method == "GET" and path == "/":
            return _html(start, "200 OK", _layout(wiring, view, courses.show(wiring, view), query))
        if method == "POST" and path == "/enroll":
            return _redirect(start, courses.enroll(wiring, _read_form(environ)))
        missing = Markup("<p>Page not found.</p>")
        return _html(start, "404 Not Found", _layout(wiring, view, missing, query))

    return application


def serve(wiring: Wiring, port: int) -> None:
    with make_server("127.0.0.1", port, make_app(wiring)) as server:
        print(f"Serving {wiring.app.title} on http://127.0.0.1:{port} (Ctrl+C to stop)")
        server.serve_forever()


def _layout(wiring: Wiring, view: Renderer, content: Markup, query: dict[str, str]) -> Markup:
    flash = Markup("")
    if "error" in query:
        flash = render(WEB / "flash.html", {"kind": "error", "text": query["error"]})
    elif "msg" in query:
        flash = render(WEB / "flash.html", {"kind": "", "text": query["msg"]})
    values = {"title": wiring.app.title, "styles": view.styles(), "flash": flash,
              "content": content}
    return render(WEB / "layout.html", values)


def _read_form(environ: dict[str, Any]) -> dict[str, str]:
    length = int(environ.get("CONTENT_LENGTH") or 0)
    body = environ["wsgi.input"].read(length).decode("utf-8")
    return {k: v[0] for k, v in parse_qs(body).items()}


def _html(start: StartResponse, status: str, page: Markup) -> list[bytes]:
    start(status, [("Content-Type", "text/html; charset=utf-8")])
    return [page.html.encode("utf-8")]


def _redirect(start: StartResponse, params: dict[str, str]) -> list[bytes]:
    start("303 See Other", [("Location", "/?" + urlencode(params))])
    return [b""]
