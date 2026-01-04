<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/utils.php';
require_once __DIR__ . '/../includes/ui.php';

require_login();
$db = get_db();
$uid = (int)$_SESSION['user_id'];
// Periode aktif (default: bulan & tahun saat ini)
$curMonth = (int)($_GET['month'] ?? date('n'));
$curYear = (int)($_GET['year'] ?? date('Y'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $category = trim($_POST['category'] ?? 'General');
        $limit = (float)$_POST['limit'];
        $month = (int)($_POST['month'] ?? date('n'));
        $year = (int)($_POST['year'] ?? date('Y'));
        $stmt = $db->prepare('INSERT INTO budgets (user_id, category, limit_amount, period_month, period_year, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$uid, $category, $limit, $month, $year]);
    }
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare('DELETE FROM budgets WHERE id=? AND user_id=?');
        $stmt->execute([$id, $uid]);
    }
}

// Ambil anggaran untuk periode aktif
$stmt = $db->prepare('SELECT id, category, limit_amount FROM budgets WHERE user_id=? AND period_month=? AND period_year=? ORDER BY category');
$stmt->execute([$uid, $curMonth, $curYear]);
$rows = $stmt->fetchAll();

// Hitung total terpakai per kategori untuk periode aktif
$stmt = $db->prepare("SELECT category, COALESCE(SUM(amount),0) AS spent FROM transactions WHERE user_id=? AND type='expense' AND MONTH(occurred_on)=? AND YEAR(occurred_on)=? GROUP BY category");
$stmt->execute([$uid, $curMonth, $curYear]);
$spentRows = $stmt->fetchAll();
$spentByCat = [];
foreach ($spentRows as $r) { $spentByCat[$r['category']] = (float)$r['spent']; }

// Stat cards
$total_budget = 0.0; $total_spent = 0.0; $over_limit = 0;
foreach ($rows as $b) {
    $lim = (float)$b['limit_amount'];
    $sp = (float)($spentByCat[$b['category']] ?? 0.0);
    $total_budget += $lim;
    $total_spent += $sp;
    if ($sp >= $lim && $lim > 0) { $over_limit++; }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anggaran</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container layout">
    <?php render_sidebar('budgets'); ?>
    <main>
        <?php render_topbar(current_user()); ?>
    <h1>Anggaran</h1>
    <div class="report-toolbar">
        <div class="muted">Kelola batas pengeluaran per kategori</div>
        <div><a class="btn primary" href="?month=<?= (int)$curMonth ?>&year=<?= (int)$curYear ?>&add=1">Tambah Anggaran</a></div>
    </div>

    <div class="stat-grid">
      <div class="stat"><div class="icon green">✔</div><div><div class="title">Total Anggaran</div><div class="value"><?= rupiah((float)$total_budget) ?></div></div></div>
      <div class="stat"><div class="icon red">↘</div><div><div class="title">Total Terpakai</div><div class="value"><?= rupiah((float)$total_spent) ?></div></div></div>
      <div class="stat"><div class="icon teal">⚠</div><div><div class="title">Melebihi Batas</div><div class="value"><?= (int)$over_limit ?> kategori</div></div></div>
    </div>

    <?php if (isset($_GET['add'])): ?>
    <div class="card">
        <form method="post" class="form">
            <input type="text" name="category" placeholder="Kategori" required>
            <input type="number" step="0.01" name="limit" placeholder="Batas Nominal" required>
            <input type="number" name="month" min="1" max="12" value="<?= (int)$curMonth ?>">
            <input type="number" name="year" min="2000" max="2100" value="<?= (int)$curYear ?>">
            <button class="btn primary" name="create" value="1">Tambah</button>
        </form>
    </div>
    <?php endif; ?>
    <div class="section-card">
        <h2>Daftar Anggaran</h2>
        <?php if (count($rows) === 0): ?>
            <div class="tile" style="display:flex; align-items:center; justify-content:center; gap:10px;">
                <div class="muted">Belum ada anggaran</div>
            </div>
            <div style="margin-top:10px;">
                <a class="btn primary" href="?month=<?= (int)$curMonth ?>&year=<?= (int)$curYear ?>&add=1">Buat Anggaran Pertama</a>
            </div>
        <?php else: ?>
        <table>
            <thead><tr><th>Kategori</th><th>Batas</th><th>Terpakai</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): $sp = (float)($spentByCat[$r['category']] ?? 0.0); $lim=(float)$r['limit_amount']; $warn = $sp >= $lim && $lim > 0; ?>
                <tr>
                    <td><?= htmlspecialchars($r['category']) ?></td>
                    <td><?= rupiah($lim) ?></td>
                    <td><?= rupiah($sp) ?></td>
                    <td><?= $warn ? 'Melebihi batas' : 'Aman' ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="btn" name="delete" value="1">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    </main>
</body>
</html>
