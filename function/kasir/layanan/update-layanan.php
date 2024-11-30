<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('Location: index.php'); // Jika belum login, arahkan ke halaman login
    exit();
}

// Cek role pengguna, jika bukan kasir, alihkan ke halaman dashboard kasir
if ($_SESSION['role'] !== 'kasir') {
    header('Location: ../../page/kasir/dashboard.php'); // Arahkan ke dashboard kasir
    exit();
}

// Konfigurasi database
$host = 'localhost';
$dbname = 'db_laundry';
$username = 'root';
$password = '';

try {
    // Membuat koneksi PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $service_id = $_POST['id'];
    $service_name = $_POST['service_name'];
    $price_per_unit = $_POST['price_per_unit'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    try {
        // Perbarui layanan di database
        $stmt = $pdo->prepare("
            UPDATE services 
            SET 
                service_name = :service_name, 
                price_per_unit = :price_per_unit, 
                description = :description, 
                status = :status 
            WHERE id = :id
        ");
        $stmt->execute([
            ':service_name' => $service_name,
            ':price_per_unit' => $price_per_unit,
            ':description' => $description,
            ':status' => $status,
            ':id' => $service_id
        ]);

         // Set pesan ke session
         $_SESSION['toast_message'] = "Data berhasil diubah!";
         header('Location: ../../../page/kasir/layanan.php');
         exit();

         header('Location: ../../../page/kasir/layanan.php'); // Redirect setelah update berhasil
         exit();
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
?>
