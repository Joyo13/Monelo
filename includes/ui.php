<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utils.php';

function render_sidebar(string $active = ''): void {
    $items = [
        ['href' => 'dashboard.php', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['href' => 'transactions.php', 'label' => 'Transaksi', 'key' => 'transactions'],
        ['href' => 'goals.php', 'label' => 'Tabungan', 'key' => 'goals'],
        ['href' => 'budgets.php', 'label' => 'Anggaran', 'key' => 'budgets'],
        ['href' => 'reports.php', 'label' => 'Laporan', 'key' => 'reports'],
        ['href' => 'profile.php', 'label' => 'Pengaturan', 'key' => 'profile'],
    ];
    echo '<aside class="sidebar"><div class="brand">MONELO</div><nav class="menu">';
    foreach ($items as $it) {
        $cls = 'menu-item' . ($active === $it['key'] ? ' active' : '');
        echo '<a class="' . $cls . '" href="' . $it['href'] . '">' . $it['label'] . '</a>';
    }
    $user = current_user();
    if ($user) {
        $initial = strtoupper(substr(($user['name'] ?: $user['email']), 0, 1));
        echo '<div class="user-card"><div class="avatar">' . $initial . '</div><div class="user-info"><div class="name">' . htmlspecialchars($user['name'] ?: 'Pengguna') . '</div><div class="plan">Free Plan</div></div></div>';
    }
    echo '</nav></aside>';
}

function render_topbar(?array $user = null): void {
    $name = $user['name'] ?? 'Pengguna';
    $initial = strtoupper(substr($name, 0, 1));
    $date = indo_date_long();
    echo '<div class="topbar"><div><div class="greet">Halo, ' . htmlspecialchars($name) . '!</div><div class="muted">' . $date . '</div></div><div class="actions"><input class="search" placeholder="Cari transaksi..."/><div class="avatar small">' . $initial . '</div></div></div>';
}
