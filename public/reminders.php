<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ui.php';

require_login();
$db = get_db();
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $title = trim($_POST['title'] ?? '');
        $type = trim($_POST['type'] ?? 'general');
        $date = $_POST['date'] ?: date('Y-m-d');
        $stmt = $db->prepare('INSERT INTO reminders (user_id, title, type, remind_on, status, created_at) VALUES (?, ?, ?, ?, "pending", NOW())');
        $stmt->execute([$uid, $title, $type, $date]);
    }
    if (isset($_POST['mark'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare('UPDATE reminders SET status="done" WHERE id=? AND user_id=?');
        $stmt->execute([$id, $uid]);
    }
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare('DELETE FROM reminders WHERE id=? AND user_id=?');
        $stmt->execute([$id, $uid]);
    }
}

$stmt = $db->prepare('SELECT id, title, type, remind_on, status FROM reminders WHERE user_id=? ORDER BY remind_on');
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();

$total = count($rows);
$pendingRows = array_filter($rows, fn($r) => $r['status'] === 'pending');
$pendingCount = count($pendingRows);
$urgentCount = 0;
foreach ($pendingRows as $r) {
    $rem = new DateTime($r['remind_on']);
    $now = new DateTime(date('Y-m-d'));
    $diff = (int)$now->diff($rem)->format('%r%a'); // negative if overdue
    if ($diff <= 3) { $urgentCount++; }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container layout">
    <?php render_sidebar('reminders'); ?>
    <main>
        <?php render_topbar(current_user()); ?>
    <h1>Pengingat</h1>
    <div class="report-toolbar">
        <div class="muted">Kelola pengingat tagihan dan pembayaran</div>
        <div><button class="btn primary" id="btnAddReminder">Tambah Pengingat</button></div>
    </div>

    <div class="stat-grid">
      <div class="stat"><div class="icon teal">🔔</div><div><div class="title">Total Pengingat</div><div class="value"><?= (int)$total ?></div></div></div>
      <div class="stat"><div class="icon red">⏳</div><div><div class="title">Belum Selesai</div><div class="value"><?= (int)$pendingCount ?></div></div></div>
      <div class="stat"><div class="icon red">⚠</div><div><div class="title">Mendesak (≤3 hari)</div><div class="value"><?= (int)$urgentCount ?></div></div></div>
    </div>

    <div class="section-card">
        <h2>Belum Selesai</h2>
        <?php if ($pendingCount === 0): ?>
            <div class="tile" style="display:flex; align-items:center; justify-content:center; min-height:120px; gap:8px;">
                <div class="muted">Tidak ada pengingat yang pending</div>
            </div>
        <?php else: ?>
            <table>
                <thead><tr><th>Tanggal</th><th>Judul</th><th>Jenis</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($pendingRows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['remind_on']) ?></td>
                        <td><?= htmlspecialchars($r['title']) ?></td>
                        <td><?= htmlspecialchars($r['type']) ?></td>
                        <td>
                            <form method="post" class="actions">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="btn" name="mark" value="1">Tandai selesai</button>
                                <button class="btn" name="delete" value="1">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div id="remModal" class="modal hidden">
        <div class="card">
            <h2>Tambah Pengingat</h2>
            <form method="post" class="form">
                <input type="text" name="title" placeholder="Judul pengingat" required>
                <input type="text" name="type" placeholder="Jenis (tagihan, tabungan, input)" required>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>">
                <div class="actions">
                    <button type="button" class="btn outline" id="btnCancelRem">Batal</button>
                    <button class="btn primary" name="create" value="1">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const remModal = document.getElementById('remModal');
    const btnAddReminder = document.getElementById('btnAddReminder');
    const btnCancelRem = document.getElementById('btnCancelRem');
    function openRem(){ remModal.classList.remove('hidden'); }
    function closeRem(){ remModal.classList.add('hidden'); }
    if (btnAddReminder) btnAddReminder.addEventListener('click', openRem);
    if (btnCancelRem) btnCancelRem.addEventListener('click', closeRem);
    </script>
    </main>
</body>
</html>
