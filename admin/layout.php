<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
// Layout AdminLTE 4 + Lucide untuk panel admin Blog Dulur.
require_once __DIR__ . '/../includes/helpers.php';

function adminlte_head(string $title, string $active = ''): void {
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> — <?= e(site_name()) ?></title>
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
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.9.1/dist/css/adminlte.min.css">
<style>
:root {
  --bs-font-sans-serif: 'Inter Variable', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  --bs-body-font-family: var(--bs-font-sans-serif);
}
body { font-family: var(--bs-body-font-family); font-feature-settings: 'cv11', 'ss01'; }
.lucide { width: 18px; height: 18px; vertical-align: -3px; }
  .nav-icon.lucide { width: 20px; height: 20px; }
  /* Sidebar kompak: hemat tinggi untuk layar laptop sempit */
  .sidebar-menu .nav-link { padding: .5rem .8rem; font-size: .9rem; }
  /* Ikon Lucide sebagai .small-box-icon asli AdminLTE 4 (70px, samar, zoom saat hover, sembunyi di HP) */
  .small-box .small-box-icon { width: 70px; height: 70px; }
  .editor-wrap { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid #dee2e6; border-radius: .5rem; overflow: hidden; }
  #md-input { width: 100%; border: none; outline: none; padding: 14px; font-family: Consolas, 'Fira Code', monospace; font-size: .9rem; line-height: 1.65; min-height: 440px; background: #fff; }
  .preview-pane { background: #fff; border-left: 1px solid #dee2e6; padding: 16px; max-height: 560px; overflow-y: auto; }
  .preview-pane pre, #md-preview pre { background: #0f172a; color: #e2e8f0; padding: 12px; border-radius: .5rem; overflow: auto; }
  .preview-pane code, #md-preview code { background: #f1f5f9; padding: 1px 5px; border-radius: 4px; }
  .preview-pane pre code, #md-preview pre code { background: transparent; padding: 0; }
  .preview-pane blockquote, #md-preview blockquote { border-left: 4px solid #059669; background: #f8fafc; padding: .4em .9em; margin: 1em 0; }
  .preview-pane table, #md-preview table { width: 100%; border-collapse: collapse; margin: 1em 0; }
  .preview-pane th, .preview-pane td, #md-preview th, #md-preview td { border: 1px solid #dee2e6; padding: 7px 10px; }
  .toolbar .btn { display: inline-flex; align-items: center; gap: 4px; }
  .toolbar .lucide { width: 15px; height: 15px; }
  @media (max-width: 768px) { .editor-wrap { grid-template-columns: 1fr; } .preview-pane { border-left: none; border-top: 1px solid #dee2e6; } }
</style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

<nav class="app-header navbar navbar-expand bg-body">
  <div class="container-fluid">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i data-lucide="menu"></i></a>
      </li>
      <li class="nav-item d-none d-md-inline-block"><a href="<?= base_url('index.php') ?>" class="nav-link"><i data-lucide="globe"></i> Kunjungi Situs</a></li>
    </ul>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item"><a class="nav-link" href="#" data-lte-toggle="fullscreen"><i data-lucide="maximize"></i></a></li>
      <li class="nav-item dropdown user-menu">
        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
          <i data-lucide="circle-user-round"></i> <span class="d-none d-md-inline"><?= e(display_name()) ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a href="<?= base_url('index.php') ?>" class="dropdown-item"><i data-lucide="globe"></i> Kunjungi Situs</a></li>
          <li><a href="<?= base_url('admin/user_edit.php?id=' . (int)($_SESSION['user_id'] ?? 0)) ?>" class="dropdown-item"><i data-lucide="circle-user-round"></i> Profil Saya</a></li>
          <li><a href="<?= base_url('admin/password.php') ?>" class="dropdown-item"><i data-lucide="key-round"></i> Ganti Kata Sandi</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a href="<?= base_url('admin/logout.php') ?>" class="dropdown-item text-danger"><i data-lucide="log-out"></i> Keluar</a></li>
        </ul>
      </li>
    </ul>
  </div>
</nav>

<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="<?= base_url('admin/index.php') ?>" class="brand-link">
      <i data-lucide="notebook-pen" class="brand-image"></i>
      <span class="brand-text fw-light"><?= e(site_name()) ?></span>
    </a>
  </div>
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" role="menu">
        <?php
        // Menu datar (treeview dimatikan): semua tautan terlihat langsung.
        // Label meniru WordPress id_ID, urutan khas WP: Pos > Media > Halaman.
        $pendingComments = 0;
        try { $pendingComments = (int)db()->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(); } catch (Throwable $e) { $pendingComments = 0; }
        $isFokusUnggah = ($active === 'media') && (($_GET['fokus'] ?? '') === 'unggah');
        ?>
        <li class="nav-item">
          <a href="<?= base_url('admin/index.php') ?>" class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>">
            <i class="nav-icon" data-lucide="layout-dashboard"></i><p>Dasbor</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('admin/edit.php') ?>" class="nav-link <?= $active === 'tulis' ? 'active' : '' ?>">
            <i class="nav-icon" data-lucide="square-pen"></i><p>Tulis Pos</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('admin/media.php') ?>" class="nav-link <?= $active === 'media' && !$isFokusUnggah ? 'active' : '' ?>">
            <i class="nav-icon" data-lucide="images"></i><p>Pustaka Media</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('admin/media.php?fokus=unggah') ?>" class="nav-link <?= $isFokusUnggah ? 'active' : '' ?>">
            <i class="nav-icon" data-lucide="upload"></i><p>Tambah Media</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('admin/pages.php') ?>" class="nav-link <?= $active === 'pages' ? 'active' : '' ?>">
            <i class="nav-icon" data-lucide="file-text"></i><p>Semua Halaman</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('admin/page_edit.php') ?>" class="nav-link <?= $active === 'page_tulis' ? 'active' : '' ?>">
            <i class="nav-icon" data-lucide="file-plus"></i><p>Tulis Halaman</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('admin/comments.php') ?>" class="nav-link <?= $active === 'comments' ? 'active' : '' ?>">
            <i class="nav-icon" data-lucide="message-square"></i><p>Komentar<?php if ($pendingComments > 0): ?><span class="nav-badge badge text-bg-warning ms-2"><?= $pendingComments ?></span><?php endif; ?></p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('admin/settings.php') ?>" class="nav-link <?= $active === 'settings' ? 'active' : '' ?>">
            <i class="nav-icon" data-lucide="sliders-horizontal"></i><p>Pengaturan</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('admin/users.php') ?>" class="nav-link <?= $active === 'users' ? 'active' : '' ?>">
            <i class="nav-icon" data-lucide="users"></i><p>Pengguna</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('admin/password.php') ?>" class="nav-link <?= $active === 'password' ? 'active' : '' ?>">
            <i class="nav-icon" data-lucide="key-round"></i><p>Ganti Kata Sandi</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('index.php') ?>" class="nav-link">
            <i class="nav-icon" data-lucide="globe"></i><p>Kunjungi Situs</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
<?php
}

function adminlte_footer(): void {
?>
<footer class="app-footer">
  <div class="float-end d-none d-sm-inline">Konten tersimpan otomatis</div>
  <strong><?= e(site_name()) ?></strong> • © 2026 Agus Supriyadi
</footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.9.1/dist/js/adminlte.min.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
<?php
}
