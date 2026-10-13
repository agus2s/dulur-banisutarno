<?php
// API CRUD Halaman Statis untuk Admin Panel (terproteksi session).
// GET    api/admin_pages.php                 -> { pages: [...] } (semua status)
// GET    api/admin_pages.php?slug=xxx        -> { page: {...} } (1 halaman + content_md)
// POST   api/admin_pages.php {title, slug?, content_md|markdown, status?}
// PUT    api/admin_pages.php {id|slug, title?, ...}
// DELETE api/admin_pages.php?slug=xxx  (atau ?id=1)
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
    // --- Ambil 1 halaman ---
    if ($method === 'GET' && trim($_GET['slug'] ?? '') !== '') {
        $slug = trim($_GET['slug']);
        $st = $pdo->prepare('SELECT id, title, slug, content_md, kind, show_in_nav, mermaid, status, created_at, updated_at FROM pages WHERE slug = ? LIMIT 1');
        $st->execute([$slug]);
        $page = $st->fetch();
        if (!$page) out(['error' => 'Halaman tidak ditemukan'], 404);
        $page['id'] = (int)$page['id'];
        $page['show_in_nav'] = (int)($page['show_in_nav'] ?? 1);
        $page['markdown'] = $page['content_md'];
        $page['updated_indo'] = time_indo($page['updated_at']);
        out(['page' => $page]);
    }

    // --- Daftar semua halaman ---
    if ($method === 'GET') {
        $st = $pdo->query('SELECT id, title, slug, kind, show_in_nav, status, created_at, updated_at FROM pages ORDER BY title ASC');
        $pages = array_map(function ($p) {
            $p['id'] = (int)$p['id'];
            $p['show_in_nav'] = (int)($p['show_in_nav'] ?? 1);
            $p['updated_indo'] = time_indo($p['updated_at']);
            return $p;
        }, $st->fetchAll());
        out(['pages' => $pages]);
    }

    // --- Buat / Update ---
    if ($method === 'POST' || $method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $title = trim((string)($input['title'] ?? ''));
        $content = (string)($input['content_md'] ?? $input['markdown'] ?? '');
        $status = ($input['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
        $kind = ($input['kind'] ?? 'biasa') === 'family' ? 'family' : 'biasa';
        // Default tampil di nav, kecuali family yang default sembunyi (bisa dioverride eksplisit).
        $showNav = array_key_exists('show_in_nav', $input) ? (!empty($input['show_in_nav']) ? 1 : 0) : ($kind === 'family' ? 0 : 1);
        $mermaid = trim((string)($input['mermaid'] ?? ''));
        $mermaid = $mermaid !== '' ? $mermaid : null;

        if ($title === '' || $content === '') out(['error' => 'Judul dan isi halaman wajib diisi.'], 422);

        if ($method === 'POST') {
            $slugIn = trim((string)($input['slug'] ?? ''));
            $slug = $slugIn !== '' ? slugify($slugIn) : unique_page_slug($pdo, $title);
            if ($slugIn !== '') {
                $chk = $pdo->prepare('SELECT id FROM pages WHERE slug = ? LIMIT 1');
                $chk->execute([$slug]);
                if ($chk->fetch()) $slug = unique_page_slug($pdo, $slug);
            }
            $st = $pdo->prepare('INSERT INTO pages (title, slug, content_md, kind, show_in_nav, mermaid, status) VALUES (?,?,?,?,?,?,?)');
            $st->execute([$title, $slug, $content, $kind, $showNav, $mermaid, $status]);
            out(['ok' => true, 'slug' => $slug, 'id' => (int)$pdo->lastInsertId()], 201);
        }

        // PUT: cari by id dulu, fallback slug
        $id = (int)($input['id'] ?? 0);
        $slugKey = trim((string)($input['slug'] ?? ''));
        if ($id > 0) {
            $st = $pdo->prepare('SELECT id, slug FROM pages WHERE id = ? LIMIT 1');
            $st->execute([$id]);
        } elseif ($slugKey !== '') {
            $st = $pdo->prepare('SELECT id, slug FROM pages WHERE slug = ? LIMIT 1');
            $st->execute([$slugKey]);
        } else {
            out(['error' => 'Parameter id/slug wajib untuk update.'], 422);
        }
        $row = $st->fetch();
        if (!$row) out(['error' => 'Halaman tidak ditemukan'], 404);

        $newSlug = $slugKey !== '' ? slugify($slugKey) : $row['slug'];
        if ($newSlug !== $row['slug']) {
            $chk = $pdo->prepare('SELECT id FROM pages WHERE slug = ? AND id != ? LIMIT 1');
            $chk->execute([$newSlug, $row['id']]);
            if ($chk->fetch()) $newSlug = unique_page_slug($pdo, $newSlug, (int)$row['id']);
        }

        $upd = $pdo->prepare('UPDATE pages SET title = ?, slug = ?, content_md = ?, kind = ?, show_in_nav = ?, mermaid = ?, status = ? WHERE id = ?');
        $upd->execute([$title, $newSlug, $content, $kind, $showNav, $mermaid, $status, $row['id']]);
        out(['ok' => true, 'slug' => $newSlug, 'id' => (int)$row['id']]);
    }

    // --- Hapus ---
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        $slug = trim($_GET['slug'] ?? '');
        if ($id > 0) {
            $st = $pdo->prepare('DELETE FROM pages WHERE id = ?');
            $st->execute([$id]);
        } elseif ($slug !== '') {
            $st = $pdo->prepare('DELETE FROM pages WHERE slug = ?');
            $st->execute([$slug]);
        } else {
            out(['error' => 'Parameter id/slug wajib untuk hapus.'], 422);
        }
        if ($st->rowCount() === 0) out(['error' => 'Halaman tidak ditemukan'], 404);
        out(['ok' => true]);
    }

    out(['error' => 'Metode tidak didukung'], 405);
} catch (PDOException $e) {
    out(['error' => $e->getMessage()], 500);
}
