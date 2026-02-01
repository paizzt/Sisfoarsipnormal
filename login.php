<?php
session_start();
include 'config/koneksi.php';

// LOGIKA LOGIN
if(isset($_POST['btn_login'])){
    $input_login = mysqli_real_escape_string($koneksi, $_POST['username']); 
    $password = $_POST['password'];

    $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$input_login' OR email='$input_login'");
    
    if(mysqli_num_rows($cek_user) > 0){
        $data = mysqli_fetch_assoc($cek_user);
        
        if(password_verify($password, $data['password'])){
            // Set Session
            $_SESSION['id_user'] = $data['id_user'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['email']    = $data['email'];
            $_SESSION['nama']     = $data['nama_lengkap'];
            $_SESSION['role']     = $data['role'];
            $_SESSION['status']   = "login";

            // Redirect sesuai Role
            if($data['role'] == 'admin'){
                header("location:admin/dashboard.php");
                exit(); // Penting: Hentikan script setelah redirect
            } 
            else if($data['role'] == 'dosen'){
                header("location:dosen/dashboard.php");
                exit();
            } 
            else if($data['role'] == 'mahasiswa'){
                header("location:mahasiswa/dashboard.php");
                exit();
            }
        } else {
            $_SESSION['pesan_error'] = "Password yang Anda masukkan salah.";
        }
    } else {
        $_SESSION['pesan_error'] = "Username atau Email tidak terdaftar.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SISFO ARSIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root { --deep-aqua: #07575B; --ocean: #66A5AD; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--deep-aqua), var(--ocean));
            height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .bg-circle { position: absolute; border-radius: 50%; background: rgba(255, 255, 255, 0.1); z-index: 0; }
        .c1 { width: 300px; height: 300px; top: -50px; right: -50px; }
        .c2 { width: 200px; height: 200px; bottom: -50px; left: -50px; }
        .login-card {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%; max-width: 400px; position: relative; z-index: 10; padding: 40px;
        }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h4 { color: var(--deep-aqua); font-weight: 700; }
        .form-control-custom { border: 2px solid #eef1f6; border-radius: 50px; padding: 12px 20px; }
        .form-control-custom:focus { border-color: var(--ocean); box-shadow: none; background-color: #f8fbff; }
        .input-group-text { background: none; border: none; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); z-index: 5; color: #aaa; cursor: pointer; }
        .btn-login { background: var(--deep-aqua); color: white; border-radius: 50px; padding: 12px; font-weight: 600; width: 100%; border: none; transition: 0.3s; }
        .btn-login:hover { background: var(--ocean); transform: translateY(-2px); }
        .auth-links a { text-decoration: none; color: var(--ocean); font-weight: 500; }
    </style>
</head>
<body>

    <div class="bg-circle c1"></div>
    <div class="bg-circle c2"></div>

    <div class="login-card">
        <div class="login-header">
            <img src="assets/img/logo.png" alt="Logo" width="60" class="mb-2" onerror="this.style.display='none'"> 
            <h4>SISFO ARSIP</h4>
            <p class="text-muted small">Silakan login untuk melanjutkan</p>
        </div>

        <form action="login.php" method="POST">
            <div class="mb-4 position-relative">
                <label class="form-label small fw-bold text-secondary ms-3">USERNAME / EMAIL</label>
                <input type="text" name="username" class="form-control form-control-custom" placeholder="Username atau Email" required autocomplete="off">
            </div>
            
            <div class="mb-4 position-relative">
                <label class="form-label small fw-bold text-secondary ms-3">PASSWORD</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="passInput" class="form-control form-control-custom" placeholder="Masukkan password" required>
                    <span class="input-group-text" onclick="togglePassword()">
                        <i class="bi bi-eye-slash" id="toggleIcon"></i>
                    </span>
                </div>
                <div class="text-end mt-2">
                    <a href="lupa_password.php" class="text-muted small text-decoration-none">Lupa Password?</a>
                </div>
            </div>

            <button type="submit" name="btn_login" class="btn btn-login">
                MASUK SEKARANG <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </form>

        <div class="auth-links mt-3 text-center small">
            Belum punya akun? <a href="register.php">Daftar disini</a>
        </div>

        <div class="text-center mt-4 text-muted small">
            &copy; 2026 Sistem Informasi Arsip Digital
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function togglePassword() {
            var input = document.getElementById("passInput");
            var icon = document.getElementById("toggleIcon");
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            } else {
                input.type = "password";
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            }
        }

        // LOGIKA NOTIFIKASI YANG DIPERBAIKI
        <?php if(isset($_SESSION['pesan_error'])): ?>
            // 1. Prioritas Pertama: Jika ada Error Password/Username
            Swal.fire({
                icon: 'error', 
                title: 'Login Gagal', 
                text: '<?php echo $_SESSION['pesan_error']; ?>', 
                confirmButtonColor: '#d33'
            }).then(() => {
                // Bersihkan URL parameter agar bersih
                window.history.replaceState(null, null, window.location.pathname);
            });
            <?php unset($_SESSION['pesan_error']); ?>
            
        <?php else: ?>
            // 2. Prioritas Kedua: Cek URL (Logout/Belum Login) HANYA JIKA tidak ada error session
            const urlParams = new URLSearchParams(window.location.search);
            const pesan = urlParams.get('pesan');

            if(pesan == 'logout'){
                Swal.fire({
                    icon: 'success', 
                    title: 'Berhasil Logout', 
                    text: 'Anda telah keluar dari sistem.', 
                    confirmButtonColor: '#07575B', 
                    timer: 2000, 
                    showConfirmButton: false
                }).then(() => {
                    window.history.replaceState(null, null, window.location.pathname);
                });
            } else if(pesan == 'belum_login'){
                Swal.fire({
                    icon: 'warning', 
                    title: 'Akses Ditolak', 
                    text: 'Silakan login terlebih dahulu.', 
                    confirmButtonColor: '#f39c12'
                }).then(() => {
                    window.history.replaceState(null, null, window.location.pathname);
                });
            }
        <?php endif; ?>
    </script>
</body>
</html>