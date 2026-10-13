# Panduan Upload & Menulis Markdown — Blog Dulur

Artikel dan halaman disimpan sebagai **Markdown mentah** di database, lalu
dirender otomatis jadi HTML saat dibuka pengunjung.

## Cara 1 — Upload file `.md` (tercepat)

1. Login admin, buka **Tulis Artikel** (`admin/edit.php`) atau **Tulis Halaman**
   (`admin/page_edit.php`).
2. Klik tombol **.md** (ikon upload) di ujung kanan toolbar editor.
3. Pilih file `.md` / `.markdown` / `.txt` dari komputer.
4. Isi editor langsung terganti file tersebut dan pratinjau diperbarui.
   - Kalau editor sudah berisi teks, muncul konfirmasi sebelum diganti.
   - Kalau kolom **Judul** masih kosong, judul diisi otomatis dari nama file
     (`cara-menulis.md` → `cara menulis`, tinggal rapikan kapitalnya).
5. Cek pratinjau, atur **Status**, klik **Simpan ke MySQL**.

> File tidak disimpan ke server — isinya dibaca di browser lalu disimpan
> sebagai teks ke database saat kamu klik Simpan.

## Cara 2 — Tulis / tempel langsung

Ketik di kolom **MARKDOWN** (kiri) sambil melihat **PRATINJAU** (kanan).
Toolbar membantu memformat: heading, tebal, miring, coret, kode, quote,
list, task list, link, gambar, tabel, garis. Draft otomatis tersimpan di
browser (localStorage), tombol `Tab` jadi 2 spasi.

## Cheatsheet format

| Kebutuhan   | Tulis                                  |
|-------------|----------------------------------------|
| Heading     | `# H1`, `## H2`, `### H3`              |
| Tebal       | `**tebal**`                            |
| Miring      | `*miring*`                             |
| Coret       | `~~coret~~`                            |
| Kode inline | `` `kode` ``                           |
| Blok kode   | ` ```php ... ``` ` (tanpa spasi)      |
| Link        | `[teks](https://...)`                  |
| Gambar      | `![alt](https://.../gambar.jpg)`       |
| List        | `- item` atau `1. langkah`             |
| Task        | `- [ ] tugas` / `- [x] selesai`        |
| Kutipan     | `> teks kutipan`                       |
| Tabel       | `\| A \| B \|` + baris `\|---\|---\|`  |
| Garis       | `---`                                  |

## Terbit vs draft

- **published** → tampil di blog, API, dan mesin pencari.
- **draft** → hanya terlihat di panel admin (untuk arsip / tulisan belum jadi).
- Slug dibuat otomatis dari judul (`Cara Menulis!` → `cara-menulis`).
  Mengubah judul saat edit akan memperbarui slug.

## Gambar & cover

- **Cover artikel**: isi kolom *Cover* dengan URL gambar
  (mis. `https://picsum.photos/seed/abc/800/400`).
- **Gambar di isi**: pakai sintaks `![alt](url)`. Simpan file gambar di
  `assets/` lalu rujuk relatif bila perlu.

## Halaman statis

Untuk *Tentang Kami*, *Kontak*, dsb: gunakan **Tulis Halaman**, bukan
artikel. Halaman terbit otomatis muncul di menu navigasi dan dibaca lewat
`halaman/slug-nya`.

**Link ke halaman lain:** tulis `[Teks](page:slug-tujuan)`, mis.
`[Hubungi kami](page:kontak)`. Otomatis jadi permalink kanonis
(`/halaman/kontak`) dan tetap benar walau blog pindah folder/domain —
jangan tulis path mentah seperti `/halaman/kontak` (rusak bila blog di
subfolder) atau `page.php?slug=...` (URL lama).

## Halaman keluarga

1. Di **Tulis Halaman**, ubah **Jenis** menjadi *Keluarga*.
2. Isi Markdown seperti biasa: deskripsi keluarga dan daftar nama anak-anak.
3. Isi **Diagram Silsilah** dengan sintaks flowchart Mermaid, mis:
   `graph TD` lalu `A[Ayah] --> B[Anak]`.
4. Diagram tampil otomatis di bawah isi halaman; ikon badge berubah jadi
   *Keluarga*.
5. Halaman keluarga **default disembunyikan** dari menu navigasi — centang
   *Tampilkan di menu navigasi* bila ingin menampilkannya.

**Slug halaman bisa diganti manual** di kolom Slug pada form (otomatis dari
judul bila dikosongkan). Slug yang bentrok otomatis diberi akhiran `-2`,
`-3`, dst. Ingat: mengganti slug mengubah permalink, URL lama tidak aktif.

## Tanya jawab

- **Pratinjau kosong?** Mulai ketik `# Judul` di kolom Markdown.
- **Draft hilang?** Cek browser yang sama (autosave per-browser); setelah
  Simpan, data aman di MySQL.
- **Tabel tidak jadi?** Pastikan baris pemisah (`|---|---|`) tepat di bawah
  baris judul tabel.
- **Ingin edit massal?** Edit file `.md` di VS Code, lalu upload ulang dan
  Simpan.
