<?php
/**
 * ================================================
 * WAHA BOT DASHBOARD – FIXED FINAL VERSION
 * ================================================
 */

session_start();

/* -----------------------------------------
    1. GET SESSION STATUS FROM WAHA
----------------------------------------- */
function getBotStatus()
{
    $url = "http://10.242.61.248:3000/api/sessions/default";

    $opts = [
        "http" => [
            "header" => ["X-Api-Key: yoursecretkey"],
        ],
    ];

    $ctx = stream_context_create($opts);
    $json = @file_get_contents($url, false, $ctx);

    if ($json === false) {
        return "DISCONNECTED";
    }

    $data = json_decode($json, true);

    if (isset($data["status"])) {
        return strtoupper($data["status"]);
    }

    return "DISCONNECTED";
}

/* -----------------------------------------
    2. DETERMINE LOGIN STATUS
----------------------------------------- */
$bot_state = getBotStatus();
$isLoggedIn = $bot_state === "WORKING";

/* -----------------------------------------
    3. UPTIME 
----------------------------------------- */
if ($isLoggedIn) {
    if (!isset($_SESSION["bot_login_time"])) {
        $_SESSION["bot_login_time"] = time();
    }
} else {
    unset($_SESSION["bot_login_time"]);
}

$bot_login_time = $_SESSION["bot_login_time"] ?? null;

/* -----------------------------------------
    4. LOGOUT BUTTON HANDLER
----------------------------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "logout") {
    $url = "http://10.242.61.248:3000/api/sessions/default/logout";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{}",
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "X-Api-Key: yoursecretkey",
        ],
    ]);

    $resp = curl_exec($ch);
    curl_close($ch);

    unset($_SESSION["bot_login_time"]);

    header("Location: bot_setting.php"); // Perbaiki redirect ke bot_setting.php (huruf kecil)
    exit();
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Bot Settings - Dashboard</title>
</head>

<body class="bg-gray-100 h-screen overflow-hidden">

    <div class="flex h-full">

        <?php include 'sidebar.php'; ?>

        <main class="flex-1 overflow-y-auto p-8">

            <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-8 rounded-lg shadow-lg text-white mb-8">
                <h2 class="text-4xl font-bold">⚙️ Bot Settings</h2>
                <p class="text-green-100">Pantau status bot & QR secara real-time</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <div id="profile-box"
                    class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $isLoggedIn ? "" : "hidden"; ?>">
                    <div class="p-6 bg-gray-50 border-b">
                        <h3 class="text-lg font-bold">👤 WhatsApp Profile</h3>
                    </div>

                    <div class="p-6 flex flex-col items-center justify-center space-y-4">
                        <div>
                            <img id="profile-picture" src="https://via.placeholder.com/150"
                                class="w-32 h-32 rounded-full shadow-lg border-4 border-blue-200 object-cover">
                        </div>

                        <div class="w-full text-center">
                            <p class="text-sm text-gray-500 mb-2">Nama Bot</p>
                            <button id="btn-edit-name"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold inline-flex items-center gap-2 transition">
                                <span id="profile-name-display">-</span>
                            </button>
                        </div>

                        <div class="w-full text-center">
                            <p class="text-sm text-gray-500 mb-1">ID WhatsApp</p>
                            <p id="profile-id" class="bg-gray-100 px-3 py-2 rounded font-mono text-sm break-all">-</p>
                        </div>
                    </div>
                </div>

                <div id="qr-box"
                    class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $isLoggedIn ? "hidden" : ""; ?>">
                    <div class="p-6 bg-gray-50 border-b">
                        <h3 class="text-lg font-bold">📱 QR Code</h3>
                    </div>

                    <div class="p-6 flex flex-col items-center justify-center min-h-96">
                        <img id="qr-image" src="qr-code.png"
                            class="w-56 h-56 rounded-lg shadow-lg border-4 border-indigo-200">

                        <p id="qr-loading"
                            class="hidden mt-3 text-yellow-700 bg-yellow-50 border border-yellow-200 p-3 rounded-lg text-sm">
                            ⏳ Mengambil QR baru...
                        </p>

                        <button id="btn-refresh-qr"
                            class="mt-4 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                            🔄 Refresh QR Code
                        </button>
                    </div>
                </div>

                <div id="login-box"
                    class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $isLoggedIn ? "" : "hidden"; ?>">
                    <div class="p-6 bg-gray-50 border-b">
                        <h3 class="text-lg font-bold">👤 Bot Profile</h3>
                    </div>

                    <div class="p-6 text-center">
                        <div
                            class="w-24 h-24 rounded-full bg-green-100 border-4 border-green-300 flex items-center justify-center mx-auto mb-4">
                            <span class="text-5xl">✅</span>
                        </div>

                        <h4 class="text-2xl font-bold text-green-700">Bot Telah Login</h4>
                        <p class="text-gray-500 text-sm mt-2">WhatsApp sudah terhubung</p>

                        <form method="POST" class="mt-6">
                            <input type="hidden" name="action" value="logout">
                            <button class="w-full bg-red-500 hover:bg-red-600 text-white py-3 rounded-lg">
                                🚪 Logout Bot
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 bg-gray-50 border-b">
                        <h4 class="text-lg font-bold">🤖 Bot Status</h4>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="flex justify-between border-b pb-4">
                            <span>Status</span>
                            <span
                                class="px-4 py-1 rounded-full text-white <?php echo $isLoggedIn ? "bg-green-500" : "bg-yellow-500"; ?>">
                                <?php echo $isLoggedIn ? "Online" : "Waiting"; ?>
                            </span>
                        </div>

                        <div class="flex justify-between border-b pb-4">
                            <span>WAHA Status</span>
                            <span class="bg-gray-100 px-3 py-1 rounded font-mono"><?php echo $bot_state; ?></span>
                        </div>

                        <div class="flex justify-between border-b pb-4">
                            <span>API Endpoint</span>
                            <span class="bg-gray-100 px-3 py-1 rounded font-mono">localhost:3000</span>
                        </div>

                        <div class="flex justify-between">
                            <span>Uptime</span>
                            <span id="uptime-display" class="bg-gray-100 px-3 py-1 rounded font-mono">
                                0s
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <h4 class="text-lg font-bold text-slate-800">⚡ Quick Actions</h4>
                    </div>
                    <div class="p-6 space-y-3">
                        <button onclick="location.reload()"
                            class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                            🔄 Refresh Data
                        </button>
                        <button
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                            ✅ Restart Bot
                        </button>
                        <button
                            class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                            📊 View Logs
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <div id="modal-edit-name" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-sm w-96 p-6 animate-in fade-in zoom-in">
            <h3 class="text-xl font-bold mb-4 text-gray-800">Edit Nama Bot</h3>
            <input id="modal-name-input" type="text" placeholder="Masukkan nama bot baru"
                class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 mb-6 focus:outline-none focus:border-blue-500 text-center text-lg">
            <div class="flex gap-3">
                <button id="btn-modal-cancel"
                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition">Batal</button>
                <button id="btn-modal-save"
                    class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition">Simpan</button>
            </div>
        </div>
    </div>

    <script>