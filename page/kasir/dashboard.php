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
         $stmt_income = $pdo->prepare("SELECT SUM(total_harga) AS total_income FROM transactions WHERE status_pembayaran = 'lunas' AND DATE(tanggal_transaksi) = CURDATE()");
         $stmt_income->execute();
         $total_income = $stmt_income->fetch(PDO::FETCH_ASSOC)['total_income'] ?? 0;

         // Query untuk Total Transaksi Hari Ini
         $stmt_transactions = $pdo->prepare("SELECT COUNT(*) AS total_transactions FROM transactions WHERE DATE(tanggal_transaksi) = CURDATE()");
         $stmt_transactions->execute();
         $total_transactions = $stmt_transactions->fetch(PDO::FETCH_ASSOC)['total_transactions'] ?? 0;

         // Query untuk Total Transaksi Hari Ini
         $stmt_lunas = $pdo->prepare("SELECT COUNT(*) AS belum_lunas FROM transactions WHERE status_pembayaran = 'belum_lunas'");
         $stmt_lunas->execute();
         $belum_lunas = $stmt_lunas->fetch(PDO::FETCH_ASSOC)['belum_lunas'] ?? 0;
         
         
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
                  grid-template-columns: 250px 4fr ; /* Kolom tengah lebih lebar */
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

         <!-- Card Total Transaksi Hari Ini -->
         <div class="bg-blue-500 text-white p-6 rounded-lg shadow-md relative">
            <i class="bx bx-transfer text-3xl absolute top-3 right-3"></i> <!-- Ikon di pojok kanan atas -->
            <h2 class="text-lg font-semibold">Transaksi Hari Ini</h2>
            <p class="text-2xl font-bold"><?php echo htmlspecialchars($total_transactions); ?> <span class="text-xl">transaksi</span></p>
         </div>

         <!-- Card Belum Lunas -->
         <div class="bg-red-500 text-white p-6 rounded-lg shadow-md relative">
            <i class="bx bx-x-circle text-3xl absolute top-3 right-3"></i> <!-- Ikon di pojok kanan atas -->
            <h2 class="text-lg font-semibold">Belum Lunas</h2>
            <p class="text-2xl font-bold"><?php echo htmlspecialchars($belum_lunas); ?></p>
         </div>
         
         <!-- Card Pendapatan Hari Ini -->
         <div class="bg-green-500 text-white p-6 rounded-lg shadow-md relative">
            <i class="bx bx-money text-3xl absolute top-3 right-3"></i> <!-- Ikon di pojok kanan atas -->
            <h2 class="text-lg font-semibold">Pendapatan Hari Ini</h2>
            <p class="text-2xl font-bold">Rp.<?php echo number_format($total_income, 0, ',', '.'); ?></p>
         </div>

         <div class="space-y-4">
         <!-- Card Total Member -->
         <div class="bg-yellow-500 text-white p-6 rounded-lg shadow-md relative">
            <i class="bx bx-group text-3xl absolute top-3 right-3"></i> <!-- Ikon di pojok kanan atas -->
            <h2 class="text-lg font-semibold">Total Member</h2>
            <p class="text-4xl font-bold"><?php echo htmlspecialchars($total_members); ?></p>
         </div>

      </div>
      </div>


         <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="mb-6 flex justify-between items-center">
               <h2 class="text-3xl font-semibold mt-1">Kasir</h2>
               <button onclick="toggleModal('modal-add-customer')" type="submit" class="w-52 bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 flex items-center justify-center space-x-2">
                  <i class="bx bx-user-plus"></i> <!-- Ikon Tambah Member -->
                  <span>Tambah Pelanggan</span>
               </button>

            </div>
            <form action="../../function/kasir/transaksi/add-transaksi.php" method="POST">
               <div class="space-y-4">
                  <!-- Dropdown Pelanggan -->
                  <div class="flex col-2 space-x-4">
                     <div class="w-full ">
                        <label for="id_pelanggan" class="block text-sm font-medium text-gray-700">Pelanggan</label>
                        <select id="id_pelanggan" name="id_pelanggan" class="w-full border rounded p-2 text-black" required>
                           <option class="text-black-200" value="">Pilih Pelanggan</option>
                           <?php
                           $conn = new mysqli('localhost', 'root', '', 'db_laundry');

                           if ($conn->connect_error) {
                                 die("Koneksi gagal: " . $conn->connect_error);
                           }

                           $sql = "SELECT id, first_name, last_name, address, status FROM member";
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
                                 echo "<option class='text-black-200' value=''>Tidak ada pelanggan. Daftarkan pelanggan dahulu</option>";
                           }
                           $conn->close();
                           ?>
                        </select>
                     </div>
                  </div>
                  

                     <!-- Dropdown Pelanggan End -->
                     <!-- Dropdown Layanan -->
                     <div id="services-container" >
                        <div class="flex space-x-4 col-2 service-entry mb-2">
                           <div class="w-full">
                              <label for="layanan_id" class="block text-sm font-medium text-gray-700">Layanan</label>
                              <select name="layanan_id[]" class="w-full border rounded p-2 text-black" id="service-select">
                                 <option class="text-gray-200" value="">Pilih Layanan</option>                                    <?php
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
                                             echo "<option class='text-black' value='" 
                                                . htmlspecialchars($row['id']) . "'>" 
                                                . htmlspecialchars($row['service_name']) . " - Rp" 
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

                           <div class="w-10/12">
                                 <label for="quantity" class="block text-sm font-medium text-gray-700">Jumlah</label>
                                 <input type="decimal" name="quantity" class="w-full p-2 border rounded-lg" placeholder="Masukkan Jumlah" required>
                           </div>

                           <div class="flex items-end">
                                 <button type="button" class="add-service bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700">
                                    <i class="bx bx-plus"></i>
                                 </button>
                                 <button type="button" class="remove-service bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 ml-2">
                                    <i class="bx bx-trash"></i>
                                 </button>
                           </div>
                        </div>
                     </div>

                  <div class="flex space-x-4 col-2">
                     <div class="w-full mb-1">
                        <label for="tanggal_transaksi" class="block text-sm font-medium text-gray-700">Tanggal Masuk</label>
                        <input type="date" name="tanggal_transaksi" id="date" class="w-full p-2 border rounded-lg " required> 
                     </div>
                     <div class="w-full mb-1">
                        <label for="durasi_layanan" class="block text-sm font-medium text-gray-700">Durasi Layanan</label>
                        <select name="durasi_layanan" id="durasi_layanan" class="w-full p-2 border rounded-lg " required>
                              <option class="text-gray-200" value="">Pilih Durasi</option>
                              <option value="0">Express (1 jam)</option>
                              <option value="1">Kilat (1 hari)</option>
                              <option value="2">Reguler (2 hari)</option>
                        </select>
                     </div>
                  </div>

                  <!-- Dropdown Metode Pengantaran -->
                  <div class="flex justify-end">
                     <div class="w-full">
                        <label for="metode_pengiriman" class="block text-sm font-medium text-gray-700">Metode Pengantaran</label>
                        <select id="metode_pengiriman" name="metode_pengiriman" class="w-full border rounded p-2 text-black">
                           <option class="text-gray-200" value="">Pilih Metode Pengantaran</option>
                           <option value="pickup" class="text-black">Ambil Sendiri (Gratis)</option>
                           <option value="delivery" class="text-black">Antar (Tambahan Rp.5000)</option>
                        </select>
                     </div>
                  </div>
                  <!-- Dropdown Metode Pengantaran -->

                  <div class="w-full mb-5 ">
                     <label for="catatan" class="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
                     <textarea name="catatan" id="catatan" class="w-full p-2 border rounded-lg "></textarea>
                  </div>
                  
                  <div class="flex justify-end">
                     <div class="w-1/2 mb-5">
                        <label for="total_harga" class="block text-2xl font-bold text-gray-700">Total</label>
                        <div class="flex items-center space-x-2">
                              <span class="text-xl font-bold text-gray-700">Rp.</span>
                              <input type="text" name="total_harga" id="total_harga" class="w-full p-2 border rounded-lg text-xl" value="" disabled oninput="formatPrice(event)" />
                        </div>
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
                     <button type="submit" class=" w-36 bg-green-600 text-white p-2 rounded-lg hover:bg-green-700">
                        <i class="bx bx-money"></i>
                        <span class="text-center"> Bayar</span>
                     </button>
                  </div>
               </div>

            </form>
         </div>

   </div>

      <!-- Modal Tambah Pelanggan -->
      <div id="modal-add-customer" class="fixed inset-0 hidden bg-gray-800 bg-opacity-75 flex items-center justify-center z-50">
         <div class="bg-white rounded-lg shadow-lg p-6 w-8/12 ">
               <div class="flex justify-between items-center mb-4">
                  <h2 class="text-2xl font-semibold text-gray-800">Tambah Pelanggan Baru</h2>
                  <button onclick="toggleModal('modal-add-customer')" class="text-gray-400 hover:text-gray-600">
                     <i class="bx bx-x text-2xl"></i>
                  </button>
               </div>
               <form method="POST" action="../../function/kasir/pelanggan/add-pelanggan.php">
                  <div class="flex cols-2 gap-4">
                     <div class="mb-4 w-full">
                        <label for="first_name" class="block text-sm font-medium text-gray-700">Nama Depan</label>
                        <input type="text" name="first_name" id="first_name" required
                              class="w-full p-2 border rounded-lg" placeholder="Masukkan Nama Depan">
                     </div>
                     <div class="mb-4 w-full">
                        <label for="last_name" class="block text-sm font-medium text-gray-700">Nama Belakang</label>
                        <input type="text" name="last_name" id="last_name" required
                           class="w-full p-2 border rounded-lg" placeholder="Masukkan Nama Belakang">
                     </div>
                  </div>
                  
                  <div class="mb-4">
                     <label for="phone" class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                     <input type="number" name="phone" id="phone" required
                           class="w-full p-2 border rounded-lg" placeholder="Masukkan Nomor Telepon">
                  </div>

                  <div class="mb-4">
                     <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                     <input type="text" name="email" id="email" required
                           class="w-full p-2 border rounded-lg" placeholder="Masukkan Email">
                  </div>

                  <div class="mb-4">
                     <label for="address" class="block text-sm font-medium text-gray-700">Alamat</label>
                     <textarea name="address" id="address" rows="4" required
                           class="w-full p-2 border rounded-lg" placeholder="Masukkan Alamat"></textarea>
                  </div>
                  <div class="mb-4">
                     <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                     <select name="status" id="status" required
                           class="w-full p-2 border rounded-lg">
                           <option class="text-black" value="">Pilih Status</option>
                           <option value="member">Member</option>
                           <option value="non_member">Non Member</option>
                     </select>
                  </div>
                  <div class="flex justify-end space-x-4">
                     <button type="button" onclick="toggleModal('modal-add-customer')" class="px-4 py-2 bg-red-600 rounded-lg hover:bg-red-700 text-white">
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

<script>
function toggleModal(modalID) {
    const modal = document.getElementById(modalID);
    if (modal.classList.contains('hidden')) {
        modal.classList.remove('hidden'); // Buka modal
    } else {
        modal.classList.add('hidden'); // Tutup modal
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('services-container');
    const deliveryMethodSelect = document.getElementById('metode_pengiriman');
    const totalAmountInput = document.getElementById('total_harga');
    const durationSelect = document.getElementById('durasi_layanan');

    // Fungsi untuk menghitung total harga
    function calculateTotal() {
        let total = 0;

        const serviceEntries = container.querySelectorAll('.service-entry');
        serviceEntries.forEach(entry => {
            const quantityInput = entry.querySelector('input[name="quantity"]');
            const serviceSelect = entry.querySelector('select[name="layanan_id[]"]');
            const pricePerUnit = parseInt(serviceSelect.options[serviceSelect.selectedIndex].textContent.split('- Rp')[1].replace(/\./g, '')) || 0;
            const quantity = parseFloat(quantityInput.value) || 0;

            // Tambahkan harga layanan * jumlah
            total += pricePerUnit * quantity;
        });

        // Tambah biaya pengiriman jika metode pengiriman adalah 'delivery'
        const deliveryFee = deliveryMethodSelect.value === 'delivery' ? 5000 : 0;
        total += deliveryFee;

        // Tambahkan biaya durasi
        let durationFee = 0;
         if (durationSelect.value === "0") {
            durationFee = 5000;
         } else if (durationSelect.value === "1") {
            durationFee = 2500;
         } else if (durationSelect.value === "2") {
            durationFee = 0;
         }

         total += durationFee;


        // Update total harga
        totalAmountInput.value = total;
    }

    // Event listener untuk ketika layanan ditambahkan, dihapus, atau kuantitas diubah
    container.addEventListener('click', (e) => {
        // Menambah layanan baru
        if (e.target.closest('.add-service')) {
            const serviceEntry = e.target.closest('.service-entry');
            const newEntry = serviceEntry.cloneNode(true);

            // Reset nilai input pada entri baru
            newEntry.querySelectorAll('select, input').forEach((input) => input.value = '');
            container.appendChild(newEntry);

            calculateTotal();
        }

        // Menghapus layanan
        if (e.target.closest('.remove-service')) {
            const serviceEntry = e.target.closest('.service-entry');
            serviceEntry.remove();
            calculateTotal();
        }
    });

    // Event listener untuk perubahan metode pengiriman
    deliveryMethodSelect.addEventListener('change', () => {
        calculateTotal();
    });

    // Event listener untuk perubahan durasi
    durationSelect.addEventListener('change', () => {
        calculateTotal();
    });

    // Event listener untuk perubahan jumlah (quantity)
    container.addEventListener('change', (e) => {
        if (e.target.closest('input[name="quantity"]') || e.target.closest('select[name="layanan_id[]"]')) {
            calculateTotal();
        }
    });

    // Event listener untuk menekan Enter
    container.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault(); // Mencegah form dari submit
            calculateTotal(); // Hitung total ketika Enter ditekan
        }
    });

    // Hitung total saat halaman dimuat pertama kali
    calculateTotal();
});


</script>



   </html>
