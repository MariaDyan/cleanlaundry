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
    $admin_role = ucfirst($admin['role'] ?? 'Kasir'); 
    $admin_photo = $admin['photo'] ?? 'default.png'; 

   //  Data Analytic
      // Query untuk Pendapatan Hari Ini
      $stmt_income = $pdo->prepare("SELECT SUM(amount) AS total_income FROM transactions WHERE DATE(transaction_date) = CURDATE()");
      $stmt_income->execute();
      $total_income = $stmt_income->fetch(PDO::FETCH_ASSOC)['total_income'] ?? 0;

      // Query untuk Total Transaksi Hari Ini
      $stmt_transactions = $pdo->prepare("SELECT COUNT(*) AS total_transactions FROM transactions WHERE DATE(transaction_date) = CURDATE()");
      $stmt_transactions->execute();
      $total_transactions = $stmt_transactions->fetch(PDO::FETCH_ASSOC)['total_transactions'] ?? 0;

      // Query untuk Total Member
      $stmt_members = $pdo->prepare("SELECT COUNT(*) AS total_members FROM member WHERE status = 'member'");
      $stmt_members->execute();
      $total_members = $stmt_members->fetch(PDO::FETCH_ASSOC)['total_members'] ?? 0;


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
                grid-template-columns: 1fr 4fr ; /* Kolom tengah lebih lebar */
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
            <a href="kasir.php" class="hover:text-blue-600 text-blue-600 font-medium">Kasir</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-user'></i>
            <a href="pelanggan.php" class="hover:text-blue-600 font-medium">Riwayat Pelanggan</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-wrench'></i>
            <a href="layanan.php" class="hover:text-blue-600 font-medium">Layanan</a>
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

<!-- Main Content -->
<div class="mt-24 p-6 custom-grid">
      <!-- Kiri: Card Pendapatan Hari Ini & Total Transaksi -->
      <div class="space-y-6">
      <!-- Card Pendapatan Hari Ini -->
      <div class="bg-green-500 text-white p-6 rounded-lg shadow-md relative">
         <i class="bx bx-money text-3xl absolute top-3 right-3"></i> <!-- Ikon di pojok kanan atas -->
         <h2 class="text-lg font-semibold">Pendapatan Hari Ini</h2>
         <p class="text-3xl font-bold">Rp <?php echo number_format($total_income, 0, ',', '.'); ?></p>
      </div>

      <!-- Card Total Transaksi Hari Ini -->
      <div class="bg-blue-500 text-white p-6 rounded-lg shadow-md relative">
         <i class="bx bx-transfer text-3xl absolute top-3 right-3"></i> <!-- Ikon di pojok kanan atas -->
         <h2 class="text-lg font-semibold">Total Transaksi Hari Ini</h2>
         <p class="text-3xl font-bold"><?php echo htmlspecialchars($total_transactions); ?></p>
      </div>

            <div class="space-y-4">
      <!-- Card Total Member -->
      <div class="bg-yellow-500 text-white p-6 rounded-lg shadow-md relative">
         <i class="bx bx-group text-3xl absolute top-3 right-3"></i> <!-- Ikon di pojok kanan atas -->
         <h2 class="text-lg font-semibold">Total Member</h2>
         <p class="text-3xl font-bold"><?php echo htmlspecialchars($total_members); ?></p>
      </div>

      <!-- Card Belum Lunas -->
      <div class="bg-red-500 text-white p-6 rounded-lg shadow-md relative">
         <i class="bx bx-x-circle text-3xl absolute top-3 right-3"></i> <!-- Ikon di pojok kanan atas -->
         <h2 class="text-lg font-semibold">Belum Lunas</h2>
         <p class="text-3xl font-bold">0</p>
      </div>
   </div>
   </div>


      <!-- Tengah: Form Input Kasir -->
      <div class="bg-white p-6 rounded-lg shadow-md">
         <div class="mb-6 flex justify-between items-center">
            <h2 class="text-3xl font-semibold mt-1">Kasir</h2>
            <button type="submit" class="w-52 bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 flex items-center justify-center space-x-2">
               <i class="bx bx-user-plus"></i> <!-- Ikon Tambah Member -->
               <span>Tambah Pelanggan</span>
            </button>

         </div>
         <form action="process_transaction.php" method="POST">
            <div class="space-y-4">
               <!-- Dropdown Pelanggan -->
               <div class="w-full">
                  <label for="service" class="block text-sm font-medium text-gray-700">Layanan</label>
                  <select id="service" name="service" class="w-full border rounded p-2 text-black">
                     <option class="text-gray-500" value="">Pilih Layanan</option>
                     <?php
                     $conn = new mysqli('localhost', 'root', '', 'db_laundry');

                     if ($conn->connect_error) {
                           die("Koneksi gagal: " . $conn->connect_error);
                     }

                     $sql = "SELECT id, first_name, last_name, status FROM member";
                     $result = $conn->query($sql);

                     if ($result->num_rows > 0) {
                           while ($row = $result->fetch_assoc()) {
                              $statusText = ($row['status'] === 'non_member') ? 'Non Member' : 'Member';
                              echo "<option class='text-black' value='" . htmlspecialchars($row['id']) . "'>"
                                 . htmlspecialchars($row['first_name']) . " "
                                 . htmlspecialchars($row['last_name']) . " - " . $statusText
                                 . "</option>";
                           }
                     } else {
                           echo "<option class='text-gray-500' value=''>Tidak ada pelanggan. Daftarkan pelanggan dahulu</option>";
                     }
                     $conn->close();
                     ?>
                  </select>
               </div>

                  <!-- Dropdown Pelanggan End -->
                  <!-- Dropdown Layanan -->
               <div class="flex space-x-4 col 2">
                  <div class="w-full">
                     <label for="service" class="block text-sm font-medium text-gray-700">Layanan</label>
                     <select id="service" name="service" class="w-full border rounded p-2 text-black">
                        <option class="text-gray-500" value="">Pilih Layanan</option>
                        <?php
                        // Koneksi ke database
                        $conn = new mysqli('localhost', 'root', '', 'db_laundry');

                        // Cek koneksi
                        if ($conn->connect_error) {
                              die("Koneksi gagal: " . $conn->connect_error);
                        }

                        // Query untuk mengambil layanan dengan status aktif
                        $sql = "SELECT * FROM services WHERE status = '1'";
                        $result = $conn->query($sql);

                        // Loop untuk menampilkan opsi dalam dropdown
                        if ($result->num_rows > 0) {
                              while ($row = $result->fetch_assoc()) {
                                 echo "<option 
                                          class='text-black' 
                                          value='" 
                                             . htmlspecialchars($row['id']) . "'>" 
                                             . htmlspecialchars($row['service_name']) . " -  Rp"
                                             . htmlspecialchars($row['price_per_unit'])
                                          . "</option>";
                              }
                        } else {
                              echo "<option class='text-gray-500' value=''>Tidak ada layanan aktif</option>";
                        }

                        // Tutup koneksi
                        $conn->close();
                        ?>
                     </select>
                  </div>
                  <!-- Dropdown Layanan End -->
                  <div class="w-full">
                     <label for="quantity" class="block text-sm font-medium text-gray-700">Jumlah:</label>
                     <input type="number" name="quantity" id="quantity" class="w-full p-2 border rounded-lg " required>
                  </div>
               </div>

               <div class="flex space-x-4 col-2">
                  <div class="w-full mb-5">
                     <label for="date" class="block text-sm font-medium text-gray-700">Tanggal Masuk:</label>
                     <input type="date" name="date" id="date" class="w-full p-2 border rounded-lg " required> 
                  </div>
                  <div class="w-full mb-5">
                     <label for="date" class="block text-sm font-medium text-gray-700">Estimasi Selesai:</label>
                     <input type="date" name="date" id="date" class="w-full p-2 border rounded-lg " required> 
                  </div>
               </div>
               <div class="w-full mb-5 ">
                  <label for="note" class="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
                  <textarea name="note" id="note" class="w-full p-2 border rounded-lg "></textarea>
               </div>
               
               <div class="flex justify-end">
                  <div class="w-1/2 mb-5">
                     <label for="amount" class="block text-xl font-semibold text-gray-700">Jumlah Total:</label>
                     <input type="number" name="amount" id="amount" class="w-full p-2 border rounded-lg" disabled required>
                  </div>
               </div>
            </div>
               
            <div class="flex justify-between">
               <div>
                     <a href="dashboard.php" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg text-center flex items-center space-x-2">
                        <i class="bx bx-x"></i>
                        <span>Batal</span>
                    </a>
               </div>
               <div>
                  <button type="submit" class=" w-36 bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700">
                     Submit
                  </button>
                  <button type="submit" class=" w-36 bg-green-600 text-white p-2 rounded-lg hover:bg-green-700">
                     Bayar
                  </button>
               </div>
            </div>

         </form>
      </div>

</div>

</body>
</html>
