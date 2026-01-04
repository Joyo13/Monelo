<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/google_oauth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password && login_user($email, $password)) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
    $error = 'Invalid credentials';
}
$googleEnabled = (bool)GOOGLE_CLIENT_ID;
$googleUrl = $googleEnabled ? google_auth_url() : '#';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk MONELO</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container auth">
    <h1>MONELO</h1>
    <div class="card">
        <h2>Masuk</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" class="form">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Kata Sandi</label>
            <input type="password" name="password" required>
            <button type="submit" class="btn primary">Masuk</button>
        </form>
        <div class="sep">atau</div>
        <a href="<?= htmlspecialchars($googleUrl) ?>" class="btn google <?= $googleEnabled ? '' : 'disabled' ?>">
            <img src="/assets/img/google.svg" alt="Google" width="18" height="18"> Masuk dengan Google
        </a>
        <p class="note">Belum punya akun? <a href="register.php">Daftar</a></p>
    </div>
    <p class="brand">Kelola keuangan pribadi dengan mudah</p>
    </div>
</body>
</html>
