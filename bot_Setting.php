<?php
session_start();

// Cek Login Dashboard
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include __DIR__ . '/connection.php';

// --- CONFIG API ---
$api_base = "https://bwaha.004090.xyz/api";
$api_key = "lewishamilton"; // Sesuaikan dengan API Key WAHA Anda
$session = "default";

// --- FUNGSI REQUEST PHP (Safe Mode - Tanpa cURL) ---
function getWahaStatus($url, $key)
{
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "X-Api-Key: $key\r\nAccept: application/json\r\n",
            "timeout" => 5,
            "ignore_errors" => true
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);

    if ($response === false)
        return null;
    return json_decode($response, true);
}

// Cek Status Awal saat Halaman Dimuat
$data = getWahaStatus("$api_base/sessions/$session", $api_key);
$bot_state = "DISCONNECTED";
$isLoggedIn = false;

if ($data) {
    $bot_state = strtoupper($data['status'] ?? 'UNKNOWN');
    if ($bot_state == 'WORKING') {
        $isLoggedIn = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>WAHA Login - Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="m-0 p-0 box-border font-sans flex h-screen bg-gray-100">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 p-8 overflow-y-auto">

        <div
            class="mb-8 bg-gradient-to-r from-indigo-500 to-purple-600 p-8 rounded-lg shadow-lg relative overflow-hidden">
            <div class="absolute -top-1/2 -right-1/2 w-96 h-96 bg-white/10 rounded-full"></div>
            <h2 class="text-white text-4xl font-bold relative z-10 drop-shadow">⚙️ WAHA Connection</h2>
            <p class="text-white/90 text-sm relative z-10 mt-2">Pantau status sesi WAHA dan pairing QR</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="border-b pb-4 mb-4">
                    <h3 class="text-xl font-bold text-gray-800">📱 Scan QR Code</h3>
                </div>

                <div id="view-loggedin"
                    class="<?= $isLoggedIn ? '' : 'hidden' ?> flex flex-col items-center justify-center py-10">
                    <div
                        class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mb-4 animate-bounce">
                        <span class="text-4xl">✅</span>
                    </div>
                    <h3 class="text-2xl font-bold text-green-700">Bot Terhubung</h3>
                    <p class="text-gray-500 mt-2 text-center">Sesi WhatsApp aktif.<br>Bot siap menerima & mengirim
                        pesan.</p>
                </div>

                <div id="view-login" class="<?= $isLoggedIn ? 'hidden' : '' ?> flex flex-col items-center">
                    <div id="status-text"
                        class="text-sm font-medium text-orange-600 mb-4 bg-orange-50 px-3 py-1 rounded-full animate-pulse">
                        Menunggu Scan QR...
                    </div>

                    <div
                        class="relative w-64 h-64 bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center mb-4 overflow-hidden">
                        <img id="qr" class="hidden w-full h-full object-contain p-2" alt="QR Code" />
                        <span id="qr-placeholder" class="text-gray-400 text-xs text-center px-4">Klik Refresh untuk
                            memuat QR</span>

                            
                    </div>
                    <div>
                        <span id="text-state" class="hidden font-mono text-sm text-gray-800 bg-gray-100 px-2 py-1 rounded">
                                <?= $bot_state ?>
                            </span>
                    </div>

                    <button id="refresh"
                        class="w-full max-w-xs rounded-md bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 transition mb-6 shadow">
                        🔄 Refresh QR
                    </button>
                    <pre id="log" class="text-xs text-red-600 mt-2 text-center w-full hidden"></pre>
                </div>
            </div>
        </div>

        <script>
            const API_KEY = "<?= $api_key ?>";
            const BASE_URL = "<?= $api_base ?>";
            const SESSION = "<?= $session ?>";

            let loginWatcher = null;

            // Elements
            const viewLogin = document.getElementById("view-login");
            const viewLoggedIn = document.getElementById("view-loggedin");
            const profileCard = document.getElementById("profile-card");

            const badgeStatus = document.getElementById("badge-status");
            const textState = document.getElementById("text-state");
            const statusText = document.getElementById("status-text");

            const qrImg = document.getElementById("qr");
            const qrPlaceholder = document.getElementById("qr-placeholder");
            const log = document.getElementById("log");

            // --- 1. CEK SESSION ---
            async function checkSession() {
                try {
                    const res = await fetch(`${BASE_URL}/sessions/${SESSION}`, {
                        headers: { "X-Api-Key": API_KEY }
                    });
                    if (!res.ok) throw new Error("API Error");
                    return await res.json();
                } catch (e) {
                    console.error(e);
                    return { status: "ERROR" };
                }
            }

            // --- 2. UPDATE UI ---
            function updateUI(status) {
                textState.textContent = status;

                if (status === "WORKING") {
                    // LOGGED IN
                    viewLogin.classList.add("hidden");
                    viewLoggedIn.classList.remove("hidden");
                    profileCard.classList.remove("hidden");

                    badgeStatus.textContent = "ONLINE";
                    badgeStatus.className = "px-3 py-1 rounded-full text-xs font-bold text-white bg-green-500";

                    loadBotProfile();
                    stopLoginWatcher();
                } else {
                    // LOGGED OUT
                    viewLogin.classList.remove("hidden");
                    viewLoggedIn.classList.add("hidden");
                    profileCard.classList.add("hidden");

                    badgeStatus.textContent = "WAITING";
                    badgeStatus.className = "px-3 py-1 rounded-full text-xs font-bold text-white bg-yellow-500";

                    if (status.includes("SCAN_QR")) {
                        statusText.textContent = "Silakan Scan QR Code di bawah";
                        if (qrImg.classList.contains("hidden")) loadQR();
                        startLoginWatcher();
                    } else {
                        statusText.textContent = "Status: " + status;
                    }
                }
            }

            // --- 3. LOAD QR ---
            async function loadQR() {
                try {
                    qrPlaceholder.textContent = "Loading QR...";
                    const res = await fetch(`${BASE_URL}/${SESSION}/auth/qr`, {
                        headers: { Accept: "image/png", "X-Api-Key": API_KEY }
                    });
                    if (!res.ok) throw new Error("Gagal QR");

                    const blob = await res.blob();
                    qrImg.src = URL.createObjectURL(blob);
                    qrImg.classList.remove("hidden");
                    qrPlaceholder.classList.add("hidden");
                } catch (e) {
                    qrPlaceholder.textContent = "Gagal memuat QR. Klik Refresh.";
                }
            }
            // Cek status saat pertama kali load via JS juga (untuk update realtime)
            checkSession().then(data => {
                const status = data.status ? data.status.toUpperCase() : "STOPPED";
                updateUI(status);
            });

        </script>
</body>

</html>