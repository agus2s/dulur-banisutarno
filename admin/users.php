<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/layout.php';
require_login();
$pdo = db();

$users = $pdo->query('SELECT id, username, display_name, email, created_at FROM users ORDER BY id ASC')->fetchAll();
$me = (int)($_SESSION['user_id'] ?? 0);
$f = flash();
adminlte_head('Pengguna', 'users');
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0 d-flex align-items-center gap-2"><i data-lucide="users"></i> Pengguna</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dasbor</a></li>
            <li class="breadcrumb-item active">Pengguna</li>
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
          <h3 class="card-title d-flex align-items-center gap-2"><i data-lucide="list"></i> Semua Pengguna</h3>
          <div class="ms-auto">
            <a class="btn btn-sm btn-primary d-flex align-items-center gap-1" href="<?= base_url('admin/user_edit.php') ?>"><i data-lucide="plus"></i> Tambah Baru</a>
          </div>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover table-striped mb-0 align-middle">
            <thead><tr><th>Nama Tampilan</th><th>Username</th><th>Email</th><th>Sejak</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><strong><?= e($u['display_name'] !== '' ? $u['display_name'] : $u['username']) ?></strong>
                <?php if ((int)$u['id'] === $me): ?> <span class="badge text-bg-info">Anda</span><?php endif; ?>
              </td>
              <td><span class="font-monospace"><?= e($u['username']) ?></span></td>
              <td><?= $u['email'] ? e($u['email']) : '<span class="text-muted">—</span>' ?></td>
              <td><small><?= e($u['created_at']) ?></small></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/user_edit.php?id=' . $u['id']) ?>"><i data-lucide="file-pen"></i> Sunting</a>
                <?php if ((int)$u['id'] !== $me): ?>
                <a class="btn btn-sm btn-outline-danger" href="<?= base_url('admin/user_delete.php?id=' . $u['id']) ?>" onclick="return confirm('Hapus pengguna <?= e($u['username']) ?>?')"><i data-lucide="trash-2"></i></a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</main>
<?php adminlte_footer(); ?>
</body></html>
