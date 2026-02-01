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

// FILTER LOGIC (Untuk Query File di Bawah)
$where_tambahan = "";
if(isset($_GET['tahun']) && $_GET['tahun'] != ""){
    $thn = mysqli_real_escape_string($koneksi, $_GET['tahun']);
    $where_tambahan .= " AND YEAR(arsip.tgl_upload) = '$thn' ";
}
if(isset($_GET['kategori']) && $_GET['kategori'] != ""){
    $kat = mysqli_real_escape_string($koneksi, $_GET['kategori']);
    $where_tambahan .= " AND arsip.id_kategori = '$kat' ";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Arsip Dosen - SISFO</title>
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
        
        /* Folder Card Style */
        .folder-card { cursor: pointer; border: 1px solid #eef1f6; background: #fff; transition: 0.3s; position: relative; overflow: hidden; }
        .folder-card:hover { border-color: var(--ocean); background: #f8fbff; transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .folder-card.active-folder { border-color: var(--deep-aqua); background-color: #e0f7fa; }
        
        .clickable-row { cursor: pointer; transition: 0.2s; }
        .clickable-row:hover { background-color: #f1f8ff !important; transform: scale(1.002); }
        
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
                <li class="active"><a href="arsip_dosen.php"><i class="bi bi-archive-fill"></i> Data Arsip</a></li>
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
                        <h5 class="fw-bold mb-0 text-secondary" style="font-size: 1.1rem;">Arsip Digital</h5>
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
                
                <div class="card shadow-sm border-0 mb-4 rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-funnel me-2"></i>Filter Pencarian</h6>
                        <form action="" method="GET" class="row g-3 align-items-end">
                             <div class="col-md-3">
                                <label class="form-label fw-bold text-secondary small">Tahun Arsip</label>
                                <select name="tahun" class="form-select border-0 bg-light shadow-sm">
                                    <option value="">Semua Tahun</option>
                                    <?php
                                    $qry_thn = mysqli_query($koneksi, "SELECT DISTINCT YEAR(tgl_upload) as tahun FROM arsip ORDER BY tahun DESC");
                                    while($t = mysqli_fetch_array($qry_thn)){
                                        $sel = (isset($_GET['tahun']) && $_GET['tahun'] == $t['tahun']) ? 'selected' : '';
                                        echo "<option value='".$t['tahun']."' $sel>".$t['tahun']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary small">Folder Kategori</label>
                                <select name="kategori" class="form-select border-0 bg-light shadow-sm">
                                    <option value="">Semua Folder</option>
                                    <?php
                                    $qry_kat = mysqli_query($koneksi, "SELECT DISTINCT kategori.* FROM kategori 
                                                        LEFT JOIN hak_akses_folder ON kategori.id_kategori = hak_akses_folder.id_kategori 
                                                        WHERE kategori.status_hapus='0' 
                                                        AND (
                                                            kategori.status_folder = 'publik' 
                                                            OR 
                                                            (kategori.status_folder = 'privat' AND hak_akses_folder.id_user = '$id_user')
                                                        )
                                                        ORDER BY nama_kategori ASC");
                                    while($k = mysqli_fetch_array($qry_kat)){
                                        $sel = (isset($_GET['kategori']) && $_GET['kategori'] == $k['id_kategori']) ? 'selected' : '';
                                        echo "<option value='".$k['id_kategori']."' $sel>".$k['nama_kategori']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                  <button type="submit" class="btn btn-ocean fw-bold shadow-sm w-100 rounded-pill"><i class="bi bi-search"></i> Terapkan</button>
                            </div>
                            <div class="col-md-1">
                                  <a href="arsip_dosen.php" class="btn btn-outline-secondary w-100 rounded-circle" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-3 justify-content-between">
                    <h5 class="fw-bold text-secondary m-0"><i class="bi bi-collection me-2 text-warning"></i>Kategori Folder</h5>
                    <?php if(isset($_GET['kategori'])) { ?>
                        <a href="arsip_dosen.php" class="btn btn-sm btn-outline-danger rounded-pill"><i class="bi bi-x-circle me-1"></i> Reset Pilihan</a>
                    <?php } ?>
                </div>

                <div class="row g-3 mb-4">
                    <?php
                    // QUERY FOLDER
                    $q_folder = mysqli_query($koneksi, "SELECT DISTINCT kategori.* FROM kategori 
                                                        LEFT JOIN hak_akses_folder ON kategori.id_kategori = hak_akses_folder.id_kategori 
                                                        WHERE kategori.status_hapus='0' 
                                                        AND (
                                                            kategori.status_folder = 'publik' 
                                                            OR 
                                                            (kategori.status_folder = 'privat' AND hak_akses_folder.id_user = '$id_user')
                                                        )
                                                        ORDER BY kategori.id_kategori DESC");
                    
                    if(mysqli_num_rows($q_folder) > 0){
                        while($f = mysqli_fetch_array($q_folder)){
                            $is_active = (isset($_GET['kategori']) && $_GET['kategori'] == $f['id_kategori']) ? 'active-folder shadow' : 'shadow-sm';
                            $status_icon = ($f['status_folder'] == 'privat') ? '<i class="bi bi-lock-fill text-danger" title="Akses Privat"></i>' : '<i class="bi bi-globe text-success" title="Publik"></i>';
                            $jml = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_arsip FROM arsip WHERE id_kategori='".$f['id_kategori']."' AND status_hapus='0'"));
                    ?>
                    <div class="col-xl-3 col-md-4 col-sm-6">
                        <div class="card folder-card <?php echo $is_active; ?> h-100 rounded-4" onclick="window.location.href='arsip_dosen.php?kategori=<?php echo $f['id_kategori']; ?>'">
                            <div class="card-body p-3 d-flex align-items-center">
                                <i class="bi bi-folder-fill text-warning fs-1 me-3"></i>
                                <div class="w-100 overflow-hidden">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="fw-bold mb-1 text-truncate text-dark"><?php echo $f['nama_kategori']; ?></h6>
                                        <small><?php echo $status_icon; ?></small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-light text-secondary border"><?php echo $f['tahun']; ?></span>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?php echo $jml; ?> File</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } } else { ?>
                        <div class="col-12"><div class="alert alert-secondary text-center rounded-pill">Belum ada folder yang tersedia untuk Anda.</div></div>
                    <?php } ?>
                </div>

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-0">
                         <h5 class="fw-bold text-secondary m-0"><i class="bi bi-files me-2 text-info"></i>Daftar Dokumen</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 py-3">Nama File</th>
                                        <th>Folder</th>
                                        <th>Tahun</th>
                                        <th>Status Akses</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // LOGIKA QUERY FILE:
                                    // Publik + Privat(allowed) + Filter Tambahan
                                    $query = "SELECT arsip.*, kategori.nama_kategori, kategori.tahun 
                                              FROM arsip 
                                              JOIN kategori ON arsip.id_kategori = kategori.id_kategori
                                              LEFT JOIN hak_akses ON arsip.id_arsip = hak_akses.id_arsip 
                                              WHERE arsip.status_hapus='0' 
                                              AND (
                                                  arsip.status_akses = 'publik' 
                                                  OR 
                                                  (arsip.status_akses = 'privat' AND hak_akses.id_user = '$id_user')
                                              )
                                              $where_tambahan
                                              GROUP BY arsip.id_arsip 
                                              ORDER BY arsip.id_arsip DESC";
                                    
                                    $result = mysqli_query($koneksi, $query);
                                    
                                    if(mysqli_num_rows($result) > 0){
                                        while($d = mysqli_fetch_array($result)){
                                            $icon_f = "bi-file-earmark-text-fill text-secondary";
                                            if(strpos($d['tipe_file'], 'pdf') !== false) $icon_f = "bi-file-earmark-pdf-fill text-danger";
                                            elseif(strpos($d['tipe_file'], 'word') !== false) $icon_f = "bi-file-earmark-word-fill text-primary";
                                            elseif(strpos($d['tipe_file'], 'image') !== false) $icon_f = "bi-file-earmark-image-fill text-success";

                                            $link_file = "../assets/files/" . $d['nama_file_fisik'];
                                    ?>
                                    <tr class="clickable-row" onclick="window.open('<?php echo $link_file; ?>', '_blank')">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <i class="bi <?php echo $icon_f; ?> fs-3 me-3"></i>
                                                <div>
                                                    <span class="fw-bold text-dark"><?php echo $d['judul_file']; ?></span>
                                                    <br>
                                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?php echo date('d M Y', strtotime($d['tgl_upload'])); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><i class="bi bi-folder2-open me-1"></i><?php echo $d['nama_kategori']; ?></span></td>
                                        <td><?php echo $d['tahun']; ?></td>
                                        <td>
                                            <?php if($d['status_akses'] == 'privat') { ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill"><i class="bi bi-lock-fill me-1"></i>Privat (Shared)</span>
                                            <?php } else { ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill"><i class="bi bi-globe me-1"></i>Publik</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-end pe-4" onclick="event.stopPropagation();">
                                            <a href="<?php echo $link_file; ?>" target="_blank" class="btn btn-sm btn-ocean rounded-pill px-4 shadow-sm fw-bold">
                                                <i class="bi bi-download me-2"></i> Unduh
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } } else { ?>
                                        <tr><td colspan="5" class="text-center py-5 text-muted fst-italic">Tidak ada dokumen yang ditemukan sesuai filter.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>