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
    $filter_name = isset($_GET['service_name']) ? $_GET['service_name'] : '';
    $filter_status = isset($_GET['status']) ? $_GET['status'] : '';

    // Ambil data limit dan halaman dari parameter GET
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Default 10 data per halaman
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Default halaman pertama
    $offset = ($page - 1) * $limit; // Hitung offset

    // Query untuk menampilkan data transaksi dengan filter
    $sql = "SELECT 
                A.id, 
                B.first_name, 
                B.last_name, 
                C.service_name, 
                C.price_per_unit, 
                A.quantity, 
                A.total_harga,
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
                DATE(A.tanggal_transaksi) = CURDATE()";

    // Tambahkan filter nama layanan
    if ($filter_name != '') {
        $sql .= " AND C.service_name LIKE :service_name";
    }

    // Tambahkan filter status transaksi
    if ($filter_status != '') {
        $sql .= " AND A.status_transaksi = :status";
    }

    // Tambahkan LIMIT dan OFFSET untuk pagination
    $sql .= " ORDER BY A.id DESC LIMIT :limit OFFSET :offset";

    $stmt_service = $pdo->prepare($sql);

    // Bind parameter filter jika ada
    if ($filter_name != '') {
        $stmt_service->bindValue(':service_name', '%' . $filter_name . '%', PDO::PARAM_STR);
    }
    if ($filter_status != '') {
        $stmt_service->bindValue(':status', $filter_status, PDO::PARAM_STR);
    }

    $stmt_service->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt_service->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt_service->execute();
    $services = $stmt_service->fetchAll(PDO::FETCH_ASSOC);

    // Hitung total jumlah transaksi setelah filter
    $sql_count = "SELECT COUNT(*) FROM transactions A 
                  INNER JOIN services C ON C.id = A.layanan_id
                  WHERE DATE(A.tanggal_transaksi) = CURDATE()";

    if ($filter_name != '') {
        $sql_count .= " AND C.service_name LIKE :service_name";
    }
    if ($filter_status != '') {
        $sql_count .= " AND A.status_transaksi = :status";
    }

    $stmt_count = $pdo->prepare($sql_count);
    if ($filter_name != '') {
        $stmt_count->bindValue(':service_name', '%' . $filter_name . '%', PDO::PARAM_STR);
    }
    if ($filter_status != '') {
        $stmt_count->bindValue(':status', $filter_status, PDO::PARAM_STR);
    }

    $stmt_count->execute();
    $total_services = $stmt_count->fetchColumn();

    // Hitung total halaman
    $total_pages = ceil($total_services / $limit);

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
    <title>Kasir Laundry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* CSS custom untuk lebar kolom */
    </style>
</head>

<body class="bg-gray-100">

<!-- Navigation Bar Start -->
<nav class="flex items-center justify-between bg-white fixed top-0 left-0 right-0 shadow-md z-10">
    <!-- Left Section: Logo and Brand Name -->
    <div class="flex items-center space-x-2 px-5">
        <img src="../../assets/logo.png" alt="Logo" class="h-24 w-24">
        <span class="text-3xl font-semibold text-blue-600">Clean Laundry</span>
    </div>

    <!-- Center Section: Navigation Links -->
    <ul class="hidden md:flex space-x-6 text-gray-700">
        <li class="flex items-center space-x-2">
            <i class='bx bx-cart'></i>
            <a href="dashboard.php" class="hover:text-blue-600  font-medium">Kasir</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-user'></i>
            <a href="pelanggan.php" class="hover:text-blue-600 font-medium ">Riwayat Pelanggan</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-wrench'></i>
            <a href="layanan.php" class="hover:text-blue-600 font-medium">Layanan</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-file'></i>
            <a href="history.php" class="hover:text-blue-600 font-medium text-blue-600">Trx Hari Ini</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-transfer-alt'></i>
            <a href="transaksi.php" class="hover:text-blue-600 font-medium">Transaksi</a>
        </li>
        
        <li class="flex items-center space-x-2">
            <i class='bx bx-log-out-circle'></i>
            <a href="../../logout.php" class="text-red-500 hover:text-red-600 font-medium">Logout</a>
        </li>
    </ul>

    <!-- Profile and Logout Section -->
    <div class="flex items-center space-x-4 px-5">
        <div>
            <span class="text-sm md:text-lg font-semibold text-blue-700">
                Welcome, <?php echo htmlspecialchars($admin_name); ?>!
            </span>
            <span class="block text-xs md:text-sm text-gray-500">
                Role: <?php echo htmlspecialchars($admin_role); ?>
            </span>
        </div>
    </div>
</nav>
<!-- Navigation Bar End -->
<div class="mt-24 p-6">

<!-- Title Section -->
<div class="bg-white p-6 rounded-lg shadow-md">
<div class="flex justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">Transaksi Hari Ini</h2>
</div>

<!-- Filter Data -->
<div class="mb-6 flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-6">
    <!-- Nama Member -->
    <div class="w-full">
        <label for="id_pelanggan" class="block text-sm font-medium text-gray-700">Nama Pelanggan</label>
        <form method="GET" action="history.php">
            <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($filter_name); ?>" placeholder="Cari Nama Layanan" class="w-full p-2 border rounded-lg mb-4" >
    </div>
    
    <!-- Status Member -->
    <div class="w-full">
        <div class="mb-1">
            <label for="status" class="block text-sm font-medium text-gray-700">Status Pesanan</label>
            <select name="status" id="status" class="w-full p-2 border rounded-lg mb-4">
                <option value="" disabled selected class="text-gray-300">Pilih Status</option>
                <option value="1" <?php echo $filter_status == '1' ? 'selected' : ''; ?>>Aktif</option>
                <option value="0" <?php echo $filter_status == '0' ? 'selected' : ''; ?>>Non Aktif</option>
            </select>
        </div>
    </div>

    <div class="w-full">
        <div class="mb-1">
            <label for="status" class="block text-sm font-medium text-gray-700">Status Pembayaran</label>
            <select name="status" id="status" class="w-full p-2 border rounded-lg mb-4">
                <option value="" disabled selected class="text-gray-300">Pilih Status</option>
                <option value="lunas" <?php echo $filter_status == 'lunas' ? 'selected' : ''; ?>>Lunas</option>
                <option value="belum_lunas" <?php echo $filter_status == 'belum_lunas' ? 'selected' : ''; ?>>Belum Lunas</option>
            </select>
        </div>
    </div>

    
</div>

<div class="w-full flex justify-between mb-5">
    <div class="w-16">
        <label for="limit" class="block text-sm font-medium text-gray-700">Tampilkan</label>
        <select name="limit" id="limit" class="w-full p-2 border rounded-lg mb-4" onchange="this.form.submit()">
            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
            <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
        </select>
    </div>
    <div class="flex space-x-4 h-10">
        <!-- Tombol Clear Filter hanya muncul jika filter diterapkan -->
        <?php if (!empty($_GET['service_name']) || !empty($_GET['status'])): ?>
            <a href="history.php" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg text-center flex items-center space-x-2">
                <!-- Ikon Hapus Filter -->
                <i class="bx bx-x"></i>
                <span>Hapus Filter</span>
            </a>
        <?php endif; ?>
        
        <!-- Tombol Filter -->
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg flex items-center space-x-2">
            <!-- Ikon Filter -->
            <i class="bx bx-search"></i>
            <span>Filter</span>
        </button>
    </div>
</div>

</form>
        <!-- Member Data Table -->
        <div class="overflow-x-auto bg-white rounded-lg shadow-sm">
        <table class="w-full table-auto text-left">
    <thead class="bg-gray-200 text-black">
        <tr>
            <th class="py-2 px-4 text-center">Aksi</th>
            <th class="py-2 px-4">Nama Pelanggan</th>
            <th class="py-2 px-4">Layanan</th>
            <th class="py-2 px-4 text-right">Qty</th>
            <th class="py-2 px-4">Tanggal Masuk</th>
            <th class="py-2 px-4">Estimasi Selesai</th>
            <th class="py-2 px-4">Total Bayar</th>
            <th class="py-2 px-4 text-center">Metode Pembayaran</th>
            <th class="py-2 px-4 text-center">Status Pesanan</th>
            <th class="py-2 px-4 text-center">Status Pembayaran</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($services as $index => $service): ?>
            <tr class="border-t">
                <td class="py-4 px-4 flex justify-center items-center">
                    <!-- Ikon Edit -->
                    <a href="javascript:void(0);" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($service)); ?>)" class="text-blue-600 hover:text-blue-800">
                        <i class="bx bx-edit"></i>
                    </a>
                    
                </td>
                <td class="py-2 px-4"><?php echo htmlspecialchars($service['first_name']) . ' ' . htmlspecialchars($service['last_name']); ?></td>
                <td class="py-2 px-4"><?php echo htmlspecialchars($service['service_name']); ?></td>
                <td class="py-2 px-4 text-right"><?php echo htmlspecialchars($service['quantity']); ?></td>
                <td class="py-2 px-4">
                    <?php echo date('j F Y', strtotime($service['tanggal_transaksi'])); ?>
                </td>
                <td class="py-2 px-4">
                    <?php echo date('j F Y', strtotime($service['estimasi_selesai'])); ?>
                </td>
                <td class="py-2 px-4 text-right">Rp <?php echo number_format($service['total_harga'], 0, ',', '.'); ?></td>
                <td class="py-2 px-4 text-center">
                    <?php if ($service['metode_pengiriman'] === 'pickup'): ?>
                        <span class="px-4 py-2 bg-green-600 text-white rounded-full ">Ambil Sendiri</span>
                    <?php elseif ($service['metode_pengiriman'] === 'delivery'): ?>
                        <span class="px-9 py-2 bg-blue-600 text-white rounded-full">Diantar</span>
                    <?php endif; ?>
                </td>
                <td class="py-2 px-4 text-center">
                    <?php if ($service['status_transaksi'] === 'sedang_diproses'): ?>
                        <span class="px-4 py-2 bg-yellow-600 text-white rounded-full ">Sedang Diproses</span>
                    <?php elseif ($service['status_transaksi'] === 'selesai'): ?>
                        <span class="px-9 py-2 bg-green-600 text-white rounded-full">Selesai</span>
                    <?php endif; ?>
                </td>
                <td class="py-2 px-4 text-center">
                    <?php if ($service['status_pembayaran'] === 'belum_lunas'): ?>
                        <span class="px-4 py-2 bg-red-600 text-white rounded-full ">Kas Bon</span>
                    <?php elseif ($service['status_pembayaran'] === 'lunas'): ?>
                        <span class="px-6 py-2 bg-green-600 text-white rounded-full">Lunas</span>
                    <?php endif; ?>

                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

            <!-- End Member Data Table -->
            
        </div>
        <!-- Pagination -->
        <div class="flex justify-between items-center mt-6">
                <div class="flex space-x-3">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&limit=<?php echo $limit; ?>" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">First</a>
                        <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Previous</a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Next</a>
                        <a href="?page=<?php echo $total_pages; ?>&limit=<?php echo $limit; ?>" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Last</a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- End Pagination -->
    </div>
    </div>
    <!-- Main Content End -->
