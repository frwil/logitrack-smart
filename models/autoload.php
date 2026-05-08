<?php
/**
 * Simple autoloader for models/ directory.
 * Include this once in index.php — all repository classes become available.
 */

// Load BaseRepository first (all others extend it).
require_once __DIR__ . '/BaseRepository.php';

// Load all other model files.
$files = glob(__DIR__ . '/*.php');
foreach ($files as $file) {
    if (basename($file) === 'BaseRepository.php' || basename($file) === 'autoload.php') {
        continue;
    }
    require_once $file;
}
