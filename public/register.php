<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/google_oauth.php';

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if ($name && $email && $password && $password === $confirm) {
        if (register_user($name, $email, $password)) {
            $message = 'Registration successful. Please login.';
        } else {
            $error = 'Email already registered';
        }
    } else {
        $error = 'Please fill all fields correctly';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container auth">
    <h1>Buat Akun</h1>
    <div class="card">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" class="form">
            <label>Nama</label>
            <input type="text" name="name" required>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Kata Sandi</label>
            <input type="password" name="password" required>
            <label>Konfirmasi Kata Sandi</label>
            <input type="password" name="confirm" required>
            <button type="submit" class="btn primary">Daftar</button>
        </form>
        <div class="sep">atau</div>
        <?php $googleEnabled = (bool)GOOGLE_CLIENT_ID; $googleUrl = $googleEnabled ? google_auth_url() : '#'; ?>
            <a href="<?= htmlspecialchars($googleUrl) ?>" class="btn google <?= $googleEnabled ? '' : 'disabled' ?>">
                <img src="/assets/img/google.svg" alt="Google" width="18" height="18"> Daftar dengan Google
            </a>
        <p class="note">Sudah punya akun? <a href="login.php">Masuk</a></p>
    </div>
</div>
</body>
</html>
