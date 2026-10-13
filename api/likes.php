<?php
// API like/unlike artikel (tanpa login, dedup via visitor_hash).
// GET  api/likes.php?slug=judul-artikel        -> { likes, liked }
// POST api/likes.php  { "slug": "...", "action": "like" | "unlike" }
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

$slug   = trim((string)($input['slug'] ?? $_GET['slug'] ?? ''));
$action = strtolower(trim((string)($input['action'] ?? $_GET['action'] ?? 'read')));

if ($slug === '') out(['error' => 'Parameter slug wajib diisi'], 400);

try {
    $st = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND status = 'published' LIMIT 1");
    $st->execute([$slug]);
    $postId = $st->fetchColumn();
    if ($postId === false) out(['error' => 'Artikel tidak ditemukan'], 404);
    $postId = (int)$postId;

    $visitor = visitor_hash();

    if ($action === 'like' || $action === 'unlike') {
        if ($method !== 'POST') out(['error' => 'Gunakan POST untuk mengubah like'], 405);
        if ($action === 'like') {
            $pdo->prepare('INSERT IGNORE INTO post_likes (post_id, visitor_hash) VALUES (?, ?)')
                ->execute([$postId, $visitor]);
        } else {
            $pdo->prepare('DELETE FROM post_likes WHERE post_id = ? AND visitor_hash = ?')
                ->execute([$postId, $visitor]);
        }
    }

    $likes = post_like_count($postId);
    $liked = post_is_liked($postId);
    out(['ok' => true, 'likes' => $likes, 'liked' => $liked]);
} catch (PDOException $e) {
    out(['error' => $e->getMessage()], 500);
}
