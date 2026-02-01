# 📂 SISFO ARSIP - Sistem Informasi Arsip Digital

**SISFO ARSIP** adalah aplikasi berbasis web untuk pengelolaan dokumen akademik, surat-menyurat, dan arsip digital. Dibangun menggunakan PHP Native, MySQL, dan Bootstrap 5 dengan desain antarmuka modern (Glassmorphism).

Sistem ini mendukung manajemen hak akses yang fleksibel (Publik & Privat) serta berbagi dokumen secara spesifik antara Mahasiswa dan Dosen.

## 🚀 Fitur Utama

### 🎨 Antarmuka (UI/UX)
* **Modern Design:** Menggunakan gaya *Glassmorphism* dengan gradien warna *Deep Aqua*.
* **Responsive:** Tampilan optimal di Desktop dan Mobile.
* **Interactive:** Notifikasi menggunakan SweetAlert2.

### 🔐 Keamanan
* **Enkripsi Password:** Menggunakan `password_hash()` (Bcrypt).
* **Role-Based Access Control:** Membedakan akses Admin, Dosen, dan Mahasiswa.
* **Session Management:** Proteksi halaman berdasarkan login dan role.

### 👥 Fitur Berdasarkan Role

#### 1. Administrator
* **Dashboard Statistik:** Ringkasan jumlah arsip, user, dan kategori.
* **Manajemen User:** Tambah, Edit, Hapus User (Dosen/Mahasiswa).
* **Manajemen Folder (Kategori):** Membuat folder Publik atau Privat (khusus dosen tertentu).
* **Manajemen Arsip:** Upload, Hapus (Soft Delete), dan Restore data dari Sampah.
* **Recycle Bin:** Fitur sampah untuk memulihkan atau menghapus permanen data.

#### 2. Dosen
* **Dashboard Personal:** Statistik arsip yang bisa diakses.
* **Akses Arsip Cerdas:**
    * Melihat semua arsip **Publik**.
    * Melihat arsip **Privat** yang secara khusus dibagikan kepadanya oleh Mahasiswa atau Admin.
* **Pencarian File:** Filter berdasarkan Tahun dan Kategori.
* **Edit Profil:** Update Email dan informasi akun.

#### 3. Mahasiswa
* **Upload Mandiri:** Mengunggah dokumen/tugas dengan fitur *Drag & Drop*.
* **Pengaturan Privasi:**
    * **Publik:** File dapat dilihat semua orang.
    * **Privat:** File hanya dapat dilihat oleh Mahasiswa itu sendiri dan Dosen yang dipilih.
* **Manajemen File Sendiri:** Edit dan Hapus file milik sendiri.
* **Pencarian:** Mencari referensi dari arsip publik.

---

## 🛠️ Teknologi yang Digunakan

* **Backend:** PHP (Native / Procedural)
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, CSS3, Bootstrap 5.3
* **Icons:** Bootstrap Icons
* **Alerts:** SweetAlert2
* **Local Server:** XAMPP / Laragon (PHP 8.x Recommended)

---

## 📂 Struktur Folder

```text
/sisfo-arsip
│
├── admin/              # Halaman Panel Admin
├── assets/             # CSS, JS, Gambar, dan File Upload
│   ├── css/
│   ├── files/          # Tempat penyimpanan fisik file
│   └── img/
├── config/             # Koneksi Database
├── dosen/              # Halaman Panel Dosen
├── mahasiswa/          # Halaman Panel Mahasiswa
│
├── index.php           # Landing Page
├── login.php           # Halaman Login
├── register.php        # Halaman Daftar (Mhs/Dosen)
├── lupa_password.php   # Reset Password
└── logout.php          # Script Logout

⚙️ Cara Instalasi
Clone atau Download repository ini.

Pindahkan folder ke dalam direktori server lokal (misal: C:\xampp\htdocs\sisfo-arsip).

Buat Database di phpMyAdmin dengan nama db_arsip.

Import file database.sql (lihat di bawah) ke dalam database tersebut.

Konfigurasi Koneksi: Buka config/koneksi.php dan sesuaikan settingan jika perlu:

PHP

$koneksi = mysqli_connect("localhost", "root", "", "db_arsip");
Aktifkan Extension ZIP (Jika menggunakan XAMPP):

Buka php.ini.

Cari ;extension=zip.

Hapus tanda titik koma ; menjadi extension=zip.

Restart Apache.

🔑 Akun Default (Untuk Testing)
Anda perlu membuat akun Admin pertama kali secara manual di database atau register, lalu ubah role-nya di phpMyAdmin.

Admin: Buat manual di database dengan role admin.

Dosen/Mahasiswa: Bisa daftar lewat halaman register.php.

📝 Skema Database (database.sql)
Copy kode SQL di bawah ini dan jalankan di tab SQL pada phpMyAdmin Anda untuk membuat tabel yang diperlukan.

SQL

-- 1. Tabel Users
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','dosen','mahasiswa') NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_user`)
);

-- 2. Tabel Kategori (Folder)
CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `tahun` varchar(10) NOT NULL,
  `status_folder` enum('publik','privat') NOT NULL DEFAULT 'publik',
  `status_hapus` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_kategori`)
);

-- 3. Tabel Arsip (File)
CREATE TABLE `arsip` (
  `id_arsip` int(11) NOT NULL AUTO_INCREMENT,
  `id_kategori` int(11) NOT NULL,
  `pengunggah_id` int(11) NOT NULL,
  `judul_file` varchar(255) NOT NULL,
  `nama_file_fisik` varchar(255) NOT NULL,
  `tipe_file` varchar(50) NOT NULL,
  `ukuran_file` varchar(50) NOT NULL,
  `jenis_surat` enum('Surat Masuk','Surat Keluar') NOT NULL,
  `status_akses` enum('publik','privat') NOT NULL DEFAULT 'publik',
  `tgl_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_hapus` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_arsip`)
);

-- 4. Tabel Hak Akses File (Sharing Privat)
CREATE TABLE `hak_akses` (
  `id_akses` int(11) NOT NULL AUTO_INCREMENT,
  `id_arsip` int(11) NOT NULL,
  `id_user` int(11) NOT NULL, -- User (Dosen) yang diberi izin
  PRIMARY KEY (`id_akses`)
);

-- 5. Tabel Hak Akses Folder (Sharing Folder Privat)
CREATE TABLE `hak_akses_folder` (
  `id_akses_folder` int(11) NOT NULL AUTO_INCREMENT,
  `id_kategori` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  PRIMARY KEY (`id_akses_folder`)
);
📄 Lisensi
Project ini dibuat untuk tujuan pembelajaran dan pengembangan sistem informasi akademik sederhana. Bebas dikembangkan lebih lanjut.

Developed by: Faisal Faiz

Year: 2026


### Tips Tambahan:
Jika Anda ingin membuat repositori di GitHub:
1.  Buat file `.gitignore` di folder utama.
2.  Isi dengan:
    ```text
    /assets/files/*
    !/assets/files/.gitkeep
    /config/koneksi.php
    ```
    *(Ini agar file-file arsip rahasia dan password database tidak ikut ter-upload)