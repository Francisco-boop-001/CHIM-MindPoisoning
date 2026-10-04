# Test fixtures

`relationship_manager.cf5030f15781637498be86debe26fcf102f5690d.php` is the complete upstream CHIM/HerikaServer `lib/relationship_manager.php` file from commit [`cf5030f15781637498be86debe26fcf102f5690d`](https://github.com/Dwemer-Dynamics/HerikaServer/commit/cf5030f15781637498be86debe26fcf102f5690d), fetched from:

`https://raw.githubusercontent.com/Dwemer-Dynamics/HerikaServer/cf5030f15781637498be86debe26fcf102f5690d/lib/relationship_manager.php`

SHA-256 of the fetched source bytes: `4497443C9480C7D7FF5D5DEABCEC9AD5AB4CDEC6F28F1FD044F89594D71E06AB`.

The fetched file contains 1,315 CRLF and 50 LF line endings. This repository's `*.php text eol=lf` rule normalizes it for Git: the LF-normalized content is 55,640 bytes with SHA-256 `C68D8BEB88D08C984846FF680D858116EB4C6735E5F1D7AE9AD44B094815DC56` and Git blob ID `868b2ea4cb7ebf5b2069c1b3a7303fec028472c8`. The first hash above identifies the fetched bytes; clean Git exports use the LF-normalized blob.

The relationship tests load this pinned class and exercise its pure `normalizeTargetName` and `normalizeRelationshipMap` methods. They do not copy its alias selection or relationship-weight logic. To refresh this fixture, fetch the same URL and verify the SHA-256 before replacing it; do not substitute a moving branch or a locally installed CHIM checkout.
