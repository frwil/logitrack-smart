<?php
require_once __DIR__ . '/BaseController.php';
$files = glob(__DIR__ . '/*.php');
foreach ($files as $file) {
    if (basename($file) === 'BaseController.php' || basename($file) === 'autoload.php') continue;
    require_once $file;
}
