"""Build and verify the project-local schema-4 CHIM plugin package."""

from __future__ import annotations

import hashlib
import json
import os
import re
import stat
import tempfile
from pathlib import Path
from zipfile import ZIP_STORED, BadZipFile, ZipFile, ZipInfo


SCHEMA_VERSION = 4
SERVER_FILES = (
    "AGENTS.md",
    "README.md",
    "influence.php",
    "manifest.json",
    "model.php",
    "prerequest.php",
    "store.php",
)
_NAME = re.compile(r"^[A-Za-z0-9][A-Za-z0-9 ._-]{0,63}$")
_VERSION = re.compile(r"^[0-9A-Za-z][0-9A-Za-z._+-]{0,63}$")
_CHECKSUM = re.compile(r"^([a-f0-9]{64})  (.+)$")


class PackageError(ValueError):
    pass


def _read_entries(project_root: Path) -> tuple[dict, dict[str, bytes]]:
    root = project_root.resolve()
    server_root = root / "server"
    if server_root.is_symlink() or not server_root.is_dir():
        raise PackageError("Missing server payload directory")
    missing = [
        name
        for name in SERVER_FILES
        if not (server_root / name).is_file() or (server_root / name).is_symlink()
    ]
    if missing:
        raise PackageError(f"Missing allowlisted server payload: {', '.join(missing)}")

    entries = {}
    for name in SERVER_FILES:
        path = server_root / name
        if not path.resolve().is_relative_to(server_root.resolve()):
            raise PackageError(f"Server payload escapes its root: {name}")
        entries[f"server/{name}"] = path.read_bytes()

    try:
        inner = json.loads(entries["server/manifest.json"])
    except (UnicodeDecodeError, json.JSONDecodeError) as error:
        raise PackageError("server/manifest.json must be valid UTF-8 JSON") from error
    if not isinstance(inner, dict):
        raise PackageError("server/manifest.json must contain an object")
    name, version = inner.get("name"), inner.get("version")
    if (
        not isinstance(name, str)
        or not _NAME.fullmatch(name)
        or name.endswith((".", " "))
    ):
        raise PackageError("server/manifest.json has an invalid package name")
    if not isinstance(version, str) or not _VERSION.fullmatch(version):
        raise PackageError("server/manifest.json has an invalid package version")

    outer = {
        "schema_version": SCHEMA_VERSION,
        "name": name,
        "version": version,
        "server": {"mutable_paths": []},
    }
    entries["manifest.json"] = (
        json.dumps(outer, ensure_ascii=False, indent=2, sort_keys=True) + "\n"
    ).encode("utf-8")
    checksums = "".join(
        f"{hashlib.sha256(data).hexdigest()}  {path}\n"
        for path, data in sorted(entries.items())
    )
    entries["checksums.sha256"] = checksums.encode("ascii")
    return outer, entries


def verify_archive(archive_path: Path, project_root: Path) -> dict:
    manifest, expected = _read_entries(Path(project_root))
    try:
        with ZipFile(archive_path) as archive:
            names = archive.namelist()
            if names != sorted(expected) or len(names) != len(set(names)):
                raise PackageError("Archive member list does not match the explicit allowlist")
            if archive.testzip() is not None:
                raise PackageError("Archive contains a CRC failure")
            actual = {name: archive.read(name) for name in names}
    except (OSError, BadZipFile, KeyError, ValueError) as error:
        if isinstance(error, PackageError):
            raise
        raise PackageError(f"Could not verify package archive: {error}") from error

    hashes = {}
    try:
        for line in actual["checksums.sha256"].decode("ascii").splitlines():
            match = _CHECKSUM.fullmatch(line)
            if not match or match[2] in hashes:
                raise PackageError("Archive checksum list is malformed")
            hashes[match[2]] = match[1]
    except UnicodeDecodeError as error:
        raise PackageError("Archive checksum list is not ASCII") from error
    if set(hashes) != set(actual) - {"checksums.sha256"}:
        raise PackageError("Archive checksum list does not cover its exact member list")
    for name, contents in actual.items():
        if name == "checksums.sha256":
            continue
        if hashes[name] != hashlib.sha256(contents).hexdigest():
            raise PackageError(f"Archive checksum mismatch: {name}")
        if contents != expected[name]:
            raise PackageError(f"Archive source mismatch: {name}")
    return manifest


def build_package(project_root: Path, archive_path: Path) -> dict:
    root = Path(project_root).resolve()
    output = Path(archive_path).resolve()
    try:
        output.relative_to(root)
    except ValueError as error:
        raise PackageError("Package output must stay inside the project") from error
    if output in {(root / "server" / name).resolve() for name in SERVER_FILES}:
        raise PackageError("Package output cannot replace a server source file")

    manifest, entries = _read_entries(root)
    output.parent.mkdir(parents=True, exist_ok=True)
    descriptor, temporary_name = tempfile.mkstemp(
        prefix=f".{output.name}.", suffix=".tmp", dir=output.parent
    )
    os.close(descriptor)
    temporary = Path(temporary_name)
    try:
        with ZipFile(temporary, "w", compression=ZIP_STORED) as archive:
            archive.comment = b""
            for name, contents in sorted(entries.items()):
                info = ZipInfo(name, date_time=(1980, 1, 1, 0, 0, 0))
                info.compress_type = ZIP_STORED
                info.create_system = 3
                info.external_attr = (stat.S_IFREG | 0o644) << 16
                archive.writestr(info, contents)
        verify_archive(temporary, root)
        os.replace(temporary, output)
    finally:
        temporary.unlink(missing_ok=True)
    return manifest


def main() -> None:
    project_root = Path(__file__).resolve().parents[1]
    inner, _ = _read_entries(project_root)
    output = project_root / "dist" / f"{inner['name']}-{inner['version']}.dwpkg"
    manifest = build_package(project_root, output)
    digest = hashlib.sha256(output.read_bytes()).hexdigest()
    print(f"Built and verified {output.relative_to(project_root)} ({manifest['name']} {manifest['version']}, sha256 {digest})")


if __name__ == "__main__":
    try:
        main()
    except (OSError, KeyError, json.JSONDecodeError, PackageError) as error:
        raise SystemExit(f"package failed: {error}") from error
