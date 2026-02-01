document.addEventListener("DOMContentLoaded", function () {
    
    // 1. Hapus Loader (Kode Lama)
    const loader = document.getElementById('loader');
    if(loader){
        loader.style.opacity = '0';
        setTimeout(() => {
            loader.style.display = 'none';
        }, 500);
    }

    // 2. Logic Sidebar Toggle (Buka Tutup)
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    
    if(sidebarCollapse){
        sidebarCollapse.addEventListener('click', function () {
            sidebar.classList.toggle('active');
        });
    }

    // 3. Logic Dark Mode / Light Mode
    const themeBtn = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const body = document.body;

    // Cek LocalStorage saat load (Ingat pilihan user)
    if (localStorage.getItem('theme') === 'dark') {
        body.classList.add('dark-mode');
        themeIcon.classList.remove('bi-sun-fill');
        themeIcon.classList.add('bi-moon-stars-fill');
        themeIcon.style.color = "#f1c40f"; // Warna kuning bulan
    }

    if(themeBtn){
        themeBtn.addEventListener('click', function() {
            body.classList.toggle('dark-mode');
            themeIcon.classList.toggle('rotate-icon'); // Efek putar

            // Ganti Icon Matahari <-> Bulan
            if (body.classList.contains('dark-mode')) {
                themeIcon.classList.remove('bi-sun-fill');
                themeIcon.classList.add('bi-moon-stars-fill');
                themeIcon.style.color = "#f1c40f"; 
                localStorage.setItem('theme', 'dark');
            } else {
                themeIcon.classList.remove('bi-moon-stars-fill');
                themeIcon.classList.add('bi-sun-fill');
                themeIcon.style.color = "var(--ocean)";
                localStorage.setItem('theme', 'light');
            }

            // Reset animasi putar setelah selesai
            setTimeout(() => {
                themeIcon.classList.remove('rotate-icon');
            }, 500);
        });
    }
});