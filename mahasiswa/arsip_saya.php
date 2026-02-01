<?php
session_start();
// Cek Login & Role
if($_SESSION['status'] != "login" || $_SESSION['role'] != 'mahasiswa'){
    header("location:../login.php?pesan=belum_login");
}

include '../config/koneksi.php';

// DATA PROFIL
$id_user = $_SESSION['id_user'];
$q_profil = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user'");
$d_profil = mysqli_fetch_array($q_profil);

// FUNGSI NOTIFIKASI
function setNotifikasi($icon, $title, $text){
    $_SESSION['swal_icon'] = $icon;
    $_SESSION['swal_title'] = $title;
    $_SESSION['swal_text'] = $text;
}

// --- LOGIKA HAPUS FILE (SOFT DELETE) ---
if(isset($_GET['hapus'])){
    $id_arsip = $_GET['hapus'];
    // Pastikan yang dihapus adalah milik user ini (Security Check)
    $cek = mysqli_query($koneksi, "SELECT id_arsip FROM arsip WHERE id_arsip='$id_arsip' AND pengunggah_id='$id_user'");
    
    if(mysqli_num_rows($cek) > 0){
        mysqli_query($koneksi, "UPDATE arsip SET status_hapus='1' WHERE id_arsip='$id_arsip'");
        setNotifikasi("success", "Terhapus", "File telah dipindahkan ke sampah.");
    } else {
        setNotifikasi("error", "Gagal", "Anda tidak memiliki akses menghapus file ini.");
    }
    header("Location: arsip_saya.php"); exit();
}

// --- LOGIKA EDIT FILE ---
if(isset($_POST['btn_edit'])){
    $id_arsip = $_POST['id_arsip'];
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul_file']);
    $id_kategori = $_POST['kategori'];
    $status_akses = $_POST['status_akses'];

    // Pastikan milik user ini
    $cek = mysqli_query($koneksi, "SELECT id_arsip FROM arsip WHERE id_arsip='$id_arsip' AND pengunggah_id='$id_user'");
    
    if(mysqli_num_rows($cek) > 0){
        $update = mysqli_query($koneksi, "UPDATE arsip SET judul_file='$judul', id_kategori='$id_kategori', status_akses='$status_akses' WHERE id_arsip='$id_arsip'");
        if($update){
            setNotifikasi("success", "Berhasil", "Data arsip diperbarui.");
        }
    }
    header("Location: arsip_saya.php"); exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Saya - Mahasiswa</title>
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
                <small class="opacity-75">Panel Mahasiswa</small>
            </div>
            <ul class="list-unstyled components flex-grow-1">
                <li><a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
                <li><a href="upload.php"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Arsip</a></li>
                <li class="active"><a href="arsip_saya.php" class="text-warning fw-bold"><i class="bi bi-archive-fill"></i> Data Arsip</a></li>
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
                        <h5 class="fw-bold mb-0 text-secondary" style="font-size: 1.1rem;">Arsip Saya</h5>
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
                
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold text-secondary m-0"><i class="bi bi-folder2-open me-2 text-warning"></i>File Upload Anda</h5>
                            <small class="text-muted">Kelola file yang telah Anda unggah.</small>
                        </div>
                        <a href="upload.php" class="btn btn-ocean rounded-pill px-4 fw-bold shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> Upload Baru
                        </a>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 py-3">File Dokumen</th>
                                        <th>Kategori</th>
                                        <th>Privasi</th>
                                        <th>Tgl Upload</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // QUERY: Hanya tampilkan milik user ini (pengunggah_id)
                                    $query = "SELECT arsip.*, kategori.nama_kategori 
                                              FROM arsip 
                                              JOIN kategori ON arsip.id_kategori = kategori.id_kategori 
                                              WHERE arsip.pengunggah_id = '$id_user' AND arsip.status_hapus='0' 
                                              ORDER BY arsip.id_arsip DESC";
                                    
                                    $result = mysqli_query($koneksi, $query);
                                    
                                    if(mysqli_num_rows($result) > 0){
                                        while($d = mysqli_fetch_array($result)){
                                            // Icon
                                            $icon = "bi-file-earmark-text-fill text-secondary";
                                            if(strpos($d['tipe_file'], 'pdf') !== false) $icon = "bi-file-earmark-pdf-fill text-danger";
                                            elseif(strpos($d['tipe_file'], 'word') !== false) $icon = "bi-file-earmark-word-fill text-primary";
                                            elseif(strpos($d['tipe_file'], 'image') !== false) $icon = "bi-file-earmark-image-fill text-success";
                                            
                                            $link = "../assets/files/" . $d['nama_file_fisik'];
                                    ?>
                                    <tr class="clickable-row">
                                        <td class="ps-4" onclick="window.open('<?php echo $link; ?>', '_blank')">
                                            <div class="d-flex align-items-center">
                                                <i class="bi <?php echo $icon; ?> fs-3 me-3"></i>
                                                <div>
                                                    <span class="fw-bold text-dark"><?php echo $d['judul_file']; ?></span>
                                                    <br>
                                                    <small class="text-muted">ID: #<?php echo $d['id_arsip']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $d['nama_kategori']; ?></span></td>
                                        <td>
                                            <?php if($d['status_akses'] == 'privat') { ?>
                                                <span class="badge bg-danger rounded-pill"><i class="bi bi-lock-fill me-1"></i> Privat</span>
                                            <?php } else { ?>
                                                <span class="badge bg-success rounded-pill"><i class="bi bi-globe me-1"></i> Publik</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($d['tgl_upload'])); ?></td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-outline-warning rounded-pill px-3 me-1" 
                                                    onclick="isiModalEdit('<?php echo $d['id_arsip']; ?>', '<?php echo addslashes($d['judul_file']); ?>', '<?php echo $d['id_kategori']; ?>', '<?php echo $d['status_akses']; ?>')" 
                                                    data-bs-toggle="modal" data-bs-target="#modalEdit">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            
                                            <button class="btn btn-sm btn-outline-danger rounded-circle" 
                                                    onclick="konfirmasiHapus('arsip_saya.php?hapus=<?php echo $d['id_arsip']; ?>')" 
                                                    title="Hapus">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php } } else { ?>
                                        <tr><td colspan="5" class="text-center py-5 text-muted fst-italic">Anda belum mengupload file apapun.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title text-dark fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit File Anda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_arsip" id="edit_id">
                        
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">JUDUL FILE</label>
                            <input type="text" name="judul_file" id="edit_judul" class="form-control bg-light" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">KATEGORI FOLDER</label>
                            <select name="kategori" id="edit_kategori" class="form-select bg-light" required>
                                <?php
                                $kat = mysqli_query($koneksi, "SELECT * FROM kategori WHERE status_folder='publik' AND status_hapus='0' ORDER BY nama_kategori ASC");
                                while($k = mysqli_fetch_array($kat)){
                                    echo "<option value='".$k['id_kategori']."'>".$k['nama_kategori']."</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">PRIVASI</label>
                            <div class="switch-field w-100 shadow-sm">
                                <input type="radio" id="edit_pub" name="status_akses" value="publik" />
                                <label for="edit_pub"><i class="bi bi-globe me-1"></i> Publik</label>
                                <input type="radio" id="edit_priv" name="status_akses" value="privat" />
                                <label for="edit_priv"><i class="bi bi-lock-fill me-1"></i> Privat</label>
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">*Mengubah ke 'Privat' akan membatasi akses hanya untuk Anda (dan Dosen jika sudah dibagikan sebelumnya).</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_edit" class="btn btn-ocean fw-bold px-4 rounded-pill">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>

    <script>
        // Notifikasi
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
                title: 'Hapus File Ini?',
                text: "File akan dipindahkan ke Sampah.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus'
            }).then((result) => { if (result.isConfirmed) window.location.href = url; });
        }

        // Isi Modal Edit
        function isiModalEdit(id, judul, kategori, status){
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_judul').value = judul;
            document.getElementById('edit_kategori').value = kategori;
            
            if(status == 'publik'){
                document.getElementById('edit_pub').checked = true;
            } else {
                document.getElementById('edit_priv').checked = true;
            }
        }
    </script>
</body>
</html>