# Test fixtures

`relationship_manager.cf5030f15781637498be86debe26fcf102f5690d.php` is the complete upstream CHIM/HerikaServer `lib/relationship_manager.php` file from commit [`cf5030f15781637498be86debe26fcf102f5690d`](https://github.com/Dwemer-Dynamics/HerikaServer/commit/cf5030f15781637498be86debe26fcf102f5690d), fetched from:

`https://raw.githubusercontent.com/Dwemer-Dynamics/HerikaServer/cf5030f15781637498be86debe26fcf102f5690d/lib/relationship_manager.php`

SHA-256 of the fetched source bytes: `4497443C9480C7D7FF5D5DEABCEC9AD5AB4CDEC6F28F1FD044F89594D71E06AB`.

The fetched file contains 1,315 CRLF and 50 LF line endings. This repository's `*.php text eol=lf` rule normalizes it for Git: the LF-normalized content is 55,640 bytes with SHA-256 `C68D8BEB88D08C984846FF680D858116EB4C6735E5F1D7AE9AD44B094815DC56` and Git blob ID `868b2ea4cb7ebf5b2069c1b3a7303fec028472c8`. The first hash above identifies the fetched bytes; clean Git exports use the LF-normalized blob.

The relationship tests load this pinned class and exercise its pure `normalizeTargetName` and `normalizeRelationshipMap` methods. They do not copy its alias selection or relationship-weight logic. To refresh this fixture, fetch the same URL and verify the SHA-256 before replacing it; do not substitute a moving branch or a locally installed CHIM checkout.

`semaphore_manager.0ec590b42.php` is a test-only copy of the complete upstream `lib/semaphore_manager.class.php` from Dwemer-Dynamics/HerikaServer commit [`0ec590b42c8087860138bb4e9aab470049ff9a2b`](https://github.com/Dwemer-Dynamics/HerikaServer/commit/0ec590b42c8087860138bb4e9aab470049ff9a2b), fetched from:

`https://raw.githubusercontent.com/Dwemer-Dynamics/HerikaServer/0ec590b42c8087860138bb4e9aab470049ff9a2b/lib/semaphore_manager.class.php`

SHA-256 of the fixture bytes: `F64D4134D7EEEB5C32908371A445EC98BBB7A5D5EED8C71BB9AF83C4EC0761B3`.

The pinned bytes include CRLF line endings and the upstream trailing space on line 52. `.gitattributes` marks only this fixture `-text -eol` and permits its upstream trailing whitespace; do not normalize or trim it. This keeps the SHA-256 above tied to the source bytes while allowing `git diff --check` to pass unchanged.

The pinned upstream tree contains `MIT-LICENSE` and no root `LICENSE` or `LICENSING.md`; the source file has no separate license header. This fixture retains the pinned MIT notice below. The pinned notice is also available at:

`https://raw.githubusercontent.com/Dwemer-Dynamics/HerikaServer/0ec590b42c8087860138bb4e9aab470049ff9a2b/MIT-LICENSE`

```text
Copyright (c) 2023-2024 Augusto Beiro and others

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
```

`tests/restore_barrier_wait_test.php` loads this exact helper because the restore barrier must coordinate with HerikaServer's `SemaphoreManager`, not a parallel test implementation. Run the test only in a private Linux IPC namespace, for example `unshare --ipc -- php -n -d extension=mbstring -d extension=sysvsem tests/restore_barrier_wait_test.php`; it uses `MAIN` to check waiting for a release and failure while the lock remains held, and verifies the test IPC namespace differs from PID 1 before acquiring the semaphore.
