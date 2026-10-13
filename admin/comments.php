<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/layout.php';
require_login();
$pdo = db();

// Aksi moderasi (approve / unapprove / delete).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            if ($action === 'approve') {
                $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$id]);
                flash('Komentar disetujui.');
            } elseif ($action === 'unapprove') {
                $pdo->prepare("UPDATE comments SET status = 'pending' WHERE id = ?")->execute([$id]);
                flash('Persetujuan komentar dibatalkan.');
            } elseif ($action === 'delete') {
                $pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([$id]);
                flash('Komentar dihapus.');
            }
        } catch (Throwable $e) {
            flash('Gagal: ' . $e->getMessage(), 'danger');
        }
    }
    header('Location: ' . base_url('admin/comments.php' . (isset($_POST['filter']) && $_POST['filter'] !== '' ? '?status=' . urlencode($_POST['filter']) : '')));
    exit;
}

$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending', 'approved', 'all'], true)) $status = 'pending';

$counts = ['pending' => 0, 'approved' => 0];
try {
    $counts['pending']  = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
    $counts['approved'] = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn();
} catch (PDOException $e) { /* tabel belum ada */ }

$sql = "SELECT c.id, c.parent_id, c.author, c.email, c.content, c.status, c.created_at, p.title AS post_title, p.slug AS post_slug
        FROM comments c LEFT JOIN posts p ON p.id = c.post_id";
$params = [];
if ($status !== 'all') {
    $sql .= ' WHERE c.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY c.created_at DESC, c.id DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$comments = $st->fetchAll();
$f = flash();
adminlte_head('Komentar', 'comments');
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0 d-flex align-items-center gap-2"><i data-lucide="message-square"></i> Komentar</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dasbor</a></li>
            <li class="breadcrumb-item active">Komentar</li>
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
          <h3 class="card-title d-flex align-items-center gap-2"><i data-lucide="list"></i> Daftar Komentar</h3>
          <div class="ms-auto d-flex gap-1">
            <a class="btn btn-sm <?= $status === 'pending' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= base_url('admin/comments.php?status=pending') ?>">Menunggu (<?= $counts['pending'] ?>)</a>
            <a class="btn btn-sm <?= $status === 'approved' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= base_url('admin/comments.php?status=approved') ?>">Disetujui (<?= $counts['approved'] ?>)</a>
            <a class="btn btn-sm <?= $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= base_url('admin/comments.php?status=all') ?>">Semua</a>
          </div>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover mb-0 align-middle">
            <thead><tr><th style="width:20%">Penulis</th><th>Komentar</th><th>Pos</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($comments as $c): ?>
            <tr>
              <td>
                <strong><?= e($c['author']) ?></strong>
                <?php if (!empty($c['email'])): ?><br><small class="text-muted"><?= e($c['email']) ?></small><?php endif; ?>
                <br><small class="text-muted d-inline-flex align-items-center gap-1"><i data-lucide="calendar-days"></i><?= e(time_indo($c['created_at'])) ?></small>
              </td>
              <td style="max-width:420px"><?= e($c['content']) ?><?php if ($c['parent_id'] !== null): ?><br><small class="text-muted d-inline-flex align-items-center gap-1"><i data-lucide="corner-down-right"></i>Balasan</small><?php endif; ?></td>
              <td>
                <?php if ($c['post_slug']): ?>
                  <a href="<?= base_url('post.php?slug=' . urlencode($c['post_slug'])) ?>#comments" target="_blank" class="d-inline-flex align-items-center gap-1"><i data-lucide="external-link"></i><?= e($c['post_title']) ?></a>
                <?php else: ?><span class="text-muted">(pos dihapus)</span><?php endif; ?>
              </td>
              <td>
                <?php if ($c['status'] === 'approved'): ?><span class="badge text-bg-success d-inline-flex align-items-center gap-1"><i data-lucide="circle-check"></i>Disetujui</span>
                <?php else: ?><span class="badge text-bg-warning d-inline-flex align-items-center gap-1"><i data-lucide="clock"></i>Menunggu</span><?php endif; ?>
              </td>
              <td class="text-end text-nowrap">
                <?php if ($c['status'] === 'pending'): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="filter" value="<?= e($status) ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-sm btn-success" name="action" value="approve" type="submit"><i data-lucide="check"></i> Setujui</button>
                </form>
                <?php else: ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="filter" value="<?= e($status) ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-sm btn-outline-warning" name="action" value="unapprove" type="submit"><i data-lucide="undo-2"></i> Batalkan Persetujuan</button>
                </form>
                <?php endif; ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Hapus komentar ini?')">
                  <input type="hidden" name="filter" value="<?= e($status) ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" name="action" value="delete" type="submit"><i data-lucide="trash-2"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$comments): ?><tr><td colspan="5" class="text-center p-4 text-muted">Tidak ada komentar pada filter ini.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</main>
<?php adminlte_footer(); ?>
</body></html>
