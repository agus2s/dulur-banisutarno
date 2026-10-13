# 📝 Blog Dulur — PHP + MySQL + Markdown

Blog sederhana tanpa framework. **Isi artikel disimpan sebagai Markdown mentah** di kolom `content_md`, dirender jadi HTML saat ditampilkan.

## ✨ Fitur
- Publik: daftar artikel, pencarian, pagination, detail + view sumber Markdown, related posts, view counter, **halaman statis** (`/halaman/slug`)
- Halaman **keluarga**: jenis halaman statis berisi deskripsi + diagram silsilah Mermaid, opsi tampil di navigasi (default sembunyi)
- Frontend: PHP klasik server-side (render langsung, tanpa proses build)
- Mode gelap: mengikuti preferensi OS + tombol bulan/matahari di navigasi (tersimpan per-perangkat)
- Admin: login, dashboard, **editor Markdown split-view** (toolbar, live preview offline, autosave draft, word count, tombol Tab), CRUD halaman statis
- Markdown support: heading, bold/italic/strike, link, gambar, code block, list, task list, quote, tabel, HR
- Aman: PDO prepared statement, `password_hash`, escaping output
- Ikon: Lucide (tanpa emoji di template publik)

## 🚀 Cara jalan (XAMPP)
1. Pastikan folder ini di `D:\xampp\htdocs\blog-dulur`
2. Salin `.env.example` menjadi `.env`, sesuaikan `DB_*` bila perlu (default XAMPP: user `root`, password kosong)
3. Start **Apache + MySQL** di XAMPP Control Panel
4. Buka `http://localhost/blog-dulur/install.php` → buat DB + admin + contoh artikel
5. Buka blog: `http://localhost/blog-dulur/`
6. Login admin: `http://localhost/blog-dulur/admin/login.php` → `admin` / `admin123`

## 🗄️ Struktur DB
- `users(id, username, password [bcrypt], display_name, email)`
- `posts(id, title, slug, excerpt, cover, content_md [MARKDOWN], status, views, created_at, updated_at)`
- `pages(id, title, slug, content_md [MARKDOWN], kind [biasa|family], show_in_nav, mermaid, status, created_at, updated_at)`

## 📁 Struktur file
```
config.php            → kredensial DB & situs
install.php           → installer otomatis
index.php / post.php  → halaman publik (render server-side)
page.php              → halaman statis publik (?slug=...)
admin/                → login, dashboard, edit (editor MD), delete, pages, page_edit, page_delete
includes/db.php       → koneksi PDO
includes/helpers.php  → slug, excerpt, auth, flash
includes/Markdown.php → parser Markdown → HTML (tanpa dependensi)
assets/style.css      → tema klasik
assets/editor.js      → toolbar + live preview + autosave
```

## ✍️ Alur Markdown
1. Tulis di `admin/edit.php` (textarea Markdown + preview)
2. Disimpan mentah ke `posts.content_md`
3. Ditampilkan via `Markdown::toHtml()` di `post.php` (+ link "Lihat sumber Markdown")
