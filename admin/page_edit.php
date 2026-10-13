<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/layout.php';
require_login();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$page = ['id' => 0, 'title' => '', 'slug' => '', 'status' => 'published', 'kind' => 'biasa', 'show_in_nav' => 1, 'content_md' => '', 'mermaid' => ''];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $page = $st->fetch() ?: $page;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $kind = ($_POST['kind'] ?? 'biasa') === 'family' ? 'family' : 'biasa';
    $showNav = isset($_POST['show_in_nav']) ? 1 : 0;
    $md = str_replace(["\r\n", "\r"], "\n", $_POST['content_md'] ?? '');
    $mermaid = trim(str_replace(["\r\n", "\r"], "\n", $_POST['mermaid'] ?? ''));
    if ($title === '' || $md === '') {
        flash('Judul dan isi wajib diisi.', 'danger');
    } else {
        $pid = (int)($_POST['id'] ?? 0);
        // Slug manual bila diisi, otomatis dari judul bila dikosongkan.
        $slugIn = slugify(trim((string)($_POST['slug'] ?? '')));
        $slug = ($slugIn !== '' && $slugIn !== 'tanpa-judul')
            ? unique_page_slug_for($pdo, $slugIn, $pid ?: null)
            : unique_page_slug($pdo, $title, $pid ?: null);
        if ($pid) {
            $st = $pdo->prepare('UPDATE pages SET title=?, slug=?, content_md=?, kind=?, show_in_nav=?, mermaid=?, status=?, updated_at=NOW() WHERE id=?');
            $st->execute([$title, $slug, $md, $kind, $showNav, $mermaid !== '' ? $mermaid : null, $status, $pid]);
            flash('Halaman diperbarui. Alamat: /halaman/' . $slug);
        } else {
            $st = $pdo->prepare("INSERT INTO pages (title, slug, content_md, kind, show_in_nav, mermaid, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())");
            $st->execute([$title, $slug, $md, $kind, $showNav, $mermaid !== '' ? $mermaid : null, $status]);
            $pid = (int)$pdo->lastInsertId();
            flash('Halaman disimpan. Alamat: /halaman/' . $slug);
        }
        header('Location: ' . base_url('admin/page_edit.php?id=' . $pid));
        exit;
    }
}
$f = flash();
$contoh = <<<'MD'
# Judul Halaman

Tulis halaman statis di sini. Contoh: Tentang Kami, Kontak, Kebijakan Privasi.

- **Tebal**, *miring*, ~~coret~~, `kode`
- [Link](https://example.com)

> Ini contoh kutipan.
MD;
$contohMermaid = <<<'MERMAID'
graph TD
    K[Kakek & Nenek] --> A[Ayah]
    K --> I[Ibu]
    A --> A1[Anak Pertama]
    A --> A2[Anak Kedua]
MERMAID;
adminlte_head($page['id'] ? 'Sunting Halaman' : 'Tambah Halaman Baru', 'page_tulis');
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0 d-flex align-items-center gap-2"><i data-lucide="file-plus"></i> <?= $page['id'] ? 'Sunting Halaman' : 'Tambah Halaman Baru' ?></h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/pages.php') ?>">Halaman</a></li>
            <li class="breadcrumb-item active"><?= $page['id'] ? 'Sunting' : 'Tambah Baru' ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <?php if ($f): ?><div class="alert alert-<?= e($f['type']) === 'danger' ? 'danger' : 'success' ?> d-flex align-items-center gap-2"><i data-lucide="<?= e($f['type']) === 'danger' ? 'triangle-alert' : 'circle-check' ?>"></i> <?= e($f['msg']) ?></div><?php endif; ?>

      <form method="post">
        <input type="hidden" name="id" id="post-id" value="<?= (int)$page['id'] ?>">
        <div class="card mb-3">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-bold">Judul</label>
              <input type="text" id="title-input" name="title" class="form-control form-control-lg" required value="<?= e($page['title']) ?>" placeholder="Tambahkan judul…">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold d-flex align-items-center gap-1" for="slug-input"><i data-lucide="link"></i> Slug (permalink)</label>
              <div class="input-group">
                <span class="input-group-text">/halaman/</span>
                <input type="text" id="slug-input" name="slug" class="form-control font-monospace" value="<?= e($page['slug'] ?? '') ?>" placeholder="otomatis-dari-judul" autocomplete="off">
              </div>
              <div class="form-text">Kosongkan untuk otomatis dari judul. Mengubah slug mengubah alamat — URL lama tidak aktif. Pratinjau: <code id="slug-preview"></code></div>
            </div>
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label fw-bold d-flex align-items-center gap-1"><i data-lucide="globe"></i> Status</label>
                <select name="status" class="form-select">
                  <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Diterbitkan (tampil di blog)</option>
                  <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draf (hanya tersimpan)</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold d-flex align-items-center gap-1"><i data-lucide="shapes"></i> Jenis</label>
                <select name="kind" id="kind-select" class="form-select">
                  <option value="biasa" <?= ($page['kind'] ?? 'biasa') === 'biasa' ? 'selected' : '' ?>>Biasa (teks statis)</option>
                  <option value="family" <?= ($page['kind'] ?? '') === 'family' ? 'selected' : '' ?>>Keluarga (deskripsi + silsilah)</option>
                </select>
              </div>
              <div class="col-md-5">
                <label class="form-label fw-bold d-flex align-items-center gap-1"><i data-lucide="menu"></i> Navigasi</label>
                <div class="form-check form-switch mt-2">
                  <input type="checkbox" name="show_in_nav" id="nav-check" class="form-check-input" value="1" <?= !empty($page['show_in_nav'] ?? 1) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="nav-check">Tampilkan di menu navigasi</label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="toolbar d-flex flex-wrap gap-1">
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="h1" title="Tajuk 1"><i data-lucide="heading-1"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="h2" title="Tajuk 2"><i data-lucide="heading-2"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="h3" title="Tajuk 3"><i data-lucide="heading-3"></i></button>
              <span class="vr mx-1"></span>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="bold" title="Tebal"><i data-lucide="bold"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="italic" title="Miring"><i data-lucide="italic"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="strike" title="Coret"><i data-lucide="strikethrough"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="code" title="Kode"><i data-lucide="code"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="codeblock" title="Blok kode"><i data-lucide="square-code"></i></button>
              <span class="vr mx-1"></span>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="quote" title="Kutipan"><i data-lucide="quote"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="ul" title="Daftar"><i data-lucide="list"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="ol" title="Daftar bernomor"><i data-lucide="list-ordered"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="task" title="Daftar tugas"><i data-lucide="list-checks"></i></button>
              <span class="vr mx-1"></span>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="link" title="Tautan"><i data-lucide="link"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="image" title="Gambar"><i data-lucide="image"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="table" title="Tabel"><i data-lucide="table"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-md="hr" title="Pemisah"><i data-lucide="minus"></i></button>
              <label class="btn btn-sm btn-outline-secondary mb-0" title="Unggah berkas .md ke editor" style="cursor:pointer"><i data-lucide="upload"></i> .md<input type="file" id="md-upload" accept=".md,.markdown,.txt" hidden></label>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="editor-wrap border-0">
              <div>
                <div class="px-3 pt-2 text-muted small d-flex align-items-center gap-1"><i data-lucide="pen-line"></i> KONTEN</div>
                <textarea id="md-input" name="content_md" placeholder="# Judul&#10;&#10;Tulis isi halaman di sini..."><?= e($page['content_md'] ?: ($page['id'] ? '' : $contoh)) ?></textarea>
              </div>
              <div class="preview-pane">
                <div class="text-muted small d-flex align-items-center gap-1 mb-2"><i data-lucide="eye"></i> PRATINJAU</div>
                <div id="md-preview"></div>
              </div>
            </div>
          </div>
          <div class="card-footer d-flex flex-wrap gap-2 align-items-center">
            <button class="btn btn-primary d-inline-flex align-items-center gap-1" type="submit"><i data-lucide="save"></i> Simpan</button>
            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-1" href="<?= base_url('admin/pages.php') ?>"><i data-lucide="arrow-left"></i> Batal</a>
            <span class="ms-auto text-muted small" id="md-count"></span>
          </div>
        </div>

        <div class="card mt-3" id="mermaid-wrap">
          <div class="card-header d-flex align-items-center gap-2"><i data-lucide="git-fork"></i> <strong>Diagram Silsilah (Mermaid)</strong></div>
          <div class="card-body">
            <p class="text-muted small mb-2">Hanya dipakai bila jenis = <strong>Keluarga</strong>. Tulis sintaks <a href="https://mermaid.js.org/syntax/flowchart.html" target="_blank" rel="noopener">flowchart Mermaid</a>, diawali <code>graph TD</code>.</p>
            <textarea name="mermaid" class="form-control font-monospace" rows="8" placeholder="graph TD&#10;    A[Ayah] --> B[Anak]"><?= e($page['mermaid'] ?? '' ?: ($page['id'] ? '' : $contohMermaid)) ?></textarea>
          </div>
        </div>
      </form>

    </div>
  </div>
</main>
<?php adminlte_footer(); ?>
<script src="<?= base_url('assets/editor.js') ?>"></script>
<script>
// Jenis family: tampilkan kolom mermaid + default sembunyi dari nav (halaman baru).
(function () {
  var kind = document.getElementById('kind-select');
  var nav = document.getElementById('nav-check');
  var wrap = document.getElementById('mermaid-wrap');
  var isNew = <?= empty($page['id']) ? 'true' : 'false' ?>;
  function sync() {
    var fam = kind.value === 'family';
    if (wrap) wrap.style.display = fam ? '' : 'none';
    if (isNew && nav) nav.checked = !fam;
  }
  if (kind) { kind.addEventListener('change', sync); sync(); }
  // Pratinjau permalink dari slug manual / judul.
  var slugInput = document.getElementById('slug-input');
  var titleInput = document.getElementById('title-input');
  var preview = document.getElementById('slug-preview');
  function jsSlug(s) {
    return s.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'tanpa-judul';
  }
  function syncPreview() {
    if (!preview) return;
    var v = slugInput && slugInput.value.trim() !== '' ? slugInput.value : (titleInput ? titleInput.value : '');
    preview.textContent = '/halaman/' + jsSlug(v);
  }
  if (slugInput) slugInput.addEventListener('input', syncPreview);
  if (titleInput) titleInput.addEventListener('input', syncPreview);
  syncPreview();
})();
</script>
</body></html>
