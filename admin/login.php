<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if (is_logged_in()) { header('Location: ' . base_url('admin/index.php')); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    try {
        $st = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $st->execute([$u]);
        $user = $st->fetch();
        if ($user && password_verify($p, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ' . base_url('admin/index.php'));
            exit;
        }
        $error = 'Nama pengguna / kata sandi salah.';
    } catch (PDOException $e) { $error = 'Blog belum siap. Buka install.php dulu.'; }
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk — <?= e(site_name()) ?></title>
<link rel="preload" href="<?= base_url('assets/fonts/inter-variable.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= base_url('assets/fonts/inter-variable-italic.woff2') ?>" as="font" type="font/woff2" crossorigin>
<style>
@font-face {
  font-family: 'Inter Variable';
  font-style: normal;
  font-weight: 100 900;
  font-display: swap;
  src: url('<?= base_url('assets/fonts/inter-variable.woff2') ?>') format('woff2-variations');
}
@font-face {
  font-family: 'Inter Variable';
  font-style: italic;
  font-weight: 100 900;
  font-display: swap;
  src: url('<?= base_url('assets/fonts/inter-variable-italic.woff2') ?>') format('woff2-variations');
}
:root { --bs-font-sans-serif: 'Inter Variable', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; --bs-body-font-family: var(--bs-font-sans-serif); }
body { font-family: var(--bs-body-font-family); font-feature-settings: 'cv11','ss01'; }
.lucide{width:18px;height:18px;vertical-align:-3px}
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.9.1/dist/css/adminlte.min.css">
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <span class="d-inline-flex align-items-center gap-2 fs-4 fw-bold"><i data-lucide="notebook-pen"></i> <?= e(site_name()) ?></span>
      <p class="text-muted small mb-0">Panel Admin</p>
    </div>
    <div class="card-body login-card-body">
      <p class="login-box-msg">Masuk untuk mengelola blog</p>
      <?php if ($error): ?><div class="alert alert-danger d-flex align-items-center gap-2"><i data-lucide="triangle-alert"></i> <?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <div class="input-group mb-3">
          <input type="text" name="username" class="form-control" placeholder="Nama Pengguna" required autofocus>
          <span class="input-group-text"><i data-lucide="user"></i></span>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Kata Sandi" required>
          <span class="input-group-text"><i data-lucide="lock"></i></span>
        </div>
        <div class="d-grid gap-2">
          <button class="btn btn-primary d-inline-flex justify-content-center align-items-center gap-2" type="submit"><i data-lucide="log-in"></i> Masuk</button>
          <a class="btn btn-outline-secondary d-inline-flex justify-content-center align-items-center gap-2" href="<?= base_url('index.php') ?>"><i data-lucide="arrow-left"></i> Kembali ke Situs</a>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.9.1/dist/js/adminlte.min.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body></html>
