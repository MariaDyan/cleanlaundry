<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard Laundry</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Centering the login form -->
    <div class="flex items-center justify-center min-h-screen">

        <!-- Card Container -->
        <div class="p-8 w-full max-w-md">

            <!-- Logo -->
            <div class="text-center mb-6">
                <img src="assets/logo.png" alt="Laundry Logo" class="w-32 h-32 mx-auto">
                <h1 class="text-md font-semibold text-blue-600">CLEAN LAUNDRY</h1>
            </div>

            <!-- Login Form -->
            <form action="login_process.php" method="POST">

                <!-- Email Input -->
                <div class="mb-2">
                    <input type="text" id="username" name="username" required placeholder="Username" class="w-full  px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Password Input -->
                <div class="mb-4">
                    <input type="password" id="password" name="password" placeholder="Password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Login Button -->
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Login
                </button>
            </form>
        </div>

    </div>

</body>
</html>
