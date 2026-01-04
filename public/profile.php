<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ui.php';

require_login();
$db = get_db();
$uid = (int)$_SESSION['user_id'];
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=monelo_export.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Tanggal','Jenis','Nominal','Kategori','Catatan']);
    $stmt = $db->prepare('SELECT occurred_on, type, amount, category, notes FROM transactions WHERE user_id=? ORDER BY occurred_on DESC');
    $stmt->execute([$uid]);
    while ($row = $stmt->fetch()) { fputcsv($out, [$row['occurred_on'], $row['type'], $row['amount'], $row['category'], $row['notes']]); }
    fclose($out);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $pass = $_POST['password'] ?? '';
        if ($name) {
            $stmt = $db->prepare('UPDATE users SET name=? WHERE id=?');
            $stmt->execute([$name, $uid]);
            $msg = 'Profil diperbarui';
        }
        if ($pass) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE users SET password_hash=? WHERE id=?');
            $stmt->execute([$hash, $uid]);
            $msg = $msg ? $msg . ' dan kata sandi diubah' : 'Kata sandi diubah';
        }
    }
    if (isset($_POST['save_prefs'])) {
        $_SESSION['pref_notify_bill'] = isset($_POST['notify_bill']) ? 1 : 0;
        $_SESSION['pref_notify_saving'] = isset($_POST['notify_saving']) ? 1 : 0;
        $_SESSION['pref_notify_budget'] = isset($_POST['notify_budget']) ? 1 : 0;
        $_SESSION['pref_daily_summary'] = isset($_POST['daily_summary']) ? 1 : 0;
        $_SESSION['pref_dark_mode'] = isset($_POST['dark_mode']) ? 1 : 0;
        $msg = 'Preferensi disimpan';
    }
    if (isset($_POST['wipe'])) {
        $db->prepare('DELETE FROM transactions WHERE user_id=?')->execute([$uid]);
        $db->prepare('DELETE FROM budgets WHERE user_id=?')->execute([$uid]);
        $db->prepare('DELETE FROM goals WHERE user_id=?')->execute([$uid]);
        $db->prepare('DELETE FROM reminders WHERE user_id=?')->execute([$uid]);
        $msg = 'Semua data berhasil dihapus';
    }
}
$stmt = $db->prepare('SELECT name, email FROM users WHERE id=?');
$stmt->execute([$uid]);
$u = $stmt->fetch() ?: ['name' => '', 'email' => ''];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container layout">
    <?php render_sidebar('profile'); ?>
    <main>
        <?php render_topbar(current_user()); ?>
        <h1>Pengaturan</h1>
        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <div class="card">
            <h2>Profil</h2>
            <div class="user-card" style="margin:0 0 12px;">
                <div class="avatar"><?= strtoupper(substr(($u['name'] ?: $u['email']), 0, 1)) ?></div>
                <div class="user-info">
                    <div class="name"><?= htmlspecialchars($u['name'] ?: 'Pengguna') ?></div>
                    <div class="muted"><?= htmlspecialchars($u['email']) ?></div>
                </div>
            </div>
            <form method="post" class="form">
                <label>Nama</label>
                <input type="text" name="name" value="<?= htmlspecialchars($u['name'] ?? '') ?>" required>
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($u['email'] ?? '') ?>" disabled>
                <label>Kata Sandi Baru</label>
                <input type="password" name="password" placeholder="Minimal 6 karakter">
                <button class="btn primary" type="submit" name="save_profile" value="1">Simpan Profil</button>
            </form>
        </div>

        <div class="card" style="margin-top:16px;">
            <h2>Notifikasi</h2>
            <form method="post" class="form">
                <div class="switch-row"><div>Pengingat Tagihan</div><label class="switch"><input type="checkbox" name="notify_bill" <?= !empty($_SESSION['pref_notify_bill'])?'checked':'' ?>><span class="slider"></span></label></div>
                <div class="switch-row"><div>Pengingat Tabungan</div><label class="switch"><input type="checkbox" name="notify_saving" <?= !empty($_SESSION['pref_notify_saving'])?'checked':'' ?>><span class="slider"></span></label></div>
                <div class="switch-row"><div>Peringatan Anggaran</div><label class="switch"><input type="checkbox" name="notify_budget" <?= !empty($_SESSION['pref_notify_budget'])?'checked':'' ?>><span class="slider"></span></label></div>
                <div class="switch-row"><div>Ringkasan Harian</div><label class="switch"><input type="checkbox" name="daily_summary" <?= !empty($_SESSION['pref_daily_summary'])?'checked':'' ?>><span class="slider"></span></label></div>
                <button class="btn primary" type="submit" name="save_prefs" value="1">Simpan Preferensi</button>
            </form>
        </div>

        <div class="card" style="margin-top:16px;">
            <h2>Tampilan</h2>
            <form method="post" class="form">
                <div class="switch-row"><div>Mode Gelap</div><label class="switch"><input type="checkbox" name="dark_mode" <?= !empty($_SESSION['pref_dark_mode'])?'checked':'' ?>><span class="slider"></span></label></div>
                <button class="btn" type="submit" name="save_prefs" value="1">Simpan</button>
            </form>
        </div>

        <div class="card" style="margin-top:16px;">
            <h2>Data & Privasi</h2>
            <form method="post" class="actions">
                <button class="btn" name="export" value="1">Ekspor Data</button>
                <button class="btn" name="wipe" value="1" style="color:#c00; border:1px solid #ffe0e0;">Hapus Semua Data</button>
            </form>
        </div>

        <div style="margin-top:16px;">
            <a class="btn primary" href="logout.php" style="background:#ef476f;">Keluar dari Akun</a>
        </div>
    </main>
</div>
</body>
</html>
