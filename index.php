<?php
session_start();
include 'config/koneksi.php';

// 1. CEK LOGIN (Auto Redirect)
// Jika sudah login, langsung lempar ke dashboard
if(isset($_SESSION['status']) && $_SESSION['status'] == "login"){
    header("location:admin/dashboard.php");
    exit();
}

// 2. AMBIL STATISTIK RINGKAS (Untuk Pemanis Tampilan)
$jml_arsip = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_arsip FROM arsip"));
$jml_user  = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_user FROM users"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISFO ARSIP - Sistem Arsip Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --deep-aqua: #07575B;
            --ocean: #66A5AD;
            --wave: #C4DFE6;
            --seafoam: #f1f4f6;
        }
        body { font-family: 'Poppins', sans-serif; overflow-x: hidden; }

        /* NAVBAR GLASSMORPHISM */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 15px 0;
            transition: 0.3s;
        }
        .navbar-brand { font-weight: 700; color: var(--deep-aqua) !important; letter-spacing: 1px; }
        .nav-link { color: #555 !important; font-weight: 500; margin-left: 20px; transition: 0.3s; }
        .nav-link:hover { color: var(--deep-aqua) !important; }
        .btn-login-nav {
            background-color: var(--deep-aqua);
            color: white !important;
            padding: 8px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(7, 87, 91, 0.2);
        }
        .btn-login-nav:hover { background-color: var(--ocean); transform: translateY(-2px); }

        /* HERO SECTION */
        .hero-section {
            padding: 100px 0 80px 0;
            background: linear-gradient(135deg, #f8fbff 0%, #fff 100%);
            position: relative;
            overflow: hidden;
        }
        /* Dekorasi Bulatan Background */
        .blob {
            position: absolute;
            background: linear-gradient(135deg, var(--wave), var(--ocean));
            opacity: 0.2;
            border-radius: 50%;
            z-index: 0;
            filter: blur(40px);
        }
        .b1 { width: 300px; height: 300px; top: -50px; right: -50px; }
        .b2 { width: 400px; height: 400px; bottom: -100px; left: -100px; }

        .hero-title { font-size: 3rem; font-weight: 800; color: var(--deep-aqua); line-height: 1.2; margin-bottom: 20px; }
        .hero-desc { font-size: 1.1rem; color: #6c757d; margin-bottom: 30px; line-height: 1.6; }
        
        .hero-img-container {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        .hero-icon-main { font-size: 12rem; background: -webkit-linear-gradient(45deg, var(--deep-aqua), var(--ocean)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1)); animation: float 3s ease-in-out infinite; }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        /* STATS CARDS */
        .stats-section { padding: 60px 0; background-color: #fff; position: relative; z-index: 2; }
        .stat-card {
            border: none;
            border-radius: 20px;
            padding: 30px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: 0.3s;
            text-align: center;
            border-bottom: 4px solid var(--deep-aqua);
        }
        .stat-card:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); }
        .stat-icon { font-size: 3rem; color: var(--ocean); margin-bottom: 15px; }
        .stat-number { font-size: 2.5rem; font-weight: 800; color: var(--deep-aqua); }
        .stat-label { color: #888; font-weight: 500; }

        /* FEATURES */
        .features-section { padding: 80px 0; background-color: #f9fbfd; }
        .feature-box { text-align: left; padding: 20px; }
        .feature-icon-circle { width: 60px; height: 60px; background-color: var(--wave); color: var(--deep-aqua); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 20px; }

        /* FOOTER */
        footer { background-color: var(--deep-aqua); color: white; padding: 40px 0 20px 0; }
        .footer-link { color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s; }
        .footer-link:hover { color: white; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/img/logo.png" alt="" width="30" height="30" class="d-inline-block align-text-top me-2" onerror="this.style.display='none'">
                SISFO ARSIP
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#beranda">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
                    <li class="nav-item"><a class="nav-link" href="#statistik">Statistik</a></li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-login-nav" href="login.php">Masuk / Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section id="beranda" class="hero-section d-flex align-items-center">
        <div class="blob b1"></div>
        <div class="blob b2"></div>
        
        <div class="container position-relative z-2">
            <div class="row align-items-center flex-column-reverse flex-lg-row">
                <div class="col-lg-6 text-center text-lg-start mt-5 mt-lg-0">
                    <span class="badge bg-light text-primary border rounded-pill px-3 py-2 mb-3 shadow-sm">
                        <i class="bi bi-stars text-warning me-1"></i> Versi 2.0 Terbaru
                    </span>
                    <h1 class="hero-title">Kelola Arsip Digital<br>Lebih Mudah & Aman.</h1>
                    <p class="hero-desc">
                        Sistem Informasi Arsip Digital untuk pengelolaan dokumen akademik, surat menyurat, dan data penting lainnya secara terpusat, aman, dan mudah diakses kapan saja.
                    </p>
                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                        <a href="login.php" class="btn btn-login-nav py-3 px-4 fs-6">Mulai Sekarang <i class="bi bi-arrow-right ms-2"></i></a>
                        <a href="#fitur" class="btn btn-outline-secondary rounded-pill py-3 px-4 fw-bold">Pelajari Dulu</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-img-container">
                        <i class="bi bi-folder-check hero-icon-main"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="statistik" class="stats-section">
        <div class="container">
            <div class="row g-4 justify-content-center">
                <div class="col-md-4 col-sm-6">
                    <div class="stat-card">
                        <i class="bi bi-files stat-icon"></i>
                        <div class="stat-number"><?php echo $jml_arsip; ?>+</div>
                        <div class="stat-label">Dokumen Tersimpan</div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="stat-card">
                        <i class="bi bi-people stat-icon"></i>
                        <div class="stat-number"><?php echo $jml_user; ?></div>
                        <div class="stat-label">Pengguna Aktif</div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="stat-card">
                        <i class="bi bi-shield-check stat-icon"></i>
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Data Aman</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="fitur" class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h6 class="text-ocean fw-bold text-uppercase ls-1">Kenapa Memilih Kami?</h6>
                <h2 class="fw-bold text-dark">Solusi Arsip Cerdas</h2>
            </div>
            
            <div class="row g-5">
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon-circle"><i class="bi bi-cloud-arrow-up"></i></div>
                        <h5 class="fw-bold">Upload Cepat & Mudah</h5>
                        <p class="text-muted">Simpan berbagai format dokumen (PDF, Word, Gambar) dengan sistem drag & drop yang intuitif.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon-circle"><i class="bi bi-search"></i></div>
                        <h5 class="fw-bold">Pencarian Instan</h5>
                        <p class="text-muted">Temukan dokumen dalam hitungan detik menggunakan fitur pencarian pintar berdasarkan judul atau tahun.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon-circle"><i class="bi bi-lock"></i></div>
                        <h5 class="fw-bold">Hak Akses Terjamin</h5>
                        <p class="text-muted">Atur privasi folder dan file. Tentukan siapa saja dosen atau mahasiswa yang bisa mengakses dokumen.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3">SISFO ARSIP</h5>
                    <p class="small opacity-75">
                        Platform manajemen arsip digital untuk mempermudah administrasi dan penyimpanan dokumen akademik secara terstruktur.
                    </p>
                </div>
                <div class="col-md-2 mb-4">
                    <h6 class="fw-bold mb-3">Navigasi</h6>
                    <ul class="list-unstyled small">
                        <li><a href="#beranda" class="footer-link">Beranda</a></li>
                        <li><a href="#fitur" class="footer-link">Fitur</a></li>
                        <li><a href="login.php" class="footer-link">Login</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">Kontak</h6>
                    <ul class="list-unstyled small opacity-75">
                        <li><i class="bi bi-envelope me-2"></i> admin@kampus.ac.id</li>
                        <li><i class="bi bi-telephone me-2"></i> (021) 1234-5678</li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">Sosial Media</h6>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white fs-5"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white fs-5"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white fs-5"><i class="bi bi-twitter"></i></a>
                    </div>
                </div>
            </div>
            <hr class="opacity-25">
            <div class="text-center small opacity-75">
                &copy; 2026 Sistem Informasi Arsip Digital. All Rights Reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-custom');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = "0 4px 20px rgba(0,0,0,0.05)";
                navbar.style.padding = "10px 0";
            } else {
                navbar.style.boxShadow = "none";
                navbar.style.padding = "15px 0";
            }
        });
    </script>
</body>
</html>