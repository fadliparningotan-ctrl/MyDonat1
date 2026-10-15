# My Donat - siap deploy di Vercel

Aplikasi PHP + MySQL. Vercel tidak punya MySQL dan tidak menyimpan file permanen,
jadi paket ini sudah disesuaikan:
- Runtime PHP: `vercel-php@0.7.4` (PHP 8.3)
- Database: MySQL eksternal (diatur lewat Environment Variables)
- Session login: disimpan di database
- Upload gambar baru: disimpan di database (gambar lama ada di folder `public/`)

## Langkah deploy

### 1. Siapkan database MySQL online (gratis)
Pilih salah satu: TiDB Cloud Serverless, Aiven (free MySQL), atau Railway.
Catat: host, port, user, password, nama database.

### 2. Upload project ke GitHub
Upload isi folder ini (bukan zip-nya) ke repository baru.

### 3. Import ke Vercel
vercel.com -> Add New -> Project -> pilih repo.
- Framework Preset: **Other**
- Build/Output Command: kosongkan

Sebelum klik Deploy, buka **Environment Variables** dan isi:

| Nama | Contoh |
|---|---|
| DB_HOST | gateway01.xxx.tidbcloud.com |
| DB_PORT | 3306 (TiDB: 4000) |
| DB_USER | user dari provider |
| DB_PASS | password dari provider |
| DB_NAME | mydonat |
| DB_SSL | true |
| SETUP_KEY | kunci rahasia buatanmu |

### 4. Buat tabel
Setelah deploy selesai buka:
`https://NAMA-PROJECT.vercel.app/setup.php?key=ISI_SETUP_KEY`
Harus muncul "OK - semua tabel berhasil dibuat". (Atau jalankan `schema.sql` manual.)

### 5. Pakai
Buka `/login.php`, klik daftar untuk membuat akun admin.

## Catatan
- Data lama dari MySQL lokal (XAMPP) tidak ikut. Export dari phpMyAdmin lalu import ke database online jika perlu.
- Ukuran upload gambar maksimal 3 MB (batas Vercel 4,5 MB per request).
- Setelah setup selesai, hapus env `SETUP_KEY` agar `/setup.php` tidak bisa dipakai lagi.
