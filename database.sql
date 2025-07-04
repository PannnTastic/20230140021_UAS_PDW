-- Database: simprak
-- Create database if not exists
CREATE DATABASE IF NOT EXISTS simprak;
USE simprak;

-- Table: users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mahasiswa','asisten') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: mata_praktikum
CREATE TABLE `mata_praktikum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_praktikum` varchar(100) NOT NULL,
  `deskripsi` text,
  `asisten_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asisten_id` (`asisten_id`),
  CONSTRAINT `mata_praktikum_ibfk_1` FOREIGN KEY (`asisten_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: modul
CREATE TABLE `modul` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mata_praktikum_id` int(11) NOT NULL,
  `judul` varchar(100) NOT NULL,
  `deskripsi` text,
  `file_materi` varchar(255) NULL,
  `urutan` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `mata_praktikum_id` (`mata_praktikum_id`),
  CONSTRAINT `modul_ibfk_1` FOREIGN KEY (`mata_praktikum_id`) REFERENCES `mata_praktikum` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: pendaftaran
CREATE TABLE `pendaftaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mahasiswa_id` int(11) NOT NULL,
  `mata_praktikum_id` int(11) NOT NULL,
  `tanggal_daftar` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `mahasiswa_mata_praktikum` (`mahasiswa_id`, `mata_praktikum_id`),
  KEY `mata_praktikum_id` (`mata_praktikum_id`),
  CONSTRAINT `pendaftaran_ibfk_1` FOREIGN KEY (`mahasiswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pendaftaran_ibfk_2` FOREIGN KEY (`mata_praktikum_id`) REFERENCES `mata_praktikum` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: laporan
CREATE TABLE `laporan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mahasiswa_id` int(11) NOT NULL,
  `modul_id` int(11) NOT NULL,
  `file_laporan` varchar(255) NOT NULL,
  `tanggal_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `nilai` decimal(5,2) NULL,
  `feedback` text NULL,
  `status` enum('pending','dinilai') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `mahasiswa_id` (`mahasiswa_id`),
  KEY `modul_id` (`modul_id`),
  CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`mahasiswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `laporan_ibfk_2` FOREIGN KEY (`modul_id`) REFERENCES `modul` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default asisten account
INSERT INTO `users` (`nama`, `email`, `password`, `role`) VALUES 
('Admin Asisten', 'admin@simprak.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'asisten'),
('Mahasiswa Demo', 'mahasiswa@simprak.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mahasiswa');

-- Insert sample mata praktikum
INSERT INTO `mata_praktikum` (`nama_praktikum`, `deskripsi`, `asisten_id`) VALUES 
('Pemrograman Web', 'Praktikum pemrograman web dasar menggunakan HTML, CSS, dan JavaScript', 1),
('Jaringan Komputer', 'Praktikum jaringan komputer dan konfigurasi perangkat jaringan', 1),
('Basis Data', 'Praktikum perancangan dan implementasi basis data', 1);

-- Insert sample modul
INSERT INTO `modul` (`mata_praktikum_id`, `judul`, `deskripsi`, `urutan`) VALUES 
(1, 'HTML & CSS Dasar', 'Mempelajari struktur HTML dan styling CSS', 1),
(1, 'JavaScript Fundamentals', 'Mempelajari JavaScript dasar dan DOM manipulation', 2),
(1, 'PHP Native', 'Mempelajari PHP untuk pengembangan web dinamis', 3),
(2, 'Konfigurasi Router', 'Praktikum konfigurasi router dan switch', 1),
(2, 'Subnetting', 'Praktikum perhitungan dan implementasi subnetting', 2),
(3, 'Database Design', 'Perancangan database dengan ERD', 1),
(3, 'SQL Queries', 'Praktikum query SQL untuk manipulasi data', 2);