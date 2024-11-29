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

        // Query untuk menampilkan data service dengan filter
        $sql = "SELECT * FROM services WHERE 1";

        // Filter berdasarkan nama
        if ($filter_name != '') {
            $sql .= " AND (service_name LIKE :service_name )";
        }

        // Filter berdasarkan status
        if ($filter_status != '') {
            $sql .= " AND status = :status";
        }

        // Menambahkan LIMIT dan OFFSET untuk pagination
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt_service = $pdo->prepare($sql);

        // Bind parameter filter dan pagination
        if ($filter_name != '') {
            $stmt_service->bindValue(':service_name', '%' . $filter_name . '%');
        }
        if ($filter_status != '') {
            $stmt_service->bindValue(':status', $filter_status);
        }
        $stmt_service->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt_service->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt_service->execute();
        $service = $stmt_service->fetchAll(PDO::FETCH_ASSOC);

        // Hitung total jumlah member setelah filter
        $sql_count = "SELECT COUNT(*) FROM services WHERE 1";
        if ($filter_name != '') {
            $sql_count .= " AND (service_name LIKE :service_name)";
        }
        if ($filter_status != '') {
            $sql_count .= " AND status = :status";
        }
        

        $stmt_count = $pdo->prepare($sql_count);
        if ($filter_name != '') {
            $stmt_count->bindValue(':service_name', '%' . $filter_name . '%');
        }
        if ($filter_status != '') {
            $stmt_count->bindValue(':status', $filter_status);
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
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
        <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
        <style>
            body {
                font-family: 'Poppins', sans-serif;
            }

            /* CSS custom untuk lebar kolom */
            @media (min-width: 768px) {
                .custom-grid {
                    display: grid;
                    grid-template-columns: 1fr 3fr 1fr; /* Kolom tengah lebih lebar */
                    gap: 20px; /* Memberikan jarak antara kolom */
                }
            }
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
                <a href="layanan.php" class="hover:text-blue-600 font-medium text-blue-600">Layanan</a>
            </li>
            <li class="flex items-center space-x-2">
                <i class='bx bx-file'></i>
                <a href="history.php" class="hover:text-blue-600 font-medium">Trx Hari Ini</a>
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
    <!-- Main Content Start -->
    <div class="mt-24 p-6">

        <!-- Title Section -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Daftar Layanan</h2>
            <button onclick="toggleModal('modal-add-service')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg flex items-center space-x-2">
            <i class="bx bx-plus"></i>
            <span>Tambah Layanan</span>
        </div>

        <!-- Filter Data -->
        <div class="mb-6 flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-6">
            <!-- Nama Member -->
            <div class="w-full">
                <label for="service_name" class="block text-sm font-medium text-gray-700">Nama Layanan</label>
                <form method="GET" action="layanan.php">
                    <input type="text" name="service_name" id="service_name" value="<?php echo htmlspecialchars($filter_name); ?>" placeholder="Cari Nama Layanan" class="w-full p-2 border rounded-lg mb-4" >
            </div>
            
            <!-- Status Member -->
            <div class="w-full">
                <div class="mb-1">
                    <label for="status" class="block text-sm font-medium text-gray-700">Status Layanan</label>
                    <select name="status" id="status" class="w-full p-2 border rounded-lg mb-4">
                        <option value="" disabled selected class="text-gray-300">Pilih Status</option>
                        <option value="1" <?php echo $filter_status == '1' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="0" <?php echo $filter_status == '0' ? 'selected' : ''; ?>>Non Aktif</option>
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
                    <a href="layanan.php" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg text-center flex items-center space-x-2">
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
        <!-- End Filter Data -->

        <!-- Member Data Table -->
        <div class="overflow-x-auto bg-white rounded-lg shadow-sm">
            <table class="w-full table-auto text-left">
                <thead class="bg-gray-200 text-black">
                    <tr>
                        <th class="py-2 px-4 text-center">Aksi</th>
                        <th class="py-2 px-4">Nama</th>
                        <th class="py-2 px-4 text-right">Harga Per Kilo</th>
                        <th class="py-2 px-4">Deskripsi</th>
                        <th class="py-2 px-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($service as $index => $services): ?>
                    <tr class="border-t">
                        <td class="py-2 px-4 flex justify-center items-center">
                            <!-- Ikon Edit -->
                            <a href="edit_pelanggan?id=<?php echo $services['id']; ?>" class="text-blue-600 hover:text-blue-800">
                            <i class="bx bx-edit"></i> 
                            </a> 
                            <!-- Spasi antara ikon -->
                            <span class="mx-2"></span>
                            <!-- Ikon Hapus -->
                            <a href="../../function/kasir/layanan/delete-layanan.php?id=<?php echo $services['id']; ?>" class="text-red-600 hover:text-red-800">
                            <i class="bx bx-trash"></i> 
                            </a>
                        </td>

                        <td class="py-2 px-4"><?php echo htmlspecialchars($services['service_name']); ?></td>
                        <td class="py-2 px-4 text-right">Rp.<?php echo htmlspecialchars($services['price_per_unit']); ?></td>
                        <td class="py-2 px-4"><?php echo htmlspecialchars($services['description']); ?></td>
                        <td class="py-2 px-4 capitalize">
                            <?php 
                                $status = htmlspecialchars($services['status']);
                                if ($status === '1') {
                                    echo 'Aktif';
                                } else {
                                    echo 'Non Aktif';
                                }
                            ?>
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

    <!-- Modal -->
    <div id="modal-add-service" class="fixed inset-0 hidden bg-gray-800 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-8/12 ">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-gray-800">Tambah Layanan Baru</h2>
                <button onclick="toggleModal('modal-add-service')" class="text-gray-400 hover:text-gray-600">
                    <i class="bx bx-x text-2xl"></i>
                </button>
            </div>
            <form method="POST" action="../../function/kasir/layanan/add-layanan.php">
                <div class="mb-4">
                    <label for="service_name" class="block text-sm font-medium text-gray-700">Nama Layanan</label>
                    <input type="text" name="service_name" id="service_name" required
                        class="w-full p-2 border rounded-lg" placeholder="Masukkan Nama Layanan">
                </div>
                <div class="mb-4">
                    <label for="price_per_unit" class="block text-sm font-medium text-gray-700">Harga Per Kilo</label>
                    <input type="number" name="price_per_unit" id="price_per_unit" required
                        class="w-full p-2 border rounded-lg" placeholder="Masukkan Harga">
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                    <textarea name="description" id="description" rows="4" required
                        class="w-full p-2 border rounded-lg" placeholder="Masukkan Deskripsi Layanan"></textarea>
                </div>
                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" required
                        class="w-full p-2 border rounded-lg">
                        <option value="1">Aktif</option>
                        <option value="0">Non Aktif</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="toggleModal('modal-add-service')" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    </body>
    </html>

    <script>
        // Script Open Close Modal
        function toggleModal(modalID) {
            const modal = document.getElementById(modalID);
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
            } else {
                modal.classList.add('hidden');
            }
        }
        // Script Open Close Modal

    </script>
