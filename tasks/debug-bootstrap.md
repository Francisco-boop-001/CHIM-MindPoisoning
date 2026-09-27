# Loader/bootstrap compatibility check

## Finding

No loader-scope mismatch was found. The installed loader includes the plugin prerequest file from inside `requireFilesRecursively()`, but that function explicitly imports the real global `$gameRequest` before including files. The plugin's top-level `_speech` guard therefore sees the same request that the core later handles.

## Read-only source evidence

Inspected installed HerikaServer revision `cf5030f15781637498be86debe26fcf102f5690d` in WSL; no installed files were changed and no endpoint/bootstrap was executed.

Read-only inspection command and relevant line output:

```text
wsl.exe -d DwemerAI4Skyrim3 -- bash -lc 'grep -n "gameRequest" /var/www/html/HerikaServer/main.php | head -16; nl -ba /var/www/html/HerikaServer/lib/data_functions.php | sed -n "7926,7948p"; nl -ba /var/www/html/HerikaServer/main.php | sed -n "1123,1135p"; nl -ba /var/www/html/HerikaServer/processor/comm.php | sed -n "632,640p"'
```

- `main.php:142-143` assigns the parsed request to `$gameRequest` and binds `$GLOBALS["gameRequest"]` by reference.
- `lib/data_functions.php:7928-7944` declares `global $gameRequest`, then recursively calls `require_once($path)` for matching extension files.
- `main.php:1125` invokes that helper for `prerequest.php`; `main.php:1132` includes `processor/comm.php` afterward. Thus the hook runs before the core `_speech` branch at `processor/comm.php:632`.
- The CHIM runtime bootstrap initializes global `$db` before the hook. `PostgresStoreDb::__construct()` only checks and retains that object; it does not issue a query. The hook's own `require_once` dependencies are the plugin's `influence.php`, `model.php`, and `store.php`. The model adapter uses the already-loaded core connector API and retains its guarded class-file fallback.
- The hook runs before core ACK handling can update delivery state. The store accepts the source event's `emitted` or `spoken` state and revalidates it before commit, which covers this order.

## Fixture decision and limits

No `tests/bootstrap_check.php` was added. The source-level contract is explicit, and a meaningful check of the actual helper would require loading core bootstrap/data-function code or executing the real prerequest callback. The runtime brief forbids server endpoint/bootstrap tests; executing the hook for `_speech` also constructs the production store adapter. A cloned helper or transformed hook would only restate the source and would not close that gap.

This closes the loader variable-scope/order question by inspection, consistent with the loader gate already recorded in `tasks/todo.md`. It is not a live bootstrap, database, provider, or in-game test; those remain unverified.
