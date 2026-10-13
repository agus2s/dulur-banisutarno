<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/layout.php';
require_login();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$post = ['id' => 0, 'title' => '', 'cover' => '', 'status' => 'published', 'content_md' => ''];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $post = $st->fetch() ?: $post;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $cover = trim($_POST['cover'] ?? '');
    $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $md = str_replace(["\r\n", "\r"], "\n", $_POST['content_md'] ?? '');
    if ($title === '' || $md === '') {
        flash('Judul dan isi wajib diisi.', 'danger');
    } else {
        $excerpt = markdown_excerpt($md);
        $pid = (int)($_POST['id'] ?? 0);
        if ($pid) {
            $slug = unique_slug($pdo, $title, $pid);
            $st = $pdo->prepare('UPDATE posts SET title=?, slug=?, excerpt=?, cover=?, content_md=?, status=?, updated_at=NOW() WHERE id=?');
            $st->execute([$title, $slug, $excerpt, $cover ?: null, $md, $status, $pid]);
            flash('Pos diperbarui. Alamat: /' . $slug);
        } else {
            $slug = unique_slug($pdo, $title);
            $st = $pdo->prepare("INSERT INTO posts (title, slug, excerpt, cover, content_md, status, created_at, updated_at) VALUES (?,?,?,?,?,? ,NOW(),NOW())");
            $st->execute([$title, $slug, $excerpt, $cover ?: null, $md, $status]);
            $pid = (int)$pdo->lastInsertId();
            flash('Pos disimpan. Alamat: /' . $slug);
        }
        header('Location: ' . base_url('admin/edit.php?id=' . $pid));
        exit;
    }
}
$f = flash();
$contoh = <<<'MD'
# Panduan Menulis

Tulis pos dengan format sederhana di kolom ini, hasilnya langsung terlihat di pratinjau.

## Format dasar
- **Tebal**, *miring*, ~~coret~~, `kode`
- [Link](https://example.com)
- ![Gambar](https://picsum.photos/600/300)

> Ini contoh kutipan.

```php
echo "Hello Blog Dulur";
```

| Kolom 1 | Kolom 2 |
|---|---|
| A | B |
MD;
adminlte_head($post['id'] ? 'Sunting Pos' : 'Tambah Pos Baru', 'tulis');
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0 d-flex align-items-center gap-2"><i data-lucide="square-pen"></i> <?= $post['id'] ? 'Sunting Pos' : 'Tambah Pos Baru' ?></h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dasbor</a></li>
            <li class="breadcrumb-item active"><?= $post['id'] ? 'Sunting' : 'Tambah Baru' ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <?php if ($f): ?><div class="alert alert-<?= e($f['type']) === 'danger' ? 'danger' : 'success' ?> d-flex align-items-center gap-2"><i data-lucide="<?= e($f['type']) === 'danger' ? 'triangle-alert' : 'circle-check' ?>"></i> <?= e($f['msg']) ?></div><?php endif; ?>

      <form method="post">
        <input type="hidden" name="id" id="post-id" value="<?= (int)$post['id'] ?>">
        <div class="card mb-3">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-bold">Judul</label>
              <input type="text" id="title-input" name="title" class="form-control form-control-lg" required value="<?= e($post['title']) ?>" placeholder="Tambahkan judul…">
            </div>
            <div class="row g-3">
              <div class="col-md-9">
                <label class="form-label fw-bold d-flex align-items-center gap-1"><i data-lucide="image"></i> Gambar Unggulan (URL, opsional)</label>
                <input type="text" name="cover" class="form-control" value="<?= e($post['cover'] ?? '') ?>" placeholder="https://…">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold d-flex align-items-center gap-1"><i data-lucide="globe"></i> Status</label>
                <select name="status" class="form-select">
                  <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Diterbitkan (tampil di blog)</option>
                  <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draf (hanya tersimpan)</option>
                </select>
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
                <textarea id="md-input" name="content_md" placeholder="# Judul&#10;&#10;Tulis isi pos di sini…"><?= e($post['content_md'] ?: ($post['id'] ? '' : $contoh)) ?></textarea>
              </div>
              <div class="preview-pane">
                <div class="text-muted small d-flex align-items-center gap-1 mb-2"><i data-lucide="eye"></i> PRATINJAU</div>
                <div id="md-preview"></div>
              </div>
            </div>
          </div>
          <div class="card-footer d-flex flex-wrap gap-2 align-items-center">
            <button class="btn btn-primary d-inline-flex align-items-center gap-1" type="submit"><i data-lucide="save"></i> Simpan</button>
            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-1" href="<?= base_url('admin/index.php') ?>"><i data-lucide="arrow-left"></i> Batal</a>
            <span class="ms-auto text-muted small" id="md-count"></span>
          </div>
        </div>
      </form>

      <div class="alert alert-warning d-flex gap-2 mt-3"><i data-lucide="lightbulb"></i>
        <div><strong>Panduan format:</strong> <code># H1</code> <code>## H2</code> <code>**tebal**</code> <code>*miring*</code> <code>`kode`</code> <code>[link](url)</code> <code>![alt](url)</code> <code>- list</code> <code>1. list</code> <code>&gt; quote</code> <code>``` code ```</code> tabel dengan <code>|</code></div>
      </div>

    </div>
  </div>
</main>
<?php adminlte_footer(); ?>
<script src="<?= base_url('assets/editor.js') ?>"></script>
</body></html>
