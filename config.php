<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
// Konfigurasi utama Blog Dulur
// Nilai dibaca dari .env (lihat .env.example). Konstanta di bawah = fallback
// bila .env / environment variable tidak tersedia.

require_once __DIR__ . '/includes/env.php';
load_env(__DIR__ . '/.env');

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'blog_dulur'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', '')); // default XAMPP kosong
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

define('SITE_NAME', 'Blog Dulur');
define('SITE_DESC', 'Blog sederhana untuk berbagi tulisan dan kabar');
define('BASE_URL', env('BASE_URL', '')); // kosongkan = otomatis. Contoh: '/blog-dulur'

// URL kanonis situs (skema + host, tanpa trailing slash). Kosongkan = otomatis
// dari request. Di produksi disarankan diisi agar URL absolut (mis. media)
// selalu konsisten, mis. SITE_URL=https://dulur.my.id
define('SITE_URL', rtrim(env('SITE_URL', ''), '/'));

define('POSTS_PER_PAGE', (int)env('POSTS_PER_PAGE', '6') ?: 6);

// Salt untuk hash identitas pengunjung (like tanpa login). Ganti di .env.
define('LIKE_SALT', env('LIKE_SALT', 'blog-dulur-likes'));

// Render selalu klasik (server-side, assets/style.css).
