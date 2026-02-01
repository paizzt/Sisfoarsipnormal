<?php
session_start();
// Cek Login
if($_SESSION['status'] != "login" || $_SESSION['role'] != 'admin'){
    header("location:../login.php?pesan=belum_login");
}
include '../config/koneksi.php';

// FUNGSI NOTIFIKASI
function setNotifikasi($icon, $title, $text){
    $_SESSION['swal_icon'] = $icon;
    $_SESSION['swal_title'] = $title;
    $_SESSION['swal_text'] = $text;
}

// -----------------------------------------------------------
// LOGIKA UPDATE PROFILE (EMAIL)
// -----------------------------------------------------------
if(isset($_POST['btn_update_profil'])){
    $id_user = $_SESSION['id_user'];
    $email_baru = mysqli_real_escape_string($koneksi, $_POST['email_baru']);
    
    // Cek apakah email sudah dipakai orang lain
    $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE email='$email_baru' AND id_user!='$id_user'");
    if(mysqli_num_rows($cek) > 0){
        setNotifikasi("error", "Gagal Update", "Email sudah digunakan pengguna lain.");
    } else {
        $update = mysqli_query($koneksi, "UPDATE users SET email='$email_baru' WHERE id_user='$id_user'");
        if($update){
            // Update session jika perlu (opsional)
            $_SESSION['email'] = $email_baru; 
            setNotifikasi("success", "Berhasil", "Email profil telah diperbarui.");
            header("Location: dashboard.php"); exit();
        }
    }
}

// AMBIL DATA STATISTIK
$jml_arsip = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_arsip FROM arsip WHERE status_hapus='0'"));
$jml_user  = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_user FROM users"));
$jml_folder= mysqli_num_rows(mysqli_query($koneksi, "SELECT id_kategori FROM kategori WHERE status_hapus='0'"));
$jml_sampah= mysqli_num_rows(mysqli_query($koneksi, "SELECT id_arsip FROM arsip WHERE status_hapus='1'"));

// AMBIL DATA USER YANG SEDANG LOGIN (Untuk Profile)
$id_user_login = $_SESSION['id_user']; 
$q_profil = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user_login'");
$d_profil = mysqli_fetch_array($q_profil);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SISFO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stat-card-modern {
            border: none;
            border-radius: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
        }
        .stat-card-modern:hover { transform: translateY(-5px); }
        .stat-card-modern .card-body { position: relative; z-index: 2; }
        .stat-card-modern i.bg-icon {
            position: absolute; right: -10px; bottom: -10px;
            font-size: 6rem; opacity: 0.15; z-index: 1; transform: rotate(-15deg);
        }
        .grad-ocean { background: linear-gradient(135deg, var(--ocean), var(--deep-aqua)); }
        .grad-sunset { background: linear-gradient(135deg, #ff9966, #ff5e62); }
        .grad-mint { background: linear-gradient(135deg, #56ab2f, #a8e063); }
        .grad-royal { background: linear-gradient(135deg, #654ea3, #eaafc8); }
    </style>
</head>
<body>

    <div id="loader"><div class="spinner-border text-info" role="status"></div></div>

    <div class="wrapper">
        
        <nav id="sidebar" class="d-flex flex-column">
            <div class="sidebar-header">
                <img src="../assets/img/logo.png" alt="Logo" class="img-fluid mb-2" style="max-height: 50px;">
                <h5 class="fw-bold mb-0">SISFO ARSIP</h5>
                <small class="opacity-75">Admin Panel v2.0</small>
            </div>

            <ul class="list-unstyled components flex-grow-1">
                <li class="active">
                    <a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a>
                </li>
                <li><a href="upload.php"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Arsip</a></li>
                <li><a href="arsip_semua.php"><i class="bi bi-archive-fill"></i> Data Arsip</a></li>
                <li><a href="kelola_user.php"><i class="bi bi-people-fill"></i> Kelola Pengguna</a></li>
                <li><a href="cari_file.php"><i class="bi bi-search"></i> Cari File</a></li>
                <li><a href="kategori.php"><i class="bi bi-tags-fill"></i> Kategori / Folder</a></li>
                <li><a href="sampah.php"><i class="bi bi-trash-fill"></i> Sampah</a></li>
            </ul>

            <div class="sidebar-footer text-center">
                <a href="../logout.php" class="btn btn-outline-light w-100 rounded-pill btn-sm">
                    <i class="bi bi-box-arrow-left me-2"></i> Logout
                </a>
                <small class="d-block mt-2 opacity-50 text-white" style="font-size: 0.7rem;">&copy; 2026 E-Arsip</small>
            </div>
        </nav>

        <div id="content">
            
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded-4 mb-4 px-3 py-2 sticky-top" style="z-index: 900;">
                <div class="d-flex align-items-center w-100">
                    <button type="button" id="sidebarCollapse" class="btn btn-light rounded-circle me-3 shadow-sm text-ocean">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    
                    <div class="d-none d-md-block">
                        <h5 class="fw-bold mb-0 text-secondary">Dashboard Overview</h5>
                        <small class="text-muted">Selamat Datang, <?php echo $d_profil['nama_lengkap']; ?>!</small>
                    </div>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button id="themeToggle" class="btn btn-light rounded-circle shadow-sm" title="Ganti Mode">
                            <i id="themeIcon" class="bi bi-sun-fill text-warning"></i>
                        </button>

                        <div class="dropdown ms-3">
                            <div class="user-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="text-end me-2 d-none d-sm-block">
                                    <span class="d-block fw-bold small text-dark"><?php echo $d_profil['nama_lengkap']; ?></span>
                                    <span class="d-block x-small text-muted" style="font-size: 0.7rem;">Administrator</span>
                                </div>
                                <img src="https://ui-avatars.com/api/?name=<?php echo $d_profil['nama_lengkap']; ?>&background=07575B&color=fff" class="rounded-circle shadow-sm" width="40" height="40">
                            </div>
                            
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom">
                                <li><h6 class="dropdown-header">Akun Saya</h6></li>
                                <li>
                                    <a class="dropdown-item dropdown-item-custom" href="#" data-bs-toggle="modal" data-bs-target="#modalProfile">
                                        <i class="bi bi-person-circle"></i> Lihat Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item dropdown-item-custom text-danger" href="../logout.php">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid p-0">
                <div class="row g-4 mb-5">
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card-modern grad-ocean shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="mb-1 text-white-50">Total Arsip</h5>
                                <h2 class="fw-bold mb-0"><?php echo $jml_arsip; ?></h2>
                                <i class="bi bi-file-earmark-text-fill bg-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card-modern grad-mint shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="mb-1 text-white-50">Pengguna</h5>
                                <h2 class="fw-bold mb-0"><?php echo $jml_user; ?></h2>
                                <i class="bi bi-people-fill bg-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card-modern grad-sunset shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="mb-1 text-white-50">Folder</h5>
                                <h2 class="fw-bold mb-0"><?php echo $jml_folder; ?></h2>
                                <i class="bi bi-folder-fill bg-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card-modern grad-royal shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="mb-1 text-white-50">Sampah</h5>
                                <h2 class="fw-bold mb-0"><?php echo $jml_sampah; ?></h2>
                                <i class="bi bi-trash-fill bg-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm border-0 rounded-4 h-100">
                            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold m-0 text-secondary"><i class="bi bi-clock-history me-2"></i>Upload Terakhir</h6>
                                <a href="arsip_semua.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4">Nama File</th>
                                                <th>Kategori</th>
                                                <th>Waktu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $recent = mysqli_query($koneksi, "SELECT arsip.*, kategori.nama_kategori FROM arsip JOIN kategori ON arsip.id_kategori = kategori.id_kategori WHERE arsip.status_hapus='0' ORDER BY id_arsip DESC LIMIT 5");
                                            while($r = mysqli_fetch_array($recent)){
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-text-fill text-ocean fs-4 me-3"></i>
                                                        <span class="fw-bold text-dark"><?php echo substr($r['judul_file'], 0, 30) . '...'; ?></span>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-light text-dark border"><?php echo $r['nama_kategori']; ?></span></td>
                                                <td class="text-muted small"><?php echo date('d/m/Y', strtotime($r['tgl_upload'])); ?></td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-4">
                         <div class="card shadow-sm border-0 rounded-4 h-100 bg-deep-aqua text-white" style="background-image: url('../assets/img/pattern.png'); background-size: cover;">
                            <div class="card-body p-4 d-flex flex-column justify-content-center text-center">
                                <div class="mb-3"><i class="bi bi-cloud-arrow-up-fill display-3 text-white-50"></i></div>
                                <h4 class="fw-bold">Upload Cepat</h4>
                                <p class="opacity-75 mb-4">Simpan dokumen baru ke dalam sistem arsip digital.</p>
                                <a href="upload.php" class="btn btn-warning fw-bold rounded-pill w-100 py-2 shadow">
                                    <i class="bi bi-plus-circle me-2"></i> Upload Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalProfile" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="" method="POST">
                    <div class="modal-body p-0">
                        <div class="bg-deep-aqua text-white text-center p-5 rounded-top-4 position-relative">
                            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
                            <img src="https://ui-avatars.com/api/?name=<?php echo $d_profil['nama_lengkap']; ?>&background=fff&color=07575B&size=128" class="rounded-circle border border-4 border-white shadow mb-3" width="100">
                            <h4 class="fw-bold mb-0"><?php echo $d_profil['nama_lengkap']; ?></h4>
                            <span class="badge bg-warning text-dark mt-2"><?php echo strtoupper($d_profil['role']); ?></span>
                        </div>
                        
                        <div class="p-4">
                            <div class="mb-3 border-bottom pb-2">
                                <label class="small text-muted fw-bold">USERNAME</label>
                                <input type="text" class="form-control-plaintext fw-bold text-dark" value="@<?php echo $d_profil['username']; ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="small text-ocean fw-bold mb-1"><i class="bi bi-pencil-square me-1"></i>EDIT EMAIL</label>
                                <input type="email" name="email_baru" class="form-control form-control-lg border-ocean" value="<?php echo $d_profil['email']; ?>" required>
                            </div>

                             <div class="mb-3">
                                <label class="small text-muted fw-bold">ID PENGGUNA</label>
                                <p class="mb-0 fw-semibold text-dark">#<?php echo $d_profil['id_user']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" name="btn_update_profil" class="btn btn-ocean rounded-pill px-4 fw-bold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        // NOTIFIKASI
        <?php if(isset($_SESSION['swal_icon'])): ?>
            Swal.fire({
                icon: '<?php echo $_SESSION['swal_icon']; ?>',
                title: '<?php echo $_SESSION['swal_title']; ?>',
                text: '<?php echo $_SESSION['swal_text']; ?>',
                confirmButtonColor: '#07575B',
                timer: 3000
            });
            <?php unset($_SESSION['swal_icon']); unset($_SESSION['swal_title']); unset($_SESSION['swal_text']); ?>
        <?php endif; ?>
    </script>
</body>
</html>