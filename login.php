<?php
session_start();

/* -----------------------------------------
    LOGIN HANDLER
----------------------------------------- */
$error = "";
$loginAttempts = $_SESSION["login_attempts"] ?? 0;
$lockoutTime = $_SESSION["lockout_time"] ?? 0;

// Check if user is locked out
if ($lockoutTime > time()) {
    $remainingTime = ceil(($lockoutTime - time()) / 60);
    $error = "❌ Akun terkunci. Coba lagi dalam {$remainingTime} menit";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    // Simple credentials - ganti dengan database query untuk production
    $validUsername = "admin";
    $validPassword = "password123"; // Hash ini dengan password_hash() di production

    if ($username === $validUsername && $password === $validPassword) {
        // Login berhasil
        $_SESSION["user_id"] = 1;
        $_SESSION["username"] = $username;
        $_SESSION["login_time"] = time();
        $_SESSION["login_attempts"] = 0;
        unset($_SESSION["lockout_time"]);

        header("Location: index.php");
        exit();
    } else {
        // Login gagal
        $loginAttempts++;
        $_SESSION["login_attempts"] = $loginAttempts;

        if ($loginAttempts >= 5) {
            // Lock account for 15 minutes
            $_SESSION["lockout_time"] = time() + (15 * 60);
            $error = "❌ Terlalu banyak percobaan. Akun terkunci selama 15 menit";
        } else {
            $remainingAttempts = 5 - $loginAttempts;
            $error = "❌ Username atau password salah. Sisa percobaan: {$remainingAttempts}";
        }
    }
}

// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Login - Bot Dashboard</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>

    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-8 text-center text-white">
                <div class="text-5xl mb-3">🤖</div>
                <h1 class="text-3xl font-bold">Bot Dashboard</h1>
                <p class="text-purple-100 mt-2">WhatsApp Bot Management System</p>
            </div>

            <!-- Form -->
            <div class="p-8">
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                        <p class="text-red-700 font-semibold"><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <!-- Username -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">👤 Username</label>
                        <input type="text" name="username" placeholder="Masukkan username"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-purple-500 transition"
                            required autofocus>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">🔐 Password</label>
                        <input type="password" name="password" placeholder="Masukkan password"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-purple-500 transition"
                            required>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="w-4 h-4 text-purple-600 rounded">
                        <label for="remember" class="ml-2 text-sm text-gray-600">Ingat saya</label>
                    </div>

                    <!-- Login Button -->
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold py-3 px-4 rounded-lg transition transform hover:scale-105 active:scale-95">
                        🚀 Login
                    </button>
                </form>

                <!-- Footer -->
                <div class="mt-6 text-center text-sm text-gray-500">
                    <p>Demo Credentials:</p>
                    <p class="font-mono text-gray-600 mt-1">Username: <strong>admin</strong></p>
                    <p class="font-mono text-gray-600">Password: <strong>password123</strong></p>
                </div>
            </div>
        </div>

        <!-- Footer Text -->
        <div class="text-center mt-6 text-white text-sm">
            <p>© 2025 Bot Dashboard. All rights reserved.</p>
        </div>
    </div>

</body>

</html>