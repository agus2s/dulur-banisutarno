<?php
// API ganti password user yang sedang login.
// POST api/change-password.php { "current": "...", "new": "...", "confirm": "..." }
//      -> { ok: true }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

function out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $pdo = db();
} catch (Throwable $e) {
    out(['error' => 'Blog belum siap — buka install.php untuk menyiapkannya.', 'install' => true], 500);
}

if (!is_logged_in()) out(['error' => 'Unauthorized — silakan login dulu.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['error' => 'Metode tidak didukung'], 405);

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$cur = (string)($input['current'] ?? '');
$new = (string)($input['new'] ?? '');
$conf = (string)($input['confirm'] ?? '');

try {
    $st = $pdo->prepare('SELECT id, password FROM users WHERE id = ? LIMIT 1');
    $st->execute([$_SESSION['user_id']]);
    $user = $st->fetch();
    if (!$user) out(['error' => 'User tidak ditemukan.'], 404);
    if (!password_verify($cur, $user['password'])) out(['error' => 'Password lama salah.'], 403);
    if (strlen($new) < 6) out(['error' => 'Password baru minimal 6 karakter.'], 422);
    if ($new !== $conf) out(['error' => 'Konfirmasi password baru tidak cocok.'], 422);

    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
        ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
    out(['ok' => true]);
} catch (PDOException $e) {
    out(['error' => 'Gagal menyimpan. Coba lagi.'], 500);
}
