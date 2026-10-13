<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = db();
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per = POSTS_PER_PAGE;
$off = ($page - 1) * $per;

$where = "WHERE status = 'published'";
$params = [];
if ($q !== '') { $where .= " AND (title LIKE ? OR content_md LIKE ?)"; $params = ["%$q%", "%$q%"]; }

$total = 0;
$posts = [];
try {
    $st = $pdo->prepare("SELECT COUNT(*) FROM posts $where");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
    $st = $pdo->prepare("SELECT id,title,slug,excerpt,cover,status,views,created_at, (SELECT COUNT(*) FROM post_likes WHERE post_id = posts.id) AS likes FROM posts $where ORDER BY created_at DESC LIMIT $per OFFSET $off");
    $st->execute($params);
    $posts = $st->fetchAll();
} catch (PDOException $e) {
    // kemungkinan belum install
    header('Location: ' . base_url('install.php'));
    exit;
}
$pages = max(1, (int)ceil($total / $per));

// Nav halaman statis
$navPages = [];
try {
    $navPages = $pdo->query("SELECT title, slug FROM pages WHERE status='published' AND show_in_nav = 1 ORDER BY title ASC")->fetchAll();
} catch (PDOException $e) { $navPages = []; }

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(site_name()) ?> — <?= e(site_desc()) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="<?= base_url('assets/favicon.svg') ?>">
<link rel="apple-touch-icon" href="<?= base_url('assets/icons/apple-touch-icon.png') ?>">
<link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
<meta name="theme-color" content="#4f46e5">
<meta name="color-scheme" content="light dark">
<script>try{if((localStorage.getItem('tema')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'))==='dark')document.documentElement.dataset.theme='dark';}catch(e){}</script>
<link rel="stylesheet" href="<?= base_url('assets/style.css') ?>">
<style>:root{--brand:<?= e(accent()) ?>;--brand2:<?= e(shade_hex(accent(), 0.8)) ?>;--grad-from:<?= e(grad_from()) ?>;--grad-to:<?= e(grad_to()) ?>}.logo span{color:color-mix(in srgb, var(--brand) 55%, #fff)}.badge{background:color-mix(in srgb, var(--brand) 12%, #fff)}</style>
<style>
  body{font-family:"Inter",ui-sans-serif,system-ui,"Segoe UI",Roboto,Arial,sans-serif}
  .lucide{width:16px;height:16px;vertical-align:-3px}
  .logo .lucide{width:22px;height:22px;vertical-align:-4px}
  .card-cover .lucide{width:48px;height:48px;opacity:.55}
  .badge{display:inline-flex;align-items:center;gap:6px}
  .nav-links{display:flex;align-items:center}
  .nav-links a{display:inline-flex;align-items:center;gap:6px}
  .card-body a.more,.search button{display:inline-flex;align-items:center;gap:6px}
</style>
</head>
<body>
<nav class="nav"><div class="nav-inner">
  <a class="logo" href="<?= base_url('index.php') ?>"><i data-lucide="notebook-pen"></i> <?php [$logoA, $logoB] = site_name_parts(); ?><span><?= e($logoA) ?></span><?= $logoB !== '' ? e($logoB) : '' ?></a>
  <div class="nav-links">
    <a href="<?= base_url('index.php') ?>" class="active"><i data-lucide="house"></i>Beranda</a>
    <?php foreach ($navPages as $np): ?>
      <a href="<?= page_url($np['slug']) ?>"><i data-lucide="file-text"></i><?= e($np['title']) ?></a>
    <?php endforeach; ?>
    <button type="button" id="theme-toggle" class="theme-toggle" title="Mode gelap" aria-label="Ganti tema gelap/terang"><i data-lucide="moon"></i></button>
  </div>
</div></nav>

<header class="hero" style="background:linear-gradient(135deg,<?= e(grad_from()) ?>,<?= e(grad_to()) ?>)"><div class="container">
  <h1><?= e(site_name()) ?></h1>
  <p><?= e(site_desc()) ?></p>
  <form class="search" method="get">
    <input type="text" name="q" placeholder="Cari artikel…" value="<?= e($q) ?>">
    <button type="submit"><i data-lucide="search"></i>Cari</button>
  </form>
</div></header>

<main class="container">
  <?php if ($q !== ''): ?>
    <p>Hasil pencarian <strong><?= e($q) ?></strong>: <?= $total ?> artikel. <a href="index.php">reset</a></p>
  <?php endif; ?>

  <?php if (!$posts): ?>
    <div class="form"><p>Belum ada artikel. <?php if (is_logged_in()): ?><a href="<?= base_url('admin/edit.php') ?>">Tulis artikel pertama <i data-lucide="arrow-right"></i></a><?php else: ?><a href="<?= base_url('install.php') ?>">Jalankan instalasi / tambah data contoh <i data-lucide="arrow-right"></i></a><?php endif; ?></p></div>
  <?php else: ?>
  <div class="grid">
    <?php foreach ($posts as $p): ?>
    <article class="card">
      <div class="card-cover"><?php if ($p['cover']): ?><img src="<?= e($p['cover']) ?>" alt="cover"><?php else: ?><i data-lucide="file-text"></i><?php endif; ?></div>
      <div class="card-body">
        <span class="badge"><i data-lucide="eye"></i> <?= (int)$p['views'] ?> dibaca • <i data-lucide="heart"></i> <?= (int)$p['likes'] ?></span>
        <h3><a href="<?= base_url('post.php?slug=' . urlencode($p['slug'])) ?>"><?= e($p['title']) ?></a></h3>
        <div class="meta"><i data-lucide="calendar-days"></i> <?= time_indo($p['created_at']) ?></div>
        <p class="excerpt"><?= e($p['excerpt'] ?: '(tanpa ringkasan)') ?></p>
        <a class="more" href="<?= base_url('post.php?slug=' . urlencode($p['slug'])) ?>">Baca selengkapnya <i data-lucide="arrow-right"></i></a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <div class="pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <?php if ($i === $page): ?><span class="cur"><?= $i ?></span>
      <?php else: ?><a href="?<?= http_build_query(['q' => $q, 'page' => $i]) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <footer class="footer">
    <?php if (!empty($navPages)): ?>
    <nav style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:10px">
      <?php foreach ($navPages as $np): ?>
        <a href="<?= page_url($np['slug']) ?>"><i data-lucide="file-text"></i><?= e($np['title']) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?= e(footer_text()) ?></footer>
</main>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
<script src="<?= base_url('assets/theme.js') ?>"></script>
</body>
</html>
