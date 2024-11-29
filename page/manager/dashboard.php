<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

// Cek role pengguna (hanya kasir diizinkan)
if ($_SESSION['role'] !== 'admin') {
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
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
            <i class='bx bx-menu-alt-left'></i>
            <a href="dashboard.php" class="hover:text-blue-600 font-medium text-blue-600">Dashboard</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-wrench'></i>
            <a href="pelanggan.php" class="hover:text-blue-600 font-medium ">Layanan</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-user'></i>
            <a href="layanan.php" class="hover:text-blue-600 font-medium">Member</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-transfer-alt'></i>
            <a href="transaksi.php" class="hover:text-blue-600 font-medium">Transaksi</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-money'></i>
            <a href="transaksi.php" class="hover:text-blue-600 font-medium">Keuangan</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-box'></i>
            <a href="transaksi.php" class="hover:text-blue-600 font-medium">Stok</a>
        </li>
        <li class="flex items-center space-x-2">
            <i class='bx bx-user-plus'></i>
            <a href="transaksi.php" class="hover:text-blue-600 font-medium">Akun</a>
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