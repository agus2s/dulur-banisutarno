<?php
// Installer Blog Dulur — buka http://localhost/blog-dulur/install.php
require_once __DIR__ . '/config.php';

$msg = [];
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . str_replace('`', '', DB_NAME) . '`');

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        excerpt TEXT NULL,
        cover VARCHAR(500) NULL,
        content_md MEDIUMTEXT NOT NULL COMMENT 'Markdown mentah',
        status ENUM('draft','published') NOT NULL DEFAULT 'draft',
        views INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FULLTEXT KEY ft_search (title, content_md)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        parent_id INT NULL COMMENT 'NULL = komentar utama, terisi = balasan',
        author VARCHAR(80) NOT NULL,
        email VARCHAR(160) NULL,
        content TEXT NOT NULL,
        status ENUM('pending','approved','spam') NOT NULL DEFAULT 'pending',
        ip_hash CHAR(64) NULL COMMENT 'SHA-256 IP+UA untuk anti-spam, tanpa simpan IP mentah',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_comments_post (post_id, status, created_at),
        KEY idx_comments_parent (parent_id),
        CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        visitor_hash CHAR(64) NOT NULL COMMENT 'SHA-256 fingerprint pengunjung, unik per artikel',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_like (post_id, visitor_hash),
        KEY idx_likes_post (post_id),
        CONSTRAINT fk_likes_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        content_md MEDIUMTEXT NOT NULL COMMENT 'Markdown mentah',
        kind VARCHAR(20) NOT NULL DEFAULT 'biasa' COMMENT 'biasa | family',
        show_in_nav TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = tampil di menu navigasi',
        mermaid MEDIUMTEXT NULL COMMENT 'Diagram silsilah (halaman family)',
        status ENUM('draft','published') NOT NULL DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FULLTEXT KEY ft_search (title, content_md)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migrasi DB lama (abaikan bila kolom sudah ada).
    foreach ([
        "ALTER TABLE pages ADD COLUMN kind VARCHAR(20) NOT NULL DEFAULT 'biasa' COMMENT 'biasa | family'",
        'ALTER TABLE pages ADD COLUMN show_in_nav TINYINT(1) NOT NULL DEFAULT 1 COMMENT \'1 = tampil di menu navigasi\'',
        "ALTER TABLE pages ADD COLUMN mermaid MEDIUMTEXT NULL COMMENT 'Diagram silsilah (halaman family)'",
    ] as $alter) {
        try { $pdo->exec($alter); } catch (PDOException $e) { /* kolom sudah ada */ }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        `k` VARCHAR(50) NOT NULL PRIMARY KEY,
        `v` TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // admin default
    $st = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $st->execute(['admin']);
    if (!$st->fetch()) {
        $pdo->prepare('INSERT INTO users (username, password) VALUES (?,?)')
            ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
        $msg[] = '✅ User admin dibuat (admin / admin123). Segera ganti password!';
    } else $msg[] = 'ℹ️ User admin sudah ada.';

    // contoh artikel markdown
    $cnt = (int)$pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    if ($cnt === 0) {
        $sample1 = <<<'MD'
# Halo, Selamat Datang di Blog Dulur! 👋

Artikel ini disimpan sebagai **Markdown mentah** di kolom `content_md` MySQL, lalu dirender jadi HTML saat dibuka.

## Kenapa Markdown?

- Gampang ditulis, mirip catatan biasa
- **Tebal**, *miring*, ~~coret~~, `kode inline`
- Bisa diedit di mana saja, portable

## Contoh elemen

### Link & gambar

Kunjungi [Google](https://google.com) atau tampilkan gambar:

![Contoh](https://picsum.photos/800/350)

### Daftar

- Apel
- Mangga
- Durian

1. Nyalakan XAMPP
2. Buka `install.php`
3. Tulis artikel!

> Markdown membuat menulis blog jadi menyenangkan. — Tim Dulur

```php
<?php
echo "Markdown tersimpan, HTML dirender otomatis!";
```

| Fitur | Status |
|---|---|
| Simpan MD | ✅ |
| Edit + Preview | ✅ |
| Render PHP | ✅ |

---

Selamat ngeblog! 🎉
MD;
        $sample2 = <<<'MD'
# Cara Menulis dengan Markdown

Panduan cepat untuk editor di `admin/edit.php`.

## Heading

```md
# H1
## H2
### H3
```

## Teks

```md
**tebal** *miring* ~~coret~~ `kode`
[link](https://example.com)
![alt](https://.../img.jpg)
```

## Quote & list

```md
> ini kutipan

- item 1
- item 2

1. langkah 1
2. langkah 2
```

Selamat mencoba editor split-view dengan **live preview**! ✍️👁️
MD;
        $ins = $pdo->prepare("INSERT INTO posts (title, slug, excerpt, cover, content_md, status) VALUES (?,?,?,?,?, 'published')");
        $ins->execute(['Halo, Selamat Datang di Blog Dulur!', 'halo-selamat-datang', 'Artikel contoh yang disimpan sebagai Markdown mentah lalu dirender otomatis.', 'https://picsum.photos/seed/dulur/800/400', $sample1]);
        $ins->execute(['Cara Menulis dengan Markdown', 'cara-menulis-markdown', 'Panduan cepat format Markdown untuk editor blog.', null, $sample2]);
        $msg[] = '✅ 2 artikel contoh Markdown ditambahkan.';
    } else $msg[] = "ℹ️ Sudah ada $cnt artikel, contoh tidak ditambahkan.";

    // contoh halaman statis
    $cntPage = (int)$pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();
    if ($cntPage === 0) {
        $about = <<<'MD'
# Tentang Blog Dulur

Blog Dulur adalah blog sederhana berbasis **PHP + MySQL** dengan konten **Markdown**.

## pointing

- Ditulis di editor Markdown split-view
- Tersimpan mentah di database
- Dirender jadi HTML saat dibuka
MD;
        $contact = <<<'MD'
# Kontak

Hubungi tim Dulur melalui halaman ini.

- **Email:** halo@example.com
- **GitHub:** https://github.com

> Pesan dibalas maksimal 2x24 jam kerja.
MD;
        $insPage = $pdo->prepare("INSERT INTO pages (title, slug, content_md, kind, show_in_nav, mermaid, status) VALUES (?,?,?,?,?,?, 'published')");
        $insPage->execute(['Tentang Kami', 'tentang-kami', $about, 'biasa', 1, null]);
        $insPage->execute(['Kontak', 'kontak', $contact, 'biasa', 1, null]);
        $familyMd = <<<'MD'
# Keluarga Kami

Kami keluarga kecil yang tinggal dengan penuh syukur. Halaman ini berisi
perkenalan singkat dan silsilah keluarga kami.

## Anak-anak

- **Ahmad** — si sulung, suka membaca dan main bola.
- **Siti** — si tengah, gemar menggambar.
- **Budi** — si bungsu, paling ceria di rumah.
MD;
        $familyTree = <<<'MERMAID'
graph TD
    K[Kakek & Nenek] --> A[Ayah]
    K --> I[Ibu]
    A --> A1[Ahmad]
    A --> A2[Siti]
    A --> A3[Budi]
MERMAID;
        $insPage->execute(['Keluarga Kami', 'keluarga-kami', $familyMd, 'family', 0, $familyTree]);
        $msg[] = '✅ 3 halaman contoh ditambahkan (Tentang Kami, Kontak, Keluarga).';
    } else $msg[] = "ℹ️ Sudah ada $cntPage halaman, contoh tidak ditambahkan.";

    // pengaturan situs default
    $cntSet = (int)$pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    if ($cntSet === 0) {
        $insSet = $pdo->prepare("INSERT INTO settings (`k`, `v`) VALUES (?,?)");
        $insSet->execute(['site_name', SITE_NAME]);
        $insSet->execute(['site_desc', SITE_DESC]);
        $insSet->execute(['footer_text', 'Blog Dulur • Berbagi tulisan dan kabar']);
        $insSet->execute(['grad_from', '#4f46e5']);
        $insSet->execute(['grad_to', '#db2777']);
        $insSet->execute(['accent', '#4f46e5']);
        $insSet->execute(['show_source', '1']);
        $msg[] = '✅ Pengaturan situs dibuat (judul, slogan, teks footer).';
    } else $msg[] = "ℹ️ Sudah ada $cntSet pengaturan, dilewati.";

    $msg[] = '🎉 Instalasi selesai. <a href="index.php">Buka Blog</a> • <a href="admin/login.php">Login Admin</a>';
} catch (PDOException $e) {
    $msg[] = '❌ Gagal: ' . htmlspecialchars($e->getMessage()) . ' — pastikan MySQL XAMPP sudah Start.';
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Install — Blog Dulur</title>
<style>body{font-family:Segoe UI,Arial;background:#f1f5f9;padding:40px}div{max-width:640px;margin:auto;background:#fff;padding:28px;border-radius:14px;line-height:2}</style></head>
<body><div><h2>⚙️ Installer Blog Dulur</h2><?php foreach ($msg as $m) echo "<p>$m</p>"; ?></div></body></html>
