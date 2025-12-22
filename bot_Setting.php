<?php ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>WAHA Login</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  </head>

  <body class="m-0 p-0 box-border font-sans flex h-screen bg-gray-100">
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 p-8 overflow-y-auto">
      <div class="mb-8 bg-gradient-to-r from-indigo-500 to-purple-600 p-8 rounded-lg shadow-lg relative overflow-hidden">
        <div class="absolute -top-1/2 -right-1/2 w-96 h-96 bg-white/10 rounded-full"></div>
        <h2 class="text-white text-4xl font-bold relative z-10 drop-shadow">⚙️ WAHA Login</h2>
        <p class="text-white/90 text-sm relative z-10 mt-2">Pantau status sesi WAHA dan pairing QR</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6 space-y-4 max-w-2xl mx-auto">
        <h2 class="text-xl font-semibold">WAHA Login</h2>

      <!-- STATUS -->
      <div id="status" class="text-sm font-medium text-gray-700">Checking session...</div>

      <!-- ACTION -->
      <button id="refresh" class="w-full rounded-md bg-blue-500 hover:bg-blue-600 text-sm font-semibold text-white py-2 px-4 transition">🔄 Refresh QR</button>

      <!-- QR -->
      <div class="flex justify-center">
        <img id="qr" class="hidden max-w-[260px] border rounded-md p-3" alt="QR Code" />
      </div>

      <!-- PAIRING -->
      <div id="pairing" class="hidden border-t pt-4 space-y-2">
        <input id="phone" placeholder="628xxxxxxxxxx" class="w-full border rounded-md px-3 py-2 text-sm" />
        <button id="requestCode" class="w-full bg-green-500 hover:bg-green-600 text-white rounded-md px-4 py-2 text-sm font-semibold transition">Request Code</button>
      </div>

      <pre id="log" class="text-xs text-red-600"></pre>
    </div>

    <script>
      const API_KEY = "0b08c9d2a8f6405d87c83538bc3892bc";
      const BASE_URL = "https://bwaha.004090.xyz";
      const SESSION = "default";

      let loginWatcher = null;

      const statusEl = document.getElementById("status");
      const qrImg = document.getElementById("qr");
      const pairingBox = document.getElementById("pairing");
      const refreshBtn = document.getElementById("refresh");
      const phoneInput = document.getElementById("phone");
      const requestBtn = document.getElementById("requestCode");
      const log = document.getElementById("log");

      async function checkSession() {
        const res = await fetch(`${BASE_URL}/api/sessions/${SESSION}`, {
          headers: { "X-Api-Key": API_KEY },
        });
        if (!res.ok) throw new Error("Session API error");
        return res.json();
      }

      async function loadQR() {
        const res = await fetch(`${BASE_URL}/api/${SESSION}/auth/qr`, {
          headers: {
            Accept: "image/png",
            "X-Api-Key": API_KEY,
          },
        });
        if (!res.ok) throw new Error("QR API error");

        const blob = await res.blob();
        qrImg.src = URL.createObjectURL(blob);
        qrImg.classList.remove("hidden");
      }

      async function requestPairingCode() {
        const phoneNumber = phoneInput.value.trim();
        if (!phoneNumber) {
          log.textContent = "Phone number required";
          return;
        }

        await fetch(`${BASE_URL}/api/${SESSION}/auth/request-code`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-Api-Key": API_KEY,
          },
          body: JSON.stringify({ phoneNumber }),
        });

        log.textContent = "Pairing code sent. Waiting for login...";
        startLoginWatcher();
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
          } catch {}
        }, 5000);
      }

      function stopLoginWatcher() {
        clearInterval(loginWatcher);
        loginWatcher = null;
      }

      function showLoggedIn() {
        statusEl.textContent = "✅ SUDAH LOGIN";
        statusEl.className = "text-sm font-medium text-green-600";
        qrImg.classList.add("hidden");
        pairingBox.classList.add("hidden");
        refreshBtn.classList.add("hidden");
      }

      async function refresh() {
        log.textContent = "";
        qrImg.classList.add("hidden");
        pairingBox.classList.add("hidden");
        statusEl.textContent = "Checking session...";

        const session = await checkSession();
        const status = session.status?.toUpperCase();

        if (status === "WORKING") {
          showLoggedIn();
          return;
        }

        if (status.includes("SCAN_QR")) {
          statusEl.textContent = "🔳 BELUM LOGIN — Scan QR / Code Pairing";
          statusEl.className = "text-sm font-medium text-orange-600";
          pairingBox.classList.remove("hidden");
          await loadQR();
          startLoginWatcher(); // ⬅️ WATCH ONLY AFTER QR SHOWN
        }
      }

      refreshBtn.addEventListener("click", refresh);
      requestBtn.addEventListener("click", requestPairingCode);

      refresh();
    </script>
  </body>
</html>