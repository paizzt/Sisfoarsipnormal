<?php
session_start();
// Cek Login & Role
if($_SESSION['status'] != "login" || $_SESSION['role'] != 'dosen'){
    header("location:../login.php?pesan=belum_login");
}

include '../config/koneksi.php';

// AMBIL DATA PROFIL
$id_user = $_SESSION['id_user'];
$q_profil = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user'");
$d_profil = mysqli_fetch_array($q_profil);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Arsip Dosen - SISFO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        /* Header & Sidebar Konsisten */
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
        
        /* Search Card Styles */
        .search-card {
            background: linear-gradient(135deg, var(--deep-aqua) 0%, var(--ocean) 100%);
            color: white; border: none; border-radius: 20px;
        }
        .form-control-search {
            border: none; padding: 15px 25px; font-size: 1.1rem; border-radius: 50px; box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .btn-search-action {
            border-radius: 50px; padding: 12px 30px; font-weight: bold; background-color: var(--wave); color: white; border: none; transition: 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-search-action:hover { background-color: #fff; color: var(--ocean); transform: translateY(-2px); }
        
        .clickable-row { cursor: pointer; transition: 0.2s; }
        .clickable-row:hover { background-color: #f1f8ff !important; transform: scale(1.002); }
        .empty-state { text-align: center; padding: 60px 20px; color: #adb5bd; }
        .empty-state i { font-size: 5rem; margin-bottom: 20px; display: block; opacity: 0.5; }
        
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
                <li><a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
                <li><a href="arsip_dosen.php"><i class="bi bi-archive-fill"></i> Data Arsip</a></li>
                <li class="active"><a href="cari_file.php" class="text-warning fw-bold"><i class="bi bi-search"></i> Cari File</a></li>
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
                        <h5 class="fw-bold mb-0 text-secondary" style="font-size: 1.1rem;">Pencarian File</h5>
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
                                <li><a class="dropdown-item dropdown-item-custom text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                
                <div class="card search-card shadow-lg mb-4">
                    <div class="card-body p-5 text-center">
                        <h3 class="fw-bold mb-3 text-white"><i class="bi bi-search me-2"></i>Cari Arsip Akademik</h3>
                        <p class="mb-4 text-white opacity-75">Cari dokumen publik atau dokumen yang dibagikan kepada Anda.</p>
                        
                        <form action="" method="GET">
                            <div class="row justify-content-center g-2">
                                <div class="col-md-6">
                                    <input type="text" name="keyword" class="form-control form-control-search" placeholder="Ketik judul file..." value="<?php echo isset($_GET['keyword']) ? $_GET['keyword'] : ''; ?>" autocomplete="off" autofocus>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-search-action w-100 h-100">CARI</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php
                if(isset($_GET['keyword'])){
                    $keyword = mysqli_real_escape_string($koneksi, $_GET['keyword']);
                    
                    // QUERY AMAN UNTUK DOSEN:
                    // 1. Filter Keyword
                    // 2. Filter Hak Akses (Publik OR (Privat + Hak Akses User Ini))
                    
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
                              AND (arsip.judul_file LIKE '%$keyword%' OR arsip.nama_file_fisik LIKE '%$keyword%')
                              GROUP BY arsip.id_arsip 
                              ORDER BY arsip.id_arsip DESC";
                    
                    $hasil = mysqli_query($koneksi, $query);
                    $jumlah_hasil = mysqli_num_rows($hasil);
                ?>
                
                <div class="d-flex align-items-center mb-3">
                    <h5 class="fw-bold text-secondary m-0">Hasil Pencarian</h5>
                    <span class="badge bg-ocean ms-3 rounded-pill"><?php echo $jumlah_hasil; ?> Ditemukan</span>
                </div>

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-0">
                        <?php if($jumlah_hasil > 0) { ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4 py-3">File Arsip</th>
                                            <th>Lokasi Folder</th>
                                            <th>Akses</th>
                                            <th class="text-end pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($d = mysqli_fetch_array($hasil)){ 
                                            // Icon logic
                                            $icon = "bi-file-earmark-text-fill text-secondary";
                                            if(strpos($d['tipe_file'], 'pdf') !== false) $icon = "bi-file-earmark-pdf-fill text-danger";
                                            elseif(strpos($d['tipe_file'], 'word') !== false) $icon = "bi-file-earmark-word-fill text-primary";
                                            elseif(strpos($d['tipe_file'], 'image') !== false) $icon = "bi-file-earmark-image-fill text-success";
                                            
                                            $link = "../assets/files/" . $d['nama_file_fisik'];
                                        ?>
                                        <tr class="clickable-row" onclick="window.open('<?php echo $link; ?>', '_blank')">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi <?php echo $icon; ?> fs-2 me-3"></i>
                                                    <div>
                                                        <span class="fw-bold text-dark"><?php echo $d['judul_file']; ?></span>
                                                        <br>
                                                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?php echo date('d M Y', strtotime($d['tgl_upload'])); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="bi bi-folder2-open me-1"></i> <?php echo $d['nama_kategori']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if($d['status_akses'] == 'privat') { ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill"><i class="bi bi-lock-fill me-1"></i>Privat (Shared)</span>
                                                <?php } else { ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill"><i class="bi bi-globe me-1"></i>Publik</span>
                                                <?php } ?>
                                            </td>
                                            <td class="text-end pe-4" onclick="event.stopPropagation();">
                                                <a href="<?php echo $link; ?>" target="_blank" class="btn btn-sm btn-ocean rounded-pill px-4 shadow-sm fw-bold">
                                                    <i class="bi bi-download me-2"></i> Unduh
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <div class="empty-state">
                                <i class="bi bi-search"></i>
                                <h5>Oops! File tidak ditemukan.</h5>
                                <p>Coba gunakan kata kunci lain atau periksa ejaan Anda.</p>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <?php } else { ?>
                    <div class="empty-state mt-5">
                        <i class="bi bi-folder-search text-warning"></i>
                        <h4 class="fw-bold text-secondary">Siap Mencari?</h4>
                        <p>Masukkan kata kunci di kolom pencarian untuk menemukan dokumen.</p>
                    </div>
                <?php } ?>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>