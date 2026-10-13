<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$me = (int)($_SESSION['user_id'] ?? 0);
if ($id && $id === $me) {
    flash('Tidak bisa menghapus akun sendiri.', 'danger');
} elseif ($id) {
    $total = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($total <= 1) {
        flash('Pengguna terakhir tidak boleh dihapus.', 'danger');
    } else {
        $st = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $st->execute([$id]);
        flash($st->rowCount() ? 'Pengguna dihapus.' : 'Pengguna tidak ditemukan.', $st->rowCount() ? 'success' : 'danger');
    }
}
header('Location: ' . base_url('admin/users.php'));
exit;
