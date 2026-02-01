<?php
session_start();
include 'config/koneksi.php';

if(isset($_POST['btn_reset'])){
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    // Cek kecocokan data
    $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE email='$email' AND username='$username'");
    
    if(mysqli_num_rows($cek) > 0){
        // Jika cocok, update password
        $update = mysqli_query($koneksi, "UPDATE users SET password='$new_pass' WHERE email='$email'");
        if($update){
            $_SESSION['swal_icon'] = "success";
            $_SESSION['swal_title'] = "Password Diubah!";
            $_SESSION['swal_text'] = "Silakan login dengan password baru Anda.";
            header("refresh:2;url=login.php");
        }
    } else {
        $_SESSION['swal_icon'] = "error";
        $_SESSION['swal_title'] = "Data Tidak Cocok";
        $_SESSION['swal_text'] = "Email dan Username tidak ditemukan dalam sistem kami.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SISFO ARSIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root { --deep-aqua: #07575B; --ocean: #66A5AD; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, var(--deep-aqua), var(--ocean)); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: rgba(255, 255, 255, 0.95); border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); width: 100%; max-width: 400px; padding: 40px; }
        .form-control-custom { border: 2px solid #eef1f6; border-radius: 50px; padding: 12px 20px; }
        .form-control-custom:focus { border-color: var(--ocean); box-shadow: none; background-color: #f8fbff; }
        .btn-reset { background: #e74c3c; color: white; border-radius: 50px; padding: 12px; font-weight: 600; width: 100%; border: none; transition: 0.3s; }
        .btn-reset:hover { background: #c0392b; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h4 style="color: var(--deep-aqua); font-weight: 700;">Reset Password</h4>
            <p class="text-muted small">Verifikasi identitas Anda untuk membuat password baru</p>
        </div>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary ms-2">1. Masukkan Email Anda</label>
                <input type="email" name="email" class="form-control form-control-custom" placeholder="contoh@email.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary ms-2">2. Masukkan Username</label>
                <input type="text" name="username" class="form-control form-control-custom" placeholder="Username" required>
            </div>
            <hr class="my-4">
            <div class="mb-4">
                <label class="form-label small fw-bold text-success ms-2">3. Buat Password Baru</label>
                <input type="password" name="new_password" class="form-control form-control-custom" placeholder="Password Baru" required>
            </div>

            <button type="submit" name="btn_reset" class="btn btn-reset mb-3">UBAH PASSWORD</button>
            
            <div class="text-center">
                <a href="login.php" class="text-secondary small text-decoration-none fw-bold"><i class="bi bi-arrow-left"></i> Batal</a>
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