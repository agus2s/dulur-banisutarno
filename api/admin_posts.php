<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
// API CRUD Posts untuk Admin Panel (terproteksi session).
// GET    api/admin_posts.php                 -> { posts: [...] } (semua status)
// GET    api/admin_posts.php?slug=xxx        -> { post: {...} } (1 post + content_md)
// POST   api/admin_posts.php {title, slug?, content_md|markdown, excerpt?, cover?, status?}
// PUT    api/admin_posts.php {id|slug, title?, ...}
// DELETE api/admin_posts.php?slug=xxx  (atau ?id=1)
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

$method = $_SERVER['REQUEST_METHOD'];

try {
    // --- Ambil 1 post ---
    if ($method === 'GET' && trim($_GET['slug'] ?? '') !== '') {
        $slug = trim($_GET['slug']);
        $st = $pdo->prepare('SELECT id, title, slug, excerpt, cover, content_md, status, views, created_at, updated_at FROM posts WHERE slug = ? LIMIT 1');
        $st->execute([$slug]);
        $post = $st->fetch();
        if (!$post) out(['error' => 'Artikel tidak ditemukan'], 404);
        $post['id'] = (int)$post['id'];
        $post['views'] = (int)$post['views'];
        $post['markdown'] = $post['content_md'];
        $post['date_indo'] = time_indo($post['created_at']);
        out(['post' => $post]);
    }

    // --- Daftar semua post ---
    if ($method === 'GET') {
        $st = $pdo->query('SELECT id, title, slug, excerpt, cover, status, views, created_at, updated_at FROM posts ORDER BY created_at DESC');
        $posts = array_map(function ($p) {
            $p['id'] = (int)$p['id'];
            $p['views'] = (int)$p['views'];
            $p['date_indo'] = time_indo($p['created_at']);
            return $p;
        }, $st->fetchAll());
        out(['posts' => $posts]);
    }

    // --- Buat / Update ---
    if ($method === 'POST' || $method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $title = trim((string)($input['title'] ?? ''));
        $content = (string)($input['content_md'] ?? $input['markdown'] ?? '');
        $excerpt = trim((string)($input['excerpt'] ?? ''));
        $cover = trim((string)($input['cover'] ?? ''));
        $status = ($input['status'] ?? 'published') === 'draft' ? 'draft' : 'published';

        if ($title === '' || $content === '') out(['error' => 'Judul dan isi artikel wajib diisi.'], 422);
        if ($excerpt === '') $excerpt = markdown_excerpt($content);

        if ($method === 'POST') {
            $slugIn = trim((string)($input['slug'] ?? ''));
            $slug = $slugIn !== '' ? slugify($slugIn) : unique_slug($pdo, $title);
            // pastikan unik meski user mengetik manual
            if ($slugIn !== '') {
                $chk = $pdo->prepare('SELECT id FROM posts WHERE slug = ? LIMIT 1');
                $chk->execute([$slug]);
                if ($chk->fetch()) $slug = unique_slug($pdo, $slug);
            }
            $st = $pdo->prepare('INSERT INTO posts (title, slug, excerpt, cover, content_md, status) VALUES (?,?,?,?,?,?)');
            $st->execute([$title, $slug, $excerpt, ($cover !== '' ? $cover : null), $content, $status]);
            out(['ok' => true, 'slug' => $slug, 'id' => (int)$pdo->lastInsertId()], 201);
        }

        // PUT: cari by id dulu, fallback slug
        $id = (int)($input['id'] ?? 0);
        $slugKey = trim((string)($input['slug'] ?? ''));
        if ($id > 0) {
            $st = $pdo->prepare('SELECT id, slug FROM posts WHERE id = ? LIMIT 1');
            $st->execute([$id]);
        } elseif ($slugKey !== '') {
            $st = $pdo->prepare('SELECT id, slug FROM posts WHERE slug = ? LIMIT 1');
            $st->execute([$slugKey]);
        } else {
            out(['error' => 'Parameter id/slug wajib untuk update.'], 422);
        }
        $row = $st->fetch();
        if (!$row) out(['error' => 'Artikel tidak ditemukan'], 404);

        // slug boleh diganti dari editor; jaga keunikan
        $newSlug = $slugKey !== '' ? slugify($slugKey) : $row['slug'];
        if ($newSlug !== $row['slug']) {
            $chk = $pdo->prepare('SELECT id FROM posts WHERE slug = ? AND id != ? LIMIT 1');
            $chk->execute([$newSlug, $row['id']]);
            if ($chk->fetch()) $newSlug = unique_slug($pdo, $newSlug, (int)$row['id']);
        }

        $upd = $pdo->prepare('UPDATE posts SET title = ?, slug = ?, excerpt = ?, cover = ?, content_md = ?, status = ? WHERE id = ?');
        $upd->execute([$title, $newSlug, $excerpt, ($cover !== '' ? $cover : null), $content, $status, $row['id']]);
        out(['ok' => true, 'slug' => $newSlug, 'id' => (int)$row['id']]);
    }

    // --- Hapus ---
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        $slug = trim($_GET['slug'] ?? '');
        if ($id > 0) {
            $st = $pdo->prepare('DELETE FROM posts WHERE id = ?');
            $st->execute([$id]);
        } elseif ($slug !== '') {
            $st = $pdo->prepare('DELETE FROM posts WHERE slug = ?');
            $st->execute([$slug]);
        } else {
            out(['error' => 'Parameter id/slug wajib untuk hapus.'], 422);
        }
        if ($st->rowCount() === 0) out(['error' => 'Artikel tidak ditemukan'], 404);
        out(['ok' => true]);
    }

    out(['error' => 'Metode tidak didukung'], 405);
} catch (PDOException $e) {
    out(['error' => $e->getMessage()], 500);
}
