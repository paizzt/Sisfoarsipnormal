<?php
session_start();
if($_SESSION['status'] != "login" || $_SESSION['role'] != 'admin'){
    header("location:../login.php?pesan=belum_login");
}
include '../config/koneksi.php';

// AMBIL DATA USER LOGIN (Untuk Profile Header)
$id_user_login = $_SESSION['id_user'];
$q_profil = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user_login'");
$d_profil = mysqli_fetch_array($q_profil);

// FUNGSI NOTIFIKASI
function setNotifikasi($icon, $title, $text){
    $_SESSION['swal_icon'] = $icon;
    $_SESSION['swal_title'] = $title;
    $_SESSION['swal_text'] = $text;
}

// -----------------------------------------------------------
// LOGIKA RESTORE & DELETE PERMANEN
// -----------------------------------------------------------

// 1. PULIHKAN FILE
if(isset($_GET['restore_file'])){
    $id = $_GET['restore_file'];
    mysqli_query($koneksi, "UPDATE arsip SET status_hapus='0' WHERE id_arsip='$id'");
    setNotifikasi("success", "Dipulihkan!", "File dikembalikan ke Data Arsip.");
    header("Location: sampah.php"); exit();
}

// 2. HAPUS PERMANEN FILE
if(isset($_GET['force_del_file'])){
    $id = $_GET['force_del_file'];
    // Ambil info file fisik
    $cek = mysqli_query($koneksi, "SELECT nama_file_fisik FROM arsip WHERE id_arsip='$id'");
    $data = mysqli_fetch_assoc($cek);
    
    // Hapus Fisik & Database
    if($data){
        $path = "../assets/files/" . $data['nama_file_fisik'];
        if(file_exists($path)) unlink($path);
        mysqli_query($koneksi, "DELETE FROM arsip WHERE id_arsip='$id'");
        setNotifikasi("success", "Dihapus Permanen", "File telah dimusnahkan.");
    }
    header("Location: sampah.php"); exit();
}

// 3. PULIHKAN FOLDER
if(isset($_GET['restore_folder'])){
    $id = $_GET['restore_folder'];
    mysqli_query($koneksi, "UPDATE kategori SET status_hapus='0' WHERE id_kategori='$id'");
    setNotifikasi("success", "Dipulihkan!", "Folder dikembalikan.");
    header("Location: sampah.php"); exit();
}

// 4. HAPUS PERMANEN FOLDER
if(isset($_GET['force_del_folder'])){
    $id = $_GET['force_del_folder'];
    // Hapus Folder di DB (Asumsi file di dalamnya sudah dihapus atau dipindah)
    mysqli_query($koneksi, "DELETE FROM kategori WHERE id_kategori='$id'");
    setNotifikasi("success", "Dihapus Permanen", "Folder telah dimusnahkan.");
    header("Location: sampah.php"); exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycle Bin - SISFO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
        
        /* Styles Khusus Halaman Sampah */
        .nav-tabs .nav-link { color: #6c757d; font-weight: 600; border: none; border-bottom: 3px solid transparent; transition: 0.3s; }
        .nav-tabs .nav-link:hover { color: var(--ocean); }
        .nav-tabs .nav-link.active { color: var(--ocean); border-bottom: 3px solid var(--ocean); background: transparent; }
        .empty-trash { padding: 60px 20px; text-align: center; color: #adb5bd; }
        .empty-trash i { font-size: 4rem; opacity: 0.5; margin-bottom: 15px; display: block; }
        
        /* Dark Mode Override */
        body.dark-mode .btn-theme-toggle { background-color: #2c2c2c; border-color: #444; color: #f1c40f; }
        body.dark-mode .user-profile-box:hover { background-color: #2c2c2c; border-color: #444; }
        body.dark-mode .user-name { color: #fff; }
        body.dark-mode .user-role { color: #aaa; }
        body.dark-mode .nav-tabs .nav-link { color: #aaa; }
        body.dark-mode .nav-tabs .nav-link.active { color: #fff; border-bottom-color: #fff; }
    </style>
</head>
<body>

    <div id="loader"><div class="spinner-border text-info" style="width: 3rem; height: 3rem;" role="status"></div></div>
    
    <div class="wrapper">
        <nav id="sidebar" class="d-flex flex-column">
            <div class="sidebar-header">
                <img src="../assets/img/logo.png" alt="Logo" class="img-fluid mb-2" style="max-height: 50px;">
                <h5 class="fw-bold mb-0">SISFO ARSIP</h5>
                <small class="opacity-75">Admin Panel v2.0</small>
            </div>
            <ul class="list-unstyled components flex-grow-1">
                <li><a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
                <li><a href="upload.php"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Arsip</a></li>
                <li><a href="arsip_semua.php"><i class="bi bi-archive-fill"></i> Data Arsip</a></li>
                <li><a href="kelola_user.php"><i class="bi bi-people-fill"></i> Kelola Pengguna</a></li>
                <li><a href="cari_file.php"><i class="bi bi-search"></i> Cari File</a></li>
                <li><a href="kategori.php"><i class="bi bi-tags-fill"></i> Kategori / Folder</a></li>
                <li class="active"><a href="sampah.php" class="text-warning fw-bold"><i class="bi bi-trash-fill"></i> Sampah</a></li>
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
                        <h5 class="fw-bold mb-0 text-secondary" style="font-size: 1.1rem;">Recycle Bin</h5>
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
                                    <span class="user-role"><?php echo ucfirst($d_profil['role']); ?></span>
                                </div>
                                <img src="https://ui-avatars.com/api/?name=<?php echo $d_profil['nama_lengkap']; ?>&background=07575B&color=fff&size=128&bold=true" class="user-avatar">
                            </div>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom border-0 shadow-lg mt-2" style="border-radius: 15px;">
                                <li class="px-3 py-2 border-bottom mb-2">
                                    <small class="text-muted fw-bold" style="font-size: 0.7rem;">AKUN SAYA</small>
                                    <div class="fw-bold text-dark"><?php echo $d_profil['email']; ?></div>
                                </li>
                                <li><a class="dropdown-item dropdown-item-custom" href="dashboard.php"><i class="bi bi-person-gear"></i> Ke Dashboard</a></li>
                                <li><a class="dropdown-item dropdown-item-custom text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="fw-bold text-danger mb-1"><i class="bi bi-trash3 me-2"></i>Item Terhapus</h4>
                                <p class="text-muted small mb-0">Item di sini akan dihapus permanen secara manual.</p>
                            </div>
                        </div>
                        
                        <ul class="nav nav-tabs mt-4" id="trashTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button"><i class="bi bi-file-earmark me-2"></i>File Terhapus</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="folders-tab" data-bs-toggle="tab" data-bs-target="#folders" type="button"><i class="bi bi-folder me-2"></i>Folder Terhapus</button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-0">
                        <div class="tab-content" id="trashTabContent">
                            
                            <div class="tab-pane fade show active" id="files" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4 py-3">Nama File</th>
                                                <th>Asal Folder</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $q_file = mysqli_query($koneksi, "SELECT arsip.*, kategori.nama_kategori FROM arsip JOIN kategori ON arsip.id_kategori = kategori.id_kategori WHERE arsip.status_hapus='1' ORDER BY id_arsip DESC");
                                            if(mysqli_num_rows($q_file) > 0){
                                                while($df = mysqli_fetch_array($q_file)){
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-x text-danger fs-3 me-3"></i>
                                                        <div>
                                                            <span class="fw-bold text-dark text-decoration-line-through"><?php echo $df['judul_file']; ?></span>
                                                            <br>
                                                            <small class="text-muted">ID: #<?php echo $df['id_arsip']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-light text-secondary border"><?php echo $df['nama_kategori']; ?></span></td>
                                                <td class="text-center">
                                                    <a href="sampah.php?restore_file=<?php echo $df['id_arsip']; ?>" class="btn btn-sm btn-success rounded-pill px-3 me-1" title="Pulihkan">
                                                        <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger rounded-circle" onclick="konfirmasiHapusPermanen('sampah.php?force_del_file=<?php echo $df['id_arsip']; ?>')" title="Hapus Permanen">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php } } else { ?>
                                                <tr><td colspan="3"><div class="empty-trash"><i class="bi bi-recycle"></i><h6>Sampah File Kosong</h6></div></td></tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="folders" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4 py-3">Nama Folder</th>
                                                <th>Tahun</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $q_fold = mysqli_query($koneksi, "SELECT * FROM kategori WHERE status_hapus='1' ORDER BY id_kategori DESC");
                                            if(mysqli_num_rows($q_fold) > 0){
                                                while($dk = mysqli_fetch_array($q_fold)){
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-folder-x text-danger fs-3 me-3"></i>
                                                        <span class="fw-bold text-dark text-decoration-line-through"><?php echo $dk['nama_kategori']; ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo $dk['tahun']; ?></td>
                                                <td class="text-center">
                                                    <a href="sampah.php?restore_folder=<?php echo $dk['id_kategori']; ?>" class="btn btn-sm btn-success rounded-pill px-3 me-1" title="Pulihkan">
                                                        <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger rounded-circle" onclick="konfirmasiHapusPermanen('sampah.php?force_del_folder=<?php echo $dk['id_kategori']; ?>')" title="Hapus Permanen">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php } } else { ?>
                                                <tr><td colspan="3"><div class="empty-trash"><i class="bi bi-recycle"></i><h6>Sampah Folder Kosong</h6></div></td></tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    
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

        function konfirmasiHapusPermanen(url) {
            Swal.fire({
                title: 'Hapus Permanen?',
                text: "Data akan HILANG SELAMANYA dan tidak bisa kembali!",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Musnahkan!',
                cancelButtonText: 'Batal'
            }).then((result) => { if (result.isConfirmed) window.location.href = url; });
        }
    </script>
</body>
</html>