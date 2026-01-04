<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function google_auth_url(): string {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/oauth_callback.php',
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'offline',
        'prompt' => 'select_account consent',
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function google_fetch_token(string $code): ?array {
    $url = 'https://oauth2.googleapis.com/token';
    $fields = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/oauth_callback.php',
        'grant_type' => 'authorization_code',
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    $resp = curl_exec($ch);
    if ($resp === false) {
        curl_close($ch);
        return null;
    }
    $codeHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($codeHttp !== 200) {
        return null;
    }
    $data = json_decode($resp, true);
    return is_array($data) ? $data : null;
}

function google_fetch_userinfo(string $accessToken): ?array {
    $url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        curl_close($ch);
        return null;
    }
    $codeHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($codeHttp !== 200) {
        return null;
    }
    $data = json_decode($resp, true);
    return is_array($data) ? $data : null;
}

