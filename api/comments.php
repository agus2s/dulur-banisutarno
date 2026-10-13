<?php
// API komentar artikel.
// GET  api/comments.php?slug=judul-artikel          -> { enabled, total, comments:[...] }
// POST api/comments.php  { slug, author, email?, content, parent_id?, website? }
//      -> { ok, pending:bool, comment? }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input  = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw ?: '', true) ?: [];
}

$slug = trim((string)($input['slug'] ?? $_GET['slug'] ?? ''));
if ($slug === '') out(['error' => 'Parameter slug wajib diisi'], 400);

if (!comments_enabled()) out(['error' => 'Kolom komentar dinonaktifkan'], 403);

try {
    $st = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND status = 'published' LIMIT 1");
    $st->execute([$slug]);
    $postId = $st->fetchColumn();
    if ($postId === false) out(['error' => 'Artikel tidak ditemukan'], 404);
    $postId = (int)$postId;

    // --- Daftar komentar ---
    if ($method === 'GET') {
        $tree = comments_for_post($postId, true);
        out([
            'ok' => true,
            'enabled' => true,
            'auto_approve' => comments_auto_approve(),
            'total' => comment_count($postId, true),
            'comments' => $tree,
        ]);
    }

    if ($method !== 'POST') out(['error' => 'Metode tidak didukung'], 405);

    // Honeypot: field tersembunyi, hanya diisi bot.
    if (trim((string)($input['website'] ?? '')) !== '') {
        out(['error' => 'Komentar ditolak (spam).'], 400);
    }

    // Cooldown 15 detik per sesi.
    $now = time();
    if (isset($_SESSION['last_comment_ts']) && $now - (int)$_SESSION['last_comment_ts'] < 15) {
        out(['error' => 'Terlalu cepat. Tunggu beberapa detik lagi.'], 429);
    }

    $author  = trim((string)($input['author'] ?? ''));
    $email   = trim((string)($input['email'] ?? ''));
    $content = trim((string)($input['content'] ?? ''));
    $parentId = (int)($input['parent_id'] ?? 0) ?: null;

    if (mb_strlen($author) < 2 || mb_strlen($author) > 80) {
        out(['error' => 'Nama harus 2–80 karakter.'], 422);
    }
    if (mb_strlen($content) < 3 || mb_strlen($content) > 2000) {
        out(['error' => 'Komentar harus 3–2000 karakter.'], 422);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        out(['error' => 'Alamat email tidak valid.'], 422);
    }

    // Validasi parent: harus komentar approved milik artikel yang sama.
    if ($parentId !== null) {
        $stp = $pdo->prepare("SELECT id FROM comments WHERE id = ? AND post_id = ? AND status = 'approved' LIMIT 1");
        $stp->execute([$parentId, $postId]);
        if (!$stp->fetchColumn()) out(['error' => 'Komentar induk tidak ditemukan.'], 422);
    }

    $status = comments_auto_approve() ? 'approved' : 'pending';
    $ins = $pdo->prepare('INSERT INTO comments (post_id, parent_id, author, email, content, status, ip_hash) VALUES (?,?,?,?,?,?,?)');
    $ins->execute([$postId, $parentId, $author, $email !== '' ? $email : null, $content, $status, visitor_hash()]);
    $newId = (int)$pdo->lastInsertId();
    $_SESSION['last_comment_ts'] = $now;

    $auto = comments_auto_approve();
    $comment = [
        'id' => $newId,
        'parent_id' => $parentId,
        'author' => $author,
        'content' => $content,
        'status' => $status,
        'created_at' => date('Y-m-d H:i:s'),
        'date_indo' => time_indo(date('Y-m-d H:i:s')),
        'children' => [],
    ];
    out([
        'ok' => true,
        'pending' => !$auto,
        'message' => $auto ? 'Komentar terkirim.' : 'Komentar terkirim dan menunggu moderasi.',
        'comment' => $comment,
        'total' => comment_count($postId, true),
    ]);
} catch (PDOException $e) {
    out(['error' => $e->getMessage()], 500);
}
