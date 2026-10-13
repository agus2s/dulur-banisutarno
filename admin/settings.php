<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['reset_colors'])) {
            save_setting('grad_from', DEFAULT_GRAD_FROM);
            save_setting('grad_to', DEFAULT_GRAD_TO);
            save_setting('accent', DEFAULT_ACCENT);
            flash('Warna gradien & aksen dikembalikan ke bawaan.');
        } else {
        $name = trim($_POST['site_name'] ?? '');
        $desc = trim($_POST['site_desc'] ?? '');
        $foot = trim($_POST['footer_text'] ?? '');
        $gf = trim($_POST['grad_from'] ?? '');
        $gt = trim($_POST['grad_to'] ?? '');
        $ac = trim($_POST['accent'] ?? '');
        $src = isset($_POST['show_source']) ? '1' : '0';
        $cEnabled = isset($_POST['comments_enabled']) ? '1' : '0';
        $cAuto = isset($_POST['comments_auto_approve']) ? '1' : '0';
        if ($name === '') {
            flash('Judul situs wajib diisi.', 'danger');
        } elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $gf) || !preg_match('/^#[0-9a-fA-F]{6}$/', $gt) || !preg_match('/^#[0-9a-fA-F]{6}$/', $ac)) {
            flash('Warna gradien/aksen tidak valid.', 'danger');
        } else {
            save_setting('site_name', $name);
            save_setting('site_desc', $desc);
            save_setting('footer_text', $foot);
            save_setting('grad_from', $gf);
            save_setting('grad_to', $gt);
            save_setting('accent', $ac);
            save_setting('show_source', $src);
            save_setting('comments_enabled', $cEnabled);
            save_setting('comments_auto_approve', $cAuto);
            flash('Pengaturan disimpan.');
        }
        }
    } catch (Throwable $e) { flash('Gagal: ' . $e->getMessage(), 'danger'); }
    header('Location: ' . base_url('admin/settings.php'));
    exit;
}
$f = flash();
adminlte_head('Pengaturan Umum', 'settings');
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0 d-flex align-items-center gap-2"><i data-lucide="settings"></i> Pengaturan Umum</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dasbor</a></li>
            <li class="breadcrumb-item active">Pengaturan</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <?php if ($f): ?><div class="alert alert-<?= e($f['type']) === 'danger' ? 'danger' : 'success' ?> d-flex align-items-center gap-2"><i data-lucide="<?= e($f['type']) === 'danger' ? 'triangle-alert' : 'circle-check' ?>"></i> <?= e($f['msg']) ?></div><?php endif; ?>

      <div class="card" style="max-width:640px">
        <form method="post">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-bold d-flex align-items-center gap-1"><i data-lucide="notebook-pen"></i> Judul situs</label>
              <input type="text" name="site_name" class="form-control" required maxlength="100" value="<?= e(site_name()) ?>" placeholder="Blog Dulur">
              <div class="form-text">Tampil di tab browser, hero, dan judul halaman.</div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold d-flex align-items-center gap-1"><i data-lucide="megaphone"></i> Slogan</label>
              <input type="text" name="site_desc" class="form-control" maxlength="255" value="<?= e(site_desc()) ?>" placeholder="Blog sederhana untuk berbagi tulisan...">
              <div class="form-text">Tampil di bawah judul pada hero halaman utama.</div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold d-flex align-items-center gap-1"><i data-lucide="text"></i> Teks footer</label>
              <input type="text" name="footer_text" class="form-control" maxlength="255" value="<?= e(footer_text()) ?>" placeholder="Blog Dulur • Berbagi tulisan dan kabar">
              <div class="form-text">Tampil di kaki halaman utama.</div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold d-flex align-items-center gap-1"><i data-lucide="palette"></i> Warna gradien hero</label>
              <div class="d-flex gap-3">
                <div>
                  <input type="color" name="grad_from" class="form-control form-control-color" value="<?= e(grad_from()) ?>" title="Warna awal">
                  <small class="text-muted">Awal</small>
                </div>
                <div>
                  <input type="color" name="grad_to" class="form-control form-control-color" value="<?= e(grad_to()) ?>" title="Warna akhir">
                  <small class="text-muted">Akhir</small>
                </div>
                <div>
                  <input type="color" name="accent" class="form-control form-control-color" value="<?= e(accent()) ?>" title="Warna aksen">
                  <small class="text-muted">Aksen</small>
                </div>
              </div>
              <div class="form-text">Latar kepala halaman utama, dari kiri atas ke kanan bawah. Aksen untuk badge, tombol, dan tautan. Bawaan: awal <code>#4f46e5</code>, akhir <code>#db2777</code>, aksen <code>#4f46e5</code>.</div>
            </div>
            <div class="mb-3">
              <div class="form-check form-switch">
                <input type="checkbox" name="show_source" value="1" class="form-check-input" id="show-source" <?= show_article_source() ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="show-source">Tampilkan teks asli di pos publik</label>
              </div>
              <div class="form-text">Bila mati, tombol "teks asli" disembunyikan dari halaman pos.</div>
            </div>
            <div class="mb-3">
              <div class="form-check form-switch">
                <input type="checkbox" name="comments_enabled" value="1" class="form-check-input" id="comments-enabled" <?= comments_enabled() ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="comments-enabled">Aktifkan kolom komentar</label>
              </div>
              <div class="form-text">Bila mati, formulir dan daftar komentar disembunyikan dari pos.</div>
            </div>
            <div class="mb-3">
              <div class="form-check form-switch">
                <input type="checkbox" name="comments_auto_approve" value="1" class="form-check-input" id="comments-auto" <?= comments_auto_approve() ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="comments-auto">Komentar baru langsung tampil</label>
              </div>
              <div class="form-text">Bila mati, komentar baru menunggu moderasi di <a href="<?= base_url('admin/comments.php') ?>">halaman Komentar</a>.</div>
            </div>
          </div>
          <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary d-inline-flex align-items-center gap-1" type="submit"><i data-lucide="save"></i> Simpan Perubahan</button>
            <button class="btn btn-outline-warning d-inline-flex align-items-center gap-1" type="submit" name="reset_colors" value="1" formnovalidate onclick="return confirm('Kembalikan warna gradien & aksen ke bawaan?')"><i data-lucide="rotate-ccw"></i> Reset warna</button>
            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-1" href="<?= base_url('admin/index.php') ?>"><i data-lucide="arrow-left"></i> Batal</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</main>
<?php adminlte_footer(); ?>
</body></html>
