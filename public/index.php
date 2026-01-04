<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

if (current_user()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MONELO</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container">
    <section class="hero">
        <h1 class="hero-title">MONELO</h1>
        <p class="hero-sub">Pantau keuangan dan capai target finansial Anda dengan mudah</p>
        <div class="hero-cta">
            <a class="btn primary" href="register.php">Mulai Gratis</a>
            <a class="btn" href="login.php">Login</a>
        </div>
    </section>

    <section class="features">
        <h2>Fitur Unggulan</h2>
        <div class="features-grid">
            <div class="tile feature"><h3>Tracking Mudah</h3><p>Catat pemasukan dan pengeluaran harian tanpa ribet.</p></div>
            <div class="tile feature"><h3>Target Personal</h3><p>Tentukan target tabungan dan pantau progresnya.</p></div>
            <div class="tile feature"><h3>Anggaran Bulanan</h3><p>Setel batas anggaran per kategori dan dapatkan peringatan.</p></div>
            <div class="tile feature"><h3>Laporan & Ekspor</h3><p>Unduh laporan transaksi ke CSV atau PDF kapan saja.</p></div>
        </div>
    </section>
</div>
</body>
</html>
