# Sistem Pendaftaran Beasiswa KIP Kuliah

Aplikasi web pendaftaran Beasiswa KIP Kuliah dibangun dengan **PHP Native (PHP 8.x) + PDO**,
**Vanilla JavaScript**, dan **Tailwind CSS (CDN)**.

## 🚀 Instalasi

1. **Clone / salin folder ini** ke direktori web server Anda (contoh: `htdocs/kip-kuliah` untuk XAMPP).

2. **Buat database** dengan mengimpor `schema.sql`:
   ```bash
   mysql -u root -p < schema.sql
   ```

3. **Atur koneksi database** di `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'kip_kuliah');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('BASE_URL', 'http://localhost/kip-kuliah'); // sesuaikan
   ```

4. **Pastikan folder upload dapat ditulis (writable)**:
   ```bash
   chmod -R 755 assets/uploads
   ```

5. Jalankan dengan PHP built-in server (opsional, untuk testing cepat):
   ```bash
   php -S localhost:8000
   ```
   Lalu buka `http://localhost:8000/`

6. Buat akun baru melalui halaman **Registrasi**. Karena ini simulasi (tanpa server email
   sungguhan), **link aktivasi akun** dan **link reset password** akan langsung ditampilkan
   di layar setelah submit form (lihat komentar `SIMULASI` pada `auth/register.php` dan
   `auth/lupa_password.php`). Untuk produksi, ganti bagian ini dengan pengiriman email
   sungguhan (mis. menggunakan PHPMailer/SMTP).

## 🗂️ Struktur Folder

```
kip-kuliah/
├── ajax/
│   ├── simpan_step.php       # Auto-save draft per step & submit final
│   └── upload_dokumen.php    # Upload dokumen (KTP/SKTM/KIP)
├── assets/
│   ├── js/
│   │   ├── api_wilayah.js    # Integrasi API wilayah.id (dropdown bertingkat)
│   │   ├── dark_mode.js      # Toggle dark/light mode (localStorage)
│   │   └── pendaftaran.js    # Logika wizard 4-step & auto-save
│   └── uploads/
│       ├── avatars/          # Avatar pengguna
│       └── dokumen/          # Dokumen pendaftaran (per ID pendaftaran)
├── auth/
│   ├── register.php          # Registrasi akun + simulasi token aktivasi
│   ├── aktivasi.php          # Aktivasi akun via token
│   ├── login.php
│   ├── logout.php
│   ├── lupa_password.php     # Request token reset password
│   └── reset_password.php    # Set password baru via token
├── includes/
│   ├── header.php            # Navbar, dark mode, Tailwind CDN
│   └── footer.php
├── config.php                 # Koneksi PDO & helper global
├── schema.sql                  # Skema database MySQL
├── index.php                   # Entry point (redirect)
├── dashboard.php                # Beranda & riwayat pendaftaran
├── pendaftaran.php               # Form multi-step (4 langkah) + auto-save
├── detail_pendaftaran.php        # Detail satu pendaftaran
├── cetak_bukti.php               # Halaman bukti pendaftaran siap cetak
└── profile.php                    # Edit profil, avatar, ganti password
```

## 🔑 Fitur Utama

- **Auth**: Registrasi, aktivasi akun (simulasi token), login, logout, lupa/reset password.
- **Dark/Light Mode**: tersimpan di `localStorage`, otomatis diterapkan saat reload.
- **Dropdown Wilayah Bertingkat**: Provinsi → Kabupaten/Kota → Kecamatan → Kelurahan,
  terintegrasi dengan REST API publik [wilayah.id](https://wilayah.id/).
- **Form Multi-Step (4 langkah)** dengan **auto-save** ke database setiap kali pengguna
  menekan "Simpan & Lanjutkan" atau "Simpan Draf" — sehingga pengisian dapat dilanjutkan
  kapan saja tanpa kehilangan data.
- **Upload Dokumen** (KTP, SKTM, Kartu KIP/PKH/KJP) dengan validasi ukuran (maks 3MB) dan
  tipe file (PDF/JPG/PNG), termasuk deteksi MIME asli via `finfo`.
- **Ringkasan & Persetujuan** sebelum pendaftaran dikirim (checklist wajib).
- **Riwayat Pendaftaran** di dashboard dengan status badge, tombol Detail & Download Bukti.
- **Cetak Bukti Pendaftaran** siap cetak / simpan sebagai PDF (via `window.print()`).

## 🔒 Catatan Keamanan

- Password di-hash menggunakan `password_hash()` (bcrypt).
- Seluruh query menggunakan **PDO Prepared Statements** untuk mencegah SQL Injection.
- Validasi CSRF token sederhana pada form-form penting.
- Folder `assets/uploads/` diproteksi dengan `.htaccess` agar file yang diunggah tidak bisa
  dieksekusi sebagai PHP.
- Validasi kepemilikan data (`user_id`) dilakukan di setiap query agar pengguna tidak dapat
  mengakses/memodifikasi data pendaftaran milik pengguna lain.

## ⚠️ Catatan Produksi

Ini adalah kerangka aplikasi (scaffold) yang siap dikembangkan lebih lanjut. Sebelum digunakan
di lingkungan produksi, pertimbangkan untuk:
- Mengganti simulasi email dengan pengiriman email sungguhan (SMTP).
- Menambahkan rate-limiting pada endpoint login & lupa password.
- Menambahkan panel Admin untuk verifikasi/penolakan pendaftaran (mengisi kolom
  `status`, `catatan_verifikasi` pada tabel `pendaftaran`).
- Menyesuaikan `BASE_URL` dan kredensial database sesuai environment.
