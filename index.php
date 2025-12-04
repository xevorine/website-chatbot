<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

include __DIR__ . '/connection.php';

// --- QUERY DATABASE ---
// Cek apakah tabel groups sudah ada, kalau belum buat
$check_table = "SHOW TABLES LIKE 'groups'";
$table_check = $conn->query($check_table);

if ($table_check->num_rows == 0) {
    // Buat tabel
    $create_sql = "CREATE TABLE `groups` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id VARCHAR(255) NOT NULL UNIQUE,
        group_name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($create_sql);
    
    // Sync existing group_id
    $sync_sql = "INSERT IGNORE INTO `groups` (group_id, group_name) 
                 SELECT DISTINCT group_id, CONCAT('Group - ', group_id) FROM warnings";
    $conn->query($sync_sql);
}

// --- QUERY DATABASE dengan JOIN ke tabel groups ---
$sql = "SELECT w.id, w.user_id, w.group_id, g.group_name, w.warning_count, w.last_warning_at, w.message, w.author 
        FROM warnings w 
        LEFT JOIN `groups` g ON w.group_id = g.group_id 
        ORDER BY w.id DESC";
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
	<title>Dashboard</title>
</head>
<body class="m-0 p-0 box-border font-sans flex h-screen bg-gray-100">

<!-- Sidebar -->
<div class="w-64 bg-slate-800 text-white p-0 overflow-y-auto shadow-lg flex flex-col">
    <div class="p-5 text-center border-b-2 border-slate-700 mb-5">
        <h1 class="text-xl font-bold">Dashboard</h1>
    </div>
    <nav class="flex-1">
        <ul class="list-none m-0 p-0">
            <li class="m-0"><a href="index.php" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400 active:bg-blue-600 active:border-l-blue-400">📊 Daftar Warning</a></li>
            <li class="m-0"><a href="user_management.php" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400">👥 User Management</a></li>
            <li class="m-0"><a href="groups.php" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400">📁 Groups</a></li>
            <li class="m-0"><a href="bot_setting.php" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400">⚙️ Bot Settings</a></li>
            <li class="m-0"><a href="#" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400">📋 Reports</a></li>
        </ul>
    </nav>
    
    <!-- User Info & Logout -->
    <div class="border-t-2 border-slate-700 p-4 space-y-3">
        <div class="bg-slate-700 rounded-lg p-3 text-center">
            <p class="text-xs text-slate-300">Logged in as</p>
            <p class="text-sm font-bold text-white"><?= htmlspecialchars($_SESSION["username"] ?? "User") ?></p>
        </div>
        <a href="index.php?logout=true" class="block w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-3 rounded-lg text-center transition text-sm">
            🚪 Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="flex-1 p-8 overflow-y-auto">
    <div class="mb-8 bg-gradient-to-r from-indigo-500 to-purple-600 p-8 rounded-lg shadow-lg relative overflow-hidden">
        <div class="absolute -top-1/2 -right-1/2 w-96 h-96 bg-white/10 rounded-full"></div>
        <h2 class="text-white text-4xl font-bold relative z-10 m-0 drop-shadow">📋 Daftar Warning</h2>
        <p class="text-white/90 text-sm relative z-10 mt-2">Kelola dan pantau semua peringatan pengguna</p>
    </div>

<table class="border-collapse w-full mt-5 bg-white shadow">
    <tr>
        <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">ID</th>
        <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Author</th>
        <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">User ID</th>
        <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Nama Group</th>
        <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Warning Count</th>
        <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Last Warning At</th>
        <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Message</th>
        <th class="p-3 border border-gray-300 text-left bg-slate-700 text-white font-bold">Aksi</th>
    </tr>

    <?php
    if ($result->num_rows > 0) {

        while ($row = $result->fetch_assoc()) {

            echo "<tr class='hover:bg-gray-100'>
                <td class='p-3 border border-gray-300 text-left'>{$row['id']}</td>
                <td class='p-3 border border-gray-300 text-left'>{$row['author']}</td>
                <td class='p-3 border border-gray-300 text-left'>{$row['user_id']}</td>
                <td class='p-3 border border-gray-300 text-left'><strong>{$row['group_name']}</strong></td>
                <td class='p-3 border border-gray-300 text-left'>{$row['warning_count']}</td>
                <td class='p-3 border border-gray-300 text-left'>{$row['last_warning_at']}</td>
                <td class='p-3 border border-gray-300 text-left'>{$row['message']}</td>
                <td class='p-3 border border-gray-300 text-left'>
                    <a class='inline-block px-3 py-1 text-sm bg-blue-500 text-white rounded transition-opacity duration-300 hover:opacity-80 mr-1 no-underline' href='edit.php?id={$row['id']}'>Edit</a>
                    <a class='inline-block px-3 py-1 text-sm bg-red-500 text-white rounded transition-opacity duration-300 hover:opacity-80 mr-1 no-underline' href='delete.php?id={$row['id']}' onclick=\"return confirm('Yakin mau hapus data ini?');\">Delete</a>
                </td>
            </tr>";
        }

    } else {
        echo "<tr><td colspan='8' class='text-center p-3'>Tidak ada data</td></tr>";
    }

    $conn->close();
    ?>
</table>
</div>

</body>
</html>
