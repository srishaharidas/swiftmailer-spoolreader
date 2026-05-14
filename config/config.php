<?php

// Prefer explicit override, then first existing known spool path.
$spoolDirCandidates = array(
    '/Users/srishaharidas/xola/codebase/xola/var/spool/default',
    '/Users/srishaharidas/xola/codebase/xola/var/cache/test/spool/default',
    '/var/www/spool/default',
    '/var/www/spool',
);

$spoolDir = getenv('SPOOL_DIR');
if ($spoolDir === false || $spoolDir === '') {
    foreach ($spoolDirCandidates as $candidate) {
        if (is_dir($candidate)) {
            $spoolDir = $candidate;
            break;
        }
    }
}

if ($spoolDir === false || $spoolDir === '') {
    // Keep a deterministic fallback to avoid an undefined constant in UI.
    $spoolDir = $spoolDirCandidates[0];
}

define('SPOOL_DIR', $spoolDir);
