<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
session_start();

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string {
    $base = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
    if ($base === '') {
        // deteksi folder otomatis
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        // jika script di /admin atau /api, naik satu level ke root proyek
        if (str_ends_with($dir, '/admin') || str_ends_with($dir, '/api')) $dir = dirname($dir);
        if ($dir === '/' || $dir === '.') $dir = '';
        $base = $dir;
    }
    return $base . '/' . ltrim($path, '/');
}

function abs_url(string $path = ''): string {
    // URL absolut (skema + host) untuk kebutuhan embed/salin.
    // Bila SITE_URL diisi (disarankan di produksi), selalu dipakai agar konsisten.
    if (defined('SITE_URL') && SITE_URL !== '') {
        return SITE_URL . base_url($path);
    }
    $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
    if ($proto === '') {
        $https = $_SERVER['HTTPS'] ?? '';
        $proto = ($https !== '' && $https !== 'off') ? 'https' : 'http';
    } else {
        $proto = explode(',', $proto)[0];
        $proto = trim($proto) !== '' ? trim($proto) : 'http';
    }
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? '');
    $host = trim(explode(',', (string)$host)[0] ?? '');
    if ($host === '') $host = 'localhost';
    return $proto . '://' . $host . base_url($path);
}

/** Permalink kanonis halaman statis (/halaman/slug, via rewrite .htaccess).
 *  Selalu pakai ini untuk link publik agar URL cantik & konsisten. */
function page_url(string $slug): string {
    return base_url('halaman/' . rawurlencode($slug));
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('~[^a-z0-9]+~', '-', $text);
    return trim($text, '-') ?: 'tanpa-judul';
}

function unique_slug(PDO $pdo, string $title, ?int $ignoreId = null): string {
    $slug = slugify($title);
    $base = $slug; $i = 1;
    while (true) {
        $sql = 'SELECT id FROM posts WHERE slug = ?';
        $params = [$slug];
        if ($ignoreId) { $sql .= ' AND id != ?'; $params[] = $ignoreId; }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        if (!$st->fetch()) return $slug;
        $slug = $base . '-' . (++$i);
    }
}

function unique_page_slug(PDO $pdo, string $title, ?int $ignoreId = null): string {
    $slug = slugify($title);
    $base = $slug; $i = 1;
    while (true) {
        $sql = 'SELECT id FROM pages WHERE slug = ?';
        $params = [$slug];
        if ($ignoreId) { $sql .= ' AND id != ?'; $params[] = $ignoreId; }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        if (!$st->fetch()) return $slug;
        $slug = $base . '-' . (++$i);
    }
}

/** Pastikan slug halaman unik (tambah -2, -3, ... bila perlu).
 *  Dipakai saat admin mengisi slug manual di form. */
function unique_page_slug_for(PDO $pdo, string $slug, ?int $ignoreId = null): string {
    $slug = slugify($slug);
    if ($slug === '') $slug = 'tanpa-judul';
    $base = $slug; $i = 1;
    while (true) {
        $sql = 'SELECT id FROM pages WHERE slug = ?';
        $params = [$slug];
        if ($ignoreId) { $sql .= ' AND id != ?'; $params[] = $ignoreId; }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        if (!$st->fetch()) return $slug;
        $slug = $base . '-' . (++$i);
    }
}

/** Ambil excerpt polos dari markdown */
function markdown_excerpt(string $md, int $len = 160): string {
    $txt = preg_replace('/```.*?```/s', ' ', $md);
    $txt = preg_replace('/[`#>*\-+!\[\]()|]/', ' ', $txt);
    $txt = preg_replace('/\s+/', ' ', trim($txt));
    if (mb_strlen($txt) > $len) $txt = mb_substr($txt, 0, $len) . '…';
    return $txt;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/** Baris pengguna yang sedang login (id, username, display_name, email) atau null. */
function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    try {
        $st = db()->prepare('SELECT id, username, display_name, email FROM users WHERE id = ? LIMIT 1');
        $st->execute([$_SESSION['user_id']]);
        $u = $st->fetch();
        return $u ?: null;
    } catch (Throwable $e) { return null; }
}

/** Nama tampilan pengguna login (jatuh kembali ke username). */
function display_name(): string {
    $u = current_user();
    $d = trim((string)($u['display_name'] ?? ''));
    if ($d !== '') return $d;
    return (string)($_SESSION['username'] ?? 'Admin');
}

/** Fingerprint pengunjung (IP + User-Agent + salt) untuk dedup like tanpa login.
 *  Disimpan sebagai SHA-256; IP mentah tidak pernah disimpan. */
function visitor_hash(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $salt = defined('LIKE_SALT') ? LIKE_SALT : 'blog-dulur-likes';
    return hash('sha256', $ip . '|' . $ua . '|' . $salt);
}

/** Jumlah like sebuah artikel. */
function post_like_count(int $postId): int {
    try {
        $st = db()->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = ?');
        $st->execute([$postId]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) { return 0; }
}

/** Apakah pengunjung sekarang sudah menyukai artikel ini. */
function post_is_liked(int $postId): bool {
    try {
        $st = db()->prepare('SELECT 1 FROM post_likes WHERE post_id = ? AND visitor_hash = ? LIMIT 1');
        $st->execute([$postId, visitor_hash()]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) { return false; }
}

/** Fitur komentar aktif? */
function comments_enabled(): bool {
    return setting('comments_enabled', '1') === '1';
}

/** Komentar baru langsung tampil (tak perlu moderasi)? */
function comments_auto_approve(): bool {
    return setting('comments_auto_approve', '0') === '1';
}

/** Jumlah komentar terlihat (approved) sebuah artikel. */
function comment_count(int $postId, bool $onlyApproved = true): int {
    try {
        $sql = 'SELECT COUNT(*) FROM comments WHERE post_id = ?';
        if ($onlyApproved) $sql .= " AND status = 'approved'";
        $st = db()->prepare($sql);
        $st->execute([$postId]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) { return 0; }
}

/** Ambil komentar sebuah artikel sebagai struktur bertingkat (parent_id). */
function comments_for_post(int $postId, bool $onlyApproved = true): array {
    try {
        $sql = 'SELECT id, parent_id, author, content, status, created_at FROM comments WHERE post_id = ?';
        if ($onlyApproved) $sql .= " AND status = 'approved'";
        $sql .= ' ORDER BY created_at ASC, id ASC';
        $st = db()->prepare($sql);
        $st->execute([$postId]);
        $rows = $st->fetchAll();
    } catch (Throwable $e) { return []; }

    $byId = [];
    foreach ($rows as $r) {
        $r['id'] = (int)$r['id'];
        $r['parent_id'] = $r['parent_id'] !== null ? (int)$r['parent_id'] : null;
        $r['date_indo'] = time_indo($r['created_at']);
        $r['children'] = [];
        $byId[$r['id']] = $r;
    }
    $tree = [];
    foreach ($byId as $id => &$r) {
        $pid = $r['parent_id'];
        if ($pid !== null && isset($byId[$pid])) {
            $byId[$pid]['children'][] = &$r;
        } else {
            $tree[] = &$r;
        }
    }
    unset($r);
    return $tree;
}

/** Pengaturan situs (tabel settings, fallback ke konstanta config). */
function setting(string $key, string $default = ''): string {
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    try {
        $st = db()->prepare('SELECT `v` FROM settings WHERE `k` = ? LIMIT 1');
        $st->execute([$key]);
        $v = $st->fetchColumn();
        $cache[$key] = $v === false ? $default : (string)$v;
    } catch (Throwable $e) { $cache[$key] = $default; }
    return $cache[$key];
}

function save_setting(string $key, string $value): void {
    db()->prepare('INSERT INTO settings (`k`, `v`) VALUES (?,?) ON DUPLICATE KEY UPDATE `v` = VALUES(`v`)')
        ->execute([$key, $value]);
}

function site_name(): string {
    return setting('site_name', defined('SITE_NAME') ? (string)SITE_NAME : 'Blog Dulur');
}

/** Pecah nama situs jadi [kata pertama, sisa] untuk logo dua warna. */
function site_name_parts(): array {
    $p = preg_split('/\s+/', trim(site_name()), 2);
    if (!$p || $p[0] === '') return ['Blog Dulur', ''];
    return [$p[0], $p[1] ?? ''];
}

function site_desc(): string {
    return setting('site_desc', defined('SITE_DESC') ? (string)SITE_DESC : '');
}

function footer_text(): string {
    return setting('footer_text', '© 2026 Agus Supriyadi • Blog Dulur');
}

/** Warna gradien hero (disetel via Admin → Pengaturan, selalu hex valid). */
define('DEFAULT_GRAD_FROM', '#4f46e5');
define('DEFAULT_GRAD_TO', '#db2777');
define('DEFAULT_ACCENT', '#4f46e5');

function grad_from(): string {
    $v = setting('grad_from', DEFAULT_GRAD_FROM);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : DEFAULT_GRAD_FROM;
}

function grad_to(): string {
    $v = setting('grad_to', DEFAULT_GRAD_TO);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : DEFAULT_GRAD_TO;
}

/** Tampilkan tombol "teks asli" di halaman artikel publik. */
function show_article_source(): bool {
    return setting('show_source', '1') === '1';
}

/** Warna aksen situs (badge, tombol, link). */
function accent(): string {
    $v = setting('accent', DEFAULT_ACCENT);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : DEFAULT_ACCENT;
}

/** Gelapkan/terangkan hex (#rrggbb). $factor <1 gelap, >1 terang. */
function shade_hex(string $hex, float $factor): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return '#4f46e5';
    $r = min(255, max(0, (int)round(hexdec(substr($hex, 0, 2)) * $factor)));
    $g = min(255, max(0, (int)round(hexdec(substr($hex, 2, 2)) * $factor)));
    $b = min(255, max(0, (int)round(hexdec(substr($hex, 4, 2)) * $factor)));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . base_url('admin/login.php'));
        exit;
    }
}

function flash(?string $msg = null, string $type = 'success') {
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return;
    }
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function time_indo(string $datetime): string {
    $ts = strtotime($datetime);
    $bulan = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
}
