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

$period = $_GET['period'] ?? 'month';
// Totals current month
$stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) AS total_income, COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) AS total_expense FROM transactions WHERE user_id=? AND MONTH(occurred_on)=MONTH(CURDATE()) AND YEAR(occurred_on)=YEAR(CURDATE())");
$stmt->execute([$uid]);
$totals = $stmt->fetch() ?: ['total_income'=>0,'total_expense'=>0];
$balance = (float)$totals['total_income'] - (float)$totals['total_expense'];

// Last 6 months income/expense
$stmt = $db->prepare("SELECT YEAR(occurred_on) y, MONTH(occurred_on) m, COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) income, COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) expense FROM transactions WHERE user_id=? AND occurred_on >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH),'%Y-%m-01') GROUP BY y,m ORDER BY y,m");
$stmt->execute([$uid]);
$series = $stmt->fetchAll();

// Category expenses current month
$stmt = $db->prepare("SELECT category, COALESCE(SUM(amount),0) AS spent FROM transactions WHERE user_id=? AND type='expense' AND MONTH(occurred_on)=MONTH(CURDATE()) AND YEAR(occurred_on)=YEAR(CURDATE()) GROUP BY category ORDER BY spent DESC");
$stmt->execute([$uid]);
$byCategory = $stmt->fetchAll();

// All rows for export
$stmt = $db->prepare('SELECT occurred_on, type, amount, category, notes FROM transactions WHERE user_id=? ORDER BY occurred_on DESC');
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="/assets/js/main.js"></script>
</head>
<body>
<div class="container layout">
    <?php render_sidebar('reports'); ?>
    <main>
        <?php render_topbar(current_user()); ?>
    <h1>Laporan</h1>
    <div class="report-toolbar">
        <div class="muted">Analisis keuangan dan laporan detail</div>
        <div class="report-actions">
            <select class="search" onchange="location.href='?period='+this.value">
                <option value="month" <?= $period==='month' ? 'selected' : '' ?>>Bulan Ini</option>
                <option value="6mo" <?= $period==='6mo' ? 'selected' : '' ?>>6 Bulan</option>
            </select>
            <button class="btn" onclick="exportTableToCSV('#repTable','transaksi.csv')">Excel</button>
            <button class="btn" onclick="exportTableToPDF('#repTable','transaksi.pdf')">PDF</button>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat"><div class="icon green">↗</div><div><div class="title">Total Pemasukan</div><div class="value"><?= rupiah((float)$totals['total_income']) ?></div></div></div>
        <div class="stat"><div class="icon red">↘</div><div><div class="title">Total Pengeluaran</div><div class="value"><?= rupiah((float)$totals['total_expense']) ?></div></div></div>
        <div class="stat"><div class="icon teal">⤴</div><div><div class="title">Saldo Bersih</div><div class="value"><?= rupiah($balance) ?></div></div></div>
    </div>

    <div class="sections">
        <div class="section-card">
            <h2>Pemasukan vs Pengeluaran</h2>
            <canvas id="ieChart" height="140"></canvas>
            <script>
                const months = <?= json_encode(array_map(fn($r) => date('M', strtotime(sprintf('%04d-%02d-01', (int)$r['y'], (int)$r['m']))), $series)) ?>;
                const incomeData = <?= json_encode(array_map('floatval', array_column($series,'income'))) ?>;
                const expenseData = <?= json_encode(array_map('floatval', array_column($series,'expense'))) ?>;
                new Chart(document.getElementById('ieChart').getContext('2d'), {
                    type: 'bar',
                    data: { labels: months, datasets: [
                        { label: 'Pemasukan', data: incomeData, backgroundColor: '#2cb67d' },
                        { label: 'Pengeluaran', data: expenseData, backgroundColor: '#ef4444' },
                    ] },
                    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
                });
            </script>
        </div>
        <div class="section-card">
            <h2>Tren Saldo</h2>
            <canvas id="balChart" height="140"></canvas>
            <script>
                const balanceData = incomeData.map((v,i)=> (v - expenseData[i]));
                new Chart(document.getElementById('balChart').getContext('2d'), {
                    type: 'line',
                    data: { labels: months, datasets: [ { label: 'Saldo', data: balanceData, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.2)' } ] },
                    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
                });
            </script>
        </div>
    </div>

    <div class="section-card" style="margin-top:16px;">
        <h2>Pengeluaran per Kategori</h2>
        <canvas id="catChart" height="140"></canvas>
        <script>
            const catLabels = <?= json_encode(array_column($byCategory, 'category')) ?>;
            const catData = <?= json_encode(array_map('floatval', array_column($byCategory, 'spent'))) ?>;
            new Chart(document.getElementById('catChart').getContext('2d'), {
                type: 'doughnut',
                data: { labels: catLabels, datasets: [{ data: catData, backgroundColor: ['#3da9fc','#2cb67d','#ef4444','#a7f3d0','#93c5fd','#86efac'] }] },
                options: { plugins: { legend: { position: 'bottom' } } }
            });
        </script>
    </div>

    <table id="repTable" style="display:none;">
        <thead><tr><th>Tanggal</th><th>Jenis</th><th>Nominal</th><th>Kategori</th><th>Catatan</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['occurred_on']) ?></td>
                <td><?= htmlspecialchars($r['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran') ?></td>
                <td><?= rupiah((float)$r['amount']) ?></td>
                <td><?= htmlspecialchars($r['category']) ?></td>
                <td><?= htmlspecialchars($r['notes'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </main>
</body>
</html>
