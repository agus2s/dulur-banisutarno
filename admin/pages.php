<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/layout.php';
require_login();
$pdo = db();

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $st = $pdo->prepare("SELECT id,title,slug,kind,show_in_nav,status,created_at,updated_at FROM pages WHERE title LIKE ? OR content_md LIKE ? ORDER BY updated_at DESC");
    $st->execute(["%$q%", "%$q%"]);
    $pages = $st->fetchAll();
} else {
    $pages = $pdo->query("SELECT id,title,slug,kind,show_in_nav,status,created_at,updated_at FROM pages ORDER BY updated_at DESC")->fetchAll();
}
$f = flash();
adminlte_head('Halaman', 'pages');
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0 d-flex align-items-center gap-2"><i data-lucide="file-text"></i> Halaman</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dasbor</a></li>
            <li class="breadcrumb-item active">Halaman</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <?php if ($f): ?><div class="alert alert-<?= e($f['type']) === 'danger' ? 'danger' : 'success' ?> d-flex align-items-center gap-2"><i data-lucide="<?= e($f['type']) === 'danger' ? 'triangle-alert' : 'circle-check' ?>"></i> <?= e($f['msg']) ?></div><?php endif; ?>

      <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
          <h3 class="card-title d-flex align-items-center gap-2"><i data-lucide="list"></i> Semua Halaman</h3>
          <div class="ms-auto d-flex gap-2">
            <form class="d-flex" method="get">
              <div class="input-group input-group-sm" style="width:220px">
                <input type="text" name="q" class="form-control" placeholder="Cari judul / isi..." value="<?= e($q) ?>">
                <button class="btn btn-outline-secondary" type="submit"><i data-lucide="search"></i></button>
              </div>
            </form>
            <a class="btn btn-sm btn-primary d-flex align-items-center gap-1" href="<?= base_url('admin/page_edit.php') ?>"><i data-lucide="plus"></i> Tambah Baru</a>
          </div>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover table-striped mb-0 align-middle">
            <thead><tr><th>Judul</th><th>Jenis</th><th>Status</th><th>Nav</th><th>Diperbarui</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($pages as $p): ?>
            <tr>
              <td><strong><?= e($p['title']) ?></strong><br><small class="text-muted d-inline-flex align-items-center gap-1"><i data-lucide="link"></i>/<?= e($p['slug']) ?></small></td>
              <td>
                <?php if (($p['kind'] ?? 'biasa') === 'family'): ?><span class="badge text-bg-info d-inline-flex align-items-center gap-1"><i data-lucide="users"></i>Keluarga</span>
                <?php else: ?><span class="badge text-bg-secondary d-inline-flex align-items-center gap-1"><i data-lucide="file-text"></i>Biasa</span><?php endif; ?>
              </td>
              <td>
                <?php if ($p['status'] === 'published'): ?><span class="badge text-bg-success d-inline-flex align-items-center gap-1"><i data-lucide="circle-check"></i>Diterbitkan</span>
                <?php else: ?><span class="badge text-bg-warning d-inline-flex align-items-center gap-1"><i data-lucide="pencil"></i>Draf</span><?php endif; ?>
              </td>
              <td class="text-center">
                <?php if (!empty($p['show_in_nav'] ?? 1)): ?><span class="d-inline-flex align-items-center gap-1 text-success" title="Tampil di menu navigasi"><i data-lucide="eye"></i></span>
                <?php else: ?><span class="d-inline-flex align-items-center gap-1 text-muted" title="Disembunyikan dari menu navigasi"><i data-lucide="eye-off"></i></span><?php endif; ?>
              </td>
              <td><small><?= e($p['updated_at']) ?></small></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/page_edit.php?id=' . $p['id']) ?>"><i data-lucide="file-pen"></i> Sunting</a>
                <?php if ($p['status'] === 'published'): ?><a class="btn btn-sm btn-outline-secondary" href="<?= page_url($p['slug']) ?>"><i data-lucide="external-link"></i></a><?php endif; ?>
                <a class="btn btn-sm btn-outline-danger" href="<?= base_url('admin/page_delete.php?id=' . $p['id']) ?>" onclick="return confirm('Hapus halaman ini?')"><i data-lucide="trash-2"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$pages): ?><tr><td colspan="6" class="text-center p-4 text-muted">Belum ada halaman. <a href="<?= base_url('admin/page_edit.php') ?>">Tulis sekarang</a></td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</main>
<?php adminlte_footer(); ?>
</body></html>
