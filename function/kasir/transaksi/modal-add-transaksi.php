<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

// Cek role pengguna (hanya kasir diizinkan)
if ($_SESSION['role'] !== 'kasir') {
    header('Location: dashboard.php');
    exit();
}

$host = 'localhost';
$dbname = 'db_laundry';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $session_username = $_SESSION['username'];

    // Mengambil nama dan role dari database
    $stmt = $pdo->prepare("SELECT first_name, role, photo FROM users WHERE username = :username");
    $stmt->execute(['username' => $session_username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $admin_name = $admin['first_name'] ?? 'Kasir';
    $admin_role = ucfirst($admin['role'] ?? 'Kasir'); // Capitalize role
    $admin_photo = $admin['photo'] ?? 'default.png';  // Default photo

    // Ambil data filter jika ada
    $filter_name = $_GET['service_name'] ?? '';
    $filter_status = $_GET['status'] ?? '';

    // Query untuk menampilkan satu data transaksi terbaru
    $sql = "SELECT 
         A.id, 
         B.first_name, 
         B.last_name, 
         C.service_name, 
         C.price_per_unit, 
         A.quantity, 
         A.tanggal_transaksi, 
         A.estimasi_selesai, 
         A.metode_pengiriman, 
         A.status_pembayaran, 
         A.status_transaksi
      FROM 
         transactions A
      INNER JOIN 
         member B ON B.id = A.id_pelanggan
      INNER JOIN
         services C ON C.id = A.layanan_id
      WHERE 
         A.tanggal_transaksi >= CURDATE()";

    // Tambahkan filter nama layanan jika ada
    if ($filter_name != '') {
        $sql .= " AND C.service_name LIKE :service_name";
    }

    // Tambahkan filter status transaksi jika ada
    if ($filter_status != '') {
        $sql .= " AND A.status_transaksi = :status";
    }

    // Ambil hanya satu transaksi terbaru
    $sql .= " ORDER BY A.id DESC LIMIT 1";

    // Menyiapkan statement
    $stmt_service = $pdo->prepare($sql);

    // Bind parameter filter jika ada
    if ($filter_name != '') {
        $stmt_service->bindValue(':service_name', '%' . $filter_name . '%', PDO::PARAM_STR);
    }
    if ($filter_status != '') {
        $stmt_service->bindValue(':status', $filter_status, PDO::PARAM_STR);
    }

    // Eksekusi query
    $stmt_service->execute();
    $services = $stmt_service->fetchAll(PDO::FETCH_ASSOC);

    // Proses pembayaran jika formulir dikirim
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $transactionId = $_POST['transaction_id'];
        $paymentMethod = $_POST['payment_method'];

        if ($paymentMethod === 'tunai') {
            // Update status pembayaran menjadi lunas
            $stmt = $pdo->prepare("UPDATE transactions SET status_pembayaran = 'lunas' WHERE id = :id");
            $stmt->execute(['id' => $transactionId]);

            // Setel sesi untuk menghindari pemrosesan berulang
            $_SESSION['payment_success'] = true;

            // Redirect ke dashboard kasir
            header("Location: ../../../page/kasir/dashboard.php");
            exit();
        } elseif ($paymentMethod === 'qris ') {
            // Tampilkan modal QRIS (akan di-handle di bagian frontend)
            $_SESSION['show_qris_modal'] = true;
            $_SESSION['transaction_id'] = $transactionId;

            // Redirect untuk menghindari pengulangan
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Proses pembayaran QRIS jika formulir dikirim
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method']) && $_POST['payment_method'] === 'qris') {
        $transactionId = $_POST['transaction_id'];

        // Update status pembayaran menjadi lunas
        $stmt = $pdo->prepare("UPDATE transactions SET status_pembayaran = 'lunas' WHERE id = :id");
        $stmt->execute(['id' => $transactionId]);

        // Setel sesi untuk menghindari pemrosesan berulang
        $_SESSION['payment_success'] = true;
        unset($_SESSION['show_qris_modal']); // Hapus flag modal setelah diproses

        // Redirect ke dashboard kasir
        header("Location: dashboard.php?print_invoice=$transactionId");
        exit();
    }

    // Cek apakah pembayaran berhasil dan tampilkan alert
    if (isset($_SESSION['payment_success']) && $_SESSION['payment_success'] === true) {
        echo "<script>alert('Pembayaran berhasil.');</script>";
        unset($_SESSION['payment_success']); // Hapus flag setelah ditampilkan
    }

    // Cek jika modal QRIS perlu ditampilkan
    $showQrisModal = isset($_SESSION['show_qris_modal']) && $_SESSION['show_qris_modal'] === true;
    if ($showQrisModal) {
        // Simulasi QR Code (ganti dengan QR Code yang sesuai)
        $qrCodeUrl = "https://example.com/qris?transaction_id=" . $_SESSION['transaction_id'];
        unset($_SESSION['show_qris_modal']); // Hapus flag setelah ditampilkan
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir Laundry - Member</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
    <script>
        // Fungsi untuk mencetak kwitansi
        function printInvoice() {
            window.print();
        }
    </script>
</head>

<body class="bg-gray-100">

<!-- Modal Tambah Transaksi -->
<div class="fixed inset-0 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 w-5/12">
    <div class="flex justify-center mb-4">
      <div class="flex flex-col items-center">
         <h2 class="text-2xl font-semibold text-gray-800 text-center"> Invoice Transaksi</h2>
         <span class="text-gray-600"><?php echo date('j F Y'); ?></span>
      </div>
    </div>

    <hr class="my-4 border-blue-700">

    <div class="space-y-4">
        <!-- Loop through each service to display the details -->
        <?php foreach ($services as $service): ?>
            <div class="flex justify-between items-center">
                <span class="font-semibold">Nama Pelanggan:</span>
                <span><?php echo htmlspecialchars($service['first_name'] . ' ' . $service['last_name']); ?></span>
            </div>

            <div class="flex justify-between items-center">
                <span class="font-semibold">Layanan:</span>
                <span><?php echo htmlspecialchars($service['service_name']); ?></span>
            </div>

            <div class="flex justify-between items-center">
                <span class="font-semibold">Harga per Unit:</span>
                <span><?php echo 'Rp ' . number_format(htmlspecialchars($service['price_per_unit']), 0, ',', '.'); ?></span>
            </div>

            <div class="flex justify-between items-center">
                <span class="font-semibold">Jumlah:</span>
                <span><?php echo htmlspecialchars($service['quantity']); ?></span>
            </div>

            <div class="flex justify-between items-center">
                <span class="font-semibold">Total Harga:</span>
                <span><?php echo 'Rp ' . number_format(htmlspecialchars($service['price_per_unit'] * $service['quantity']), 0, ',', '.'); ?></span>
            </div>

            <div class="flex justify-between items-center">
                <span class="font-semibold">Tanggal Transaksi:</span>
                <span><?php echo date('j F Y', strtotime($service['tanggal_transaksi'])); ?></span>
            </div>

            <div class="flex justify-between items-center">
                <span class="font-semibold">Estimasi Selesai:</span>
                <span><?php echo date('j F Y', strtotime($service['estimasi_selesai'])); ?></span>
            </div>

            <div class="flex justify-between items-center">
                <span class="font-semibold">Metode Pengiriman:</span>
                <span>
                    <?php
                    if ($service['metode_pengiriman'] == 'pickup') {
                        echo '<span class="px-4 py-2 text-green-900 font-extrabold rounded">Ambil Sendiri</span>';
                    } elseif ($service['metode_pengiriman'] == 'delivery') {
                        echo '<span class="px-4 py-2 text-blue-900 rounded font-extrabold">Kirim</span>';
                    }
                    ?>
                </span>
            </div>

            <div class="flex justify-between items-center">
                <span class="font-semibold">Status Pembayaran:</span>
                <span>
                    <?php
                    if ($service['status_pembayaran'] == 'lunas') {
                        echo '<span class ="px-4 py-2 text-green-500 font-extrabold rounded">Lunas</span>';
                    } elseif ($service['status_pembayaran'] == 'belum_lunas') {
                        echo '<span class="px-4 py-2 text-red-600 rounded font-extrabold">Belum Lunas</span>';
                    }
                    ?>
                </span>
            </div>

            <div class="flex justify-between items-center">
                <span class="font-semibold">Status Transaksi:</span>
                <span>
                    <?php
                    if ($service['status_transaksi'] == 'selesai') {
                        echo '<span class="px-4 py-2 text-green-500 font-extrabold rounded">Selesai</span>';
                    } elseif ($service['status_transaksi'] == 'sedang_diproses') {
                        echo '<span class="px-4 py-2 text-yellow-600 rounded font-extrabold">Sedang Diproses</span>';
                    }
                    ?>
                </span>
            </div>

            <hr class="my-4 border-blue-700">
        <?php endforeach; ?>
    </div>

    <form method="POST" action="">
      <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($service['id']); ?>">
      <div class="flex justify-center space-x-4 mt-5">
         <a href="../../../page/kasir/history.php" class="px-4 py-2 bg-red-600 rounded-lg hover:bg-red-700 text-white">
          Kas Bon
         </a>
        <?php if ($service['status_pembayaran'] !== 'lunas'): ?>
            <select name="payment_method" class="border rounded p-2">
                <option value="">Pilih Metode Pembayaran</option>
                <option value="tunai">Tunai</option>
                <option value="qris">QRIS</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              Proses Pembayaran
            </button>
        <?php else: ?>
            <span class="px-4 py-2 bg-gray-400 rounded-lg text-white">Pembayaran Sudah Lunas</span>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if ($showQrisModal): ?>
<div class="fixed inset-0 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 w-5/12">
    <h2 class="text-2xl font-semibold text-gray-800 text-center">QRIS Pembayaran</h2>
    <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="mx-auto my-4">
    <div class="flex justify-end">
      <form method="POST" action="">
        <input ```php
        <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($_SESSION['transaction_id']); ?>">
        <input type="hidden" name="payment_method" value="qris">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
          OK
        </button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
    // Cek jika ada parameter untuk mencetak kwitansi
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('print_invoice')) {
        window.onload = function() {
            printInvoice(); // Panggil fungsi untuk mencetak kwitansi
        };
    }
</script>

</body>
</html>