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

$stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) AS total_income, COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) AS total_expense FROM transactions WHERE user_id = ?");
$stmt->execute([$uid]);
$totals = $stmt->fetch() ?: ['total_income'=>0,'total_expense'=>0];
$balance = (float)$totals['total_income'] - (float)$totals['total_expense'];

$stmt = $db->prepare("SELECT category, COALESCE(SUM(amount),0) AS spent FROM transactions WHERE user_id = ? AND type='expense' AND occurred_on >= DATE_FORMAT(CURDATE(),'%Y-%m-01') GROUP BY category ORDER BY spent DESC LIMIT 6");
$stmt->execute([$uid]);
$byCategory = $stmt->fetchAll();

$stmt = $db->prepare("SELECT b.category, b.limit_amount, COALESCE(SUM(t.amount),0) AS spent FROM budgets b LEFT JOIN transactions t ON t.user_id=b.user_id AND t.type='expense' AND t.category=b.category AND MONTH(t.occurred_on)=b.period_month AND YEAR(t.occurred_on)=b.period_year WHERE b.user_id=? AND b.period_month=MONTH(CURDATE()) AND b.period_year=YEAR(CURDATE()) GROUP BY b.id ORDER BY b.category");
$stmt->execute([$uid]);
$budgets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container layout">
    <?php render_sidebar('dashboard'); ?>
    <main>
        <?php render_topbar(current_user()); ?>
        <div class="hero-card">
            <div class="label">Saldo Total</div>
            <div class="balance"><?= rupiah($balance) ?></div>
            <div class="mini">
                <div class="tile"><div class="muted">Pemasukan</div><div><?= rupiah((float)$totals['total_income']) ?></div></div>
                <div class="tile"><div class="muted">Pengeluaran</div><div><?= rupiah((float)$totals['total_expense']) ?></div></div>
            </div>
        </div>
        <div class="chips">
            <a class="chip chip-transaksi" href="transactions.php"><span class="ico"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg></span> Transaksi</a>
            <a class="chip chip-income" href="transactions.php?type=income"><span class="ico"><svg viewBox="0 0 24 24"><path d="M12 5l-6 6M12 5l6 6M12 5v14" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span> Pemasukan</a>
            <a class="chip chip-expense" href="transactions.php?type=expense"><span class="ico"><svg viewBox="0 0 24 24"><path d="M12 19l-6-6M12 19l6-6M12 19V5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span> Pengeluaran</a>
            <a class="chip chip-goals" href="goals.php"><span class="ico"><svg viewBox="0 0 24 24"><rect x="6" y="8" width="12" height="10" rx="3" stroke="#fff" stroke-width="2" fill="none"/><circle cx="12" cy="6" r="3" stroke="#fff" stroke-width="2" fill="none"/></svg></span> Tabungan</a>
            <a class="chip chip-reports" href="reports.php"><span class="ico"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="3" height="7" fill="#fff"/><rect x="10" y="7" width="3" height="10" fill="#fff"/><rect x="15" y="12" width="3" height="5" fill="#fff"/></svg></span> Laporan</a>
        </div>
        <div class="sections">
            <div class="section-card">
                <h2>Distribusi Pengeluaran (Bulan Ini)</h2>
                <canvas id="catChart" height="120"></canvas>
                <script>
                    const catLabels = <?= json_encode(array_column($byCategory, 'category')) ?>;
                    const catData = <?= json_encode(array_map('floatval', array_column($byCategory, 'spent'))) ?>;
                    const ctx = document.getElementById('catChart').getContext('2d');
                    new Chart(ctx, { type: 'doughnut', data: { labels: catLabels, datasets: [{ data: catData, backgroundColor: ['#f97316','#3b82f6','#ec4899','#eab308','#8b5cf6'] }] }, options: { plugins: { legend: { position: 'bottom' } } } });
                </script>
            </div>
            <div class="section-card">
                <h2>Peringatan Anggaran</h2>
                <?php foreach ($budgets as $b): $warn = (float)$b['spent'] >= (float)$b['limit_amount']; ?>
                    <div class="tile">
                        <div class="muted">Kategori: <?= htmlspecialchars($b['category']) ?></div>
                        <div>Terpakai: <?= rupiah((float)$b['spent']) ?> / Batas: <?= rupiah((float)$b['limit_amount']) ?></div>
                        <?php if ($warn): ?><div class="warning">Melebihi batas</div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>
