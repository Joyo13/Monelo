<?php
declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'monelo';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

define('DB_HOST', $dbHost);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);

$clientId = getenv('GOOGLE_CLIENT_ID') ?: '';
$clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
$envBase = getenv('BASE_URL');
$baseUrl = $envBase === false ? '/monelo/public' : $envBase;

define('GOOGLE_CLIENT_ID', $clientId);
define('GOOGLE_CLIENT_SECRET', $clientSecret);
define('BASE_URL', $baseUrl);

