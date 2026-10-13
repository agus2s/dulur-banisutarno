<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
// API Media Manager (Admin)
// GET    api/media.php                 -> daftar berkas gambar di uploads/
//          ?limit=48&page=1            -> paginasi ({ files, total, page, limit, has_more })
// POST   api/media.php                 -> upload berkas (image)
// DELETE api/media.php?id=...          -> hapus berkas
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

function out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!is_logged_in()) {
    out(['error' => 'Unauthorized — silakan login dulu.'], 401);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uploadDir = __DIR__ . '/../uploads/';
$uploadUrl = rtrim(abs_url('uploads'), '/') . '/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        out(['error' => 'Gagal membuat folder uploads/ — periksa izin tulis hosting.'], 500);
    }
}
if (!is_writable($uploadDir)) {
    out(['error' => 'Folder uploads/ tidak bisa ditulisi — periksa izin (chmod 755/775) di hosting.'], 500);
}

/** Ambil batas ukuran unggah server (upload_max_filesize / post_max_size) dalam byte. */
function server_upload_limit(): int {
    $parse = function (string $v): int {
        $v = trim($v);
        $unit = strtolower(substr($v, -1));
        $num = (int)$v;
        if (is_numeric(substr($v, -1))) return $num;
        switch ($unit) {
            case 'g': return $num * 1073741824;
            case 'm': return $num * 1048576;
            case 'k': return $num * 1024;
            default: return $num;
        }
    };
    return min($parse(ini_get('upload_max_filesize')), $parse(ini_get('post_max_size')));
}

try {
    if ($method === 'GET') {
        $files = [];
        if (is_dir($uploadDir)) {
            // glob() bisa mengembalikan false bila gagal; jangan teruskan ke foreach
            // agar tidak memicu warning yang merusak badan JSON.
            $found = glob($uploadDir . '*.{jpg,jpeg,png,gif,webp,avif,svg,JPG,JPEG,PNG,GIF,WEBP,AVIF,SVG}', GLOB_BRACE);
            foreach (($found ?: []) as $f) {
                if (is_file($f)) {
                    $name = basename($f);
                    // Nama berkas non-UTF-8 (mis. Latin-1 dari Windows) membuat
                    // json_encode() mengembalikan false -> respons kosong.
                    if (!preg_match('//u', $name)) {
                        if (function_exists('mb_convert_encoding')) {
                            $name = mb_convert_encoding($name, 'UTF-8', 'Windows-1252');
                        } else {
                            continue;
                        }
                    }
                    // @: berkas tak terbaca (terkunci/dihapus) dilewati diam-diam.
                    $size = @filesize($f);
                    $mtime = @filemtime($f);
                    if ($size === false || $mtime === false) continue;
                    $ext  = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                    $files[] = [
                        'name' => $name,
                        'url'  => $uploadUrl . rawurlencode($name),
                        'size' => $size,
                        'ext'  => $ext,
                        'mtime'=> $mtime,
                    ];
                }
            }
        }
        usort($files, fn($a,$b) => $b['mtime'] <=> $a['mtime']);
        $total = count($files);
        $limit = min(max((int)($_GET['limit'] ?? 48), 1), 200);
        $page  = max((int)($_GET['page'] ?? 1), 1);
        $slice = array_slice($files, ($page - 1) * $limit, $limit);
        $json = json_encode(
            ['ok' => true, 'files' => $slice, 'total' => $total, 'page' => $page, 'limit' => $limit, 'has_more' => $total > $page * $limit],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
        );
        if ($json === false) out(['error' => 'Gagal menyusun daftar berkas: ' . json_last_error_msg()], 500);
        echo $json;
        exit;
    }

    if ($method === 'POST') {
        if (empty($_FILES['file']['tmp_name'])) {
            // Umumnya karena berkas melebihi batas upload_max_filesize/post_max_size server.
            $maxMb = round(server_upload_limit() / 1048576, 1);
            out(['error' => 'Berkas tidak diterima — kemungkinan melebihi batas unggah server (' . $maxMb . ' MB). Kecilkan berkas atau naikkan limit di hosting.'], 422);
        }
        $f = $_FILES['file'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            out(['error' => 'Unggahan gagal (kode ' . $f['error'] . ')'], 422);
        }
        $mime = mime_content_type($f['tmp_name']) ?: '';
        $allowedMime = ['image/jpeg','image/pjpeg','image/png','image/gif','image/webp','image/avif','image/svg+xml'];
        if (!in_array($mime, $allowedMime, true)) {
            out(['error' => 'Hanya berkas gambar yang diizinkan (jpg/png/gif/webp/avif/svg)'], 422);
        }
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp','avif','svg'], true)) {
            out(['error' => 'Ekstensi tidak diizinkan'], 422);
        }
        $max = 5 * 1024 * 1024; // 5 MB
        if ($f['size'] > $max) {
            out(['error' => 'Ukuran berkas maksimal 5 MB'], 422);
        }
        $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $uploadDir . $name;
        if (!move_uploaded_file($f['tmp_name'], $target)) {
            out(['error' => 'Gagal menyimpan berkas'], 500);
        }
        out(['ok' => true, 'name' => $name, 'url' => $uploadUrl . rawurlencode($name)]);
    }

    if ($method === 'DELETE') {
        $id = trim($_GET['id'] ?? ($_GET['name'] ?? ''));
        if ($id === '') out(['error' => 'Parameter id/name wajib'], 422);
        $id = basename($id);
        $target = $uploadDir . $id;
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $id)) {
            out(['error' => 'Nama berkas tidak valid'], 422);
        }
        if (is_file($target)) {
            @unlink($target);
        }
        out(['ok' => true]);
    }

    out(['error' => 'Metode tidak didukung'], 405);
} catch (Throwable $e) {
    out(['error' => $e->getMessage()], 500);
}
