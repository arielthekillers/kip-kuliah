-- =========================================================
-- SKEMA DATABASE: Sistem Pendaftaran Beasiswa KIP Kuliah
-- Database: MySQL 8.x
-- =========================================================

CREATE DATABASE IF NOT EXISTS `kip_kuliah` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kip_kuliah`;

-- ---------------------------------------------------------
-- Tabel: users
-- ---------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_lengkap` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `no_wa` VARCHAR(20) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status_akun` ENUM('belum_aktif','aktif','nonaktif') NOT NULL DEFAULT 'belum_aktif',
  `token_aktivasi` VARCHAR(100) DEFAULT NULL,
  `token_reset` VARCHAR(100) DEFAULT NULL,
  `token_reset_expired` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel: pendaftaran
-- Menyimpan draft & data final pendaftaran (multi-step + auto-save)
-- ---------------------------------------------------------
CREATE TABLE `pendaftaran` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `kode_pendaftaran` VARCHAR(30) DEFAULT NULL,

  -- Info Tahap / Jalur
  `tahap_periode` VARCHAR(100) DEFAULT 'Tahap 1 - 2026',
  `jenjang` VARCHAR(20) DEFAULT NULL,          -- D3/D4/S1
  `jalur_masuk` VARCHAR(50) DEFAULT NULL,      -- SNBP/SNBT/Mandiri
  `pilihan_pt` VARCHAR(150) DEFAULT NULL,

  -- STEP 1: Data Pribadi
  `nik` CHAR(16) DEFAULT NULL,
  `nama_lengkap` VARCHAR(150) DEFAULT NULL,
  `tempat_lahir` VARCHAR(100) DEFAULT NULL,
  `tanggal_lahir` DATE DEFAULT NULL,
  `jenis_kelamin` ENUM('L','P') DEFAULT NULL,
  `nama_ibu_kandung` VARCHAR(150) DEFAULT NULL,
  `alamat_jalan` VARCHAR(255) DEFAULT NULL,
  `rt` VARCHAR(5) DEFAULT NULL,
  `rw` VARCHAR(5) DEFAULT NULL,
  `kode_pos` VARCHAR(10) DEFAULT NULL,
  `provinsi_id` VARCHAR(20) DEFAULT NULL,
  `provinsi_nama` VARCHAR(100) DEFAULT NULL,
  `kabupaten_id` VARCHAR(20) DEFAULT NULL,
  `kabupaten_nama` VARCHAR(100) DEFAULT NULL,
  `kecamatan_id` VARCHAR(20) DEFAULT NULL,
  `kecamatan_nama` VARCHAR(100) DEFAULT NULL,
  `kelurahan_id` VARCHAR(20) DEFAULT NULL,
  `kelurahan_nama` VARCHAR(100) DEFAULT NULL,
  `no_wa_aktif` VARCHAR(20) DEFAULT NULL,
  `email_aktif` VARCHAR(150) DEFAULT NULL,

  -- STEP 2: Data Pendidikan
  `nama_lembaga` VARCHAR(200) DEFAULT NULL,
  `program_studi` VARCHAR(150) DEFAULT NULL,
  `nisn` VARCHAR(20) DEFAULT NULL,
  `nim` VARCHAR(30) DEFAULT NULL,
  `tahun_masuk` YEAR DEFAULT NULL,

  -- STEP 4: Persetujuan
  `setuju_data_benar` TINYINT(1) NOT NULL DEFAULT 0,
  `setuju_konsekuensi` TINYINT(1) NOT NULL DEFAULT 0,
  `setuju_tidak_diubah` TINYINT(1) NOT NULL DEFAULT 0,

  -- Status & progres
  `current_step` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `status` ENUM('draft','menunggu_verifikasi','diverifikasi','tidak_lolos_verifikasi','menunggu_perbaikan') NOT NULL DEFAULT 'draft',
  `catatan_verifikasi` JSON DEFAULT NULL,
  `submitted_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_pendaftaran_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel: dokumen_pendaftaran
-- ---------------------------------------------------------
CREATE TABLE `dokumen_pendaftaran` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `pendaftaran_id` INT UNSIGNED NOT NULL,
  `jenis_dokumen` ENUM('ktp','sktm','kip') NOT NULL,
  `nama_file_asli` VARCHAR(255) NOT NULL,
  `nama_file_simpan` VARCHAR(255) NOT NULL,
  `path_file` VARCHAR(255) NOT NULL,
  `ukuran_file` INT UNSIGNED DEFAULT NULL, -- bytes
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT `fk_dokumen_pendaftaran` FOREIGN KEY (`pendaftaran_id`) REFERENCES `pendaftaran`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uniq_pendaftaran_jenis` (`pendaftaran_id`, `jenis_dokumen`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel: activity_log (opsional, jejak aktivitas akun)
-- ---------------------------------------------------------
CREATE TABLE `activity_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `aktivitas` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Index tambahan untuk performa query dashboard
CREATE INDEX idx_pendaftaran_user_status ON pendaftaran(user_id, status);

-- ---------------------------------------------------------
-- Tabel: periode_pendaftaran
-- ---------------------------------------------------------
CREATE TABLE `periode_pendaftaran` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_periode` VARCHAR(150) NOT NULL,
  `tanggal_buka` DATETIME NOT NULL,
  `tanggal_tutup` DATETIME NOT NULL,
  `status_periode` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'nonaktif',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Index tambahan untuk performa query periode pendaftaran
CREATE INDEX idx_periode_aktif ON periode_pendaftaran(status_periode, tanggal_buka, tanggal_tutup);

-- ---------------------------------------------------------
-- Tabel: settings
-- ---------------------------------------------------------
CREATE TABLE `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `app_name` VARCHAR(100) NOT NULL DEFAULT 'KIP Kuliah',
  `app_timezone` VARCHAR(50) NOT NULL DEFAULT 'Asia/Jakarta',
  `email_from` VARCHAR(150) NOT NULL DEFAULT 'noreply@abdulwachid.com',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default setting
INSERT INTO `settings` (`app_name`, `app_timezone`, `email_from`) VALUES ('Sistem Pendaftaran Beasiswa KIP Kuliah', 'Asia/Jakarta', 'noreply@abdulwachid.com');
