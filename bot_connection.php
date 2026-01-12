<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

/* ================= CONFIG ================= */
$api_base = "https://bwaha.004090.xyz/api";
$api_key  = "lewishamilton";
$session  = "default";
$dashboard_url = "dashboard.php";

/* ============== SAFE REQUEST ============== */
function wahaGet($url, $key)
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
    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, false, $ctx);
    return $res ? json_decode($res, true) : null;
}

/* ============== INITIAL CHECK ============== */
$data = wahaGet("$api_base/sessions/$session", $api_key);
$status = strtoupper($data['status'] ?? 'DISCONNECTED');

if ($status === 'WORKING') {
    header("Location: $dashboard_url");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Connect WhatsApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="h-screen w-screen bg-white flex items-center justify-center text-gray-900">

<div class="w-full max-w-sm px-6">

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight">
            Connect WhatsApp
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            Scan QR using WhatsApp on your phone
        </p>
    </div>

    <!-- QR Container -->
    <div class="border border-gray-200 aspect-square flex items-center justify-center mb-4">
        <img id="qr" class="hidden w-full h-full object-contain p-3" alt="QR Code">
        <span id="qr-placeholder" class="text-xs text-gray-400">
            Loading QR…
        </span>
    </div>

    <!-- Status -->
    <div id="status-text" class="text-xs text-gray-500 mb-4">
        Waiting for scan
    </div>

    <!-- Action -->
    <button id="refresh"
        class="w-full border border-gray-300 text-sm py-2 hover:bg-gray-50 transition">
        Refresh QR
    </button>

</div>

<script>
const API_KEY  = "<?= $api_key ?>";
const BASE_URL = "<?= $api_base ?>";
const SESSION  = "<?= $session ?>";
const DASHBOARD = "<?= $dashboard_url ?>";

const qrImg = document.getElementById("qr");
const placeholder = document.getElementById("qr-placeholder");
const statusText = document.getElementById("status-text");

/* Load QR */
async function loadQR() {
    try {
        placeholder.textContent = "Loading QR…";
        const res = await fetch(`${BASE_URL}/${SESSION}/auth/qr`, {
            headers: {
                "Accept": "image/png",
                "X-Api-Key": API_KEY
            }
        });
        if (!res.ok) throw new Error();
        const blob = await res.blob();
        qrImg.src = URL.createObjectURL(blob);
        qrImg.classList.remove("hidden");
        placeholder.classList.add("hidden");
    } catch {
        placeholder.textContent = "Failed to load QR";
    }
}

/* Poll status */
async function checkSession() {
    try {
        const res = await fetch(`${BASE_URL}/sessions/${SESSION}`, {
            headers: { "X-Api-Key": API_KEY }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (data.status === "WORKING") {
            statusText.textContent = "Connected. Redirecting…";
            setTimeout(() => location.href = DASHBOARD, 600);
        }
    } catch {}
}

document.getElementById("refresh").onclick = loadQR;

loadQR();
setInterval(checkSession, 3000);
</script>

</body>
</html>