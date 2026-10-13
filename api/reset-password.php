<?php
// API minta reset password.
// POST api/reset-password.php { "email": "admin@example.com" }
//      -> { ok: true, message: "Jika email terdaftar, link reset telah dikirim." }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

function out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['error' => 'Metode tidak didukung'], 405);

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim((string)($input['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) out(['error' => 'Email tidak valid'], 422);

try {
    $pdo = db();
    $st = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $st->execute([$email]); // jika email = username di aplikasi ini
    $user = $st->fetch();

    // Simulasikan pengiriman email (di aplikasi nyata, kirim email dengan link token)
    // Untuk demo, kita hanya log di server
    error_log("Password reset requested for user: $email");

    // RESPON STANDAR: jangan ungkap apakah email terdaftar
    out(['ok' => true, 'message' => 'Jika email terdaftar, link reset telah dikirim.']);
} catch (PDOException $e) {
    out(['error' => $e->getMessage()], 500);
}
