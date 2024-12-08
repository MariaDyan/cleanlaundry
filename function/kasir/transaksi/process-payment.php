<?php
session_start();
header('Content-Type: application/json');

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login.']);
    exit();
}

// Koneksi ke database
$host = 'localhost';
$dbname = 'db_laundry';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil data dari permintaan POST
    $data = json_decode(file_get_contents('php://input'), true);
    $method = $data['method'] ?? '';

    if ($method === 'tunai') {
        // Contoh: Update status pembayaran menjadi lunas
        $transactionId = 1; // Ganti dengan ID transaksi sebenarnya (ambil dari session atau request)
        $stmt = $pdo->prepare("UPDATE transactions SET status_pembayaran = 'lunas' WHERE id = :id");
        $stmt->execute(['id' => $transactionId]);

        echo json_encode(['success' => true, 'message' => 'Pembayaran tunai berhasil.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Metode pembayaran tidak valid.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
