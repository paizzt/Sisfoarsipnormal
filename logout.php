<?php
session_start();

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke halaman login dengan pesan sukses
header("location:login.php?pesan=logout");
?>