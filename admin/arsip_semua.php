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
// LOGIKA 1: DOWNLOAD ZIP
// -----------------------------------------------------------
if(isset($_GET['download_folder'])){
    $id_kat = $_GET['download_folder'];
    $q_kat = mysqli_query($koneksi, "SELECT nama_kategori FROM kategori WHERE id_kategori='$id_kat'");
    $d_kat = mysqli_fetch_array($q_kat);
    $nama_zip = "Arsip_" . str_replace(" ", "_", $d_kat['nama_kategori']) . ".zip";

    $q_file = mysqli_query($koneksi, "SELECT nama_file_fisik, judul_file FROM arsip WHERE id_kategori='$id_kat' AND status_hapus='0'");
    
    if(mysqli_num_rows($q_file) > 0){
        $zip = new ZipArchive();
        $tmp_file = tempnam(sys_get_temp_dir(), 'zip');
        if ($zip->open($tmp_file, ZipArchive::CREATE) === TRUE) {
            while($f = mysqli_fetch_array($q_file)){
                $path = "../assets/files/" . $f['nama_file_fisik'];
                if(file_exists($path)){
                    $ext = pathinfo($f['nama_file_fisik'], PATHINFO_EXTENSION);
                    $zip->addFile($path, $f['judul_file'] . "." . $ext);
                }
            }
            $zip->close();
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename='.$nama_zip);
            header('Content-Length: ' . filesize($tmp_file));
            readfile($tmp_file);
            unlink($tmp_file);
            exit;
        }
    } else {
        setNotifikasi("info", "Folder Kosong", "Tidak ada file aktif untuk didownload.");
        header("Location: arsip_semua.php"); exit();
    }
}

// -----------------------------------------------------------
// LOGIKA 2: CRUD FOLDER
// -----------------------------------------------------------

// BUAT FOLDER BARU
if(isset($_POST['btn_buat_folder'])){
    $nama_folder = mysqli_real_escape_string($koneksi, $_POST['nama_folder_baru']);
    $status_folder = $_POST['status_folder'];
    $tahun_folder = $_POST['tahun_folder']; 
    
    $simpan = mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori, status_folder, tahun) VALUES ('$nama_folder', '$status_folder', '$tahun_folder')");
    
    if($simpan){
        $id_kat_baru = mysqli_insert_id($koneksi);
        if($status_folder == 'privat' && isset($_POST['opsi_privat_folder']) && $_POST['opsi_privat_folder'] == 'pilih'){
             $dosen_terpilih = isset($_POST['akses_folder_dosen']) ? $_POST['akses_folder_dosen'] : [];
             foreach($dosen_terpilih as $id_user){
                 mysqli_query($koneksi, "INSERT INTO hak_akses_folder (id_kategori, id_user) VALUES ('$id_kat_baru', '$id_user')");
             }
        }
        setNotifikasi("success", "Berhasil!", "Folder $nama_folder dibuat.");
        header("Location: arsip_semua.php"); exit();
    }
}

// EDIT FOLDER
if(isset($_POST['btn_edit_folder'])){
    $id = $_POST['id_kategori'];
    $nama = $_POST['nama_folder'];
    $status = $_POST['status_folder'];
    
    mysqli_query($koneksi, "UPDATE kategori SET nama_kategori='$nama', status_folder='$status' WHERE id_kategori='$id'");
    mysqli_query($koneksi, "DELETE FROM hak_akses_folder WHERE id_kategori='$id'");

    if($status == 'privat' && isset($_POST['opsi_privat_folder_edit']) && $_POST['opsi_privat_folder_edit'] == 'pilih'){
        $dosen_terpilih = isset($_POST['akses_folder_dosen_edit']) ? $_POST['akses_folder_dosen_edit'] : [];
        foreach($dosen_terpilih as $id_user){
            mysqli_query($koneksi, "INSERT INTO hak_akses_folder (id_kategori, id_user) VALUES ('$id', '$id_user')");
        }
    }
    setNotifikasi("success", "Update Berhasil!", "Folder diperbarui.");
    header("Location: arsip_semua.php"); exit();
}

// HAPUS FOLDER (SOFT DELETE)
if(isset($_GET['hapus_folder'])){
    $id = $_GET['hapus_folder'];
    mysqli_query($koneksi, "UPDATE kategori SET status_hapus='1' WHERE id_kategori='$id'");
    setNotifikasi("success", "Terhapus!", "Folder dipindahkan ke Sampah.");
    header("Location: arsip_semua.php"); exit();
}

// -----------------------------------------------------------
// LOGIKA 3: CRUD FILE
// -----------------------------------------------------------

// EDIT FILE
if(isset($_POST['btn_edit_file'])){
    $id_arsip = $_POST['id_arsip'];
    $judul_baru = $_POST['judul_file_baru'];
    $jenis_baru = $_POST['jenis_surat_baru'];
    
    mysqli_query($koneksi, "UPDATE arsip SET judul_file='$judul_baru', jenis_surat='$jenis_baru' WHERE id_arsip='$id_arsip'");
    setNotifikasi("success", "Berhasil", "Data file diperbarui.");
    header("Location: " . $_SERVER['HTTP_REFERER']); exit();
}

// HAPUS FILE (SOFT DELETE)
if(isset($_GET['hapus_file'])){
    $id_f = $_GET['hapus_file'];
    mysqli_query($koneksi, "UPDATE arsip SET status_hapus='1' WHERE id_arsip='$id_f'");
    setNotifikasi("success", "Terhapus!", "File dipindahkan ke Sampah.");
    header("Location: " . $_SERVER['HTTP_REFERER']); exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Arsip - SISFO</title>
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
        
        /* Table & Card Style */
        .folder-card { cursor: pointer; border: 1px solid #eef1f6; background: #fff; transition: 0.3s; }
        .folder-card:hover { border-color: var(--ocean); background: #f8fbff; transform: translateY(-3px); }
        .action-hover { opacity: 0; transition: 0.2s; }
        .folder-card:hover .action-hover { opacity: 1; }
        .clickable-row { cursor: pointer; transition: background 0.2s; }
        .clickable-row:hover { background-color: #f1f8ff !important; }
        
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
                <small class="opacity-75">Admin Panel v2.0</small>
            </div>
            <ul class="list-unstyled components flex-grow-1">
                <li><a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
                <li><a href="upload.php"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Arsip</a></li>
                <li class="active"><a href="arsip_semua.php"><i class="bi bi-archive-fill"></i> Data Arsip</a></li>
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
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded-4 mb-4 px-4 py-2 sticky-top" style="z-index: 900;">
                <div class="d-flex align-items-center w-100">
                    <button type="button" id="sidebarCollapse" class="btn btn-light rounded-circle me-3 shadow-sm text-ocean">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    <div class="d-none d-md-block">
                        <h5 class="fw-bold mb-0 text-secondary" style="font-size: 1.1rem;">Data Arsip</h5>
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
                
                <div class="card shadow-sm border-0 mb-4 rounded-4">
                    <div class="card-body p-4">
                        <form action="" method="GET" class="row g-3 align-items-end">
                             <?php if(isset($_GET['folder_id'])): ?>
                                <input type="hidden" name="folder_id" value="<?php echo $_GET['folder_id']; ?>">
                             <?php endif; ?>

                             <div class="col-md-3">
                                <label class="form-label fw-bold text-secondary small"><i class="bi bi-calendar-event me-1"></i> Filter Tahun</label>
                                <select name="tahun" class="form-select border-0 bg-light shadow-sm">
                                    <option value="">Semua Tahun</option>
                                    <?php
                                    $qry_thn = mysqli_query($koneksi, "SELECT DISTINCT YEAR(tgl_upload) as tahun FROM arsip ORDER BY tahun DESC");
                                    while($t = mysqli_fetch_array($qry_thn)){
                                        $selected = (isset($_GET['tahun']) && $_GET['tahun'] == $t['tahun']) ? 'selected' : '';
                                        echo "<option value='".$t['tahun']."' $selected>".$t['tahun']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-secondary small"><i class="bi bi-envelope me-1"></i> Jenis Surat</label>
                                <select name="jenis_surat" class="form-select border-0 bg-light shadow-sm">
                                    <option value="">Semua</option>
                                    <option value="Surat Masuk" <?php if(isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'Surat Masuk') echo 'selected'; ?>>Surat Masuk</option>
                                    <option value="Surat Keluar" <?php if(isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'Surat Keluar') echo 'selected'; ?>>Surat Keluar</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex gap-2">
                                  <button type="submit" class="btn btn-ocean fw-bold shadow-sm px-4 rounded-pill"><i class="bi bi-funnel"></i> Terapkan</button>
                                  <a href="arsip_semua.php" class="btn btn-outline-secondary rounded-circle" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                                  
                                  <button type="button" class="btn btn-warning text-white fw-bold shadow-sm ms-auto rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalBuatFolder">
                                    <i class="bi bi-folder-plus me-1"></i> Folder Baru
                                  </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php 
                if(isset($_GET['folder_id'])){
                    $id_f_aktif = $_GET['folder_id'];
                    $info_f = mysqli_fetch_array(mysqli_query($koneksi, "SELECT * FROM kategori WHERE id_kategori='$id_f_aktif'"));
                ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <a href="arsip_semua.php" class="btn btn-outline-secondary btn-sm mb-2 rounded-pill px-3"><i class="bi bi-arrow-left"></i> Kembali</a>
                            <h3 class="fw-bold text-ocean mb-0">
                                <i class="bi bi-folder2-open me-2"></i><?php echo $info_f['nama_kategori']; ?>
                            </h3>
                            <span class="badge bg-secondary mt-1"><i class="bi bi-calendar"></i> <?php echo $info_f['tahun']; ?></span>
                        </div>
                        <div>
                            <a href="arsip_semua.php?download_folder=<?php echo $id_f_aktif; ?>" class="btn btn-success fw-bold shadow-sm px-4 rounded-pill">
                                <i class="bi bi-file-earmark-zip me-2"></i> Download ZIP
                            </a>
                        </div>
                    </div>
                <?php } else { ?>
                    
                    <div class="d-flex align-items-center mb-3">
                        <h5 class="fw-bold text-secondary m-0"><i class="bi bi-collection me-2 text-warning"></i>Kategori Folder</h5>
                    </div>
                    
                    <div class="row g-3 mb-5">
                        <?php
                        // Ambil folder yang belum dihapus (status_hapus='0')
                        $folder_query = mysqli_query($koneksi, "SELECT * FROM kategori WHERE status_hapus='0' ORDER BY id_kategori DESC");
                        while($f = mysqli_fetch_array($folder_query)){
                            $jml = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_arsip FROM arsip WHERE id_kategori='".$f['id_kategori']."' AND status_hapus='0'"));
                            $lock_icon = ($f['status_folder'] == 'privat') ? '<i class="bi bi-lock-fill text-danger ms-1" title="Privat"></i>' : '';
                        ?>
                        <div class="col-xl-3 col-md-4 col-sm-6">
                            <div class="card folder-card shadow-sm h-100 position-relative rounded-4">
                                <div class="position-absolute top-0 end-0 p-2 action-hover" style="z-index: 10;">
                                    <button class="btn btn-sm btn-light text-primary shadow-sm" onclick="isiModalEditFolder('<?php echo $f['id_kategori']; ?>', '<?php echo $f['nama_kategori']; ?>', '<?php echo $f['status_folder']; ?>')" data-bs-toggle="modal" data-bs-target="#modalEditFolder" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn btn-sm btn-light text-danger shadow-sm" onclick="konfirmasiHapus('arsip_semua.php?hapus_folder=<?php echo $f['id_kategori']; ?>')" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                                </div>
                                
                                <a href="arsip_semua.php?folder_id=<?php echo $f['id_kategori']; ?>" class="text-decoration-none text-dark h-100 p-4 d-flex flex-column justify-content-center align-items-center text-center">
                                    <i class="bi bi-folder-fill display-4 text-warning mb-3"></i>
                                    <h6 class="fw-bold mb-1 text-truncate w-100"><?php echo $f['nama_kategori']; ?> <?php echo $lock_icon; ?></h6>
                                    <span class="badge bg-light text-secondary border border-secondary-subtle rounded-pill">
                                        <?php echo $jml; ?> File &bull; <?php echo $f['tahun']; ?>
                                    </span>
                                </a>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class="d-flex align-items-center mb-3">
                     <h5 class="fw-bold text-secondary m-0"><i class="bi bi-files me-2 text-info"></i>Daftar File Arsip</h5>
                     <small class="text-muted ms-3 fst-italic">*Klik baris untuk membuka file</small>
                </div>
                
                <div class="card shadow-sm border-0 overflow-hidden rounded-4">
                    <div class="card-body p-0">
                         <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 py-3">Nama File</th>
                                        <th>Jenis</th>
                                        <th>Folder</th>
                                        <th>Akses</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $where = " WHERE arsip.status_hapus='0' "; // Hanya tampilkan yang aktif
                                    if(isset($_GET['folder_id'])) { $where .= " AND arsip.id_kategori = '".$_GET['folder_id']."'"; }
                                    if(isset($_GET['tahun']) && $_GET['tahun'] != "") { $where .= " AND YEAR(tgl_upload) = '".$_GET['tahun']."'"; }
                                    if(isset($_GET['jenis_surat']) && $_GET['jenis_surat'] != "") { $where .= " AND jenis_surat = '".$_GET['jenis_surat']."'"; }

                                    $query = "SELECT arsip.*, kategori.nama_kategori FROM arsip JOIN kategori ON arsip.id_kategori = kategori.id_kategori $where ORDER BY id_arsip DESC";
                                    $tampil = mysqli_query($koneksi, $query);
                                    
                                    while($data = mysqli_fetch_array($tampil)){
                                        $badge_cls = ($data['jenis_surat'] == 'Surat Masuk') ? 'bg-info bg-opacity-10 text-info border border-info' : 'bg-warning bg-opacity-10 text-warning border border-warning';
                                        
                                        $icon_f = "bi-file-earmark-text-fill text-secondary";
                                        if(strpos($data['tipe_file'], 'pdf') !== false) $icon_f = "bi-file-earmark-pdf-fill text-danger";
                                        elseif(strpos($data['tipe_file'], 'word') !== false) $icon_f = "bi-file-earmark-word-fill text-primary";
                                        elseif(strpos($data['tipe_file'], 'image') !== false) $icon_f = "bi-file-earmark-image-fill text-success";
                                        
                                        $link_file = "../assets/files/" . $data['nama_file_fisik'];
                                    ?>
                                    <tr class="clickable-row" onclick="window.open('<?php echo $link_file; ?>', '_blank')">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <i class="bi <?php echo $icon_f; ?> fs-3 me-3"></i>
                                                <div>
                                                    <span class="fw-bold text-dark"><?php echo $data['judul_file']; ?></span>
                                                    <br>
                                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?php echo date('d M Y', strtotime($data['tgl_upload'])); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge rounded-pill <?php echo $badge_cls; ?>"><?php echo $data['jenis_surat']; ?></span></td>
                                        <td><span class="badge bg-light text-dark border"><i class="bi bi-folder2-open me-1"></i><?php echo $data['nama_kategori']; ?></span></td>
                                        <td>
                                            <?php if($data['status_akses'] == 'privat') { ?>
                                                <span class="badge bg-danger rounded-pill"><i class="bi bi-lock-fill me-1"></i>Privat</span>
                                            <?php } else { ?>
                                                <span class="badge bg-success rounded-pill"><i class="bi bi-globe me-1"></i>Publik</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-end pe-4" onclick="event.stopPropagation();">
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" 
                                                    onclick="editFile('<?php echo $data['id_arsip']; ?>', '<?php echo $data['judul_file']; ?>', '<?php echo $data['jenis_surat']; ?>')" 
                                                    data-bs-toggle="modal" data-bs-target="#modalEditFile">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger rounded-circle" 
                                                    onclick="konfirmasiHapus('arsip_semua.php?hapus_file=<?php echo $data['id_arsip']; ?>')" 
                                                    title="Hapus">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <?php if(mysqli_num_rows($tampil) == 0){ echo "<tr><td colspan='5' class='text-center py-5 text-muted fst-italic'>Belum ada data arsip.</td></tr>"; } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBuatFolder" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-deep-aqua text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-folder-plus me-2"></i>Buat Folder Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">NAMA FOLDER</label>
                            <input type="text" name="nama_folder_baru" class="form-control form-control-lg bg-light" placeholder="Contoh: Skripsi" required>
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
                                <input type="radio" id="f_publik" name="status_folder" value="publik" checked onclick="toggleFolderDosen(false)"/>
                                <label for="f_publik"><i class="bi bi-globe me-1"></i> Publik</label>
                                <input type="radio" id="f_privat" name="status_folder" value="privat" onclick="toggleFolderDosen(true)"/>
                                <label for="f_privat"><i class="bi bi-lock-fill me-1"></i> Privat</label>
                            </div>
                        </div>
                        <div id="containerFolderPrivat" class="mb-3 p-3 bg-light border rounded" style="display: none;">
                            <label class="form-label small fw-bold text-danger">Pengaturan Privat:</label>
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="opsi_privat_folder" id="opt_tidak_ada" value="tidak_ada" checked onclick="toggleListDosenFolder(false)">
                                    <label class="form-check-label small" for="opt_tidak_ada">Hanya Admin</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="opsi_privat_folder" id="opt_pilih" value="pilih" onclick="toggleListDosenFolder(true)">
                                    <label class="form-check-label small" for="opt_pilih">Pilih Dosen Tertentu</label>
                                </div>
                            </div>
                            <div id="listDosenFolder" class="card mt-2" style="display: none; max-height: 150px; overflow-y: auto;">
                                <div class="card-body p-2">
                                    <?php
                                    $dosen2 = mysqli_query($koneksi, "SELECT * FROM users WHERE role='dosen' ORDER BY nama_lengkap ASC");
                                    while($d2 = mysqli_fetch_array($dosen2)){
                                        echo '<div class="form-check border-bottom py-1">
                                                <input class="form-check-input" type="checkbox" name="akses_folder_dosen[]" value="'.$d2['id_user'].'">
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
                        <button type="submit" name="btn_buat_folder" class="btn btn-ocean fw-bold px-4 rounded-pill">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditFolder" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title text-dark fw-bold"><i class="bi bi-pencil-fill me-2"></i>Edit Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_kategori" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">NAMA FOLDER</label>
                            <input type="text" name="nama_folder" id="edit_nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">STATUS</label>
                            <div class="switch-field w-100 shadow-sm">
                                <input type="radio" id="edit_publik" name="status_folder" value="publik" onclick="toggleFolderDosenEdit(false)"/>
                                <label for="edit_publik">Publik</label>
                                <input type="radio" id="edit_privat" name="status_folder" value="privat" onclick="toggleFolderDosenEdit(true)"/>
                                <label for="edit_privat">Privat</label>
                            </div>
                        </div>
                        <div id="containerFolderPrivatEdit" class="mb-3 p-3 bg-light border rounded" style="display: none;">
                            <label class="form-label small fw-bold text-danger">Akses Dosen (Reset):</label>
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="opsi_privat_folder_edit" id="opt_tidak_ada_edit" value="tidak_ada" checked onclick="toggleListDosenFolderEdit(false)">
                                    <label class="form-check-label small" for="opt_tidak_ada_edit">Hanya Admin</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="opsi_privat_folder_edit" id="opt_pilih_edit" value="pilih" onclick="toggleListDosenFolderEdit(true)">
                                    <label class="form-check-label small" for="opt_pilih_edit">Pilih Dosen Tertentu</label>
                                </div>
                            </div>
                            <div id="listDosenFolderEdit" class="card mt-2" style="display: none; max-height: 150px; overflow-y: auto;">
                                <div class="card-body p-2">
                                    <?php
                                    $dosen_edit = mysqli_query($koneksi, "SELECT * FROM users WHERE role='dosen' ORDER BY nama_lengkap ASC");
                                    while($de = mysqli_fetch_array($dosen_edit)){
                                        echo '<div class="form-check border-bottom py-1">
                                                <input class="form-check-input" type="checkbox" name="akses_folder_dosen_edit[]" value="'.$de['id_user'].'">
                                                <label class="form-check-label small">'.$de['nama_lengkap'].'</label>
                                              </div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_edit_folder" class="btn btn-warning w-100 rounded-pill fw-bold">Update Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditFile" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-ocean text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Edit Data File</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_arsip" id="id_file_edit">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">JUDUL FILE</label>
                            <input type="text" name="judul_file_baru" id="judul_file_edit" class="form-control form-control-lg bg-light" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">JENIS SURAT</label>
                            <div class="switch-field w-100 shadow-sm">
                                <input type="radio" id="edit_surat_masuk" name="jenis_surat_baru" value="Surat Masuk" />
                                <label for="edit_surat_masuk">Masuk</label>
                                <input type="radio" id="edit_surat_keluar" name="jenis_surat_baru" value="Surat Keluar" />
                                <label for="edit_surat_keluar">Keluar</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_edit_file" class="btn btn-ocean w-100 rounded-pill fw-bold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    
    <script>
        <?php if(isset($_SESSION['swal_icon'])): ?>
            Swal.fire({icon: '<?php echo $_SESSION['swal_icon']; ?>', title: '<?php echo $_SESSION['swal_title']; ?>', text: '<?php echo $_SESSION['swal_text']; ?>', confirmButtonColor: '#07575B', timer: 3000});
            <?php unset($_SESSION['swal_icon']); unset($_SESSION['swal_title']); unset($_SESSION['swal_text']); ?>
        <?php endif; ?>

        function konfirmasiHapus(url) {
            Swal.fire({title: 'Pindahkan ke Sampah?', text: "Data bisa dipulihkan nanti.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Hapus'}).then((result) => { if (result.isConfirmed) window.location.href = url; });
        }

        // Logic Buat Folder
        function toggleFolderDosen(isPrivate) { document.getElementById('containerFolderPrivat').style.display = isPrivate ? 'block' : 'none'; }
        function toggleListDosenFolder(showList) { document.getElementById('listDosenFolder').style.display = showList ? 'block' : 'none'; }

        // Logic Edit Folder
        function isiModalEditFolder(id, nama, status){
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            if(status == 'publik') { document.getElementById('edit_publik').checked = true; toggleFolderDosenEdit(false); } 
            else { document.getElementById('edit_privat').checked = true; toggleFolderDosenEdit(true); }
        }
        function toggleFolderDosenEdit(isPrivate) { document.getElementById('containerFolderPrivatEdit').style.display = isPrivate ? 'block' : 'none'; }
        function toggleListDosenFolderEdit(showList) { document.getElementById('listDosenFolderEdit').style.display = showList ? 'block' : 'none'; }

        // Logic Edit File
        function editFile(id, judul, jenis){
            document.getElementById('id_file_edit').value = id;
            document.getElementById('judul_file_edit').value = judul;
            if(jenis == 'Surat Masuk') document.getElementById('edit_surat_masuk').checked = true;
            else document.getElementById('edit_surat_keluar').checked = true;
        }
    </script>
</body>
</html>