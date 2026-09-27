"""Exercise the installed manager's GNU tar strip-one extraction shape."""

from __future__ import annotations

import argparse
import subprocess
import sys
import tempfile
from pathlib import Path

PROJECT = Path(__file__).resolve().parents[1]
TESTS = Path(__file__).resolve().parent
sys.path.insert(0, str(PROJECT))
sys.path.insert(0, str(TESTS))

from scripts.package import (
    SERVER_FILES,
    build_repository_archive,
    verify_repository_archive,
)
from test_package import write_fixture_project


def files_under(root: Path) -> dict[str, bytes]:
    files = {}
    for path in root.rglob("*"):
        if path.is_symlink():
            raise RuntimeError("Extracted scratch tree contains a symlink.")
        if path.is_file():
            files[path.relative_to(root).as_posix()] = path.read_bytes()
        elif not path.is_dir():
            raise RuntimeError("Extracted scratch tree contains a non-file entry.")
    return dict(sorted(files.items()))


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "archive",
        nargs="?",
        help="existing repository archive to extract (defaults to a generated fixture)",
    )
    options = parser.parse_args()
    with tempfile.TemporaryDirectory(
        prefix=".repository-tar-check-run-", dir=TESTS
    ) as scratch_name:
        scratch = Path(scratch_name)
        if options.archive:
            archive = Path(options.archive).resolve()
            if not archive.is_file() or not archive.is_relative_to(PROJECT):
                raise RuntimeError("Archive must be an existing file inside this project.")
            source = PROJECT
        else:
            source = scratch / "source"
            source.mkdir()
            write_fixture_project(source, extra_files=True)
            archive = source / "dist" / "mind_poisoning.tar.gz"
            build_repository_archive(source, archive)
        verify_repository_archive(archive, source)

        version = subprocess.run(
            ["tar", "--version"], check=True, capture_output=True, text=True
        ).stdout.splitlines()[0]
        if "GNU tar" not in version:
            raise RuntimeError(f"Expected GNU tar, got: {version}")

        extracted = scratch / "extracted"
        extracted.mkdir()
        command = [
            "tar",
            "xvfz",
            str(archive),
            "-C",
            str(extracted),
            "--strip-components=1",
        ]
        result = subprocess.run(command, capture_output=True, text=True)
        if result.returncode != 0:
            raise RuntimeError(
                f"GNU tar exited {result.returncode}: {result.stdout}{result.stderr}"
            )

        expected = {
            name: (source / "server" / name).read_bytes() for name in SERVER_FILES
        }
        if files_under(extracted) != expected:
            raise RuntimeError("GNU tar extracted bytes differ from the allowlisted fixture.")
        print(f"PASS: {version}")
        scope = "candidate" if options.archive else "fixture"
        print(f"PASS: GNU tar xvfz with --strip-components=1 extracted the {scope} into scratch.")
        print("PASS: extracted bytes match the seven allowlisted source files; other files are excluded.")

    if Path(scratch_name).exists():
        raise RuntimeError("Repository tar scratch root was not removed.")
    print("PASS: project-local scratch tree cleaned.")


if __name__ == "__main__":
    main()
