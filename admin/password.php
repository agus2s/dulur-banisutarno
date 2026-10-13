<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/layout.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cur = $_POST['current'] ?? '';
    $new = $_POST['new'] ?? '';
    $conf = $_POST['confirm'] ?? '';
    try {
        $st = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $st->execute([$_SESSION['user_id']]);
        $user = $st->fetch();
        if (!$user) {
            $error = 'Pengguna tidak ditemukan.';
        } elseif (!password_verify($cur, $user['password'])) {
            $error = 'Kata sandi lama salah.';
        } elseif (strlen($new) < 6) {
            $error = 'Kata sandi baru minimal 6 karakter.';
        } elseif ($new !== $conf) {
            $error = 'Konfirmasi kata sandi baru tidak cocok.';
        } else {
            db()->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('Kata sandi berhasil diganti.');
            header('Location: ' . base_url('admin/password.php'));
            exit;
        }
    } catch (PDOException $e) { $error = 'Gagal menyimpan. Coba lagi.'; }
}
$f = flash();
adminlte_head('Ganti Kata Sandi', 'password');
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0 d-flex align-items-center gap-2"><i data-lucide="key-round"></i> Ganti Kata Sandi</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dasbor</a></li>
            <li class="breadcrumb-item active">Ganti Kata Sandi</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <?php if ($f): ?><div class="alert alert-success d-flex align-items-center gap-2"><i data-lucide="circle-check"></i> <?= e($f['msg']) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger d-flex align-items-center gap-2"><i data-lucide="triangle-alert"></i> <?= e($error) ?></div><?php endif; ?>

      <div class="card" style="max-width:560px">
        <form method="post">
          <div class="card-body">
            <p class="text-muted small">Masuk sebagai <strong><?= e($_SESSION['username'] ?? '') ?></strong>. Kata sandi baru minimal 6 karakter.</p>
            <div class="mb-3">
              <label class="form-label fw-bold">Kata Sandi Lama</label>
              <input type="password" name="current" class="form-control" required autocomplete="current-password" autofocus>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Kata Sandi Baru</label>
              <input type="password" name="new" class="form-control" required minlength="6" autocomplete="new-password">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Konfirmasi Kata Sandi Baru</label>
              <input type="password" name="confirm" class="form-control" required minlength="6" autocomplete="new-password">
            </div>
          </div>
          <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary d-inline-flex align-items-center gap-1" type="submit"><i data-lucide="save"></i> Simpan Kata Sandi</button>
            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-1" href="<?= base_url('admin/index.php') ?>"><i data-lucide="arrow-left"></i> Batal</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</main>
<?php adminlte_footer(); ?>
</body></html>
