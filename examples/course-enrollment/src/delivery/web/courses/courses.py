"""The courses page: every course with its seats, and the form to enroll a student.

Page code reads the request, calls features and picks the response. What the
page shows is laid out in courses.html. Each part's markup and CSS stay in
the feature that owns it.
"""

from delivery.web.render import Markup, Renderer
from delivery.wiring import Wiring

USER_ERRORS = (LookupError, ValueError, RuntimeError)


def show(wiring: Wiring, view: Renderer) -> Markup:
    rows = view.feature_each("list_courses", "row", wiring.list_courses.execute())
    form = wiring.enroll.form()
    return view.page("courses", {
        "courses": view.feature("list_courses", "view", {"rows": rows}),
        "enroll": view.feature("enroll", "view", {
            "student_options": view.feature_each("enroll", "option", form.students),
            "course_options": view.feature_each("enroll", "option", form.courses),
        }),
    })


def enroll(wiring: Wiring, form: dict[str, str]) -> dict[str, str]:
    """Handles the enroll form. Returns what the redirect back to the page should say."""
    try:
        student_id = _whole_number(form, "student_id")
        wiring.enroll.execute(student_id, form.get("course_code", ""))
    except USER_ERRORS as error:
        return {"error": str(error)}
    return {"msg": "Enrolled. Run `python3 app.py work` to send email."}


def _whole_number(form: dict[str, str], key: str) -> int:
    value = form.get(key, "").strip()
    if not value.isdigit():
        raise ValueError(f"Pick a {key.replace('_', ' ')}")
    return int(value)
