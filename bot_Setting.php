<?php
/**
 * ================================================
 *  WAHA BOT DASHBOARD – FIXED FINAL VERSION
 * ================================================
 */

session_start();

/* -----------------------------------------
    1. GET SESSION STATUS FROM WAHA
----------------------------------------- */
function getBotStatus()
{
    $url = "http://10.147.19.163:3000/api/sessions/default";

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

    // WAHA session object contains "status"
    if (isset($data["status"])) {
        return strtoupper($data["status"]); // WORKING / STOPPED / SCAN_QR / FAILED
    }

    return "DISCONNECTED";
}

/* -----------------------------------------
    2. DETERMINE LOGIN STATUS
----------------------------------------- */
$bot_state = getBotStatus();
$isLoggedIn = $bot_state === "WORKING";

/* -----------------------------------------
    3. UPTIME (Start when login, reset when logout)
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
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST["action"] === "logout") {
    // Call WAHA logout
    $url = "http://10.147.19.163:3000/api/sessions/default/logout";

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

    // Remove local session
    unset($_SESSION["bot_login_time"]);

    header("Location: bot_Setting.php");
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

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-800 text-white overflow-y-auto shadow-lg">
        <div class="border-b border-slate-700 p-6 text-center">
            <h1 class="text-2xl font-bold">Dashboard</h1>
        </div>
        <nav class="py-6">
            <ul class="space-y-1">
                <li><a href="index.php" class="block px-6 py-3 hover:bg-slate-700">📊 Daftar Warning</a></li>
                <li><a href="#" class="block px-6 py-3 hover:bg-slate-700">👥 User Management</a></li>
                <li><a href="groups.php" class="block px-6 py-3 hover:bg-slate-700">📁 Groups</a></li>
                <li><a href="bot_Setting.php" class="block px-6 py-3 bg-blue-600 border-l-4 border-blue-400">⚙️ Bot Settings</a></li>
                <li><a href="#" class="block px-6 py-3 hover:bg-slate-700">📋 Reports</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-8">

        <!-- Header -->
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-8 rounded-lg shadow-lg text-white mb-8">
            <h2 class="text-4xl font-bold">⚙️ Bot Settings</h2>
            <p class="text-green-100">Pantau status bot & QR secara real-time</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- PROFILE BOX -->
            <div id="profile-box" class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $isLoggedIn
                ? ""
                : "hidden"; ?>">
                <div class="p-6 bg-gray-50 border-b">
                    <h3 class="text-lg font-bold">👤 WhatsApp Profile</h3>
                </div>

                <div class="p-6 flex flex-col items-center justify-center space-y-4">
                    <!-- Profile Picture -->
                    <div class="relative">
                        <img id="profile-picture" src="https://via.placeholder.com/150" class="w-32 h-32 rounded-full shadow-lg border-4 border-blue-200 object-cover">
                        <button id="btn-change-picture" class="absolute bottom-0 right-0 bg-blue-500 hover:bg-blue-600 text-white rounded-full p-2 shadow-lg" title="Ubah Foto">
                            📷
                        </button>
                    </div>

                    <!-- Profile Name -->
                    <div class="w-full text-center">
                        <p class="text-sm text-gray-500 mb-2">Nama Bot</p>
                        <button id="btn-edit-name" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold inline-flex items-center gap-2 transition">
                             <span id="profile-name-display">-</span>
                        </button>
                    </div>

                    <!-- Profile ID -->
                    <div class="w-full text-center">
                        <p class="text-sm text-gray-500 mb-1">ID WhatsApp</p>
                        <p id="profile-id" class="bg-gray-100 px-3 py-2 rounded font-mono text-sm break-all">-</p>
                    </div>

                    <!-- Delete Picture Button -->
                    <button id="btn-delete-picture" class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm">
                        🗑️ Hapus Foto Profile
                    </button>
                </div>
            </div>

            <!-- QR BOX -->
            <div id="qr-box" class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $isLoggedIn
                ? "hidden"
                : ""; ?>">
                <div class="p-6 bg-gray-50 border-b">
                    <h3 class="text-lg font-bold">📱 QR Code</h3>
                </div>

                <div class="p-6 flex flex-col items-center justify-center min-h-96">
                    <img id="qr-image" src="qr-code.png" class="w-56 h-56 rounded-lg shadow-lg border-4 border-indigo-200">

                    <p id="qr-loading" class="hidden mt-3 text-yellow-700 bg-yellow-50 border border-yellow-200 p-3 rounded-lg text-sm">
                        ⏳ Mengambil QR baru...
                    </p>

                    <button id="btn-refresh-qr" class="mt-4 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        🔄 Refresh QR Code
                    </button>
                </div>
            </div>

            <!-- LOGIN BOX -->
            <div id="login-box" class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $isLoggedIn
                ? ""
                : "hidden"; ?>">
                <div class="p-6 bg-gray-50 border-b">
                    <h3 class="text-lg font-bold">👤 Bot Profile</h3>
                </div>

                <div class="p-6 text-center">
                    <div class="w-24 h-24 rounded-full bg-green-100 border-4 border-green-300 flex items-center justify-center mx-auto mb-4">
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

        <!-- STATUS -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 bg-gray-50 border-b"><h4 class="text-lg font-bold">🤖 Bot Status</h4></div>

                <div class="p-6 space-y-4">

                    <div class="flex justify-between border-b pb-4">
                        <span>Status</span>
                        <span class="px-4 py-1 rounded-full text-white <?php echo $isLoggedIn
                            ? "bg-green-500"
                            : "bg-yellow-500"; ?>">
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

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <h4 class="text-lg font-bold text-slate-800">⚡ Quick Actions</h4>
                </div>
                <div class="p-6 space-y-3">
                    <button onclick="location.reload()" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                        🔄 Refresh Data
                    </button>
                    <button class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                        ✅ Restart Bot
                    </button>
                    <button class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                        📊 View Logs
                    </button>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- MODAL EDIT NAMA -->
<div id="modal-edit-name" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-96 p-6 animate-in fade-in zoom-in">
        <h3 class="text-xl font-bold mb-4 text-gray-800">Edit Nama Bot</h3>
        
        <input id="modal-name-input" type="text" placeholder="Masukkan nama bot baru" class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 mb-6 focus:outline-none focus:border-blue-500 text-center text-lg">
        
        <div class="flex gap-3">
            <button id="btn-modal-cancel" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition">
                Batal
            </button>
            <button id="btn-modal-save" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                Simpan
            </button>
        </div>
    </div>
</div>


<script>
// --------------------------
//  AUTO REFRESH STATUS (Polling setiap 3 detik)
// --------------------------
async function checkBotStatus() {
    try {
        let res = await fetch("http://10.147.19.163:3000/api/sessions/default", {
            headers: {
                "X-Api-Key": "yoursecretkey",
            }
        });

        if (res.status === 200) {
            let data = await res.json();
            let status = data?.status ? data.status.toUpperCase() : "DISCONNECTED";
            let isLoggedIn = status === "WORKING";

            // Update Status Display
            let statusSpan = document.querySelector("span[class*='bg-green-500'], span[class*='bg-yellow-500']");
            if (statusSpan) {
                statusSpan.textContent = isLoggedIn ? "Online" : "Waiting";
                statusSpan.className = isLoggedIn 
                    ? "px-4 py-1 rounded-full text-white bg-green-500" 
                    : "px-4 py-1 rounded-full text-white bg-yellow-500";
            }

            // Update WAHA Status
            let wahaStatusSpan = document.querySelectorAll("span")[9]; // Adjust selector if needed
            if (wahaStatusSpan) {
                wahaStatusSpan.textContent = status;
            }

            // Toggle QR Box & Login Box
            let qrBox = document.getElementById("qr-box");
            let loginBox = document.getElementById("login-box");
            let profileBox = document.getElementById("profile-box");
            
            if (isLoggedIn) {
                qrBox.classList.add("hidden");
                loginBox.classList.remove("hidden");
                profileBox.classList.remove("hidden");
                
                // Load profile
                loadBotProfile();
                
                // Start uptime if just logged in
                if (!loginTime) {
                    loginTime = Date.now();
                }
            } else {
                qrBox.classList.remove("hidden");
                loginBox.classList.add("hidden");
                profileBox.classList.add("hidden");
                loginTime = null;
            }

            console.log("✅ Status Updated:", status);
        }
    } catch (e) {
        console.log("❌ Gagal check status:", e);
    }
}

// --------------------------
//  LOAD BOT PROFILE
// --------------------------
async function loadBotProfile() {
    try {
        let res = await fetch("http://10.147.19.163:3000/api/default/profile", {
            headers: {
                "X-Api-Key": "yoursecretkey",
            }
        });

        if (res.status === 200) {
            let data = await res.json();
            
            // Update ID
            document.getElementById("profile-id").textContent = data.id || "-";
            
            // Update Name Display
            let nameDisplay = data.name || "Belum Ada Nama";
            document.getElementById("profile-name-display").textContent = nameDisplay;
            document.getElementById("modal-name-input").value = data.name || "";
            
            // Update Picture - Handle both data.picture and data.file.url format
            let picUrl = data.picture || (data.file && data.file.url);
            if (picUrl) {
                document.getElementById("profile-picture").src = picUrl;
            }
            
            console.log("✅ Profile Loaded:", data);
        }
    } catch (e) {
        console.log("❌ Gagal load profile:", e);
    }
}

// --------------------------
//  MODAL EDIT NAMA - OPEN
// --------------------------
document.getElementById("btn-edit-name").addEventListener("click", () => {
    document.getElementById("modal-edit-name").classList.remove("hidden");
    document.getElementById("modal-name-input").focus();
    document.getElementById("modal-name-input").select();
});

// --------------------------
//  MODAL EDIT NAMA - CANCEL
// --------------------------
document.getElementById("btn-modal-cancel").addEventListener("click", () => {
    document.getElementById("modal-edit-name").classList.add("hidden");
});

// Close modal when clicking outside
document.getElementById("modal-edit-name").addEventListener("click", (e) => {
    if (e.target.id === "modal-edit-name") {
        document.getElementById("modal-edit-name").classList.add("hidden");
    }
});

// --------------------------
//  MODAL EDIT NAMA - SAVE
// --------------------------
document.getElementById("btn-modal-save").addEventListener("click", async () => {
    let newName = document.getElementById("modal-name-input").value.trim();
    
    if (!newName) {
        alert("⚠️ Nama tidak boleh kosong!");
        return;
    }

    try {
        let res = await fetch("http://10.147.19.163:3000/api/default/profile/name", {
            method: "PUT",
            headers: {
                "X-Api-Key": "yoursecretkey",
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ name: newName })
        });

        if (res.status === 200) {
            alert("✅ Nama berhasil diubah!");
            document.getElementById("profile-name-display").textContent = newName;
            document.getElementById("modal-edit-name").classList.add("hidden");
            console.log("✅ Name Updated:", newName);
        } else {
            alert("❌ Gagal mengubah nama");
        }
    } catch (e) {
        alert("❌ ERROR: " + e.message);
    }
});

// Allow saving with Enter key
document.getElementById("modal-name-input").addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
        document.getElementById("btn-modal-save").click();
    }
});

// --------------------------
//  CHANGE BOT PICTURE
// --------------------------
document.getElementById("btn-change-picture").addEventListener("click", () => {
    let input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.onchange = async (e) => {
        let file = e.target.files[0];
        if (!file) return;

        try {
            let formData = new FormData();
            formData.append("file", file);

            console.log("📸 Uploading picture...", file.name);

            let res = await fetch("http://10.147.19.163:3000/api/default/profile/picture", {
                method: "PUT",
                headers: {
                    "X-Api-Key": "yoursecretkey",
                },
                body: formData
            });

            console.log("Response Status:", res.status);
            let responseText = await res.text();
            console.log("Response Body:", responseText);

            if (res.status === 200 || res.status === 204) {
                alert("✅ Foto berhasil diubah!");
                setTimeout(() => loadBotProfile(), 500);
            } else {
                alert("❌ Gagal mengubah foto (Status: " + res.status + ")");
            }
        } catch (e) {
            console.error("❌ ERROR:", e);
            alert("❌ ERROR: " + e.message);
        }
    };
    input.click();
});

// --------------------------
//  DELETE BOT PICTURE
// --------------------------
document.getElementById("btn-delete-picture").addEventListener("click", async () => {
    if (!confirm("⚠️ Yakin ingin menghapus foto profile?")) return;

    try {
        let res = await fetch("http://10.147.19.163:3000/api/default/profile/picture", {
            method: "DELETE",
            headers: {
                "X-Api-Key": "yoursecretkey",
            }
        });

        if (res.status === 200) {
            alert("✅ Foto berhasil dihapus!");
            document.getElementById("profile-picture").src = "https://via.placeholder.com/150";
        } else {
            alert("❌ Gagal menghapus foto");
        }
    } catch (e) {
        alert("❌ ERROR: " + e.message);
    }
});

// Check status LANGSUNG saat page load + setiap 3 detik
checkBotStatus();
setInterval(checkBotStatus, 3000);

// --------------------------
//  QR Refresh Button
// --------------------------
document.getElementById("btn-refresh-qr").addEventListener("click", async () => {
    console.log("Refresh QR ditekan");

    const loading = document.getElementById("qr-loading");
    const img     = document.getElementById("qr-image");

    loading.classList.remove("hidden");
    img.classList.add("hidden");

    try {
        let res = await fetch("http://10.147.19.163:3000/api/default/auth/qr", {
            headers: {
                "X-Api-Key": "yoursecretkey",
                "accept": "image/png"
            }
        });

        if (res.status !== 200) {
            loading.innerHTML = "❌ QR gagal diambil";
            return;
        }

        let blob   = await res.blob();
        let reader = new FileReader();

        reader.onloadend = () => {
            img.src = reader.result;
            img.classList.remove("hidden");
            loading.classList.add("hidden");
            console.log("QR baru diterima:", new Date().toLocaleTimeString());
        };

        reader.readAsDataURL(blob);

    } catch (e) {
        loading.innerHTML = "❌ ERROR mengambil QR";
    }
});

// --------------------------
//  UPTIME
// --------------------------
let loginTime = <?= $bot_login_time ? $bot_login_time * 1000 : "null" ?>;

setInterval(() => {
    if (!loginTime) return;

    const sec = Math.floor((Date.now() - loginTime) / 1000);
    const h   = Math.floor(sec / 3600);
    const m   = Math.floor((sec % 3600) / 60);
    const s   = sec % 60;

    document.getElementById("uptime-display").textContent =
        h > 0 ? `${h}h ${m}m ${s}s` :
        m > 0 ? `${m}m ${s}s` :
        `${s}s`;

}, 1000);
</script>

</body>
</html>
