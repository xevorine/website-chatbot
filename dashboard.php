<?php
// Tampilkan Error (Untuk Debugging jika masih error)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 1. CEK LOGIN USER WEBSITE
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// 2. HANDLE LOGOUT USER WEBSITE
if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Pastikan file connection.php ada
if (!file_exists(__DIR__ . '/connection.php')) {
    die("Error: File connection.php tidak ditemukan!");
}
include __DIR__ . '/connection.php';

// ==========================================
//  KONFIGURASI API WAHA
// ==========================================
$API_BASE = "https://bwaha.004090.xyz/api";
$API_KEY  = "lewishamilton"; 
$SESSION  = "default";

// ==========================================
//  HELPER FUNCTION (SAFE MODE - TANPA CURL)
// ==========================================
function fetchAPI($url, $key, $method = "GET", $data = null) {
    $options = [
        "http" => [
            "method" => $method,
            "header" => "X-Api-Key: $key\r\n" .
                        "Accept: application/json\r\n",
            "timeout" => 5,
            "ignore_errors" => true
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ];

    if ($method === "POST") {
        $options["http"]["header"] .= "Content-Type: application/json\r\n";
        if ($data) {
            $options["http"]["content"] = json_encode($data);
        }
    }

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) return null;
    return json_decode($response, true);
}

// ==========================================
//  LOGIC ACTION (DELETE & UPDATE)
// ==========================================

// DELETE
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM warnings WHERE id = $id");
    header("Location: dashboard.php#warnings"); 
    exit();
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_warning'])) {
    $id = (int)$_POST['id'];
    $message = $_POST['message'];
    $author = $_POST['author'];
    
    $stmt = $conn->prepare("UPDATE warnings SET message = ?, author = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $message, $author, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: dashboard.php#warnings");
    exit();
}

// ==========================================
//  AMBIL DATA DARI DATABASE (Safe Query)
// ==========================================

// 1. Analytics Users
$res_users = $conn->query("SELECT MAX(author) as author, user_id, SUM(warning_count) as total FROM warnings GROUP BY user_id ORDER BY total DESC LIMIT 5");

// 2. Analytics Groups (Dari DB Log)
$res_groups_db = $conn->query("SELECT g.group_name, w.group_id, COUNT(*) as total FROM warnings w LEFT JOIN `groups` g ON w.group_id = g.group_id GROUP BY w.group_id ORDER BY total DESC LIMIT 5");

// 3. Heatmap Data (WIB)
// Menggunakan DATE_ADD untuk server yang UTC
$res_heatmap = $conn->query("SELECT HOUR(DATE_ADD(last_warning_at, INTERVAL 7 HOUR)) as jam, COUNT(*) as jumlah FROM warnings GROUP BY jam ORDER BY jam ASC");
$heatmap_raw = [];
if($res_heatmap) {
    while($r = $res_heatmap->fetch_assoc()) $heatmap_raw[$r['jam']] = $r['jumlah'];
}

$heatmap_final = [];
for($i=0; $i<24; $i++) $heatmap_final[] = $heatmap_raw[$i] ?? 0;
$heatmap_json = json_encode($heatmap_final);

// 4. Daftar Warning (Semua)
$res_warnings = $conn->query("SELECT * FROM warnings ORDER BY id DESC");

// ==========================================
//  AMBIL DATA DARI API (OPSIONAL)
// ==========================================
$groups_api = [];
$bot_status = "UNKNOWN";

// Cek status bot sekilas
$statusData = fetchAPI("$API_BASE/sessions/$SESSION", $API_KEY);
if ($statusData) {
    $bot_status = strtoupper($statusData['status'] ?? 'UNKNOWN');
    
    // Jika bot nyala, ambil daftar grup live
    if ($bot_status == 'WORKING') {
        $api_response = fetchAPI("$API_BASE/$SESSION/groups", $API_KEY);
        if (is_array($api_response)) {
            foreach($api_response as $g) {
                // Handle struktur data WAHA yang kadang beda
                $gid = $g['id']['_serialized'] ?? $g['id'] ?? '-';
                $gname = $g['name'] ?? $g['subject'] ?? $gid;
                $groups_api[] = ['id' => $gid, 'name' => $gname];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Monitoring</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white text-xl">📊</div>
                    <div>
                        <h1 class="font-bold text-lg leading-none text-gray-800">Dashboard Monitoring</h1>
                        <div class="flex items-center gap-2 text-xs mt-1">
                            Bot Status: 
                            <?php if($bot_status == 'WORKING'): ?>
                                <span class="text-green-600 font-bold">● ONLINE</span>
                            <?php else: ?>
                                <span class="text-red-500 font-bold">● <?= $bot_status ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden md:flex gap-1 bg-gray-100 p-1 rounded-lg mr-4">
                        <a href="#analytics" class="px-3 py-1.5 text-sm font-medium rounded-md hover:bg-white hover:shadow-sm transition">Analytics</a>
                        <a href="#warnings" class="px-3 py-1.5 text-sm font-medium rounded-md hover:bg-white hover:shadow-sm transition">Warnings</a>
                        <a href="#groups" class="px-3 py-1.5 text-sm font-medium rounded-md hover:bg-white hover:shadow-sm transition">Groups</a>
                    </div>
                    
                    <a href="?logout=true" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition shadow-sm">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8 space-y-12">

        <section id="analytics" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="h-8 w-1 bg-blue-600 rounded-full"></div>
                <h2 class="text-2xl font-bold text-gray-800">Analytics & Insight</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 col-span-1 lg:col-span-1">
                    <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">⏰ Waktu Pelanggaran (WIB)</h3>
                    <div class="h-48 relative"><canvas id="timeChart"></canvas></div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">💀 Top 5 Pelanggar</h3>
                    <ul class="space-y-3">
                        <?php if($res_users && $res_users->num_rows > 0): ?>
                            <?php $rank=1; while($u = $res_users->fetch_assoc()): ?>
                            <li class="flex justify-between items-center text-sm p-2 hover:bg-gray-50 rounded-lg transition">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 flex items-center justify-center bg-gray-100 rounded-full text-xs font-bold text-gray-600">#<?= $rank++ ?></span>
                                    <div>
                                        <div class="font-semibold text-gray-800"><?= htmlspecialchars($u['author']) ?></div>
                                        <div class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($u['user_id']) ?></div>
                                    </div>
                                </div>
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold"><?= $u['total'] ?> Poin</span>
                            </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="text-gray-400 text-center text-sm">Belum ada data.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">📢 Top Grup Toxic (DB)</h3>
                    <ul class="space-y-4">
                        <?php if($res_groups_db && $res_groups_db->num_rows > 0): ?>
                            <?php while($g = $res_groups_db->fetch_assoc()): 
                                $pct = min($g['total']*5, 100); ?>
                            <li class="text-sm">
                                <div class="flex justify-between mb-1">
                                    <span class="font-semibold text-gray-700 truncate w-3/4"><?= htmlspecialchars($g['group_name'] ?? 'Unknown Group') ?></span>
                                    <span class="text-xs font-bold text-gray-500"><?= $g['total'] ?></span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-gradient-to-r from-orange-400 to-red-500 h-1.5 rounded-full" style="width: <?= $pct ?>%"></div>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="text-gray-400 text-center text-sm">Belum ada data.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </section>

        <section id="warnings" class="scroll-mt-24">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-1 bg-red-600 rounded-full"></div>
                    <h2 class="text-2xl font-bold text-gray-800">Daftar Warning</h2>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200">
                            <tr>
                                <th class="p-4 w-10">No</th>
                                <th class="p-4">Author / Pelanggar</th>
                                <th class="p-4">Grup</th>
                                <th class="p-4 text-center">Poin</th>
                                <th class="p-4">Pesan Toxic</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php 
                            if($res_warnings && $res_warnings->num_rows > 0):
                                $no=1; while($row = $res_warnings->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition group">
                                    <td class="p-4 text-gray-400"><?= $no++ ?></td>
                                    <td class="p-4 font-medium text-gray-900"><?= htmlspecialchars($row['author']) ?></td>
                                    <td class="p-4 text-xs font-mono text-indigo-600 bg-indigo-50 px-2 py-1 rounded w-fit">
                                        <?= htmlspecialchars(substr($row['group_id'], 0, 15)) ?>...
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-700 font-bold text-xs">
                                            <?= $row['warning_count'] ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-gray-600 italic max-w-xs truncate">
                                        "<?= htmlspecialchars($row['message']) ?>"
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
                                            <button onclick='openEditModal(<?= json_encode($row) ?>)' class="bg-blue-100 text-blue-600 p-1.5 rounded hover:bg-blue-200">✏️</button>
                                            <a href="?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Hapus permanen data ini?')" class="bg-red-100 text-red-600 p-1.5 rounded hover:bg-red-200">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; 
                            else: ?>
                                <tr><td colspan="6" class="p-6 text-center text-gray-400">Belum ada data warning.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="groups" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="h-8 w-1 bg-green-500 rounded-full"></div>
                <h2 class="text-2xl font-bold text-gray-800">Grup Aktif (Live API)</h2>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="max-h-96 overflow-y-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 sticky top-0 z-10 border-b border-gray-200">
                            <tr>
                                <th class="p-4 w-12 text-center">#</th>
                                <th class="p-4">Nama Grup</th>
                                <th class="p-4">Group ID</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php 
                            if(empty($groups_api)) {
                                echo "<tr><td colspan='3' class='p-8 text-center text-gray-400'>
                                    Tidak ada data grup.<br>
                                    <span class='text-xs text-red-400'>Kemungkinan Bot OFF atau belum join grup.</span>
                                </td></tr>";
                            }
                            foreach($groups_api as $i => $g): 
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 text-center text-gray-400"><?= $i+1 ?></td>
                                <td class="p-4 font-bold text-gray-800"><?= htmlspecialchars($g['name']) ?></td>
                                <td class="p-4 font-mono text-xs text-green-600 select-all"><?= htmlspecialchars($g['id']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div id="editModal" class="fixed inset-0 bg-black/50 hidden z-[60] flex items-center justify-center backdrop-blur-sm transition-opacity">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 transform transition-all scale-95 opacity-0" id="modalContent">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <h3 class="text-xl font-bold text-gray-800">✏️ Edit Data Warning</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="update_warning" value="1">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Pelanggar (Author)</label>
                            <input type="text" name="author" id="edit_author" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pesan Toxic</label>
                            <textarea name="message" id="edit_message" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition"></textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3">
                        <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-lg font-semibold text-gray-600 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold text-white shadow-lg transition">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // 1. Chart JS Logic
            const rawData = <?= $heatmap_json ?>;
            const now = new Date();
            const currentHour = now.getHours();
            const labels = [];
            const dataPoints = [];

            // Rotasi jam agar jam sekarang ada di kanan
            for (let i = 0; i < 24; i++) {
                const hour = (currentHour + i + 1) % 24; 
                labels.push(hour.toString().padStart(2, '0') + ":00");
                dataPoints.push(rawData[hour] ?? 0);
            }

            const ctx = document.getElementById('timeChart');
            if(ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Jumlah Warning',
                            data: dataPoints,
                            backgroundColor: 'rgba(37, 99, 235, 0.6)', // Blue
                            borderColor: 'rgba(37, 99, 235, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }

            // 2. Modal Logic
            function openEditModal(data) {
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_author').value = data.author;
                document.getElementById('edit_message').value = data.message;
                
                const modal = document.getElementById('editModal');
                const content = document.getElementById('modalContent');
                
                modal.classList.remove('hidden');
                setTimeout(() => {
                    content.classList.remove('scale-95', 'opacity-0');
                    content.classList.add('scale-100', 'opacity-100');
                }, 10);
            }

            function closeModal() {
                const modal = document.getElementById('editModal');
                const content = document.getElementById('modalContent');
                
                content.classList.remove('scale-100', 'opacity-100');
                content.classList.add('scale-95', 'opacity-0');
                
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 200);
            }
        </script>

    </main>

    <footer class="border-t border-gray-200 mt-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 py-6 text-center text-gray-400 text-sm">
            &copy; <?= date('Y') ?> Bot Dashboard System. Dibuat dengan ❤️.
        </div>
    </footer>

</body>
</html>