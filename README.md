# KIP Kuliah — Sistem Pendaftaran Beasiswa

Sistem pendaftaran beasiswa KIP Kuliah berbasis web menggunakan PHP & MySQL.

---

## Teknologi

- PHP 8+
- MySQL
- XAMPP (Apache)
- Vanilla CSS + Tailwind CDN

---

## Instalasi

1. Clone / copy folder ini ke `htdocs/kip-kuliah`
2. Import `schema.sql` ke database MySQL
3. Sesuaikan konfigurasi di `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'kip_kuliah');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('BASE_URL', 'http://localhost/kip-kuliah');
   ```
4. Buat akun admin langsung dari database:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'emailanda@gmail.com';
   ```
5. Akses di browser: `http://localhost/kip-kuliah`

---

## Fitur

**Pendaftar**
- Registrasi & login akun
- Mengisi formulir pendaftaran multi-step
- Upload dokumen (KTP, SKTM, KIP)
- Melihat status & riwayat pendaftaran
- Download bukti pendaftaran (PDF)
- Perbaikan data jika diminta verifikator

**Admin**
- Dashboard statistik pendaftaran
- Manajemen periode pendaftaran
- Verifikasi pendaftar (Lolos / Tidak Lolos / Menunggu Perbaikan)
- Catatan verifikasi untuk pendaftar
- Manajemen user & hapus pendaftaran
- Log aktivitas & pengaturan sistem

---

## Struktur Folder

```
kip-kuliah/
├── admin/          # Panel admin
├── ajax/           # Handler AJAX (upload, simpan, dll)
├── assets/         # CSS, JS, gambar, uploads
├── auth/           # Login, register, forgot password
├── includes/       # Header & footer user
├── config.php      # Konfigurasi utama
├── dashboard.php   # Dashboard pendaftar
├── pendaftaran.php # Form pendaftaran
└── schema.sql      # Struktur database
```

---

## Akun Default

Tidak ada akun default. Daftarkan akun baru lewat halaman registrasi, lalu set role admin via SQL (lihat langkah instalasi).
