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
    header("Location: dashboard.php");
    exit();
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Login - Bot Dashboard</title>
</head>

<body>

    <section class="bg-white  min-h-screen flex items-center justify-center">
        <div class="container flex items-center justify-center px-6 mx-auto">

            <form method="POST" class="w-full max-w-md">

                <div class="text-center">
                    <i class="fa-solid fa-dice-d20 text-blue-500 text-3xl"></i>
                    <h1 class="mt-3 text-2xl font-semibold text-gray-800 capitalize sm:text-3xl ">Sign In</h1>
                    <p class="mt-1 text-gray-500 ">Bot Dashboard Management</p>
                </div>

                <?php if (isset($error) && $error): ?>
                    <div class="mt-6 flex w-full max-w-sm mx-auto overflow-hidden bg-white rounded-lg shadow-md ">
                        <div class="flex items-center justify-center w-12 bg-red-500">
                            <svg class="w-6 h-6 text-white fill-current" viewBox="0 0 40 40"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M20 3.36667C10.8167 3.36667 3.3667 10.8167 3.3667 20C3.3667 29.1833 10.8167 36.6333 20 36.6333C29.1834 36.6333 36.6334 29.1833 36.6334 20C36.6334 10.8167 29.1834 3.36667 20 3.36667ZM19.1334 33.3333V22.9H13.3334L21.6667 6.66667V17.1H27.25L19.1334 33.3333Z" />
                            </svg>
                        </div>
                        <div class="px-4 py-2 -mx-3">
                            <div class="mx-3">
                                <span class="font-semibold text-red-500 ">Error</span>
                                <p class="text-sm text-gray-600 "><?= htmlspecialchars($error) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="relative flex items-center mt-8">
                    <span class="absolute">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mx-3 text-gray-300 " fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </span>

                    <input type="text" name="username" required
                        class="block w-full py-3 text-gray-700 bg-white border rounded-lg px-11    focus:border-blue-400  focus:ring-blue-300 focus:outline-none focus:ring focus:ring-opacity-40"
                        placeholder="Username">
                </div>

                <div class="relative flex items-center mt-4">
                    <span class="absolute">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mx-3 text-gray-300 " fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>

                    <input type="password" name="password" required
                        class="block w-full px-10 py-3 text-gray-700 bg-white border rounded-lg    focus:border-blue-400  focus:ring-blue-300 focus:outline-none focus:ring focus:ring-opacity-40"
                        placeholder="Password">
                </div>

                <div class="flex items-center justify-between mt-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="remember" id="remember"
                            class="w-4 h-4 text-blue-500 border-gray-300 rounded focus:ring-blue-400    ">
                        <label for="remember" class="ml-2 text-sm text-gray-600 ">Ingat saya</label>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit"
                        class="w-full px-6 py-3 text-sm font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-blue-500 rounded-lg hover:bg-blue-400 focus:outline-none focus:ring focus:ring-blue-300 focus:ring-opacity-50">
                        Sign in
                    </button>
                </div>

                <div class="mt-6 text-center text-xs text-gray-400 ">
                    <p>Demo: <strong>admin</strong> / <strong>password123</strong></p>
                    <p class="mt-2">© 2025 Bot Dashboard. All rights reserved.</p>
                </div>

            </form>
        </div>
    </section>
</body>

</html>