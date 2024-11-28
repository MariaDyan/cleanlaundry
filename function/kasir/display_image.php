<!-- Skrip Ambil Foto Data Blob -->
<?php
session_start();
$host = 'localhost';
$dbname = 'db_laundry';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SELECT photo FROM users WHERE username = :username");
$stmt->execute(['username' => $_SESSION['username']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin && $admin['photo']) {
    header("Content-Type: image/jpeg");  // Ubah sesuai format foto
    echo $admin['photo'];  // Output binary data
} else {
    // Tampilkan foto default jika tidak ada
    header("Location: default.png");
}
?>
<!-- Skrip Ambil Foto Data Blob -->