<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
// API pengaturan publik.
// GET api/settings.php -> { site_name, site_desc, footer_text, grad_from, grad_to, accent, show_source, comments_enabled, comments_auto_approve }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

function out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $pdo = db();
} catch (Throwable $e) {
    out(['error' => 'Blog belum siap — buka install.php untuk menyiapkannya.', 'install' => true], 500);
}

$settings = [
    'site_name'            => site_name(),
    'site_desc'            => site_desc(),
    'footer_text'          => footer_text(),
    'grad_from'            => grad_from(),
    'grad_to'              => grad_to(),
    'accent'               => accent(),
    'show_source'          => (bool)(setting('show_source', '1') === '1'),
    'comments_enabled'     => (bool)comments_enabled(),
    'comments_auto_approve'=> (bool)comments_auto_approve(),
];

out(['ok' => true, 'settings' => $settings]);
