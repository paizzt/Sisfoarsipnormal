<?php
session_start();
// Cek Login & Role
if($_SESSION['status'] != "login" || $_SESSION['role'] != 'mahasiswa'){
    header("location:../login.php?pesan=belum_login");
}

include '../config/koneksi.php';

// DATA PROFIL (Header)
$id_user = $_SESSION['id_user'];
$q_profil = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user'");
$d_profil = mysqli_fetch_array($q_profil);

// FUNGSI NOTIFIKASI
function setNotifikasi($icon, $title, $text){
    $_SESSION['swal_icon'] = $icon;
    $_SESSION['swal_title'] = $title;
    $_SESSION['swal_text'] = $text;
}

// --- LOGIKA PROSES UPLOAD ---
if(isset($_POST['btn_upload'])){
    $id_kategori = $_POST['kategori'];
    $jenis_surat = $_POST['jenis_surat']; // Surat Masuk / Surat Keluar
    $status_akses = $_POST['status_akses'];
    $pengunggah = $_SESSION['id_user']; 
    
    // Jika Privat, ambil array dosen yang dipilih
    $akses_dosen = isset($_POST['akses_dosen']) ? $_POST['akses_dosen'] : [];

    // Folder Fisik
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
                // Insert Data Utama
                $query_upload = "INSERT INTO arsip (id_kategori, judul_file, nama_file_fisik, tipe_file, ukuran_file, jenis_surat, status_akses, pengunggah_id) 
                                 VALUES ('$id_kategori', '$nama_file', '$nama_baru', '$tipe_file', '$ukuran_file', '$jenis_surat', '$status_akses', '$pengunggah')";
                
                if(mysqli_query($koneksi, $query_upload)){
                    $id_arsip_baru = mysqli_insert_id($koneksi);

                    // Jika Privat, Berikan Hak Akses ke Dosen Terpilih
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
        setNotifikasi("success", "Berhasil!", "$berhasil dokumen telah diupload.");
        header("Location: arsip_saya.php"); // Redirect ke halaman list file saya
        exit();
    } else {
        setNotifikasi("error", "Gagal!", "Terjadi kesalahan saat upload.");
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Arsip - Mahasiswa</title>
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
                <small class="opacity-75">Panel Mahasiswa</small>
            </div>
            <ul class="list-unstyled components flex-grow-1">
                <li><a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
                <li class="active"><a href="upload.php" class="text-warning fw-bold"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Arsip</a></li>
                <li><a href="arsip_saya.php"><i class="bi bi-archive-fill"></i> Data Arsip</a></li>
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
                        <h5 class="fw-bold mb-0 text-secondary" style="font-size: 1.1rem;">Form Upload</h5>
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
                                    <span class="user-role">Mahasiswa</span>
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
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-5">
                        <h4 class="fw-bold mb-4 text-center" style="color: var(--deep-aqua);">Upload Dokumen</h4>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary">File Dokumen</label>
                                <div class="upload-area" id="uploadArea">
                                    <i class="bi bi-cloud-arrow-up"></i>
                                    <h5 id="fileLabel">Klik atau Seret File ke Sini</h5>
                                    <p class="small mb-0">PDF, Word, JPG (Bisa Banyak File)</p>
                                    <input type="file" name="file_arsip[]" id="fileInput" class="file-input-hidden" multiple required>
                                </div>
                                <div id="fileList" class="mt-2 text-muted small fst-italic"></div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary">Simpan di Folder</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-warning border-0 shadow-sm"><i class="bi bi-folder-fill"></i></span>
                                    <select name="kategori" class="form-select border-0 shadow-sm bg-light" required>
                                        <option value="">-- Pilih Kategori / Folder --</option>
                                        <?php
                                        // Mahasiswa hanya bisa pilih folder PUBLIK
                                        $kat = mysqli_query($koneksi, "SELECT * FROM kategori WHERE status_folder='publik' AND status_hapus='0' ORDER BY nama_kategori ASC");
                                        while($k = mysqli_fetch_array($kat)){
                                            echo "<option value='".$k['id_kategori']."'>".$k['nama_kategori']." (Tahun: ".$k['tahun'].")</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <small class="text-muted" style="font-size: 0.8rem;">*Hanya folder publik yang tersedia.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold text-secondary d-block mb-2">Tipe Dokumen</label>
                                    <div class="switch-field shadow-sm">
                                        <input type="radio" id="surat_masuk" name="jenis_surat" value="Surat Masuk" checked/>
                                        <label for="surat_masuk">Surat Masuk</label>
                                        <input type="radio" id="surat_keluar" name="jenis_surat" value="Surat Keluar" />
                                        <label for="surat_keluar">Surat Keluar</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold text-secondary d-block mb-2">Privasi File</label>
                                    <div class="switch-field shadow-sm">
                                        <input type="radio" id="akses_publik" name="status_akses" value="publik" checked onclick="toggleDosen(false)"/>
                                        <label for="akses_publik"><i class="bi bi-globe me-1"></i> Publik</label>
                                        <input type="radio" id="akses_privat" name="status_akses" value="privat" onclick="toggleDosen(true)"/>
                                        <label for="akses_privat"><i class="bi bi-lock-fill me-1"></i> Privat</label>
                                    </div>
                                </div>
                            </div>

                            <div id="containerListDosen" class="mb-4" style="display: none;">
                                <div class="card border-warning bg-light">
                                    <div class="card-header bg-warning text-white py-2">
                                        <small class="fw-bold"><i class="bi bi-person-video3 me-2"></i>Bagikan ke Dosen (Wajib Pilih jika Privat)</small>
                                    </div>
                                    <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                        <?php
                                        // Tampilkan List Dosen
                                        $dosen = mysqli_query($koneksi, "SELECT * FROM users WHERE role='dosen' ORDER BY nama_lengkap ASC");
                                        while($d = mysqli_fetch_array($dosen)){
                                        ?>
                                            <div class="form-check border-bottom py-2">
                                                <input class="form-check-input" type="checkbox" name="akses_dosen[]" value="<?php echo $d['id_user']; ?>" id="dosen_<?php echo $d['id_user']; ?>">
                                                <label class="form-check-label small fw-semibold" for="dosen_<?php echo $d['id_user']; ?>">
                                                    <?php echo $d['nama_lengkap']; ?>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" name="btn_upload" class="btn btn-ocean btn-lg shadow fw-bold rounded-pill">
                                    <i class="bi bi-cloud-upload-fill me-2"></i> UPLOAD DOKUMEN
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    
    <script>
        // Notifikasi PHP
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

        // Toggle List Dosen
        function toggleDosen(isPrivate) { 
            const list = document.getElementById('containerListDosen');
            if(isPrivate){
                list.style.display = 'block';
                // Animasi kecil
                list.style.opacity = 0;
                setTimeout(() => { list.style.opacity = 1; list.style.transition = 'opacity 0.5s'; }, 50);
            } else {
                list.style.display = 'none';
            }
        }

        // JS Upload Area Interaktif
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
                uploadArea.style.backgroundColor = "white";
                uploadArea.style.borderColor = "#ccc";
            }
        });
    </script>
</body>
</html>