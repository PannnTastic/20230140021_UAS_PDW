# SIMPRAK - Sistem Informasi Manajemen Praktikum

## Deskripsi
SIMPRAK adalah sistem informasi manajemen praktikum berbasis web yang dirancang untuk memudahkan pengelolaan kegiatan praktikum di institusi pendidikan. Sistem ini menghubungkan mahasiswa dan asisten dalam satu platform terpadu.

## Fitur Utama

### Untuk Mahasiswa:
- **Pencarian Mata Praktikum**: Melihat dan mencari mata praktikum yang tersedia
- **Pendaftaran**: Mendaftar ke mata praktikum yang diinginkan
- **Praktikum Saya**: Melihat mata praktikum yang telah diikuti
- **Detail & Tugas**: 
  - Mengunduh materi praktikum
  - Mengumpulkan laporan/tugas
  - Melihat nilai dan feedback

### Untuk Asisten:
- **Manajemen Mata Praktikum**: CRUD mata praktikum
- **Manajemen Modul**: CRUD modul per mata praktikum dengan upload materi
- **Laporan Masuk**: Melihat dan mengelola laporan mahasiswa dengan filter
- **Penilaian**: Memberikan nilai dan feedback untuk laporan
- **Manajemen Pengguna**: CRUD pengguna (mahasiswa dan asisten)

## Teknologi yang Digunakan
- **Backend**: PHP Native (tanpa framework)
- **Database**: MySQL/MariaDB
- **Frontend**: HTML, CSS, JavaScript
- **Styling**: Tailwind CSS
- **File Upload**: Sistem upload file untuk materi dan laporan

## Struktur Database
- `users`: Data pengguna (mahasiswa dan asisten)
- `mata_praktikum`: Data mata praktikum
- `modul`: Data modul per mata praktikum
- `pendaftaran`: Data pendaftaran mahasiswa ke mata praktikum
- `laporan`: Data laporan yang dikumpulkan mahasiswa

## Instalasi

1. **Clone/Download Project**
   ```bash
   git clone https://github.com/ramaravictor/SistemPengumpulanTugas.git
   ```

2. **Setup Database**
   - Buat database MySQL/MariaDB
   - Import file `database.sql`
   - Sesuaikan konfigurasi database di `config.php`

3. **Konfigurasi**
   - Edit `config.php` sesuai dengan pengaturan database Anda
   - Pastikan folder `uploads/` memiliki permission write

4. **Menjalankan Aplikasi**
   - Gunakan web server (Apache/Nginx) atau PHP built-in server
   - Akses aplikasi melalui browser

## Default Akun
- **Admin/Asisten**: 
  - Email: admin@simprak.com
  - Password: password
- **Mahasiswa Demo**:
  - Email: mahasiswa@simprak.com  
  - Password: password

## Struktur Folder
```
simprak/
├── asisten/                 # Panel asisten
│   ├── templates/          # Template asisten
│   ├── dashboard.php       # Dashboard asisten
│   ├── mata_praktikum.php  # Manajemen mata praktikum
│   ├── modul.php           # Manajemen modul
│   ├── laporan.php         # Manajemen laporan
│   └── users.php           # Manajemen pengguna
├── mahasiswa/              # Panel mahasiswa
│   ├── templates/          # Template mahasiswa
│   ├── dashboard.php       # Dashboard mahasiswa
│   ├── courses.php         # Pencarian praktikum
│   ├── my_courses.php      # Praktikum saya
│   ├── course_detail.php   # Detail praktikum
│   └── enroll_course.php   # Pendaftaran praktikum
├── uploads/                # Folder upload file
│   ├── materi/            # File materi
│   └── laporan/           # File laporan
├── config.php              # Konfigurasi database
├── database.sql            # Script database
├── index.php               # Halaman utama
├── login.php               # Halaman login
├── register.php            # Halaman registrasi
└── logout.php              # Logout
```

## Fitur Keamanan
- Password hashing menggunakan PHP `password_hash()`
- Session management yang aman
- Input sanitization
- File upload validation
- Role-based access control

## Cara Penggunaan

### Mahasiswa:
1. Register akun baru atau login
2. Cari mata praktikum yang tersedia
3. Daftar ke mata praktikum yang diinginkan
4. Akses detail praktikum untuk download materi dan upload laporan
5. Lihat nilai dan feedback dari asisten

### Asisten:
1. Login dengan akun asisten
2. Kelola mata praktikum dan modul
3. Upload materi untuk setiap modul
4. Pantau laporan yang masuk dari mahasiswa
5. Berikan nilai dan feedback untuk laporan

## Kontribusi
Proyek ini dibuat sebagai tugas UAS Praktikum Pengembangan Desain Web. Silakan fork dan berkontribusi untuk pengembangan lebih lanjut.

## Lisensi
MIT License - Silakan gunakan dan modifikasi sesuai kebutuhan.
