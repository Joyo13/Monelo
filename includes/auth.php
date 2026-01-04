<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function register_user(string $name, string $email, string $password): bool {
    $db = get_db();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $exists = $stmt->fetch();
    if ($exists) {
        return false;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$name, $email, $hash]);
    return true;
}

function login_user(string $email, string $password): bool {
    $db = get_db();
    $stmt = $db->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    return true;
}

function login_with_google(array $googleUser): bool {
    if (!isset($googleUser['email'])) {
        return false;
    }
    $db = get_db();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$googleUser['email']]);
    $user = $stmt->fetch();
    if ($user) {
        $stmt = $db->prepare('UPDATE users SET google_id = ? WHERE id = ?');
        $stmt->execute([$googleUser['id'] ?? null, $user['id']]);
        $_SESSION['user_id'] = (int)$user['id'];
        return true;
    }
    $stmt = $db->prepare('INSERT INTO users (name, email, google_id, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$googleUser['name'] ?? ($googleUser['given_name'] ?? ''), $googleUser['email'], $googleUser['id'] ?? null]);
    $_SESSION['user_id'] = (int)$db->lastInsertId();
    return true;
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function current_user(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $db = get_db();
    $stmt = $db->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

