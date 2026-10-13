<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/Markdown.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { header('Location: ' . base_url('index.php')); exit; }

$pdo = db();
try {
    $st = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1");
    $st->execute([$slug]);
    $page = $st->fetch();
} catch (PDOException $e) {
    header('Location: ' . base_url('install.php'));
    exit;
}
if (!$page) { http_response_code(404); $notfound = true; }
else {
    $html = Markdown::toHtml($page['content_md']);
    $isFamily = ($page['kind'] ?? 'biasa') === 'family';
    $tree = trim((string)($page['mermaid'] ?? ''));
    try {
        $navPages = $pdo->query("SELECT title, slug FROM pages WHERE status='published' AND show_in_nav = 1 ORDER BY title ASC")->fetchAll();
    } catch (PDOException $e) { $navPages = []; }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page) ? e($page['title']) . ' — ' . e(site_name()) : 'Tidak ditemukan' ?></title>
<link rel="canonical" href="<?= abs_url('halaman/' . urlencode($slug)) ?>">
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
<style>:root{--brand:<?= e(accent()) ?>;--brand2:<?= e(shade_hex(accent(), 0.8)) ?>}.logo span{color:color-mix(in srgb, var(--brand) 55%, #fff)}.badge{background:color-mix(in srgb, var(--brand) 12%, #fff)}</style>
<style>
  body{font-family:"Inter",ui-sans-serif,system-ui,"Segoe UI",Roboto,Arial,sans-serif}
  .lucide{width:16px;height:16px;vertical-align:-3px}
  .logo .lucide{width:22px;height:22px;vertical-align:-4px}
  .badge{display:inline-flex;align-items:center;gap:6px}
  .nav-links{display:flex;align-items:center}
  .nav-links a{display:inline-flex;align-items:center;gap:6px}
  .meta{display:inline-flex;align-items:center;gap:6px}
  .footer nav{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:10px}
  .footer nav a{display:inline-flex;align-items:center;gap:6px}
</style>
</head>
<body>
<nav class="nav"><div class="nav-inner">
  <a class="logo" href="<?= base_url('index.php') ?>"><i data-lucide="notebook-pen"></i> <?php [$logoA, $logoB] = site_name_parts(); ?><span><?= e($logoA) ?></span><?= $logoB !== '' ? e($logoB) : '' ?></a>
  <div class="nav-links">
    <a href="<?= base_url('index.php') ?>"><i data-lucide="house"></i>Beranda</a>
    <?php foreach (($navPages ?? []) as $np): ?>
      <a href="<?= page_url($np['slug']) ?>"><i data-lucide="file-text"></i><?= e($np['title']) ?></a>
    <?php endforeach; ?>
    <button type="button" id="theme-toggle" class="theme-toggle" title="Mode gelap" aria-label="Ganti tema gelap/terang"><i data-lucide="moon"></i></button>
  </div>
</div></nav>
<main class="container">
<?php if (!empty($notfound)): ?>
  <div class="form"><h2>404 — Halaman tidak ditemukan</h2><p><a href="<?= base_url('index.php') ?>">Kembali ke beranda</a></p></div>
<?php else: ?>
  <article class="article">
    <?php if (!empty($isFamily)): ?><span class="badge"><i data-lucide="users"></i>Keluarga</span>
    <?php else: ?><span class="badge"><i data-lucide="file-text"></i>Halaman Statis</span><?php endif; ?>
    <h1><?= e($page['title']) ?></h1>
    <div class="meta"><i data-lucide="calendar-days"></i> <?= time_indo($page['updated_at']) ?></div>
    <div class="markdown-body"><?= $html ?></div>
    <?php if (!empty($isFamily) && ($tree ?? '') !== ''): ?>
    <hr>
    <h3><i data-lucide="git-fork"></i> Silsilah Keluarga</h3>
    <div class="mermaid"><?= e($tree) ?></div>
    <?php endif; ?>
  </article>
<?php endif; ?>
<footer class="footer">
  <a href="<?= base_url('index.php') ?>"><i data-lucide="arrow-left"></i> Semua artikel</a>
  <?php if (!empty($navPages)): ?>
  <nav style="margin-top:10px">
    <?php foreach ($navPages as $np): ?>
      <a href="<?= page_url($np['slug']) ?>"><i data-lucide="file-text"></i><?= e($np['title']) ?></a>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>
  <div style="margin-top:10px"><?= e(footer_text()) ?></div>
</footer>
</main>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
<script src="<?= base_url('assets/theme.js') ?>"></script>
<?php if (!empty($isFamily) && ($tree ?? '') !== ''): ?>
<script type="module">
  import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@12/dist/mermaid.esm.min.mjs';
  mermaid.initialize({startOnLoad:true});
</script>
<?php endif; ?>
</body>
</html>
