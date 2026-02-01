<?php
session_start();
// Cek Login & Role Khusus Dosen
if($_SESSION['status'] != "login" || $_SESSION['role'] != 'dosen'){
    header("location:../login.php?pesan=belum_login");
}

include '../config/koneksi.php';

// --- LOGIKA UPDATE PROFIL (EMAIL) ---
if(isset($_POST['btn_update_profil'])){
    $id_user = $_SESSION['id_user'];
    $email_baru = mysqli_real_escape_string($koneksi, $_POST['email_baru']);
    
    // Cek apakah email sudah dipakai orang lain
    $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE email='$email_baru' AND id_user!='$id_user'");
    if(mysqli_num_rows($cek) > 0){
        $_SESSION['swal_icon'] = "error";
        $_SESSION['swal_title'] = "Gagal Update";
        $_SESSION['swal_text'] = "Email sudah digunakan pengguna lain.";
    } else {
        $update = mysqli_query($koneksi, "UPDATE users SET email='$email_baru' WHERE id_user='$id_user'");
        if($update){
            $_SESSION['email'] = $email_baru; // Update session
            $_SESSION['swal_icon'] = "success";
            $_SESSION['swal_title'] = "Berhasil";
            $_SESSION['swal_text'] = "Email profil telah diperbarui.";
            header("Location: dashboard.php"); exit();
        }
    }
}

// AMBIL DATA PROFIL TERBARU
$id_user = $_SESSION['id_user'];
$q_profil = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user'");
$d_profil = mysqli_fetch_array($q_profil);

// FUNGSI LOGIKA STATISTIK
// 1. Hitung Arsip Publik
$q_publik = mysqli_query($koneksi, "SELECT id_arsip FROM arsip WHERE status_akses='publik' AND status_hapus='0'");
$jml_publik = mysqli_num_rows($q_publik);

// 2. Hitung Arsip Privat yang dishare ke Dosen ini
$q_privat = mysqli_query($koneksi, "SELECT arsip.id_arsip FROM arsip 
                                    JOIN hak_akses ON arsip.id_arsip = hak_akses.id_arsip 
                                    WHERE hak_akses.id_user='$id_user' AND arsip.status_hapus='0'");
$jml_privat = mysqli_num_rows($q_privat);

// 3. Total Akses
$total_akses = $jml_publik + $jml_privat;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - SISFO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        /* Styling Dashboard */
        .stat-card-modern { border: none; border-radius: 20px; color: white; position: relative; overflow: hidden; transition: transform 0.3s; height: 100%; }
        .stat-card-modern:hover { transform: translateY(-5px); }
        .stat-card-modern .card-body { position: relative; z-index: 2; }
        .stat-card-modern i.bg-icon { position: absolute; right: -10px; bottom: -10px; font-size: 6rem; opacity: 0.15; z-index: 1; transform: rotate(-15deg); }
        .grad-ocean { background: linear-gradient(135deg, var(--ocean), var(--deep-aqua)); }
        .grad-sunset { background: linear-gradient(135deg, #ff9966, #ff5e62); }
        .grad-mint { background: linear-gradient(135deg, #56ab2f, #a8e063); }
        
        /* Header Custom */
        .header-right { display: flex; align-items: center; gap: 1rem; }
        .btn-theme-toggle { width: 42px; height: 42px; border-radius: 50%; border: 1px solid #eee; background-color: #fff; color: var(--ocean); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; transition: 0.3s; }
        .btn-theme-toggle:hover { background-color: var(--seafoam); color: var(--deep-aqua); transform: rotate(15deg); }
        .header-divider { width: 1px; height: 30px; background-color: #e0e0e0; }
        .user-profile-box { display: flex; align-items: center; gap: 12px; padding: 6px 8px 6px 15px; border-radius: 50px; transition: 0.3s; cursor: pointer; border: 1px solid transparent; }
        .user-profile-box:hover, .user-profile-box[aria-expanded="true"] { background-color: #f8f9fa; border-color: #eee; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .user-info { text-align: right; line-height: 1.2; }
        .user-name { font-weight: 700; color: var(--deep-aqua); font-size: 0.95rem; display: block; }
        .user-role { font-size: 0.75rem; color: #888; font-weight: 500; display: block; margin-top: 2px; }
        .user-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        /* Dark Mode Override */
        body.dark-mode .btn-theme-toggle { background-color: #2c2c2c; border-color: #444; color: #f1c40f; }
        body.dark-mode .user-profile-box:hover { background-color: #2c2c2c; border-color: #444; }
        body.dark-mode .user-name { color: #fff; }
        body.dark-mode .user-role { color: #aaa; }
    </style>
</head>
<body>

    <div id="loader"><div class="spinner-border text-info" style="width: 3rem; height: 3rem;" role="status"></div></div>

    <div class="wrapper">
        
        <nav id="sidebar" class="d-flex flex-column">
            <div class="sidebar-header">
                <img src="../assets/img/logo.png" alt="Logo" class="img-fluid mb-2" style="max-height: 50px;">
                <h5 class="fw-bold mb-0">SISFO ARSIP</h5>
                <small class="opacity-75">Panel Dosen</small>
            </div>

            <ul class="list-unstyled components flex-grow-1">
                <li class="active"><a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
                <li><a href="arsip_dosen.php"><i class="bi bi-archive-fill"></i> Data Arsip</a></li>
                <li><a href="cari_file.php"><i class="bi bi-search"></i> Cari File</a></li>
            </ul>

            <div class="sidebar-footer text-center">
                <a href="../logout.php" class="btn btn-outline-light w-100 rounded-pill btn-sm">
                    <i class="bi bi-box-arrow-left me-2"></i> Logout
                </a>
                <small class="d-block mt-2 opacity-50 text-white" style="font-size: 0.7rem;">&copy; 2026 E-Arsip</small>
            </div>
        </nav>

        <div id="content">
            
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded-4 mb-4 px-4 py-2 sticky-top" style="z-index: 900;">
                <div class="d-flex align-items-center w-100">
                    <button type="button" id="sidebarCollapse" class="btn btn-light rounded-circle me-3 shadow-sm text-ocean">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    
                    <div class="d-none d-md-block">
                        <h5 class="fw-bold mb-0 text-secondary" style="font-size: 1.1rem;">Dashboard Dosen</h5>
                        <small class="text-muted">Selamat Datang, <?php echo $d_profil['nama_lengkap']; ?>!</small>
                    </div>

                    <div class="ms-auto header-right">
                        <button id="themeToggle" class="btn-theme-toggle" title="Ganti Mode Tampilan">
                            <i id="themeIcon" class="bi bi-sun-fill"></i>
                        </button>
                        <div class="header-divider d-none d-sm-block"></div>
                        <div class="dropdown">
                            <div class="user-profile-box" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-info d-none d-sm-block">
                                    <span class="user-name"><?php echo $d_profil['nama_lengkap']; ?></span>
                                    <span class="user-role">Dosen Pengajar</span>
                                </div>
                                <img src="https://ui-avatars.com/api/?name=<?php echo $d_profil['nama_lengkap']; ?>&background=07575B&color=fff&size=128&bold=true" class="user-avatar">
                            </div>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom border-0 shadow-lg mt-2" style="border-radius: 15px;">
                                <li class="px-3 py-2 border-bottom mb-2">
                                    <small class="text-muted fw-bold" style="font-size: 0.7rem;">AKUN SAYA</small>
                                    <div class="fw-bold text-dark"><?php echo $d_profil['email']; ?></div>
                                </li>
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
                    <div class="col-xl-4 col-md-6">
                        <div class="card stat-card-modern grad-ocean shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="mb-1 text-white-50">Total Akses Arsip</h5>
                                <h2 class="fw-bold mb-0"><?php echo $total_akses; ?></h2>
                                <p class="mb-0 small text-white-50 mt-1">Dokumen yang bisa Anda lihat</p>
                                <i class="bi bi-folder2-open bg-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card stat-card-modern grad-mint shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="mb-1 text-white-50">Arsip Publik</h5>
                                <h2 class="fw-bold mb-0"><?php echo $jml_publik; ?></h2>
                                <p class="mb-0 small text-white-50 mt-1">Dokumen umum</p>
                                <i class="bi bi-globe bg-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card stat-card-modern grad-sunset shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="mb-1 text-white-50">Arsip Privat</h5>
                                <h2 class="fw-bold mb-0"><?php echo $jml_privat; ?></h2>
                                <p class="mb-0 small text-white-50 mt-1">Dibagikan khusus untuk Anda</p>
                                <i class="bi bi-lock-fill bg-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold m-0 text-secondary"><i class="bi bi-clock-history me-2"></i>Arsip Terbaru untuk Anda</h6>
                        <a href="arsip_dosen.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Nama File</th>
                                        <th>Kategori</th>
                                        <th>Tipe Akses</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT arsip.*, kategori.nama_kategori 
                                              FROM arsip 
                                              JOIN kategori ON arsip.id_kategori = kategori.id_kategori
                                              LEFT JOIN hak_akses ON arsip.id_arsip = hak_akses.id_arsip 
                                              WHERE arsip.status_hapus='0' 
                                              AND (
                                                  arsip.status_akses = 'publik' 
                                                  OR 
                                                  (arsip.status_akses = 'privat' AND hak_akses.id_user = '$id_user')
                                              )
                                              GROUP BY arsip.id_arsip 
                                              ORDER BY arsip.id_arsip DESC LIMIT 5";
                                    
                                    $recent = mysqli_query($koneksi, $query);
                                    while($r = mysqli_fetch_array($recent)){
                                        $label_akses = ($r['status_akses'] == 'publik') ? '<span class="badge bg-success rounded-pill">Publik</span>' : '<span class="badge bg-danger rounded-pill">Privat (Shared)</span>';
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-file-earmark-text-fill text-ocean fs-4 me-3"></i>
                                                <span class="fw-bold text-dark"><?php echo substr($r['judul_file'], 0, 40); ?></span>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $r['nama_kategori']; ?></span></td>
                                        <td><?php echo $label_akses; ?></td>
                                        <td class="text-muted small"><?php echo date('d/m/Y', strtotime($r['tgl_upload'])); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
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
                            <span class="badge bg-warning text-dark mt-2">DOSEN</span>
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
        // Notifikasi SweetAlert
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