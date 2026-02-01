<?php
session_start();
// Cek Login & Role
if($_SESSION['status'] != "login" || $_SESSION['role'] != 'admin'){
    header("location:../login.php?pesan=belum_login");
}

include '../config/koneksi.php';

// AMBIL DATA USER LOGIN
$id_user_login = $_SESSION['id_user'];
$q_profil = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user_login'");
$d_profil = mysqli_fetch_array($q_profil);

// FUNGSI NOTIFIKASI
function setNotifikasi($icon, $title, $text){
    $_SESSION['swal_icon'] = $icon;
    $_SESSION['swal_title'] = $title;
    $_SESSION['swal_text'] = $text;
}

// --- LOGIKA 1: TAMBAH FOLDER BARU ---
if(isset($_POST['btn_buat_folder'])){
    $nama_folder = mysqli_real_escape_string($koneksi, $_POST['nama_folder_baru']);
    $status_folder = $_POST['status_folder'];
    $tahun_folder = $_POST['tahun_folder']; 
    
    $simpan = mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori, status_folder, tahun) VALUES ('$nama_folder', '$status_folder', '$tahun_folder')");
    
    if($simpan){
        $id_kat_baru = mysqli_insert_id($koneksi);
        
        // Simpan Hak Akses Folder Privat
        if($status_folder == 'privat' && isset($_POST['opsi_privat_folder']) && $_POST['opsi_privat_folder'] == 'pilih'){
             $dosen_terpilih = isset($_POST['akses_folder_dosen']) ? $_POST['akses_folder_dosen'] : [];
             foreach($dosen_terpilih as $id_user){
                 mysqli_query($koneksi, "INSERT INTO hak_akses_folder (id_kategori, id_user) VALUES ('$id_kat_baru', '$id_user')");
             }
        }
        setNotifikasi("success", "Berhasil!", "Folder baru tahun $tahun_folder telah dibuat.");
        header("Location: upload.php");
        exit();
    }
}

// --- LOGIKA 2: PROSES UPLOAD FILE (DIPERBARUI) ---
if(isset($_POST['btn_upload'])){
    
    // 1. VALIDASI DATA KOSONG (Wajib pilih Folder, Jenis Surat, Akses)
    if(empty($_POST['kategori']) || empty($_POST['jenis_surat']) || empty($_POST['status_akses'])){
        setNotifikasi("warning", "Data Belum Lengkap!", "Mohon pilih Folder, Jenis Surat, dan Status Akses terlebih dahulu.");
        header("Location: upload.php");
        exit();
    }

    $id_kategori = $_POST['kategori'];
    $jenis_surat = $_POST['jenis_surat'];
    $status_akses = $_POST['status_akses'];
    $pengunggah = $_SESSION['id_user']; 
    $akses_dosen = isset($_POST['akses_dosen']) ? $_POST['akses_dosen'] : [];

    // Cek Folder Fisik
    $target_dir = "../assets/files/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $jumlah_file = count($_FILES['file_arsip']['name']);
    $berhasil = 0;
    
    for($i=0; $i<$jumlah_file; $i++){
        $nama_file = $_FILES['file_arsip']['name'][$i];
        $tmp_file = $_FILES['file_arsip']['tmp_name'][$i];
        $ukuran_file = $_FILES['file_arsip']['size'][$i];
        $tipe_file = $_FILES['file_arsip']['type'][$i];
        $error = $_FILES['file_arsip']['error'][$i];

        if($error === 0){
            $ext = pathinfo($nama_file, PATHINFO_EXTENSION);
            $nama_baru = time() . "_" . rand(100, 999) . "." . $ext;
            $path = $target_dir . $nama_baru;

            if(move_uploaded_file($tmp_file, $path)){
                $query_upload = "INSERT INTO arsip (id_kategori, judul_file, nama_file_fisik, tipe_file, ukuran_file, jenis_surat, status_akses, pengunggah_id) 
                                 VALUES ('$id_kategori', '$nama_file', '$nama_baru', '$tipe_file', '$ukuran_file', '$jenis_surat', '$status_akses', '$pengunggah')";
                
                if(mysqli_query($koneksi, $query_upload)){
                    $id_arsip_baru = mysqli_insert_id($koneksi);

                    if($status_akses == 'privat' && !empty($akses_dosen)){
                        foreach($akses_dosen as $id_dosen){
                            mysqli_query($koneksi, "INSERT INTO hak_akses (id_arsip, id_user) VALUES ('$id_arsip_baru', '$id_dosen')");
                        }
                    }
                    $berhasil++;
                }
            }
        }
    }

    if($berhasil > 0){
        setNotifikasi("success", "Upload Sukses!", "$berhasil file berhasil diarsipkan.");
        header("Location: arsip_semua.php");
        exit();
    } else {
        setNotifikasi("error", "Gagal!", "Tidak ada file yang terupload atau file corrupt.");
        header("Location: upload.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Arsip - SISFO</title>
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
                <li class="active"><a href="upload.php" class="text-warning fw-bold"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Arsip</a></li>
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
            
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded-4 mb-4 px-4 py-2 sticky-top" style="z-index: 900;">
                <div class="d-flex align-items-center w-100">
                    <button type="button" id="sidebarCollapse" class="btn btn-light rounded-circle me-3 shadow-sm text-ocean">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    <div class="d-none d-md-block">
                        <h5 class="fw-bold mb-0 text-secondary" style="font-size: 1.1rem;">Form Upload Arsip</h5>
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
                    <div class="card-body p-5">
                        <h4 class="fw-bold mb-4 text-center" style="color: var(--deep-aqua);">Upload Dokumen Baru</h4>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary">File Dokumen <span class="text-danger">*</span></label>
                                <div class="upload-area" id="uploadArea">
                                    <i class="bi bi-cloud-arrow-up"></i>
                                    <h5 id="fileLabel">Klik atau Seret File ke Sini</h5>
                                    <p class="small mb-0">PDF, DOCX, JPG (Bisa Banyak File)</p>
                                    <input type="file" name="file_arsip[]" id="fileInput" class="file-input-hidden" multiple required>
                                </div>
                                <div id="fileList" class="mt-2 text-muted small fst-italic"></div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-secondary">1. Pilih Tahun</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-warning border-0 shadow-sm"><i class="bi bi-calendar-event"></i></span>
                                        <select id="filterTahun" class="form-select border-0 shadow-sm bg-light" onchange="filterFolderByYear()">
                                            <option value="">-- Pilih Tahun --</option>
                                            <option value="all" class="fw-bold">📂 Semua Tahun</option>
                                            <?php
                                            $currYear = date('Y');
                                            for($y=$currYear; $y>=$currYear-5; $y--){
                                                echo "<option value='$y'>$y</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold text-secondary">2. Simpan di Folder <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-warning border-0 shadow-sm"><i class="bi bi-folder-fill"></i></span>
                                        <select name="kategori" id="selectKategori" class="form-select border-0 shadow-sm bg-light" required disabled>
                                            <option value="" selected disabled>-- Pilih Folder --</option>
                                            <?php
                                            $kat = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id_kategori DESC");
                                            while($k = mysqli_fetch_array($kat)){
                                                $icon = ($k['status_folder'] == 'privat') ? 'lock-fill' : 'globe'; 
                                                echo "<option value='".$k['id_kategori']."' data-tahun='".$k['tahun']."' data-icon='$icon'>".$k['nama_kategori']."</option>";
                                            }
                                            ?>
                                        </select>
                                        <button class="btn btn-warning text-white fw-bold shadow-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalBuatFolder">
                                            <i class="bi bi-plus-lg"></i> Baru
                                        </button>
                                    </div>
                                    <small id="folderHint" class="text-muted" style="font-size: 0.8rem;">*Pilih tahun terlebih dahulu</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold text-secondary d-block mb-2">Jenis Surat <span class="text-danger">*</span></label>
                                    <div class="switch-field shadow-sm">
                                        <input type="radio" id="surat_masuk" name="jenis_surat" value="Surat Masuk" required />
                                        <label for="surat_masuk"><i class="bi bi-envelope-arrow-down-fill me-1"></i> Masuk</label>
                                        <input type="radio" id="surat_keluar" name="jenis_surat" value="Surat Keluar" required />
                                        <label for="surat_keluar"><i class="bi bi-envelope-arrow-up-fill me-1"></i> Keluar</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold text-secondary d-block mb-2">Hak Akses File <span class="text-danger">*</span></label>
                                    <div class="switch-field shadow-sm">
                                        <input type="radio" id="akses_publik" name="status_akses" value="publik" required onclick="toggleDosen(false)"/>
                                        <label for="akses_publik"><i class="bi bi-globe me-1"></i> Publik</label>
                                        <input type="radio" id="akses_privat" name="status_akses" value="privat" required onclick="toggleDosen(true)"/>
                                        <label for="akses_privat"><i class="bi bi-lock-fill me-1"></i> Privat</label>
                                    </div>
                                </div>
                            </div>

                            <div id="containerListDosen" class="mb-4" style="display: none;">
                                <div class="card border-warning bg-light">
                                    <div class="card-header bg-warning text-white py-2">
                                        <small class="fw-bold"><i class="bi bi-lock-fill"></i> Pilih Dosen untuk File Ini</small>
                                    </div>
                                    <div class="card-body p-2" style="max-height: 150px; overflow-y: auto;">
                                        <?php
                                        $dosen = mysqli_query($koneksi, "SELECT * FROM users WHERE role='dosen' ORDER BY nama_lengkap ASC");
                                        while($d = mysqli_fetch_array($dosen)){
                                        ?>
                                            <div class="form-check border-bottom py-1">
                                                <input class="form-check-input" type="checkbox" name="akses_dosen[]" value="<?php echo $d['id_user']; ?>" id="file_dosen_<?php echo $d['id_user']; ?>">
                                                <label class="form-check-label small" for="file_dosen_<?php echo $d['id_user']; ?>">
                                                    <?php echo $d['nama_lengkap']; ?>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" name="btn_upload" class="btn btn-ocean btn-lg shadow fw-bold rounded-pill">
                                    UPLOAD SEKARANG
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBuatFolder" tabindex="-1" aria-hidden="true">
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
                            <label class="form-label text-secondary fw-bold small">TAHUN FOLDER</label>
                            <select name="tahun_folder" class="form-select bg-light" required>
                                <?php
                                $currYear = date('Y');
                                echo "<option value='$currYear' selected>$currYear</option>";
                                echo "<option value='".($currYear-1)."'>".($currYear-1)."</option>";
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">STATUS FOLDER</label>
                            <div class="switch-field shadow-sm w-100">
                                <input type="radio" id="f_publik" name="status_folder" value="publik" checked onclick="toggleFolderDosen(false)"/>
                                <label for="f_publik"><i class="bi bi-globe me-1"></i> Publik</label>
                                <input type="radio" id="f_privat" name="status_folder" value="privat" onclick="toggleFolderDosen(true)"/>
                                <label for="f_privat"><i class="bi bi-lock-fill me-1"></i> Privat</label>
                            </div>
                        </div>

                        <div id="containerFolderPrivat" class="mb-3 p-3 bg-light border rounded" style="display: none;">
                            <label class="form-label small fw-bold text-danger">Pengaturan Akses Privat:</label>
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
                                    // Reset Pointer Dosen
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
                        <button type="submit" name="btn_buat_folder" class="btn btn-ocean fw-bold px-4 rounded-pill w-100">Simpan Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    
    <script>
        // 1. SWEETALERT NOTIFIKASI
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

        // 2. LOGIC FILTER FOLDER BERDASARKAN TAHUN
        const selectTahun = document.getElementById('filterTahun');
        const selectKategori = document.getElementById('selectKategori');
        const allOptions = Array.from(selectKategori.querySelectorAll('option:not([value=""])'));

        function filterFolderByYear() {
            const selectedYear = selectTahun.value;
            const folderHint = document.getElementById('folderHint');

            selectKategori.value = "";
            if(selectedYear === "") {
                selectKategori.disabled = true;
                folderHint.style.display = 'block';
                return;
            }
            selectKategori.disabled = false;
            folderHint.style.display = 'none';

            allOptions.forEach(option => {
                const folderYear = option.getAttribute('data-tahun');
                if (selectedYear === "all" || folderYear === selectedYear) {
                    option.style.display = 'block';
                    option.hidden = false;
                } else {
                    option.style.display = 'none';
                    option.hidden = true;
                }
            });
        }

        // JS Upload Area
        const fileInput = document.getElementById('fileInput');
        const fileLabel = document.getElementById('fileLabel');
        const fileList = document.getElementById('fileList');
        const uploadArea = document.getElementById('uploadArea');

        fileInput.addEventListener('change', function(e) {
            const count = fileInput.files.length;
            if(count > 0){
                fileLabel.innerHTML = `<strong>${count} File Dipilih</strong>`;
                uploadArea.style.borderColor = "var(--ocean)";
                uploadArea.style.backgroundColor = "#e0f7fa";
                let names = "";
                for(let i = 0; i < count; i++){ names += fileInput.files[i].name + ", "; }
                fileList.innerText = "File: " + names.slice(0, -2);
            } else {
                fileLabel.innerHTML = "Klik atau Seret File ke Sini";
                fileList.innerText = "";
            }
        });

        function toggleDosen(isPrivate) { document.getElementById('containerListDosen').style.display = isPrivate ? 'block' : 'none'; }
        function toggleFolderDosen(isPrivate) { document.getElementById('containerFolderPrivat').style.display = isPrivate ? 'block' : 'none'; }
        function toggleListDosenFolder(showList) { document.getElementById('listDosenFolder').style.display = showList ? 'block' : 'none'; }
    </script>
</body>
</html>