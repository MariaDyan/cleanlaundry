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
         $first_name = $_POST['first_name'];
         $last_name = $_POST['last_name'];
         $phone = $_POST['phone'];
         $email = $_POST['email'];
         $address = $_POST['address'];
         $status = $_POST['status'];
         
         // Query untuk menambahkan kamar baru ke dalam database
         $stmt = $pdo->prepare("INSERT INTO member (first_name, last_name, phone, email, address, status) 
                                 VALUES (:first_name, :last_name, :phone, :email, :address, :status)");
         $stmt->execute([
               'first_name' => $first_name,
               'last_name' => $last_name,
               'phone' => $phone,
               'email' => $email,
               'address' => $address,
               'status' => $status,
         ]);

         $_SESSION['toast_message'] = "Data berhasil ditambahkan!";
         header('Location: ../../../page/kasir/dashboard.php');
         exit();

         // Redirect ke halaman kamar setelah berhasil menambahkan
         header('Location: ../../../page/kasir/dashboard.php');
         exit();
      }

   } catch (PDOException $e) {
      echo 'Connection failed: ' . $e->getMessage();
      exit();
   }
   ?>


   ?>