<?php
session_start();
include __DIR__ . '/connection.php';

// --- 1. Query Top 5 Toxic Users (SUDAH DIPERBAIKI) ---
// Menggunakan MAX(author) agar lolos validasi database mode strict
$sql_top_users = "SELECT MAX(author) as author, user_id, SUM(warning_count) as total_violation 
                  FROM warnings 
                  GROUP BY user_id 
                  ORDER BY total_violation DESC 
                  LIMIT 5";

$res_top_users = $conn->query($sql_top_users);

// Cek error query user
if (!$res_top_users) {
    die("Error Query Top Users: " . $conn->error);
}

// --- 2. Query Top Groups ---
$sql_top_groups = "SELECT g.group_name, w.group_id, COUNT(*) as total_events 
                   FROM warnings w
                   LEFT JOIN `groups` g ON w.group_id = g.group_id
                   GROUP BY w.group_id 
                   ORDER BY total_events DESC 
                   LIMIT 5";

$res_top_groups = $conn->query($sql_top_groups);

// Cek error query group
if (!$res_top_groups) {
    die("Error Query Top Groups: " . $conn->error);
}

// --- 3. Query Heatmap Jam ---
$sql_heatmap = "SELECT HOUR(last_warning_at) as jam, COUNT(*) as jumlah 
                FROM warnings 
                GROUP BY jam 
                ORDER BY jam ASC";
$res_heatmap = $conn->query($sql_heatmap);

// Cek error query heatmap
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
    <title>Analytics & Insight</title>
</head>

<body class="bg-gray-100 h-screen overflow-hidden">

    <div class="flex h-full">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="flex-1 overflow-y-auto p-8">

            <div class="bg-gradient-to-r from-orange-500 to-red-600 p-8 rounded-lg shadow-lg text-white mb-8">
                <h2 class="text-4xl font-bold">📈 Analytics & Insight</h2>
                <p class="text-orange-100 mt-2">Analisa perilaku user dan grup secara visual</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

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
                            if ($res_top_users->num_rows > 0) {
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
                    <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">📢 Grup Paling "Ramai"</h3>
                    <ul class="space-y-4">
                        <?php
                        if ($res_top_groups->num_rows > 0) {
                            while ($g = $res_top_groups->fetch_assoc()) {
                                // Hitung persentase sederhana untuk visual bar (asumsi max 100 utk contoh)
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

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-700 mb-4">⏰ Jam Rawan Pelanggaran (Waktu Server)</h3>
                <div class="h-64">
                    <canvas id="timeChart"></canvas>
                </div>
            </div>

        </main>
    </div>

    <script>
        // Data dari PHP ke JS
        const rawData = <?php echo json_encode($heatmap_data); ?>;

        // Siapkan label 00 - 23
        const labels = [];
        const dataPoints = [];

        for (let i = 0; i < 24; i++) {
            labels.push(i + ":00");
            dataPoints.push(rawData[i] || 0); // Jika tidak ada data jam itu, isi 0
        }

        const ctx = document.getElementById('timeChart');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Warning',
                    data: dataPoints,
                    backgroundColor: 'rgba(239, 68, 68, 0.7)', // Tailwind Red-500
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>