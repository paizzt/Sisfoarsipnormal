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
// LOGIKA 1: TAMBAH FOLDER
// -----------------------------------------------------------
if(isset($_POST['btn_tambah'])){
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_folder']);
    $tahun = $_POST['tahun_folder'];
    $status = $_POST['status_folder'];

    $simpan = mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori, tahun, status_folder) VALUES ('$nama', '$tahun', '$status')");
    
    if($simpan){
        $id_baru = mysqli_insert_id($koneksi);
        
        // Simpan Hak Akses Jika Privat
        if($status == 'privat' && isset($_POST['opsi_privat']) && $_POST['opsi_privat'] == 'pilih'){
            $akses = isset($_POST['akses_dosen']) ? $_POST['akses_dosen'] : [];
            foreach($akses as $id_user){
                mysqli_query($koneksi, "INSERT INTO hak_akses_folder (id_kategori, id_user) VALUES ('$id_baru', '$id_user')");
            }
        }
        setNotifikasi("success", "Berhasil!", "Folder baru telah ditambahkan.");
        header("Location: kategori.php"); exit();
    }
}

// -----------------------------------------------------------
// LOGIKA 2: EDIT FOLDER
// -----------------------------------------------------------
if(isset($_POST['btn_edit'])){
    $id = $_POST['id_kategori'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_folder']);
    $tahun = $_POST['tahun_folder'];
    $status = $_POST['status_folder'];

    // Update Data Utama
    mysqli_query($koneksi, "UPDATE kategori SET nama_kategori='$nama', tahun='$tahun', status_folder='$status' WHERE id_kategori='$id'");
    
    // Reset Hak Akses Lama (Hapus semua akses lama folder ini)
    mysqli_query($koneksi, "DELETE FROM hak_akses_folder WHERE id_kategori='$id'");

    // Simpan Hak Akses Baru
    if($status == 'privat' && isset($_POST['opsi_privat_edit']) && $_POST['opsi_privat_edit'] == 'pilih'){
        $akses = isset($_POST['akses_dosen_edit']) ? $_POST['akses_dosen_edit'] : [];
        foreach($akses as $id_user){
            mysqli_query($koneksi, "INSERT INTO hak_akses_folder (id_kategori, id_user) VALUES ('$id', '$id_user')");
        }
    }
    setNotifikasi("success", "Update Sukses!", "Data folder diperbarui.");
    header("Location: kategori.php"); exit();
}

// -----------------------------------------------------------
// LOGIKA 3: HAPUS FOLDER (SOFT DELETE)
// -----------------------------------------------------------
if(isset($_GET['hapus'])){
    $id = $_GET['hapus'];
    // Pindahkan ke sampah (status_hapus = 1)
    mysqli_query($koneksi, "UPDATE kategori SET status_hapus='1' WHERE id_kategori='$id'");
    setNotifikasi("success", "Terhapus!", "Folder dipindahkan ke Sampah.");
    header("Location: kategori.php"); exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - SISFO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
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
        
        .stat-card-folder { border: none; border-radius: 20px; color: white; transition: 0.3s; }
        .stat-card-folder:hover { transform: translateY(-5px); }
        .bg-gradient-1 { background: linear-gradient(135deg, var(--deep-aqua), var(--ocean)); }
        .bg-gradient-2 { background: linear-gradient(135deg, #11998e, #38ef7d); }
        .bg-gradient-3 { background: linear-gradient(135deg, #FF416C, #FF4B2B); }
        
        /* Dark Mode Override */
        body.dark-mode .btn-theme-toggle { background-color: #2c2c2c; border-color: #444; color: #f1c40f; }
        body.dark-mode .user-profile-box:hover { background-color: #2c2c2c; border-color: #444; }
        body.dark-mode .user-name { color: #fff; }
        body.dark-mode .user-role { color: #aaa; }
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
                <li><a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
                <li><a href="upload.php"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Arsip</a></li>
                <li><a href="arsip_semua.php"><i class="bi bi-archive-fill"></i> Data Arsip</a></li>
                <li><a href="kelola_user.php"><i class="bi bi-people-fill"></i> Kelola Pengguna</a></li>
                <li><a href="cari_file.php"><i class="bi bi-search"></i> Cari File</a></li>
                <li class="active"><a href="kategori.php" class="text-warning fw-bold"><i class="bi bi-tags-fill"></i> Kategori / Folder</a></li>
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
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded-4 mb-4 px-4 py-2 sticky-top" style="z-index: 900;">
                <div class="d-flex align-items-center w-100">
                    <button type="button" id="sidebarCollapse" class="btn btn-light rounded-circle me-3 shadow-sm text-ocean">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    <div class="d-none d-md-block">
                        <h5 class="fw-bold mb-0 text-secondary" style="font-size: 1.1rem;">Manajemen Folder</h5>
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
                
                <?php
                $tot_folder = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM kategori WHERE status_hapus='0'"));
                $tot_publik = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM kategori WHERE status_hapus='0' AND status_folder='publik'"));
                $tot_privat = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM kategori WHERE status_hapus='0' AND status_folder='privat'"));
                ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card-folder bg-gradient-1 p-3 shadow-sm h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 text-white-50">Total Folder</h6>
                                    <h2 class="fw-bold mb-0"><?php echo $tot_folder; ?></h2>
                                </div>
                                <i class="bi bi-folder2-open fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card-folder bg-gradient-2 p-3 shadow-sm h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 text-white-50">Folder Publik</h6>
                                    <h2 class="fw-bold mb-0"><?php echo $tot_publik; ?></h2>
                                </div>
                                <i class="bi bi-globe fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card-folder bg-gradient-3 p-3 shadow-sm h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 text-white-50">Folder Privat</h6>
                                    <h2 class="fw-bold mb-0"><?php echo $tot_privat; ?></h2>
                                </div>
                                <i class="bi bi-lock-fill fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-secondary m-0"><i class="bi bi-table me-2"></i>Daftar Kategori</h5>
                    <button type="button" class="btn btn-ocean fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Folder
                    </button>
                </div>

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 py-3">Nama Folder</th>
                                        <th>Tahun</th>
                                        <th>Status Akses</th>
                                        <th>Isi File</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = mysqli_query($koneksi, "SELECT * FROM kategori WHERE status_hapus='0' ORDER BY id_kategori DESC");
                                    if(mysqli_num_rows($query) > 0){
                                        while($d = mysqli_fetch_array($query)){
                                            // Hitung jumlah file
                                            $jml_file = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_arsip FROM arsip WHERE id_kategori='".$d['id_kategori']."' AND status_hapus='0'"));
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-folder-fill text-warning fs-4 me-3"></i>
                                                <span class="fw-bold text-dark"><?php echo $d['nama_kategori']; ?></span>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $d['tahun']; ?></span></td>
                                        <td>
                                            <?php if($d['status_folder'] == 'privat') { ?>
                                                <span class="badge bg-danger rounded-pill"><i class="bi bi-lock-fill me-1"></i> Privat</span>
                                            <?php } else { ?>
                                                <span class="badge bg-success rounded-pill"><i class="bi bi-globe me-1"></i> Publik</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <span class="text-muted small"><i class="bi bi-file-earmark-text me-1"></i><?php echo $jml_file; ?> File</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" 
                                                    onclick="isiModalEdit('<?php echo $d['id_kategori']; ?>', '<?php echo $d['nama_kategori']; ?>', '<?php echo $d['tahun']; ?>', '<?php echo $d['status_folder']; ?>')" 
                                                    data-bs-toggle="modal" data-bs-target="#modalEdit">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger rounded-circle" 
                                                    onclick="konfirmasiHapus('kategori.php?hapus=<?php echo $d['id_kategori']; ?>')" 
                                                    title="Hapus ke Sampah">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php } } else { ?>
                                        <tr><td colspan="5" class="text-center py-5 text-muted fst-italic">Belum ada data folder.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-deep-aqua text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-folder-plus me-2"></i>Folder Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">NAMA FOLDER</label>
                            <input type="text" name="nama_folder" class="form-control bg-light" placeholder="Contoh: Skripsi" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">TAHUN</label>
                            <select name="tahun_folder" class="form-select bg-light" required>
                                <?php
                                $currYear = date('Y');
                                for($y=$currYear; $y>=$currYear-5; $y--) echo "<option value='$y'>$y</option>";
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">STATUS AKSES</label>
                            <div class="switch-field w-100 shadow-sm">
                                <input type="radio" id="add_pub" name="status_folder" value="publik" checked onclick="toggleDosenAdd(false)"/>
                                <label for="add_pub"><i class="bi bi-globe me-1"></i> Publik</label>
                                <input type="radio" id="add_priv" name="status_folder" value="privat" onclick="toggleDosenAdd(true)"/>
                                <label for="add_priv"><i class="bi bi-lock-fill me-1"></i> Privat</label>
                            </div>
                        </div>

                        <div id="containerPrivatAdd" class="mb-3 p-3 bg-light border rounded" style="display: none;">
                            <label class="form-label small fw-bold text-danger">Pengaturan Privat:</label>
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="opsi_privat" id="opt_no_add" value="tidak_ada" checked onclick="toggleListAdd(false)">
                                    <label class="form-check-label small" for="opt_no_add">Hanya Admin</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="opsi_privat" id="opt_yes_add" value="pilih" onclick="toggleListAdd(true)">
                                    <label class="form-check-label small" for="opt_yes_add">Pilih Dosen Tertentu</label>
                                </div>
                            </div>
                            <div id="listDosenAdd" class="card mt-2" style="display: none; max-height: 150px; overflow-y: auto;">
                                <div class="card-body p-2">
                                    <?php
                                    $dosen = mysqli_query($koneksi, "SELECT * FROM users WHERE role='dosen' ORDER BY nama_lengkap ASC");
                                    while($d = mysqli_fetch_array($dosen)){
                                        echo '<div class="form-check border-bottom py-1">
                                                <input class="form-check-input" type="checkbox" name="akses_dosen[]" value="'.$d['id_user'].'">
                                                <label class="form-check-label small">'.$d['nama_lengkap'].'</label>
                                              </div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_tambah" class="btn btn-ocean fw-bold px-4 rounded-pill">Simpan Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title text-dark fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_kategori" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">NAMA FOLDER</label>
                            <input type="text" name="nama_folder" id="edit_nama" class="form-control bg-light" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">TAHUN</label>
                            <select name="tahun_folder" id="edit_tahun" class="form-select bg-light" required>
                                <?php
                                $currYear = date('Y');
                                for($y=$currYear; $y>=$currYear-5; $y--) echo "<option value='$y'>$y</option>";
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">STATUS AKSES</label>
                            <div class="switch-field w-100 shadow-sm">
                                <input type="radio" id="edit_pub" name="status_folder" value="publik" onclick="toggleDosenEdit(false)"/>
                                <label for="edit_pub"><i class="bi bi-globe me-1"></i> Publik</label>
                                <input type="radio" id="edit_priv" name="status_folder" value="privat" onclick="toggleDosenEdit(true)"/>
                                <label for="edit_priv"><i class="bi bi-lock-fill me-1"></i> Privat</label>
                            </div>
                        </div>

                        <div id="containerPrivatEdit" class="mb-3 p-3 bg-light border rounded" style="display: none;">
                            <label class="form-label small fw-bold text-danger">Akses Dosen (Reset):</label>
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="opsi_privat_edit" id="opt_no_edit" value="tidak_ada" checked onclick="toggleListEdit(false)">
                                    <label class="form-check-label small" for="opt_no_edit">Hanya Admin</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="opsi_privat_edit" id="opt_yes_edit" value="pilih" onclick="toggleListEdit(true)">
                                    <label class="form-check-label small" for="opt_yes_edit">Pilih Dosen Tertentu</label>
                                </div>
                            </div>
                            <div id="listDosenEdit" class="card mt-2" style="display: none; max-height: 150px; overflow-y: auto;">
                                <div class="card-body p-2">
                                    <?php
                                    // Reset pointer query
                                    $dosen2 = mysqli_query($koneksi, "SELECT * FROM users WHERE role='dosen' ORDER BY nama_lengkap ASC");
                                    while($d2 = mysqli_fetch_array($dosen2)){
                                        echo '<div class="form-check border-bottom py-1">
                                                <input class="form-check-input" type="checkbox" name="akses_dosen_edit[]" value="'.$d2['id_user'].'">
                                                <label class="form-check-label small">'.$d2['nama_lengkap'].'</label>
                                              </div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_edit" class="btn btn-warning fw-bold px-4 rounded-pill">Update Data</button>
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

        function konfirmasiHapus(url) {
            Swal.fire({
                title: 'Hapus Folder?',
                text: "Folder akan dipindahkan ke Sampah.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus'
            }).then((result) => { if (result.isConfirmed) window.location.href = url; });
        }

        // AUTO-FILL MODAL EDIT
        function isiModalEdit(id, nama, tahun, status){
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_tahun').value = tahun;

            if(status == 'publik'){
                document.getElementById('edit_pub').checked = true;
                toggleDosenEdit(false);
            } else {
                document.getElementById('edit_priv').checked = true;
                toggleDosenEdit(true);
            }
        }

        // TOGGLE LOGIC (TAMBAH)
        function toggleDosenAdd(isPrivate) { document.getElementById('containerPrivatAdd').style.display = isPrivate ? 'block' : 'none'; }
        function toggleListAdd(show) { document.getElementById('listDosenAdd').style.display = show ? 'block' : 'none'; }

        // TOGGLE LOGIC (EDIT)
        function toggleDosenEdit(isPrivate) { document.getElementById('containerPrivatEdit').style.display = isPrivate ? 'block' : 'none'; }
        function toggleListEdit(show) { document.getElementById('listDosenEdit').style.display = show ? 'block' : 'none'; }
    </script>
</body>
</html>