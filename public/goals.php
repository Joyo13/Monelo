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
        $icon = trim($_POST['icon'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $target = (float)$_POST['target'];
        $initial = (float)($_POST['initial_saved'] ?? 0);
        $deadline = $_POST['deadline'] ?: null;
        $dispName = $icon ? ($icon . ' ' . $name) : $name;
        $stmt = $db->prepare('INSERT INTO goals (user_id, name, target_amount, saved_amount, deadline, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$uid, $dispName, $target, $initial, $deadline]);
    }
    if (isset($_POST['update'])) {
        $id = (int)$_POST['id'];
        $saved = (float)$_POST['saved_amount'];
        $stmt = $db->prepare('UPDATE goals SET saved_amount=? WHERE id=? AND user_id=?');
        $stmt->execute([$saved, $id, $uid]);
    }
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare('DELETE FROM goals WHERE id=? AND user_id=?');
        $stmt->execute([$id, $uid]);
    }
}

$stmt = $db->prepare('SELECT id, name, target_amount, saved_amount, deadline FROM goals WHERE user_id=? ORDER BY deadline IS NULL, deadline');
$stmt->execute([$uid]);
$goals = $stmt->fetchAll();

$total_saved = 0.0; $total_target = 0.0; $achieved = 0; $total_goals = count($goals);
foreach ($goals as $g) {
    $total_saved += (float)$g['saved_amount'];
    $total_target += (float)$g['target_amount'];
    if ((float)$g['saved_amount'] >= (float)$g['target_amount'] && (float)$g['target_amount'] > 0) { $achieved++; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabungan</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container layout">
    <?php render_sidebar('goals'); ?>
    <main>
        <?php render_topbar(current_user()); ?>
    <h1>Tabungan</h1>
    <div class="report-toolbar">
        <div class="muted">Kelola target tabungan dan tujuan finansial Anda</div>
        <div><button class="btn primary" id="btnNewGoal">Buat Target Utama</button></div>
    </div>

    <div class="stat-grid">
      <div class="stat"><div class="icon green">ⓘ</div><div><div class="title">Total Tabungan</div><div class="value"><?= rupiah((float)$total_saved) ?></div></div></div>
      <div class="stat"><div class="icon teal">🎯</div><div><div class="title">Total Target</div><div class="value"><?= rupiah((float)$total_target) ?></div></div></div>
      <div class="stat"><div class="icon green">✔</div><div><div class="title">Target Tercapai</div><div class="value"><?= (int)$achieved ?> / <?= (int)$total_goals ?></div></div></div>
    </div>

    <div class="section-card">
        <?php if (count($goals) === 0): ?>
            <div class="tile" style="display:flex; align-items:center; justify-content:center; gap:10px; min-height:120px;">
                <div class="muted">Belum ada target tabungan</div>
            </div>
            <div style="margin-top:10px;">
                <button class="btn primary" id="btnFirstGoal">Buat Target Utama</button>
            </div>
        <?php else: ?>
            <table>
                <thead><tr><th>Nama</th><th>Target</th><th>Terkumpul</th><th>Progres</th><th>Batas Waktu</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($goals as $g): $progress = (float)$g['target_amount']>0 ? min(100, round(((float)$g['saved_amount']/(float)$g['target_amount'])*100)) : 0; ?>
                    <tr>
                        <td><?= htmlspecialchars($g['name']) ?></td>
                        <td><?= rupiah((float)$g['target_amount']) ?></td>
                        <td><?= rupiah((float)$g['saved_amount']) ?></td>
                        <td><?= $progress ?>%</td>
                        <td><?= htmlspecialchars($g['deadline'] ?? '-') ?></td>
                        <td>
                            <form method="post" class="actions">
                                <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                                <input type="number" step="0.01" name="saved_amount" placeholder="Ubah terkumpul" value="<?= (float)$g['saved_amount'] ?>">
                                <button class="btn" name="update" value="1">Ubah</button>
                                <button class="btn" name="delete" value="1">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div id="goalModal" class="modal hidden">
        <div class="card">
            <h2>Buat Target Tabungan</h2>
            <form method="post" class="form" id="goalForm">
                <div class="pill-icons" id="iconPicker">
                    <div class="icon-option" data-ico="🎯">🎯</div>
                    <div class="icon-option" data-ico="🏖️">🏖️</div>
                    <div class="icon-option" data-ico="🚗">🚗</div>
                    <div class="icon-option" data-ico="🏡">🏡</div>
                    <div class="icon-option" data-ico="💍">💍</div>
                    <div class="icon-option" data-ico="🎮">🎮</div>
                    <div class="icon-option" data-ico="🍼">🍼</div>
                    <div class="icon-option" data-ico="🎓">🎓</div>
                    <div class="icon-option" data-ico="✈️">✈️</div>
                    <div class="icon-option" data-ico="📱">📱</div>
                </div>
                <input type="hidden" name="icon" id="iconValue">
                <label>Nama Target</label>
                <input type="text" name="name" placeholder="Contoh: Liburan ke Bali" required>
                <label>Target (Rp)</label>
                <input type="number" step="0.01" name="target" placeholder="5000000" required>
                <label>Tabungan Awal (Rp)</label>
                <input type="number" step="0.01" name="initial_saved" placeholder="0" value="0">
                <label>Target Tanggal</label>
                <input type="date" name="deadline" placeholder="dd/mm/yyyy">
                <div class="actions">
                    <button type="button" class="btn outline" id="btnCancelModal">Batal</button>
                    <button class="btn primary" name="create" value="1">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const modal = document.getElementById('goalModal');
    const btnNew = document.getElementById('btnNewGoal');
    const btnFirst = document.getElementById('btnFirstGoal');
    const btnCancel = document.getElementById('btnCancelModal');
    const iconPicker = document.getElementById('iconPicker');
    const iconValue = document.getElementById('iconValue');
    function openModal(){ modal.classList.remove('hidden'); }
    function closeModal(){ modal.classList.add('hidden'); }
    if (btnNew) btnNew.addEventListener('click', openModal);
    if (btnFirst) btnFirst.addEventListener('click', openModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);
    iconPicker.querySelectorAll('.icon-option').forEach(el => {
        el.addEventListener('click', () => {
            iconPicker.querySelectorAll('.icon-option').forEach(i => i.classList.remove('selected'));
            el.classList.add('selected');
            iconValue.value = el.getAttribute('data-ico');
        });
    });
    </script>
    </main>
</body>
</html>
