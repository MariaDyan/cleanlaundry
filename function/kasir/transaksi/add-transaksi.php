<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

// Cek role pengguna, jika bukan kasir, alihkan ke halaman lain
if ($_SESSION['role'] !== 'kasir') {
    header('Location: ../../page/kasir/dashboard.php');
    exit();
}

$host = 'localhost';
$dbname = 'db_laundry';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ambil data dari form
        $id_pelanggan = $_POST['id_pelanggan'];
        $layanan_ids = $_POST['layanan_id']; // Ini adalah array
        $quantities = $_POST['quantity']; // Ini juga array
        $metode_pengiriman = $_POST['metode_pengiriman'];
        $durasi_layanan = $_POST['durasi_layanan'];
        $catatan = $_POST['catatan'] ?? '';
        $tanggal_transaksi = date('Y-m-d H:i:s');

        // Inisialisasi total harga
        $total_harga = 0;

        // Loop untuk menghitung total harga
        foreach ($layanan_ids as $index => $layanan_id) {
            // Ambil harga layanan dari tabel services
            $stmtService = $pdo->prepare("SELECT price_per_unit FROM services WHERE id = :layanan_id");
            $stmtService->execute(['layanan_id' => $layanan_id]);
            $service = $stmtService->fetch(PDO::FETCH_ASSOC);

            if (!$service) {
                $_SESSION['toast_message'] = "Layanan tidak ditemukan!";
                header('Location: ../../../page/kasir/dashboard.php');
                exit();
            }

            $harga_per_unit = $service['price_per_unit'];
            $quantity = $quantities[$index]; // Ambil quantity sesuai index

            // Hitung total harga untuk layanan ini
            $total_harga += ($quantity * $harga_per_unit) + $biaya_pengiriman ;
        }

        // Hitung biaya pengiriman
        $biaya_pengiriman = ($metode_pengiriman === 'delivery') ? 5000 : 0;

        // Hitung biaya durasi
        $biaya_durasi = 0;
        if ($durasi_layanan == '0') {
            $biaya_durasi = 5000; // Express (1 jam)
        } elseif ($durasi_layanan == '1') {
            $biaya_durasi = 2500; // Kilat (1 hari)
        } elseif ($durasi_layanan == '2') {
            $biaya_durasi = 0; // Reguler (2 hari)
        }

        // Total harga akhir
        $total_harga += $biaya_pengiriman + $biaya_durasi;

        // Hitung estimasi selesai berdasarkan durasi layanan
        $today = new DateTime();
        if ($durasi_layanan == '0') {
            $today->modify('+1 hour');
        } elseif ($durasi_layanan == '1') {
            $today->modify('+1 day');
        } elseif ($durasi_layanan == '2') {
            $today->modify('+2 days');
        }
        $estimasi_selesai = $today->format('Y-m-d H:i:s');

        // Loop untuk memasukkan setiap layanan ke dalam database
        foreach ($layanan_ids as $index => $layanan_id) {
            $quantity = $quantities[$index]; // Ambil quantity sesuai index

            // Query untuk menambahkan transaksi ke dalam database
            $stmt = $pdo->prepare("
                INSERT INTO transactions 
                (id_pelanggan, layanan_id, quantity, total_harga, biaya_pengiriman, tanggal_transaksi, metode_pengiriman, durasi_layanan, catatan, estimasi_selesai, status_transaksi, status_pembayaran) 
                VALUES 
                (:id_pelanggan, :layanan_id, :quantity, :total_harga, :biaya_pengiriman, :tanggal_transaksi, :metode_pengiriman, :durasi_layanan, :catatan, :estimasi_selesai, 'sedang_diproses', 'belum_lunas')
            ");

            // Eksekusi pernyataan dengan parameter yang benar
            $stmt->execute([
                'id_pelanggan' => $id_pelanggan,
                'layanan_id' => $layanan_id,
                'quantity' => $quantity,
                'total_harga' => $total_harga,
                'biaya_pengiriman' => $biaya_pengiriman,
                'tanggal_transaksi' => $tanggal_transaksi,
                'metode_pengiriman' => $metode_pengiriman,
                'durasi_layanan' => $durasi_layanan,
                'catatan' => $catatan,
                'estimasi_selesai' => $estimasi_selesai
            ]);
        }

        $_SESSION['toast_message'] = "Transaksi berhasil ditambahkan!";
        header('Location:modal-add-transaksi.php');
        exit();
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}
?>