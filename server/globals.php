<?php
declare(strict_types=1);

// Expose the side-effect-free intimacy API before companion hooks run.
require_once __DIR__ . '/intimacy.php';
require_once __DIR__ . '/reflection.php';
