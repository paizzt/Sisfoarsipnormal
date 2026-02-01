<?php
session_start();
include 'config/koneksi.php';

// Tangkap inputan (bisa berisi email, bisa berisi username)
$user_email = $_POST['user_email']; 
$password   = $_POST['password'];

// Query Logika OR: Cari user dimana Email-nya cocok ATAU Username-nya cocok
$login = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$user_email' OR username='$user_email'");
$cek = mysqli_num_rows($login);

if($cek > 0){
    $data = mysqli_fetch_assoc($login);

    // Verifikasi Password
    if(password_verify($password, $data['password'])){
        
        // Simpan Session lengkap
        $_SESSION['id_user'] = $data['id_user']; // Penting untuk relasi database nanti
        $_SESSION['username'] = $data['username'];
        $_SESSION['email'] = $data['email'];
        $_SESSION['nama'] = $data['nama_lengkap'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['status'] = "login";

        // Redirect sesuai Role
        if($data['role'] == "admin"){
            header("location:admin/dashboard.php");
        } else if($data['role'] == "dosen"){
            header("location:dosen/dashboard.php");
        } else if($data['role'] == "mahasiswa"){
            header("location:mahasiswa/dashboard.php");
        }

    } else {
        // Password Salah
        header("location:login.php?pesan=gagal");
    }
} else {
    // Email atau Username Tidak Ditemukan
    header("location:login.php?pesan=gagal");
}
?>