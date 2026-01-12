<?php
session_start();
include __DIR__ . '/connection.php';

// --- 1. Query Top 5 Toxic Users ---
$sql_top_users = "SELECT MAX(author) as author, user_id, SUM(warning_count) as total_violation 
                  FROM warnings 
                  GROUP BY user_id 
                  ORDER BY total_violation DESC 
                  LIMIT 5";
$res_top_users = $conn->query($sql_top_users);

// --- 2. Query Top Groups ---
$sql_top_groups = "SELECT g.group_name, w.group_id, COUNT(*) as total_events 
                   FROM warnings w
                   LEFT JOIN `groups` g ON w.group_id = g.group_id
                   GROUP BY w.group_id 
                   ORDER BY total_events DESC 
                   LIMIT 5";
$res_top_groups = $conn->query($sql_top_groups);

// --- 3. Query Heatmap Jam (WIB / GMT+7) ---
// Menggunakan DATE_ADD untuk kompabilitas maksimal (menambah 7 jam dari waktu server)
// Asumsi: Server menyimpan waktu dalam UTC. Jika server sudah WIB, ubah INTERVAL jadi 0.
$sql_heatmap = "SELECT HOUR(DATE_ADD(last_warning_at, INTERVAL 7 HOUR)) as jam, COUNT(*) as jumlah 
                FROM warnings 
                GROUP BY jam 
                ORDER BY jam ASC";
$res_heatmap = $conn->query($sql_heatmap);

if (!$res_heatmap) {
    die("Error Query Heatmap: " . $conn->error);
}

$heatmap_data = [];
while ($row = $res_heatmap->fetch_assoc()) {
    $heatmap_data[$row['jam']] = $row['jumlah'];
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Analytics & Insight</title>
</head>

<body class="bg-gray-100 h-screen overflow-hidden">

    <div class="flex h-full">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="flex-1 overflow-y-auto">

            <div class="bg-[#8EA7E9] p-8 shadow flex items-center justify-between">
                <p class="text-white font-bold text-xl">Analytics & Insight</h2>
                <p class="text-white/80 text-sm">
                    Analisa perilaku user dan grup secara visual
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-8">

                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">💀 Top 5 Toxic Users</h3>
                    <table class="w-full">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-2 py-2 text-left">User</th>
                                <th class="px-2 py-2 text-right">Total Poin</th>
                                <th class="px-2 py-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            if ($res_top_users && $res_top_users->num_rows > 0) {
                                $rank = 1;
                                while ($u = $res_top_users->fetch_assoc()) {
                                    $bg = $rank == 1 ? 'bg-red-100 text-red-700' : '';
                                    echo "<tr class='$bg hover:bg-gray-50'>";
                                    echo "<td class='px-2 py-3'>
                                            <div class='font-bold'>" . htmlspecialchars($u['author']) . "</div>
                                            <div class='text-xs text-gray-400'>" . htmlspecialchars($u['user_id']) . "</div>
                                          </td>";
                                    echo "<td class='px-2 py-3 text-right font-mono font-bold text-red-500'>" . $u['total_violation'] . "</td>";
                                    echo "<td class='px-2 py-3 text-center'>" . ($rank == 1 ? '👑' : '#' . $rank) . "</td>";
                                    echo "</tr>";
                                    $rank++;
                                }
                            } else {
                                echo "<tr><td colspan='3' class='text-center py-4 text-gray-400'>Belum ada data</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">📢 Grup Paling "Toxic"</h3>
                    <ul class="space-y-4">
                        <?php
                        if ($res_top_groups && $res_top_groups->num_rows > 0) {
                            while ($g = $res_top_groups->fetch_assoc()) {
                                $width = min($g['total_events'] * 5, 100);
                                echo "<li>";
                                echo "<div class='flex justify-between mb-1'>";
                                echo "<span class='font-semibold text-gray-700'>" . htmlspecialchars($g['group_name'] ?? $g['group_id']) . "</span>";
                                echo "<span class='text-sm text-gray-500'>" . $g['total_events'] . " Warnings</span>";
                                echo "</div>";
                                echo "<div class='w-full bg-gray-200 rounded-full h-2.5'>";
                                echo "<div class='bg-orange-500 h-2.5 rounded-full' style='width: {$width}%'></div>";
                                echo "</div>";
                                echo "</li>";
                            }
                        } else {
                            echo "<p class='text-gray-400 text-center'>Belum ada data</p>";
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <div class="p-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-700 mb-4">⏰ Jam Rawan Pelanggaran (WIB / GMT+7)</h3>
                    <div class="h-64">
                        <canvas id="timeChart"></canvas>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        const rawData = <?php echo json_encode($heatmap_data); ?>;

        // Ambil JAM SEKARANG dari browser (WIB user)
        const now = new Date();
        const currentHour = now.getHours(); // 0–23 WIB

        const labels = [];
        const dataPoints = [];

        // Putar timeline agar sesuai jam sekarang
        for (let i = 0; i < 24; i++) {
            const hour = (currentHour + i) % 24;
            const label = hour.toString().padStart(2, '0') + ":00";

            labels.push(label);
            dataPoints.push(rawData[hour] ?? 0);
        }

        const ctx = document.getElementById('timeChart');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Warning (WIB Realtime)',
                    data: dataPoints,
                    borderWidth: 1,
                    borderRadius: 4,
                    backgroundColor: 'rgba(249, 115, 22, 0.8)',   // ORANGE (Tailwind orange-500)
                    borderColor: 'rgb(234, 88, 12)'               // ORANGE DARK
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>

</body>

</html>