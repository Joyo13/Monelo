<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/google_oauth.php';

if (!isset($_GET['code'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$token = google_fetch_token($_GET['code']);
if (!$token || !isset($token['access_token'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$info = google_fetch_userinfo($token['access_token']);
if (!$info) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if (login_with_google($info)) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

header('Location: ' . BASE_URL . '/login.php');
exit;

