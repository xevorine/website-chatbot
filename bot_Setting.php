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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        body {
            background: #f5f5f5;
        }

        .card {
            border: 1px solid #e1e1e1;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: bold;
        }

        .clean-box {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
    </style>
</head>

<body class="m-0 p-0 box-border font-sans flex h-screen bg-gray-100">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 p-8 overflow-y-auto">
        <div class="mb-8 p-6 rounded-lg clean-box flex items-center justify-between">
            <div>
                <h2 class="text-gray-800 text-3xl font-bold flex items-center gap-2">
                    <i class="fa-solid fa-plug"></i> WAHA Connection
                </h2>
                <p class="text-gray-500 text-sm mt-1">
                    Monitor status sesi WAHA dan proses pairing QR.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <div class="clean-box rounded-lg p-6">
                <div class="border-b pb-4 mb-4">
                    <h3 class="section-title text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-qrcode"></i> Scan QR Code
                    </h3>
                </div>

                <div id="view-loggedin"
                    class="<?= $isLoggedIn ? '' : 'hidden' ?> flex flex-col items-center justify-center py-10 text-center">
                    <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-circle-check text-4xl text-gray-700"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700">Bot Terhubung</h3>
                    <p class="text-gray-500 mt-2">Sesi aktif dan siap menerima pesan.</p>
                </div>

                <div id="view-login" class="<?= $isLoggedIn ? 'hidden' : '' ?> flex flex-col items-center">
                    <div id="status-text"
                        class="text-sm font-medium text-gray-600 mb-4 border border-gray-300 px-3 py-1 rounded-full">
                        Menunggu Scan QR...
                    </div>

                    <div
                        class="relative w-64 h-64 bg-white border border-gray-400 rounded-lg flex items-center justify-center mb-4 overflow-hidden">
                        <img id="qr" class="hidden w-full h-full object-contain p-2" alt="QR Code" />
                        <span id="qr-placeholder" class="text-gray-400 text-xs text-center px-4">
                            Klik Refresh untuk memuat QR
                        </span>
                    </div>

                    <div>
                        <span id="text-state"
                            class="hidden font-mono text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded">
                            <?= $bot_state ?>
                        </span>
                    </div>

                    <button id="refresh"
                        class="w-full max-w-xs rounded-md bg-gray-800 hover:bg-black text-white font-semibold py-2 px-4 transition mb-6 shadow">
                        <i class="fa-solid fa-rotate"></i> Refresh QR
                    </button>

                    <pre id="log" class="text-xs text-red-600 mt-2 text-center w-full hidden"></pre>
                </div>
            </div>
        </div>

        <script>
            const API_KEY = "<?= $api_key ?>";
            const BASE_URL = "https://bwaha.004090.xyz";
            const SESSION = "<?= $session ?>";

            let loginWatcher = null;

            const viewLogin = document.getElementById("view-login");
            const viewLoggedIn = document.getElementById("view-loggedin");
            const textState = document.getElementById("text-state");
            const statusText = document.getElementById("status-text");
            const qrImg = document.getElementById("qr");
            const qrPlaceholder = document.getElementById("qr-placeholder");
            const log = document.getElementById("log");
            const refreshBtn = document.getElementById("refresh");

            async function checkSession() {
                const res = await fetch(`${BASE_URL}/api/sessions/${SESSION}`, {
                    headers: { "X-Api-Key": API_KEY }
                });
                if (!res.ok) throw new Error("Session API error");
                return res.json();
            }

            async function loadQR() {
                try {
                    qrPlaceholder.textContent = "Loading QR...";
                    const res = await fetch(`${BASE_URL}/api/${SESSION}/auth/qr`, {
                        headers: {
                            Accept: "image/png",
                            "X-Api-Key": API_KEY
                        }
                    });
                    if (!res.ok) throw new Error("QR API error");

                    const blob = await res.blob();
                    qrImg.src = URL.createObjectURL(blob);
                    qrImg.classList.remove("hidden");
                    qrPlaceholder.classList.add("hidden");
                } catch (e) {
                    qrPlaceholder.textContent = "Gagal memuat QR. Klik Refresh.";
                    console.error(e);
                }
            }

            function startLoginWatcher() {
                if (loginWatcher) return;
                loginWatcher = setInterval(async () => {
                    try {
                        const session = await checkSession();
                        if (session.status === "WORKING") {
                            stopLoginWatcher();
                            showLoggedIn();
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }, 5000);
            }

            function stopLoginWatcher() {
                clearInterval(loginWatcher);
                loginWatcher = null;
            }

            function showLoggedIn() {
                viewLogin.classList.add("hidden");
                viewLoggedIn.classList.remove("hidden");
                textState.textContent = "WORKING";
            }

            async function refresh() {
                log.textContent = "";
                log.classList.add("hidden");
                qrImg.classList.add("hidden");
                qrPlaceholder.classList.remove("hidden");
                qrPlaceholder.textContent = "Checking session...";
                statusText.textContent = "Memeriksa sesi...";

                try {
                    const session = await checkSession();
                    const status = session.status?.toUpperCase();
                    textState.textContent = status;

                    if (status === "WORKING") {
                        showLoggedIn();
                        return;
                    }

                    if (status.includes("SCAN_QR")) {
                        statusText.textContent = "Belum Login — Scan QR";
                        viewLogin.classList.remove("hidden");
                        viewLoggedIn.classList.add("hidden");
                        await loadQR();
                        startLoginWatcher();
                    } else {
                        statusText.textContent = "Status: " + status;
                        qrPlaceholder.textContent = "Klik Refresh untuk memuat QR";
                    }
                } catch (e) {
                    statusText.textContent = "Error: Gagal cek sesi";
                    qrPlaceholder.textContent = "Gagal memuat. Klik Refresh.";
                    log.textContent = e.message;
                    log.classList.remove("hidden");
                    console.error(e);
                }
            }

            refreshBtn.addEventListener("click", refresh);
            refresh();
        </script>
</body>

</html>