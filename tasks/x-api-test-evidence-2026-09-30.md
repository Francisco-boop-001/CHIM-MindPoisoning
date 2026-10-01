# Reflection API and cleanup evidence — 2026-09-30

## Changes in scope

- Mind Poisoning declares `ChimMindPoisoning\MIND_POISONING_REFLECTION_API_VERSION = 1` in `server/reflection.php`.
- `tests/reflection_observer_test.php` imports the embedded `plugins/private_conversation` source and checks the namespaced API version. It no longer depends on the sibling standalone PCV checkout.
- `plugins/private_conversation/tests/log_check.php` now defers bounded fixture cleanup until after other shutdown callbacks. It removes only the fixed test files/directories and expected `private-conversation-<uid>-<16 lowercase hex>` fallback child; unexpected names and symlinks are preserved, and incomplete cleanup returns a failure.
- The fatal cleanup probe starts a logged request, triggers `E_USER_ERROR`, verifies the logger wrote `routing.request_finished/fatal_error` before cleanup, then checks that the fixture and its fallback file are absent.

## Verification

Focused WSL checks passed:

```text
php -l server/reflection.php
No syntax errors detected

php -l tests/reflection_observer_test.php
No syntax errors detected

php -l plugins/private_conversation/tests/log_check.php
No syntax errors detected

php plugins/private_conversation/tests/log_check.php
PASS: schema/redaction, IDs, debug window, five-segment rotation, private permissions, concurrent JSONL, unsafe-path rejection, lock fallback

php tests/reflection_observer_test.php
reflection observer/importer: 5 cases passed (committed delta, zero change, provider failure, unconfirmed commit, warning skip)
```

The observer fixture also passed from a temporary source export containing MP `server/`, `tests/`, and the embedded `plugins/private_conversation/` tree, with no sibling PCV project available. The export used Python `TemporaryDirectory`, copied those three source trees, ran `php tests/reflection_observer_test.php` from the export root, and removed only that generated temporary export on exit:

```python
with tempfile.TemporaryDirectory(prefix="chim-mp-clean-source-export-") as export:
    for relative in ("server", "tests", "plugins/private_conversation"):
        shutil.copytree(os.path.join(source, relative), os.path.join(export, relative))
    subprocess.run(
        ["php", os.path.join(export, "tests", "reflection_observer_test.php")],
        cwd=export,
        check=True,
    )
```

The pre-fix cleanup residue was the authorized fixture `/tmp/chim-private-conversation-log-check-a43fe636e0ff967f`: its only descendants were `fallback-temp/private-conversation-0-e255c44bf48da123` with regular `events.jsonl` (296 bytes, mode 0600) and `events.lock` (0 bytes, mode 0600), plus an empty `unsafe` directory (mode 0755). The exact path and known metadata were revalidated with `lstat`; bounded `unlink`/empty-directory removal completed, and the parent was verified absent. No file contents were read. No other temporary or server state was removed.

These are isolated PHP/source-export checks. They do not prove installed-plugin equivalence, database/provider behavior, or in-game behavior. X-01, X-02, and X-05 remain with their separately assigned owners; this evidence covers the files and fixtures in this change's scope.
