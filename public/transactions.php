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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $type = $_POST['type'] === 'income' ? 'income' : 'expense';
        $amount = (float)$_POST['amount'];
        $category = trim($_POST['category'] ?? 'General');
        $notes = trim($_POST['notes'] ?? '');
        $date = $_POST['date'] ?: date('Y-m-d');
        $stmt = $db->prepare('INSERT INTO transactions (user_id, type, amount, category, notes, occurred_on, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$uid, $type, $amount, $category, $notes, $date]);
    }
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare('DELETE FROM transactions WHERE id=? AND user_id=?');
        $stmt->execute([$id, $uid]);
    }
}

$period = $_GET['period'] ?? 'month';
$q = trim($_GET['q'] ?? '');
$typeFilter = $_GET['type'] ?? 'all';
$where = 'user_id = ?';
if ($period === 'month') {
    $where .= ' AND MONTH(occurred_on)=MONTH(CURDATE()) AND YEAR(occurred_on)=YEAR(CURDATE())';
} elseif ($period === 'week') {
    $where .= ' AND YEARWEEK(occurred_on, 1)=YEARWEEK(CURDATE(), 1)';
} elseif ($period === 'day') {
    $where .= ' AND occurred_on=CURDATE()';
}
if ($typeFilter === 'income') { $where .= " AND type='income'"; }
if ($typeFilter === 'expense') { $where .= " AND type='expense'"; }
if ($q !== '') { $where .= ' AND (category LIKE ? OR notes LIKE ?)'; }

$sql = "SELECT id, type, amount, category, notes, occurred_on FROM transactions WHERE $where ORDER BY occurred_on DESC, id DESC";
$params = [$uid];
if ($q !== '') { $like = '%' . $q . '%'; $params[] = $like; $params[] = $like; }
$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/main.js"></script>
</head>
<body>
<div class="container layout">
    <?php render_sidebar('transactions'); ?>
    <main>
        <?php render_topbar(current_user()); ?>
    <h1>Transaksi</h1>
    <div class="section-card">
        <h2>Tambahkan Transaksi</h2>
        <form method="post" class="form">
            <select name="type">
                <option value="income">Pemasukan</option>
                <option value="expense">Pengeluaran</option>
            </select>
            <input type="number" step="0.01" name="amount" placeholder="Nominal" required>
            <input type="text" name="category" placeholder="Kategori" required>
            <input type="date" name="date" value="<?= date('Y-m-d') ?>">
            <input type="text" name="notes" placeholder="Catatan">
            <div class="actions">
                <button class="btn primary" name="create" value="1">Simpan</button>
            </div>
        </form>
    </div>

    <div class="report-toolbar">
        <div style="flex:1;">
            <input class="search" id="txSearch" placeholder="Cari transaksi..." value="<?= htmlspecialchars($q) ?>" style="width:100%;">
        </div>
        <div class="report-actions">
            <select id="typeFilter" class="search">
                <option value="all" <?= $typeFilter==='all'?'selected':'' ?>>Semua</option>
                <option value="income" <?= $typeFilter==='income'?'selected':'' ?>>Pemasukan</option>
                <option value="expense" <?= $typeFilter==='expense'?'selected':'' ?>>Pengeluaran</option>
            </select>
            <button class="btn" onclick="exportTableToCSV('#txTable','transaksi.csv')">Ekspor CSV</button>
        </div>
    </div>

    <div class="section-card">
        <?php if (count($items) === 0): ?>
            <div class="tile" style="display:flex; align-items:center; justify-content:center; min-height:120px;">
                <div class="muted">Tidak ada transaksi ditemukan</div>
            </div>
        <?php else: ?>
            <table id="txTable">
                <thead>
                <tr><th>Tanggal</th><th>Jenis</th><th>Nominal</th><th>Kategori</th><th>Catatan</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars($it['occurred_on']) ?></td>
                        <td><?= htmlspecialchars($it['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran') ?></td>
                        <td><?= rupiah((float)$it['amount']) ?></td>
                        <td><?= htmlspecialchars($it['category']) ?></td>
                        <td><?= htmlspecialchars($it['notes'] ?? '') ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                <button class="btn" name="delete" value="1">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
    const typeSel = document.getElementById('typeFilter');
    const searchInp = document.getElementById('txSearch');
    if (typeSel) typeSel.addEventListener('change', () => {
        const t = typeSel.value;
        const qv = searchInp.value.trim();
        const url = new URL(window.location.href);
        url.searchParams.set('type', t);
        if (qv) { url.searchParams.set('q', qv); } else { url.searchParams.delete('q'); }
        window.location.href = url.toString();
    });
    if (searchInp) searchInp.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const t = typeSel.value;
            const url = new URL(window.location.href);
            url.searchParams.set('type', t);
            const qv = searchInp.value.trim();
            if (qv) { url.searchParams.set('q', qv); } else { url.searchParams.delete('q'); }
            window.location.href = url.toString();
        }
    });
    </script>
    </main>
</body>
</html>
