"""Web delivery end to end: a page composes feature views, and their CSS comes with them."""

import io
import tempfile
import unittest
from pathlib import Path
from typing import Any
from urllib.parse import urlencode

from delivery.web.app import make_app
from delivery.wiring import build


class WebTest(unittest.TestCase):
    def setUp(self) -> None:
        self.folder = tempfile.TemporaryDirectory()
        self.app = make_app(build(Path(self.folder.name), environ={}))

    def tearDown(self) -> None:
        self.folder.cleanup()

    def request(self, method: str, path: str, form: dict[str, str] | None = None) -> tuple:
        body = urlencode(form or {}).encode("utf-8")
        environ: dict[str, Any] = {
            "REQUEST_METHOD": method, "PATH_INFO": path, "QUERY_STRING": "",
            "CONTENT_LENGTH": str(len(body)), "wsgi.input": io.BytesIO(body),
        }
        seen: dict[str, Any] = {}

        def start(status: str, headers: list[tuple[str, str]]) -> None:
            seen.update(status=status, headers=dict(headers))

        page = b"".join(self.app(environ, start)).decode("utf-8")
        return seen["status"], seen["headers"], page

    def test_the_courses_page_places_each_feature_with_its_css(self) -> None:
        status, _, page = self.request("GET", "/")
        self.assertEqual(status, "200 OK")
        self.assertLess(page.index('<section class="courses">'),
                        page.index('<section class="enroll">'))
        # Feature CSS loads in the order the features first rendered, after the layout's.
        self.assertLess(page.index(".flash {"), page.index(".courses table"))
        self.assertLess(page.index(".courses table"), page.index(".enroll form"))

    def test_a_page_without_a_feature_loads_none_of_its_css(self) -> None:
        status, _, page = self.request("GET", "/nowhere")
        self.assertEqual(status, "404 Not Found")
        self.assertNotIn(".courses table", page)

    def test_the_enroll_form_redirects_back_with_the_outcome(self) -> None:
        status, headers, _ = self.request("POST", "/enroll", {"student_id": "", "course_code": ""})
        self.assertEqual(status, "303 See Other")
        self.assertIn("error=Pick+a+student+id", headers["Location"])


if __name__ == "__main__":
    unittest.main()
