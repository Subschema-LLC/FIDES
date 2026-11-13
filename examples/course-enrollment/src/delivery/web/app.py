"""Web delivery: HTTP in, feature calls, HTML out. No business rules here.

Pages are composed here from feature views. A feature's view never pulls in
another feature's view.
"""

from pathlib import Path
from typing import Any, Callable, Iterable
from urllib.parse import parse_qs, urlencode
from wsgiref.simple_server import make_server

from delivery.web.render import Markup, render, render_each, trusted_file
from delivery.wiring import Wiring

FEATURES = Path(__file__).resolve().parents[2] / "features"
WEB = Path(__file__).resolve().parent
USER_ERRORS = (LookupError, ValueError, RuntimeError)

StartResponse = Callable[[str, list[tuple[str, str]]], Any]


def make_app(wiring: Wiring) -> Callable[[dict[str, Any], StartResponse], Iterable[bytes]]:
    def application(environ: dict[str, Any], start: StartResponse) -> Iterable[bytes]:
        method = environ["REQUEST_METHOD"]
        path = environ.get("PATH_INFO") or "/"
        query = {k: v[0] for k, v in parse_qs(environ.get("QUERY_STRING", "")).items()}

        if method == "GET" and path == "/":
            return _html(start, "200 OK", _home(wiring, query))
        if method == "POST" and path == "/enroll":
            form = _read_form(environ)
            try:
                student_id = _whole_number(form, "student_id")
                wiring.enroll.execute(student_id, form.get("course_code", ""))
            except USER_ERRORS as error:
                return _redirect(start, {"error": str(error)})
            return _redirect(start, {"msg": "Enrolled. Run `python3 app.py work` to send email."})
        return _html(start, "404 Not Found", _page(wiring, Markup("<p>Page not found.</p>"), []))

    return application


def serve(wiring: Wiring, port: int) -> None:
    with make_server("127.0.0.1", port, make_app(wiring)) as server:
        print(f"Serving {wiring.app.title} on http://127.0.0.1:{port} (Ctrl+C to stop)")
        server.serve_forever()


def _home(wiring: Wiring, query: dict[str, str]) -> Markup:
    rows = render_each(FEATURES / "list_courses" / "row.html", wiring.list_courses.execute())
    courses = render(FEATURES / "list_courses" / "view.html", {"rows": rows})

    form = wiring.enroll.form()
    option = FEATURES / "enroll" / "option.html"
    enroll = render(FEATURES / "enroll" / "view.html", {
        "student_options": render_each(option, form.students),
        "course_options": render_each(option, form.courses),
    })

    flash = Markup("")
    if "error" in query:
        flash = render(WEB / "flash.html", {"kind": "error", "text": query["error"]})
    elif "msg" in query:
        flash = render(WEB / "flash.html", {"kind": "", "text": query["msg"]})

    styles = [FEATURES / "list_courses" / "list_courses.css", FEATURES / "enroll" / "enroll.css"]
    return _page(wiring, flash + courses + enroll, styles)


def _page(wiring: Wiring, content: Markup, styles: list[Path]) -> Markup:
    css = Markup("".join(trusted_file(path).html for path in styles))
    values = {"title": wiring.app.title, "styles": css, "content": content}
    return render(WEB / "layout.html", values)


def _read_form(environ: dict[str, Any]) -> dict[str, str]:
    length = int(environ.get("CONTENT_LENGTH") or 0)
    body = environ["wsgi.input"].read(length).decode("utf-8")
    return {k: v[0] for k, v in parse_qs(body).items()}


def _whole_number(form: dict[str, str], key: str) -> int:
    value = form.get(key, "").strip()
    if not value.isdigit():
        raise ValueError(f"Pick a {key.replace('_', ' ')}")
    return int(value)


def _html(start: StartResponse, status: str, page: Markup) -> list[bytes]:
    start(status, [("Content-Type", "text/html; charset=utf-8")])
    return [page.html.encode("utf-8")]


def _redirect(start: StartResponse, params: dict[str, str]) -> list[bytes]:
    start("303 See Other", [("Location", "/?" + urlencode(params))])
    return [b""]
