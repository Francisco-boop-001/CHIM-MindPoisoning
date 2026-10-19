<?php
declare(strict_types=1);

// Expose side-effect-free evidence APIs before companion hooks run.
require_once __DIR__ . '/intimacy.php';
require_once __DIR__ . '/reflection.php';
require_once __DIR__ . '/written_evidence.php';
