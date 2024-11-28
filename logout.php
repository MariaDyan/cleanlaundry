<?php
session_start(); // Memulai sesi

// Menghapus semua variabel sesi
session_unset(); 

// Menghancurkan sesi
session_destroy(); 

// Mencegah caching pada halaman yang telah dilogout
header("Cache-Control: no-cache, no-store, must-revalidate"); // Menghindari cache browser
header("Pragma: no-cache"); // Menghindari cache browser
header("Expires: 0"); // Menghindari cache browser

// Mengarahkan kembali ke halaman login
header("Location: index.php"); 
exit();
?>
