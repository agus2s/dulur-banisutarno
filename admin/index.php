<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/layout.php';
require_login();
$pdo = db();

$total     = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$published = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$draft     = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn();
$pagesCount = 0;
try { $pagesCount = (int)$pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn(); } catch (PDOException $e) { $pagesCount = 0; }

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $st = $pdo->prepare("SELECT id,title,slug,status,views,created_at,updated_at FROM posts WHERE title LIKE ? OR content_md LIKE ? ORDER BY updated_at DESC");
    $st->execute(["%$q%", "%$q%"]);
    $posts = $st->fetchAll();
} else {
    $posts = $pdo->query("SELECT id,title,slug,status,views,created_at,updated_at FROM posts ORDER BY updated_at DESC")->fetchAll();
}
$f = flash();
adminlte_head('Dasbor', 'dashboard');
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0 d-flex align-items-center gap-2"><i data-lucide="layout-dashboard"></i> Dasbor</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item active">Dasbor</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <?php if ($f): ?><div class="alert alert-<?= e($f['type']) === 'danger' ? 'danger' : 'success' ?> d-flex align-items-center gap-2"><i data-lucide="<?= e($f['type']) === 'danger' ? 'triangle-alert' : 'circle-check' ?>"></i> <?= e($f['msg']) ?></div><?php endif; ?>

      <div class="row g-3 mb-3">
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-primary">
            <div class="inner"><h3><?= $total ?></h3><p>Total Pos</p></div>
            <i data-lucide="newspaper" class="small-box-icon"></i>
            <a href="<?= base_url('admin/index.php') ?>" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">Kelola <i data-lucide="arrow-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-success">
            <div class="inner"><h3><?= $published ?></h3><p>Diterbitkan</p></div>
            <i data-lucide="globe" class="small-box-icon"></i>
            <a href="<?= base_url('index.php') ?>" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">Kunjungi Situs <i data-lucide="arrow-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-warning">
            <div class="inner"><h3><?= $draft ?></h3><p>Draf</p></div>
            <i data-lucide="file-pen" class="small-box-icon"></i>
            <a href="<?= base_url('admin/edit.php') ?>" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">Tulis Pos <i data-lucide="arrow-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-secondary">
            <div class="inner"><h3><?= $pagesCount ?></h3><p>Halaman</p></div>
            <i data-lucide="file-text" class="small-box-icon"></i>
            <a href="<?= base_url('admin/pages.php') ?>" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">Kelola <i data-lucide="arrow-right"></i></a>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
          <h3 class="card-title d-flex align-items-center gap-2"><i data-lucide="list"></i> Semua Pos</h3>
          <div class="ms-auto d-flex gap-2">
            <form class="d-flex" method="get">
              <div class="input-group input-group-sm" style="width:220px">
                <input type="text" name="q" class="form-control" placeholder="Cari judul / isi…" value="<?= e($q) ?>">
                <button class="btn btn-outline-secondary" type="submit"><i data-lucide="search"></i></button>
              </div>
            </form>
            <a class="btn btn-sm btn-primary d-flex align-items-center gap-1" href="<?= base_url('admin/edit.php') ?>"><i data-lucide="plus"></i> Tambah Baru</a>
          </div>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover table-striped mb-0 align-middle">
            <thead><tr><th>Judul</th><th>Status</th><th><span class="d-inline-flex align-items-center gap-1"><i data-lucide="eye"></i>Dilihat</span></th><th>Diperbarui</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($posts as $p): ?>
            <tr>
              <td><strong><?= e($p['title']) ?></strong><br><small class="text-muted d-inline-flex align-items-center gap-1"><i data-lucide="link"></i>/<?= e($p['slug']) ?></small></td>
              <td>
                <?php if ($p['status'] === 'published'): ?><span class="badge text-bg-success d-inline-flex align-items-center gap-1"><i data-lucide="circle-check"></i>Diterbitkan</span>
                <?php else: ?><span class="badge text-bg-warning d-inline-flex align-items-center gap-1"><i data-lucide="pencil"></i>Draf</span><?php endif; ?>
              </td>
              <td><?= (int)$p['views'] ?></td>
              <td><small><?= e($p['updated_at']) ?></small></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/edit.php?id=' . $p['id']) ?>"><i data-lucide="file-pen"></i> Sunting</a>
                <?php if ($p['status'] === 'published'): ?><a class="btn btn-sm btn-outline-secondary" href="<?= base_url('post.php?slug=' . urlencode($p['slug'])) ?>"><i data-lucide="external-link"></i></a><?php endif; ?>
                <a class="btn btn-sm btn-outline-danger" href="<?= base_url('admin/delete.php?id=' . $p['id']) ?>" onclick="return confirm('Hapus pos ini?')"><i data-lucide="trash-2"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$posts): ?><tr><td colspan="5" class="text-center p-4 text-muted">Belum ada pos. <a href="<?= base_url('admin/edit.php') ?>">Tulis sekarang</a></td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</main>
<?php adminlte_footer(); ?>
</body></html>
