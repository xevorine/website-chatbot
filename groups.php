<?php
session_start();

// Cek Login (Opsional, sesuaikan dengan kebutuhan)
// if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }

// =============================
// DB CONNECTION
// =============================
require __DIR__ . "/connection.php";

// =============================
// AMBIL GROUP_ID DARI WARNINGS
// =============================
$groupIds = [];

$sql = "SELECT DISTINCT group_id FROM warnings WHERE group_id IS NOT NULL AND group_id != ''";
$res = $conn->query($sql);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $groupIds[] = $row["group_id"];
    }
}

$conn->close();
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
    <title>Test Groups - Dashboard</title>
</head>

<body class="m-0 p-0 box-border font-sans flex h-screen bg-gray-100">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 overflow-y-auto">

        <div class="bg-[#8EA7E9] p-8 shadow flex items-center justify-between">
            <p class="text-white font-bold text-xl">Groups List</h2>
            <p class="text-white/80 text-sm">
                Jam lokal Anda (WIB): <span id="localClock"></span>
            </p>
        </div>


        <div class="p-6">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div class="text-gray-700">
                    <span class="font-semibold text-lg">Total Group ID di DB:</span>
                    <span class="bg-indigo-100 text-indigo-800 text-sm font-bold px-3 py-1 rounded-full ml-2">
                        <?= count($groupIds) ?>
                    </span>
                </div>

                <div class="flex items-center gap-4">
                    <span id="status" class="text-sm text-gray-500 italic">Status: Idle</span>
                    <!-- <button onclick="loadGroups()"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition shadow-md flex items-center gap-2">
                        🔄 Load Data
                    </button> -->
                </div>
            </div>

            <div class="overflow-hidden border border-gray-200 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3.5 text-sm font-medium text-left text-gray-500">No</th>
                            <th class="px-4 py-3.5 text-sm font-medium text-left text-gray-500">Group ID</th>
                            <th class="px-4 py-3.5 text-sm font-medium text-left text-gray-500">Group Name (Live API)
                            </th>
                        </tr>
                    </thead>

                    <tbody id="group-table" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">
                                Klik tombol "Load Data" untuk mengambil data grup...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script>
        const USED_GROUP_IDS = <?= json_encode($groupIds, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <script>
        const API_URL = "https://bwaha.004090.xyz/api/default/groups";
        const API_KEY = "lewishamilton"; // Sesuaikan jika perlu ganti key

        const USED_SET = new Set(USED_GROUP_IDS);

        async function loadGroups() {
            const statusEl = document.getElementById("status");
            const tbody = document.getElementById("group-table");

            statusEl.textContent = "Status: Memuat data...";
            statusEl.className = "text-sm text-blue-600 font-semibold animate-pulse";

            // Tampilkan loading di tabel
            tbody.innerHTML = `
            <tr>
                <td colspan="3" class="px-6 py-12 text-center">
                    <div class="flex justify-center items-center gap-3">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                        <span class="text-gray-500">Sedang mengambil data dari API...</span>
                    </div>
                </td>
            </tr>`;

            try {
                const res = await fetch(API_URL, {
                    headers: {
                        "X-Api-Key": API_KEY,
                        "Accept": "application/json"
                    }
                });

                if (!res.ok) {
                    statusEl.textContent = `Error: ${res.status}`;
                    statusEl.className = "text-sm text-red-600 font-bold";
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-red-500 font-semibold">
                            ❌ Gagal mengambil data (HTTP ${res.status})
                        </td>
                    </tr>`;
                    return;
                }

                const data = await res.json();
                statusEl.textContent = "Status: Selesai";
                statusEl.className = "text-sm text-green-600 font-bold";

                tbody.innerHTML = ""; // Bersihkan tabel

                let index = 0;

                data.forEach(group => {
                    // Normalisasi ID (kadang _serialized, kadang string biasa)
                    const groupId = group?.id?._serialized || group?.id || null;

                    if (!groupId) return;

                    // FILTER: Hanya tampilkan jika ID ada di database warnings
                    if (!USED_SET.has(groupId)) return;

                    const groupName = group?.name || group?.subject || groupId;

                    index++;

                    const tr = document.createElement("tr");
                    tr.className = "hover:bg-gray-50 transition duration-150";

                    tr.innerHTML = `
                    <td class="px-6 py-4 text-center font-medium text-gray-900 border-r border-gray-100">${index}</td>
                    <td class="px-6 py-4 font-mono text-xs text-blue-600 font-bold break-all border-r border-gray-100 select-all">
                        ${groupId}
                    </td>
                    <td class="px-6 py-4 font-semibold text-gray-800">
                        ${escapeHtml(groupName)}
                    </td>
                `;
                    tbody.appendChild(tr);
                });

                if (index === 0) {
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                            ⚠️ Tidak ada grup aktif yang cocok dengan database warnings.
                        </td>
                    </tr>`;
                }

            } catch (err) {
                console.error(err);
                statusEl.textContent = "Status: Error Koneksi";
                statusEl.className = "text-sm text-red-600 font-bold";
                tbody.innerHTML = `
                <tr>
                    <td colspan="3" class="px-6 py-8 text-center text-red-600">
                        ❌ Terjadi kesalahan JS / CORS (Cek Console)
                    </td>
                </tr>`;
            }
        }

        // Mencegah XSS sederhana
        function escapeHtml(str) {
            return String(str)
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;");
        }

        function updateClock() {
            const now = new Date();
            document.getElementById("localClock").textContent =
                now.toLocaleTimeString("id-ID", { timeZone: "Asia/Jakarta" });
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

    <script>
        window.onload = () => loadGroups();
    </script>

</body>

</html>