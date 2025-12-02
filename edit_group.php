<?php
include __DIR__ . '/connection.php';

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

$group_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$group_id) {
    header("Location: group.php");
    exit;
}

// Get group data
$sql = "SELECT * FROM `groups` WHERE group_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $group_id);
$stmt->execute();
$result = $stmt->get_result();
$group = $result->fetch_assoc();

// Get warning stats
$warn_sql = "SELECT COUNT(*) as total_warnings, COUNT(DISTINCT user_id) as total_users FROM warnings WHERE group_id = ?";
$warn_stmt = $conn->prepare($warn_sql);
$warn_stmt->bind_param("s", $group_id);
$warn_stmt->execute();
$warn_result = $warn_stmt->get_result();
$warn_stats = $warn_result->fetch_assoc();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
	<title>Edit Group - Dashboard</title>
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
            <li class="m-0"><a href="#" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400">⚙️ Settings</a></li>
            <li class="m-0"><a href="#" class="block p-4 text-white no-underline transition-all duration-300 border-l-4 border-transparent hover:bg-slate-700 hover:border-l-blue-400">📋 Reports</a></li>
        </ul>
    </nav>
</div>

<!-- Main Content -->
<div class="flex-1 p-8 overflow-y-auto">
    <a href="group.php" class="inline-block mb-5 px-4 py-2 bg-slate-700 text-white rounded no-underline transition-all duration-300 hover:bg-slate-600">← Kembali ke Groups</a>
    
    <div class="mb-8 bg-gradient-to-r from-indigo-500 to-purple-600 p-8 rounded-lg shadow-lg relative overflow-hidden">
        <div class="absolute -top-1/2 -right-1/2 w-96 h-96 bg-white/10 rounded-full"></div>
        <h2 class="text-white text-4xl font-bold relative z-10 m-0 drop-shadow">✏️ Edit Group</h2>
        <p class="text-white/90 text-sm relative z-10 mt-2">Kelola informasi grup: <?php echo htmlspecialchars($group_id); ?></p>
    </div>
    
    <div class="bg-white p-8 rounded-lg shadow">
        <!-- Statistics -->
        <div class="grid grid-cols-2 gap-4 mb-8">
            <div class="bg-gray-50 p-4 rounded border-l-4 border-indigo-500">
                <div class="text-gray-500 text-xs">Total Warnings</div>
                <div class="text-2xl font-bold text-slate-700 mt-1"><?php echo $warn_stats['total_warnings']; ?></div>
            </div>
            <div class="bg-gray-50 p-4 rounded border-l-4 border-indigo-500">
                <div class="text-gray-500 text-xs">Total Users</div>
                <div class="text-2xl font-bold text-slate-700 mt-1"><?php echo $warn_stats['total_users']; ?></div>
            </div>
        </div>
        
        <form method="POST" action="update_group.php">
            <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">
            
            <div class="mb-5">
                <label for="group_id_display" class="block mb-2 font-bold text-slate-700">Group ID (Read-only)</label>
                <input type="text" id="group_id_display" value="<?php echo htmlspecialchars($group_id); ?>" disabled class="w-full p-2 border border-gray-300 rounded text-sm font-sans">
            </div>
            
            <div class="mb-5">
                <label for="group_name" class="block mb-2 font-bold text-slate-700">Nama Group *</label>
                <input type="text" id="group_name" name="group_name" value="<?php echo htmlspecialchars($group['group_name']); ?>" required placeholder="Masukkan nama group" class="w-full p-2 border border-gray-300 rounded text-sm font-sans focus:outline-none focus:border-indigo-500 focus:shadow-md focus:shadow-indigo-300/30">
            </div>
            
            <div class="mb-5">
                <label for="description" class="block mb-2 font-bold text-slate-700">Deskripsi</label>
                <textarea id="description" name="description" placeholder="Masukkan deskripsi group (opsional)" class="w-full p-2 border border-gray-300 rounded text-sm font-sans resize-vertical min-h-24 focus:outline-none focus:border-indigo-500 focus:shadow-md focus:shadow-indigo-300/30"><?php echo htmlspecialchars($group['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="flex gap-2 mt-8">
                <button type="submit" class="flex-1 px-5 py-2 bg-green-500 text-white rounded font-bold transition-all duration-300 hover:bg-green-600">💾 Simpan</button>
                <a href="group.php" class="flex-1 px-5 py-2 bg-red-500 text-white rounded font-bold transition-all duration-300 hover:bg-red-600 no-underline text-center">❌ Batal</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>

<?php
$stmt->close();
$warn_stmt->close();
$conn->close();
?>
