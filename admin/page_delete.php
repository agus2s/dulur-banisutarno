<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $st = db()->prepare('DELETE FROM pages WHERE id = ?');
    $st->execute([$id]);
    flash('Halaman dihapus.');
}
header('Location: ' . base_url('admin/pages.php'));
exit;
