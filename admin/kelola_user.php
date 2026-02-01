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
// LOGIKA 1: TAMBAH USER
// -----------------------------------------------------------
if(isset($_POST['btn_tambah_user'])){
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Cek Duplikat
    $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE email='$email' OR username='$username'");
    if(mysqli_num_rows($cek) > 0){
        setNotifikasi("error", "Gagal!", "Username atau Email sudah terdaftar.");
    } else {
        $simpan = mysqli_query($koneksi, "INSERT INTO users (nama_lengkap, email, username, password, role) VALUES ('$nama', '$email', '$username', '$password', '$role')");
        if($simpan){
            setNotifikasi("success", "Berhasil!", "Pengguna baru ditambahkan.");
            header("Location: kelola_user.php"); exit();
        }
    }
}

// -----------------------------------------------------------
// LOGIKA 2: EDIT USER
// -----------------------------------------------------------
if(isset($_POST['btn_edit_user'])){
    $id_user = $_POST['id_user'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $role = $_POST['role'];

    if(empty($_POST['password'])){
        // Update tanpa ganti password
        $query = "UPDATE users SET nama_lengkap='$nama', email='$email', username='$username', role='$role' WHERE id_user='$id_user'";
    } else {
        // Update dengan password baru
        $pass_baru = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query = "UPDATE users SET nama_lengkap='$nama', email='$email', username='$username', password='$pass_baru', role='$role' WHERE id_user='$id_user'";
    }

    $update = mysqli_query($koneksi, $query);
    if($update){
        setNotifikasi("success", "Update Berhasil!", "Data pengguna diperbarui.");
        header("Location: kelola_user.php"); exit();
    }
}

// -----------------------------------------------------------
// LOGIKA 3: HAPUS USER
// -----------------------------------------------------------
if(isset($_GET['hapus_user'])){
    $id = $_GET['hapus_user'];
    
    // Cegah hapus diri sendiri
    if($id == $_SESSION['id_user']){
        setNotifikasi("error", "Ditolak!", "Tidak bisa menghapus akun yang sedang login.");
        header("Location: kelola_user.php"); exit();
    }

    $hapus = mysqli_query($koneksi, "DELETE FROM users WHERE id_user='$id'");
    if($hapus){
        setNotifikasi("success", "Terhapus!", "Pengguna berhasil dihapus.");
        header("Location: kelola_user.php"); exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - SISFO</title>
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
        
        /* Table Style Override */
        .table-custom thead th { background-color: #f8f9fa; color: #6c757d; font-weight: 600; border-bottom: 2px solid #eef1f6; }
        .avatar-initial { width: 40px; height: 40px; border-radius: 10px; background-color: var(--seafoam); color: var(--deep-aqua); display: flex; align-items: center; justify-content: center; font-weight: bold; }
        
        /* Dark Mode Override */
        body.dark-mode .btn-theme-toggle { background-color: #2c2c2c; border-color: #444; color: #f1c40f; }
        body.dark-mode .user-profile-box:hover { background-color: #2c2c2c; border-color: #444; }
        body.dark-mode .user-name { color: #fff; }
        body.dark-mode .user-role { color: #aaa; }
        body.dark-mode .avatar-initial { background-color: #333; color: #fff; }
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
                <li class="active"><a href="kelola_user.php"><i class="bi bi-people-fill"></i> Kelola Pengguna</a></li>
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
                        <h5 class="fw-bold mb-0 text-secondary" style="font-size: 1.1rem;">Manajemen Pengguna</h5>
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
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold text-ocean mb-1">Daftar Pengguna</h4>
                        <p class="text-muted small mb-0">Kelola akun akses untuk Dosen dan Mahasiswa.</p>
                    </div>
                    <button type="button" class="btn btn-ocean fw-bold shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                        <i class="bi bi-person-plus-fill me-2"></i> Tambah User
                    </button>
                </div>

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4 py-3">Nama Pengguna</th>
                                        <th>Kontak Info</th>
                                        <th>Role</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id_user DESC");
                                    while($d = mysqli_fetch_array($query)){
                                        // Badge Warna Role
                                        $badge_role = "bg-secondary";
                                        $icon_role = "bi-person";
                                        
                                        if($d['role'] == 'admin'){ 
                                            $badge_role = "bg-danger"; $icon_role = "bi-shield-lock-fill";
                                        } else if($d['role'] == 'dosen'){ 
                                            $badge_role = "bg-primary"; $icon_role = "bi-person-video3";
                                        } else if($d['role'] == 'mahasiswa'){ 
                                            $badge_role = "bg-success"; $icon_role = "bi-mortarboard-fill";
                                        }
                                        
                                        // Inisial untuk Avatar
                                        $inisial = strtoupper(substr($d['nama_lengkap'], 0, 1) . substr(strrchr($d['nama_lengkap'], " "), 1, 1));
                                        if($inisial == "") $inisial = strtoupper(substr($d['nama_lengkap'], 0, 2));
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-initial me-3 border shadow-sm">
                                                    <?php echo $inisial; ?>
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-dark d-block"><?php echo $d['nama_lengkap']; ?></span>
                                                    <small class="text-muted text-uppercase" style="font-size: 0.7rem;">ID: #<?php echo $d['id_user']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <small class="text-dark fw-semibold mb-1"><i class="bi bi-envelope me-2 text-secondary"></i><?php echo $d['email']; ?></small>
                                                <small class="text-muted"><i class="bi bi-at me-2 text-secondary"></i><?php echo $d['username']; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $badge_role; ?> rounded-pill px-3 py-2 fw-normal">
                                                <i class="<?php echo $icon_role; ?> me-1"></i> <?php echo ucfirst($d['role']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" 
                                                    onclick="isiModalEdit('<?php echo $d['id_user']; ?>', '<?php echo $d['nama_lengkap']; ?>', '<?php echo $d['email']; ?>', '<?php echo $d['username']; ?>', '<?php echo $d['role']; ?>')" 
                                                    data-bs-toggle="modal" data-bs-target="#modalEditUser">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </button>
                                            
                                            <?php if($d['id_user'] != $_SESSION['id_user']) { ?>
                                            <button class="btn btn-sm btn-outline-danger rounded-circle" 
                                                    onclick="konfirmasiHapus('kelola_user.php?hapus_user=<?php echo $d['id_user']; ?>')" 
                                                    title="Hapus">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                            <?php } ?>
                                        </td>
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

    <div class="modal fade" id="modalTambahUser" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-deep-aqua text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Tambah Pengguna</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">NAMA LENGKAP</label>
                            <input type="text" name="nama" class="form-control bg-light" placeholder="Contoh: Budi Santoso" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary fw-bold small">EMAIL</label>
                                <input type="email" name="email" class="form-control bg-light" placeholder="email@contoh.com" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary fw-bold small">USERNAME</label>
                                <input type="text" name="username" class="form-control bg-light" placeholder="Username unik" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary fw-bold small">PASSWORD</label>
                                <input type="password" name="password" class="form-control bg-light" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary fw-bold small">ROLE / PERAN</label>
                                <select name="role" class="form-select bg-light" required>
                                    <option value="mahasiswa">Mahasiswa</option>
                                    <option value="dosen">Dosen</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_tambah_user" class="btn btn-ocean fw-bold px-4 rounded-pill">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditUser" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title text-dark fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_user" id="edit_id">
                        
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">NAMA LENGKAP</label>
                            <input type="text" name="nama" id="edit_nama" class="form-control bg-light" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary fw-bold small">EMAIL</label>
                                <input type="email" name="email" id="edit_email" class="form-control bg-light" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary fw-bold small">USERNAME</label>
                                <input type="text" name="username" id="edit_username" class="form-control bg-light" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary fw-bold small">PASSWORD BARU</label>
                                <input type="password" name="password" class="form-control bg-light" placeholder="(Kosongkan jika tetap)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-secondary fw-bold small">ROLE / PERAN</label>
                                <select name="role" id="edit_role" class="form-select bg-light" required>
                                    <option value="mahasiswa">Mahasiswa</option>
                                    <option value="dosen">Dosen</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_edit_user" class="btn btn-warning fw-bold px-4 rounded-pill">Update Data</button>
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
            Swal.fire({title: 'Hapus User?', text: "Data akan hilang permanen!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Hapus'}).then((result) => { if (result.isConfirmed) window.location.href = url; });
        }

        function isiModalEdit(id, nama, email, username, role){
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
        }
    </script>
</body>
</html>