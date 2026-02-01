<?php
session_start();
include 'config/koneksi.php';

if(isset($_POST['btn_register'])){
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Tangkap input Role dari form
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);

    // Cek Duplikat
    $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE email='$email' OR username='$username'");
    if(mysqli_num_rows($cek) > 0){
        $_SESSION['swal_icon'] = "error";
        $_SESSION['swal_title'] = "Gagal Daftar";
        $_SESSION['swal_text'] = "Email atau Username sudah digunakan!";
    } else {
        $simpan = mysqli_query($koneksi, "INSERT INTO users (nama_lengkap, email, username, password, role) VALUES ('$nama', '$email', '$username', '$password', '$role')");
        if($simpan){
            $_SESSION['swal_icon'] = "success";
            $_SESSION['swal_title'] = "Berhasil!";
            $_SESSION['swal_text'] = "Akun berhasil dibuat. Silakan login.";
            header("refresh:2;url=login.php"); 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SISFO ARSIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root { --deep-aqua: #07575B; --ocean: #66A5AD; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, var(--deep-aqua), var(--ocean)); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: rgba(255, 255, 255, 0.95); border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); width: 100%; max-width: 450px; padding: 40px; }
        
        /* Custom Input Style */
        .form-control-custom, .form-select-custom { 
            border: 2px solid #eef1f6; 
            border-radius: 50px; 
            padding: 12px 20px; 
            width: 100%;
        }
        .form-control-custom:focus, .form-select-custom:focus { 
            border-color: var(--ocean); 
            box-shadow: none; 
            background-color: #f8fbff; 
            outline: none;
        }
        
        .btn-login { background: var(--deep-aqua); color: white; border-radius: 50px; padding: 12px; font-weight: 600; width: 100%; border: none; transition: 0.3s; }
        .btn-login:hover { background: var(--ocean); }
        .auth-links a { color: var(--ocean); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h4 style="color: var(--deep-aqua); font-weight: 700;">Buat Akun Baru</h4>
            <p class="text-muted small">Lengkapi data diri Anda</p>
        </div>

        <form action="" method="POST">
            <div class="mb-3">
                <input type="text" name="nama" class="form-control form-control-custom" placeholder="Nama Lengkap" required>
            </div>
            <div class="mb-3">
                <input type="email" name="email" class="form-control form-control-custom" placeholder="Alamat Email" required>
            </div>
            <div class="mb-3">
                <input type="text" name="username" class="form-control form-control-custom" placeholder="Username" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control form-control-custom" placeholder="Password" required>
            </div>
            
            <div class="mb-4">
                <select name="role" class="form-select form-select-custom text-secondary" required>
                    <option value="" selected disabled>-- Daftar Sebagai --</option>
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="dosen">Dosen</option>
                </select>
            </div>

            <button type="submit" name="btn_register" class="btn btn-login mb-3">DAFTAR SEKARANG</button>
            
            <div class="text-center">
                <a href="login.php" class="auth-links text-secondary small"><i class="bi bi-arrow-left"></i> Kembali ke Login</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if(isset($_SESSION['swal_icon'])): ?>
            Swal.fire({
                icon: '<?php echo $_SESSION['swal_icon']; ?>',
                title: '<?php echo $_SESSION['swal_title']; ?>',
                text: '<?php echo $_SESSION['swal_text']; ?>',
                confirmButtonColor: '#07575B'
            });
            <?php unset($_SESSION['swal_icon']); unset($_SESSION['swal_title']); unset($_SESSION['swal_text']); ?>
        <?php endif; ?>
    </script>
</body>
</html>