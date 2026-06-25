<?php
require 'env_loader.php';

echo "Server IPs:\n";
echo "  MySQL hostname resolves to: " . gethostbyname($_ENV['DB_HOST']) . "\n";

$ip = '2a00:b6e0:1:100:1::1';
echo "\nTrying $ip...\n";
$con = @mysqli_connect("[$ip]", $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
if (!$con) { echo "FAIL: " . mysqli_connect_error() . "\n"; }
else { echo "OK\n"; $r = mysqli_query($con, 'SELECT COUNT(*) cnt FROM users'); echo "Users: " . mysqli_fetch_assoc($r)['cnt'] . "\n"; }
