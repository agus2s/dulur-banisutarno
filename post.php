<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/Markdown.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { header('Location: ' . base_url('index.php')); exit; }

$pdo = db();
$navPages = [];
try {
    $navPages = $pdo->query("SELECT title, slug FROM pages WHERE status='published' AND show_in_nav = 1 ORDER BY title ASC")->fetchAll();
} catch (PDOException $e) { $navPages = []; }
$st = $pdo->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'published' LIMIT 1");
$st->execute([$slug]);
$post = $st->fetch();
if (!$post) { http_response_code(404); $notfound = true; }
else {
    $postId = (int)$post['id'];

    // Simpan komentar (mode klasik, submit biasa + redirect PRG).
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && comments_enabled()) {
        $author  = trim($_POST['author'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
        $hp = trim($_POST['website'] ?? '');
        $err = '';
        $now = time();
        if ($hp !== '') $err = 'Komentar ditolak (spam).';
        elseif (isset($_SESSION['last_comment_ts']) && $now - (int)$_SESSION['last_comment_ts'] < 15) $err = 'Terlalu cepat. Tunggu beberapa detik lagi.';
        elseif (mb_strlen($author) < 2 || mb_strlen($author) > 80) $err = 'Nama harus 2–80 karakter.';
        elseif (mb_strlen($content) < 3 || mb_strlen($content) > 2000) $err = 'Komentar harus 3–2000 karakter.';
        elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Alamat email tidak valid.';
        if ($err === '' && $parentId !== null) {
            $stp = $pdo->prepare("SELECT id FROM comments WHERE id = ? AND post_id = ? AND status = 'approved' LIMIT 1");
            $stp->execute([$parentId, $postId]);
            if (!$stp->fetchColumn()) $err = 'Komentar induk tidak ditemukan.';
        }
        if ($err === '') {
            try {
                $status = comments_auto_approve() ? 'approved' : 'pending';
                $pdo->prepare('INSERT INTO comments (post_id, parent_id, author, email, content, status, ip_hash) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$postId, $parentId, $author, $email !== '' ? $email : null, $content, $status, visitor_hash()]);
                $_SESSION['last_comment_ts'] = $now;
                flash(comments_auto_approve() ? 'Komentar terkirim.' : 'Komentar terkirim & menunggu moderasi.');
            } catch (PDOException $e) { flash('Gagal menyimpan komentar.', 'danger'); }
        } else {
            flash($err, 'danger');
        }
        header('Location: ' . base_url('post.php?slug=' . urlencode($slug)) . '#comments');
        exit;
    }

    $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?")->execute([$postId]);
    $html = Markdown::toHtml($post['content_md']);
    $likeCount = post_like_count($postId);
    $isLiked = post_is_liked($postId);
    $comments = comments_for_post($postId, true);
    $commentTotal = comment_count($postId, true);
    $commentFlash = flash();
    // related
    $rel = $pdo->prepare("SELECT title, slug FROM posts WHERE status='published' AND id != ? ORDER BY created_at DESC LIMIT 3");
    $rel->execute([$postId]);
    $related = $rel->fetchAll();
}

if (!function_exists('render_comment_list')) {
    function render_comment_list(array $list, int $depth = 0): void {
        foreach ($list as $c) {
            $ml = $depth > 0 ? ' style="margin-left:' . (min($depth, 4) * 22) . 'px"' : '';
            echo '<div class="comment"' . $ml . '>';
            echo '<div class="comment-head"><span class="comment-avatar">' . e(mb_strtoupper(mb_substr($c['author'], 0, 1))) . '</span>';
            echo '<strong>' . e($c['author']) . '</strong><span class="comment-date">' . e($c['date_indo']) . '</span></div>';
            echo '<div class="comment-body">' . nl2br(e($c['content'])) . '</div>';
            echo '<button type="button" class="comment-reply" data-id="' . (int)$c['id'] . '" data-name="' . e($c['author']) . '">Balas</button>';
            if (!empty($c['children'])) render_comment_list($c['children'], $depth + 1);
            echo '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($post) ? e($post['title']) . ' — ' . e(site_name()) : 'Tidak ditemukan' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="<?= base_url('assets/favicon.svg') ?>">
<link rel="apple-touch-icon" href="<?= base_url('assets/icons/apple-touch-icon.png') ?>">
<link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
<meta name="theme-color" content="#4f46e5">
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
  .card-body a.more,.footer a{display:inline-flex;align-items:center;gap:6px}
</style>
</head>
<body>
<nav class="nav"><div class="nav-inner">
  <a class="logo" href="<?= base_url('index.php') ?>"><i data-lucide="notebook-pen"></i> <?php [$logoA, $logoB] = site_name_parts(); ?><span><?= e($logoA) ?></span><?= $logoB !== '' ? e($logoB) : '' ?></a>
  <div class="nav-links">
    <a href="<?= base_url('index.php') ?>"><i data-lucide="arrow-left"></i>Beranda</a>
    <?php foreach (($navPages ?? []) as $np): ?>
      <a href="<?= page_url($np['slug']) ?>"><i data-lucide="file-text"></i><?= e($np['title']) ?></a>
    <?php endforeach; ?>
  </div>
</div></nav>
<main class="container">
<?php if (!empty($notfound)): ?>
  <div class="form"><h2>404 — Artikel tidak ditemukan</h2><p><a href="<?= base_url('index.php') ?>">Kembali ke beranda</a></p></div>
<?php else: ?>
  <article class="article">
    <span class="badge"><i data-lucide="eye"></i> <?= (int)$post['views'] + 1 ?> dibaca</span>
    <h1><?= e($post['title']) ?></h1>
    <div class="meta"><i data-lucide="calendar-days"></i> <?= time_indo($post['created_at']) ?> • diperbarui <?= time_indo($post['updated_at']) ?></div>
    <div style="display:flex;align-items:center;gap:12px;margin:14px 0">
      <button type="button" id="like-btn" class="btn btn-sm<?= !empty($isLiked) ? ' btn-primary' : '' ?>"
        data-slug="<?= e($post['slug']) ?>" aria-pressed="<?= !empty($isLiked) ? 'true' : 'false' ?>">
        <i data-lucide="heart"></i> <span id="like-label"><?= !empty($isLiked) ? 'Disukai' : 'Suka' ?></span>
      </button>
      <span class="meta" style="margin:0"><i data-lucide="heart"></i> <span id="like-count"><?= (int)($likeCount ?? 0) ?></span> menyukai</span>
    </div>
    <?php if ($post['cover']): ?><div class="article-cover"><img src="<?= e($post['cover']) ?>" alt="cover"></div><?php endif; ?>
    <div class="markdown-body"><?= $html ?></div>
    <?php if (show_article_source()): ?>
    <hr>
    <details>
      <summary><i data-lucide="file-text"></i> Lihat teks asli artikel</summary>
      <pre style="white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:14px;border-radius:10px"><?= e($post['content_md']) ?></pre>
    </details>
    <?php endif; ?>
  </article>
  <?php if (comments_enabled()): ?>
  <section class="comments" id="comments">
    <h3><i data-lucide="message-circle"></i> <?= (int)($commentTotal ?? 0) ?> Komentar</h3>
    <?php if (!empty($commentFlash)): ?>
      <div class="alert alert-<?= $commentFlash['type'] === 'danger' ? 'danger' : 'success' ?>"><?= e($commentFlash['msg']) ?></div>
    <?php endif; ?>
    <div id="comment-list">
      <?php if (empty($comments)): ?>
        <p class="meta">Belum ada komentar. Jadilah yang pertama!</p>
      <?php else: render_comment_list($comments); endif; ?>
    </div>
    <form class="form comment-form" method="post" action="<?= base_url('post.php?slug=' . urlencode($post['slug'])) ?>#comments">
      <h4>Tinggalkan komentar</h4>
      <p id="reply-info" class="meta" style="display:none"></p>
      <input type="hidden" name="parent_id" id="parent_id" value="">
      <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="form-row">
        <div class="form-group">
          <label for="c-author">Nama *</label>
          <input type="text" id="c-author" name="author" required minlength="2" maxlength="80" autocomplete="name">
        </div>
        <div class="form-group">
          <label for="c-email">Email (opsional, tak ditampilkan)</label>
          <input type="email" id="c-email" name="email" maxlength="160" autocomplete="email">
        </div>
      </div>
      <div class="form-group">
        <label for="c-content">Komentar *</label>
        <textarea id="c-content" name="content" required minlength="3" maxlength="2000" rows="4"></textarea>
      </div>
      <p class="meta"><?= comments_auto_approve() ? 'Komentar langsung tampil.' : 'Komentar tampil setelah disetujui admin.' ?></p>
      <button class="btn btn-primary" type="submit"><i data-lucide="send"></i> Kirim komentar</button>
    </form>
  </section>
  <?php endif; ?>
  <?php if (!empty($related)): ?>
  <h3>Artikel lain</h3>
  <div class="grid">
    <?php foreach ($related as $r): ?>
      <article class="card"><div class="card-body">
        <h3><a href="<?= base_url('post.php?slug=' . urlencode($r['slug'])) ?>"><?= e($r['title']) ?></a></h3>
        <a class="more" href="<?= base_url('post.php?slug=' . urlencode($r['slug'])) ?>">Baca <i data-lucide="arrow-right"></i></a>
      </div></article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>
<footer class="footer">
  <a class="more" href="<?= base_url('index.php') ?>"><i data-lucide="arrow-left"></i> Semua artikel</a>
  <?php if (!empty($navPages)): ?>
  <nav style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:10px">
    <?php foreach ($navPages as $np): ?>
      <a class="more" href="<?= page_url($np['slug']) ?>"><i data-lucide="file-text"></i><?= e($np['title']) ?></a>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>
  <div style="margin-top:10px"><?= e(footer_text()) ?></div></footer>
</main>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
<script>
(function () {
  var btn = document.getElementById('like-btn');
  if (!btn) return;
  var api = <?= json_encode(base_url('api/likes.php')) ?>;
  btn.addEventListener('click', function () {
    if (btn.disabled) return;
    btn.disabled = true;
    var wasLiked = btn.getAttribute('aria-pressed') === 'true';
    fetch(api, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ slug: btn.dataset.slug, action: wasLiked ? 'unlike' : 'like' })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j || typeof j.likes === 'undefined') return;
        document.getElementById('like-count').textContent = j.likes;
        document.getElementById('like-label').textContent = j.liked ? 'Disukai' : 'Suka';
        btn.setAttribute('aria-pressed', j.liked ? 'true' : 'false');
        btn.classList.toggle('btn-primary', j.liked);
      })
      .catch(function () {})
      .then(function () { btn.disabled = false; });
  });
})();
(function () {
  var list = document.getElementById('comment-list');
  if (!list) return;
  var box = document.getElementById('reply-info');
  var hidden = document.getElementById('parent_id');
  var content = document.getElementById('c-content');
  list.addEventListener('click', function (e) {
    var b = e.target.closest('.comment-reply');
    if (!b) return;
    if (hidden) hidden.value = b.getAttribute('data-id');
    if (box) { box.textContent = 'Membalas ' + b.getAttribute('data-name') + ' — '; box.style.display = 'block'; }
    var cancel = document.createElement('a');
    cancel.href = '#'; cancel.textContent = 'batal';
    cancel.onclick = function (ev) { ev.preventDefault(); if (hidden) hidden.value = ''; if (box) box.style.display = 'none'; };
    if (box && !box.querySelector('a.cancel-reply')) { cancel.className = 'cancel-reply'; box.appendChild(cancel); }
    if (content) content.focus();
  });
})();
</script>
</body>
</html>
