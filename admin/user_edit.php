<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/layout.php';
require_login();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$user = ['id' => 0, 'username' => '', 'display_name' => '', 'email' => ''];
if ($id) {
    $st = $pdo->prepare('SELECT id, username, display_name, email FROM users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $user = $st->fetch() ?: $user;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['id'] ?? 0);
    $username = trim((string)($_POST['username'] ?? ''));
    $dname = trim((string)($_POST['display_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $pw1 = (string)($_POST['password'] ?? '');
    $pw2 = (string)($_POST['password_confirm'] ?? '');
    $emailDb = $email !== '' ? $email : null;

    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username 3–50 karakter: huruf, angka, titik, strip, underscore.';
    } elseif ($dname === '' || mb_strlen($dname) > 100) {
        $error = 'Nama tampilan wajib diisi (maks 100 karakter).';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Alamat email tidak valid.';
    }
    if ($error === '') {
        $chk = $pdo->prepare('SELECT id FROM users WHERE username = ?' . ($pid ? ' AND id != ?' : '') . ' LIMIT 1');
        $chk->execute($pid ? [$username, $pid] : [$username]);
        if ($chk->fetch()) $error = 'Username sudah dipakai pengguna lain.';
    }
    if ($error === '' && $email !== '') {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?' . ($pid ? ' AND id != ?' : '') . ' LIMIT 1');
        $chk->execute($pid ? [$email, $pid] : [$email]);
        if ($chk->fetch()) $error = 'Email sudah dipakai pengguna lain.';
    }
    $gantiPw = ($pw1 !== '' || $pw2 !== '');
    if ($error === '') {
        if (!$pid && !$gantiPw) $error = 'Kata sandi wajib diisi untuk pengguna baru.';
        elseif ($gantiPw && strlen($pw1) < 6) $error = 'Kata sandi minimal 6 karakter.';
        elseif ($gantiPw && $pw1 !== $pw2) $error = 'Konfirmasi kata sandi tidak cocok.';
    }
    if ($error === '') {
        try {
            if ($pid) {
                if ($gantiPw) {
                    $st = $pdo->prepare('UPDATE users SET username = ?, display_name = ?, email = ?, password = ? WHERE id = ?');
                    $st->execute([$username, $dname, $emailDb, password_hash($pw1, PASSWORD_DEFAULT), $pid]);
                } else {
                    $st = $pdo->prepare('UPDATE users SET username = ?, display_name = ?, email = ? WHERE id = ?');
                    $st->execute([$username, $dname, $emailDb, $pid]);
                }
                flash('Pengguna diperbarui.');
            } else {
                $st = $pdo->prepare('INSERT INTO users (username, password, display_name, email) VALUES (?,?,?,?)');
                $st->execute([$username, password_hash($pw1, PASSWORD_DEFAULT), $dname, $emailDb]);
                $pid = (int)$pdo->lastInsertId();
                flash('Pengguna ditambahkan.');
            }
            header('Location: ' . base_url('admin/user_edit.php?id=' . $pid));
            exit;
        } catch (PDOException $e) { $error = 'Gagal menyimpan. Coba lagi.'; }
    }
    // Tampilkan kembali isian agar tidak hilang saat galat.
    $user = ['id' => $pid, 'username' => $username, 'display_name' => $dname, 'email' => $email];
}
$isSelf = $user['id'] && (int)$user['id'] === (int)($_SESSION['user_id'] ?? 0);
adminlte_head($user['id'] ? 'Sunting Pengguna' : 'Tambah Pengguna', 'users');
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0 d-flex align-items-center gap-2"><i data-lucide="user-round-pen"></i> <?= $user['id'] ? 'Sunting Pengguna' : 'Tambah Pengguna Baru' ?></h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/users.php') ?>">Pengguna</a></li>
            <li class="breadcrumb-item active"><?= $user['id'] ? 'Sunting' : 'Tambah Baru' ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <?php if ($error): ?><div class="alert alert-danger d-flex align-items-center gap-2"><i data-lucide="triangle-alert"></i> <?= e($error) ?></div><?php endif; ?>

      <form method="post" style="max-width:640px">
        <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
        <div class="card">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-bold">Username *</label>
              <input type="text" name="username" class="form-control font-monospace" required value="<?= e($user['username']) ?>" placeholder="mis. agus" autocomplete="off">
              <div class="form-text">3–50 karakter: huruf, angka, titik, strip, underscore. Dipakai untuk masuk.</div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Nama Tampilan *</label>
              <input type="text" name="display_name" class="form-control" required maxlength="100" value="<?= e($user['display_name']) ?>" placeholder="mis. Agus Supriyadi">
              <div class="form-text">Tampil di panel admin sebagai nama Anda.</div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Email</label>
              <input type="email" name="email" class="form-control" maxlength="160" value="<?= e($user['email'] ?? '') ?>" placeholder="nama@example.com" autocomplete="off">
              <div class="form-text">Opsional. Bisa dipakai untuk masuk selain username.</div>
            </div>
            <hr>
            <div class="mb-3">
              <label class="form-label fw-bold">Kata Sandi <?= $user['id'] ? '(kosongkan bila tidak diganti)' : '*' ?></label>
              <input type="password" name="password" class="form-control" <?= $user['id'] ? '' : 'required' ?> minlength="6" autocomplete="new-password">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Ulangi Kata Sandi <?= $user['id'] ? '' : '*' ?></label>
              <input type="password" name="password_confirm" class="form-control" <?= $user['id'] ? '' : 'required' ?> minlength="6" autocomplete="new-password">
              <div class="form-text">Disimpan sebagai hash bcrypt — kata sandi asli tidak pernah tersimpan.</div>
            </div>
          </div>
          <div class="card-footer d-flex flex-wrap gap-2 align-items-center">
            <button class="btn btn-primary d-inline-flex align-items-center gap-1" type="submit"><i data-lucide="save"></i> Simpan</button>
            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-1" href="<?= base_url('admin/users.php') ?>"><i data-lucide="arrow-left"></i> Batal</a>
            <?php if ($isSelf): ?><span class="ms-auto text-muted small">Ini akun Anda sendiri.</span><?php endif; ?>
          </div>
        </div>
      </form>

    </div>
  </div>
</main>
<?php adminlte_footer(); ?>
</body></html>
