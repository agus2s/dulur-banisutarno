<?php
// API autentikasi pengguna.
// POST api/auth.php  { "username": "admin", "password": "secret" }
//      -> { "ok": true, "user_id": 1, "username": "admin", "token": "..." }
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

// Cek sesi aktif: GET api/auth.php -> { ok, user_id, username } / 401
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    session_start();
    if (!empty($_SESSION['user_id'])) {
        out(['ok' => true, 'user_id' => (int)$_SESSION['user_id'], 'username' => $_SESSION['username'] ?? 'admin']);
    }
    out(['error' => 'Belum login'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['error' => 'Metode tidak didukung'], 405);

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$username = trim((string)($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($username === '' || $password === '') out(['error' => 'Username dan password wajib'], 422);

try {
    $st = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
    $st->execute([$username]);
    $user = $st->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        out(['error' => 'Kredensial tidak valid'], 401);
    }

    // Sesi sudah dimulai oleh includes/helpers.php; tinggal isi
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    // Token sederhana berbasis session
    $token = hash('sha256', session_id() . $_SERVER['REMOTE_ADDR'] . $user['id']);

    out(['ok' => true, 'user_id' => (int)$user['id'], 'username' => $user['username'], 'token' => $token]);
} catch (PDOException $e) {
    out(['error' => $e->getMessage()], 500);
}
