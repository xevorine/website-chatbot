<?php
include __DIR__ . '/connection.php';

// --- QUERY DATABASE ---
$sql = "SELECT w.group_id, g.group_name 
    FROM (SELECT DISTINCT group_id FROM warnings) w
    LEFT JOIN `groups` g ON w.group_id = g.group_id
    ORDER BY w.group_id";
$result = $conn->query($sql);

if (!$result) {
    die("Query Error: " . $conn->error);
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
	<title>Groups - Dashboard</title>
</head>
<body class="m-0 p-0 box-border font-sans flex h-screen bg-gray-100">

<!-- Sidebar -->
<div class="w-64 bg-slate-800 text-white p-0 overflow-y-auto shadow-lg">
    <div class="p-5 text-center border-b-2 border-slate-700 mb-5">
        <h1 class="text-xl font-bold">Dashboard</h1>
    </div>
    <nav>
        <ul class="list-none m-0 p-0">
            <li class="m-0"><a href="index.php" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400">📊 Daftar Warning</a></li>
            <li class="m-0"><a href="group.php" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400 bg-blue-600 border-l-blue-400">📁 Groups</a></li>
            <li class="m-0"><a href="#" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400">👥 User Management</a></li>
            <li class="m-0"><a href="bot_setting.php" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400">⚙️ Bot Settings</a></li>
            <li class="m-0"><a href="#" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400">📋 Reports</a></li>
        </ul>
    </nav>
</div>

<!-- Main Content -->
<div class="flex-1 p-8 overflow-y-auto">
    <div class="mb-8 bg-gradient-to-r from-indigo-500 to-purple-600 p-8 rounded-lg shadow-lg relative overflow-hidden">
        <div class="absolute -top-1/2 -right-1/2 w-96 h-96 bg-white/10 rounded-full"></div>
        <h2 class="text-white text-4xl font-bold relative z-10 m-0 drop-shadow">📁 Manajemen Groups</h2>
        <p class="text-white/90 text-sm relative z-10 mt-2">Kelola dan pantau semua grup pengguna</p>
    </div>
    
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
        <div class="bg-white p-5 rounded shadow">
            <div class="text-gray-700 text-sm">Total Groups</div>
            <?php
            $count_sql = "SELECT COUNT(DISTINCT group_id) as total FROM warnings";
            $count_result = $conn->query($count_sql);
            $count_row = $count_result->fetch_assoc();
            echo '<div class="text-3xl font-bold text-indigo-500 mt-2">' . $count_row['total'] . '</div>';
            ?>
        </div>
        <div class="bg-white p-5 rounded shadow">
            <div class="text-gray-700 text-sm">Total Warnings</div>
            <?php
            $warning_sql = "SELECT COUNT(*) as total FROM warnings";
            $warning_result = $conn->query($warning_sql);
            $warning_row = $warning_result->fetch_assoc();
            echo '<div class="text-3xl font-bold text-indigo-500 mt-2">' . $warning_row['total'] . '</div>';
            ?>
        </div>
    </div>

    <table class="border-collapse w-full bg-white shadow">
        <tr>
            <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">No</th>
            <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Group ID</th>
            <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Nama Group</th>
            <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Total Warnings</th>
            <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Avg Warning Per User</th>
            <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Last Warning</th>
            <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Aksi</th>
        </tr>

        <?php
        if ($result->num_rows > 0) {
            $no = 1;
            while ($row = $result->fetch_assoc()) {
                $group_id = $row['group_id'];
                $group_name = $row['group_name'] ?? 'N/A';

                // Get warning count for this group
                $count_sql = "SELECT COUNT(*) as total FROM warnings WHERE group_id = ?";
                $count_stmt = $conn->prepare($count_sql);
                $count_stmt->bind_param("s", $group_id);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $count_data = $count_result->fetch_assoc();
                $total_warnings = $count_data['total'];

                // Get unique users count for this group
                $users_sql = "SELECT COUNT(DISTINCT user_id) as total FROM warnings WHERE group_id = ?";
                $users_stmt = $conn->prepare($users_sql);
                $users_stmt->bind_param("s", $group_id);
                $users_stmt->execute();
                $users_result = $users_stmt->get_result();
                $users_data = $users_result->fetch_assoc();
                $total_users = $users_data['total'];

                // Calculate average
                $avg_warning = $total_users > 0 ? round($total_warnings / $total_users, 2) : 0;

                // Get last warning date
                $last_sql = "SELECT last_warning_at FROM warnings WHERE group_id = ? ORDER BY last_warning_at DESC LIMIT 1";
                $last_stmt = $conn->prepare($last_sql);
                $last_stmt->bind_param("s", $group_id);
                $last_stmt->execute();
                $last_result = $last_stmt->get_result();
                $last_data = $last_result->fetch_assoc();
                $last_warning = $last_data['last_warning_at'] ?? 'N/A';

                echo "<tr class='hover:bg-gray-100'>
                    <td class='p-3 border border-gray-300 text-left'>$no</td>
                    <td class='p-3 border border-gray-300 text-left'><strong>$group_id</strong></td>
                    <td class='p-3 border border-gray-300 text-left'>$group_name</td>
                    <td class='p-3 border border-gray-300 text-left'>$total_warnings</td>
                    <td class='p-3 border border-gray-300 text-left'>$avg_warning</td>
                    <td class='p-3 border border-gray-300 text-left'>$last_warning</td>
                    <td class='p-3 border border-gray-300 text-left'>
                        <a class='inline-block px-3 py-1 text-sm bg-green-500 text-white rounded transition-opacity duration-300 hover:opacity-80 mr-1 no-underline' href='group_detail.php?id=$group_id'>Lihat</a>
                        <a class='inline-block px-3 py-1 text-sm bg-blue-500 text-white rounded transition-opacity duration-300 hover:opacity-80 mr-1 no-underline' href='edit_group.php?id=$group_id'>Edit</a>
                    </td>
                </tr>";
                $no++;
            }
        } else {
            echo "<tr><td colspan='7' class='text-center p-8'><strong>Tidak ada data group</strong></td></tr>";
        }

        $conn->close();
        ?>
    </table>
</div>

</body>
</html>
