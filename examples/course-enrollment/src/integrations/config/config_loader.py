"""The single place settings are merged.

For each settings class: start from the defaults the class declares, layer the
TOML file over them, then layer environment variables named PREFIX_KEY (for
example COURSE_MAX_COURSES_PER_STUDENT). Later layers win. Scalars override,
tables (maps) merge key by key, lists replace whole.

Hard rules:
  1. A key in a file that the settings class does not declare stops startup,
     naming the key and the file. This catches typos.
  2. Merged values are checked by building the settings object. Invalid
     values stop startup.
  3. Settings are built once, at startup, and are frozen.
  4. Secrets never come from files. Environment only.

This is merging data, not inheritance. Nothing extends anything.
"""

import dataclasses
import os
import tomllib
from dataclasses import dataclass
from pathlib import Path
from typing import Any, Mapping


@dataclass(frozen=True)
class LoadedSettings:
    settings: Any
    values: dict[str, Any]
    provenance: dict[str, str]  # setting path -> "default", "file" or "env"


class ConfigLoader:
    def __init__(self, environ: Mapping[str, str] | None = None) -> None:
        self._environ = os.environ if environ is None else environ

    def load(self, settings_class: type, file_path: Path, env_prefix: str) -> LoadedSettings:
        fields = dataclasses.fields(settings_class)
        defaults = {f.name: _default_of(settings_class, f) for f in fields}
        values = dict(defaults)
        provenance: dict[str, str] = {}
        _mark(defaults, "", "default", provenance)

        if file_path.is_file():
            with file_path.open("rb") as handle:
                from_file = tomllib.load(handle)
            unknown = sorted(set(from_file) - set(defaults))
            if unknown:
                raise ValueError(
                    f"Unknown setting {', '.join(unknown)} in {file_path}. "
                    f"{settings_class.__name__} declares: {', '.join(defaults)}"
                )
            for key, value in from_file.items():
                _check_type(key, value, defaults[key], str(file_path))
            values = merge(values, from_file)
            _mark(from_file, "", "file", provenance)

        for key, default in defaults.items():
            env_name = f"{env_prefix}_{key.upper()}"
            raw = self._environ.get(env_name, "")
            if raw == "":
                continue
            values[key] = _coerce(raw, default, env_name)
            provenance[key] = "env"

        try:
            settings = settings_class(**values)
        except (TypeError, ValueError) as error:
            raise ValueError(f"Invalid {settings_class.__name__}: {error}") from error
        return LoadedSettings(settings, values, provenance)


def merge(base: dict[str, Any], over: dict[str, Any]) -> dict[str, Any]:
    """Scalars override, maps merge key by key, lists replace whole."""
    result = dict(base)
    for key, value in over.items():
        if isinstance(value, dict) and isinstance(result.get(key), dict):
            result[key] = merge(result[key], value)
        else:
            result[key] = value
    return result


def _default_of(settings_class: type, field: dataclasses.Field) -> Any:
    if field.default is not dataclasses.MISSING:
        return field.default
    if field.default_factory is not dataclasses.MISSING:
        return field.default_factory()
    raise ValueError(f"{settings_class.__name__}.{field.name} must declare a default")


def _check_type(key: str, value: Any, default: Any, source: str) -> None:
    expected = type(default)
    if isinstance(default, bool) != isinstance(value, bool) or not isinstance(value, expected):
        raise ValueError(f"Setting {key} in {source} must be {expected.__name__}, got {value!r}")


def _coerce(raw: str, default: Any, env_name: str) -> Any:
    text = raw.strip()
    if isinstance(default, bool):
        if text.lower() in ("true", "1", "yes"):
            return True
        if text.lower() in ("false", "0", "no"):
            return False
        raise ValueError(f"{env_name} must be true or false, got {raw!r}")
    if isinstance(default, int):
        try:
            return int(text)
        except ValueError:
            raise ValueError(f"{env_name} must be a whole number, got {raw!r}") from None
    if isinstance(default, str):
        return raw
    raise ValueError(f"{env_name} cannot be set from the environment. Use the config file.")


def _mark(data: dict[str, Any], prefix: str, layer: str, provenance: dict[str, str]) -> None:
    for key, value in data.items():
        path = f"{prefix}{key}"
        if isinstance(value, dict):
            _mark(value, f"{path}.", layer, provenance)
        else:
            provenance[path] = layer
