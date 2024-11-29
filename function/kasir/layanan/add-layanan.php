<?php
   session_start();

   // Cek apakah pengguna sudah login
   if (!isset($_SESSION['username'])) {
      header('Location: index.php'); // Jika belum login, arahkan ke halaman login
      exit();
   }

   // Cek role pengguna, jika bukan admin, alihkan ke halaman lain
   if ($_SESSION['role'] !== 'kasir') {
      header('Location: ../../page/kasir/dashboard.php'); // Jika bukan admin, arahkan ke dashboard user atau halaman lain
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
         $service_name = $_POST['service_name'];
         $price_per_unit = $_POST['price_per_unit'];
         $description = $_POST['description'];
         $status = $_POST['status'];
         
         // Query untuk menambahkan kamar baru ke dalam database
         $stmt = $pdo->prepare("INSERT INTO services (service_name, price_per_unit, description, status) 
                                 VALUES (:service_name, :price_per_unit, :description, :status)");
         $stmt->execute([
               'service_name' => $service_name,
               'price_per_unit' => $price_per_unit,
               'description' => $description,
               'status' => $status,
         ]);

         $_SESSION['toast_message'] = "Data berhasil ditambahkan!";
         header('Location: ../../../page/kasir/layanan.php');
         exit();

         // Redirect ke halaman kamar setelah berhasil menambahkan
         header('Location: ../../../page/kasir/layanan.php');
         exit();
      }

   } catch (PDOException $e) {
      echo 'Connection failed: ' . $e->getMessage();
      exit();
   }
   ?>


   ?>