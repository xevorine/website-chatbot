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

// --- QUERY DATABASE & TABLE SETUP ---
$check_table = "SHOW TABLES LIKE 'groups'";
$table_check = $conn->query($check_table);

if ($table_check->num_rows == 0) {
    $create_sql = "CREATE TABLE `groups` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id VARCHAR(255) NOT NULL UNIQUE,
        group_name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($create_sql);
    
    $sync_sql = "INSERT IGNORE INTO `groups` (group_id, group_name) 
                 SELECT DISTINCT group_id, CONCAT('Group - ', group_id) FROM warnings";
    $conn->query($sync_sql);
}

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
    <title>Dashboard Warning</title>
</head>

<body class="m-0 p-0 box-border font-sans flex h-screen bg-gray-100">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 p-8 overflow-y-auto">
        <div class="mb-8 bg-gradient-to-r from-indigo-500 to-purple-600 p-8 rounded-lg shadow-lg relative overflow-hidden">
            <div class="absolute -top-1/2 -right-1/2 w-96 h-96 bg-white/10 rounded-full"></div>
            <h2 class="text-white text-4xl font-bold relative z-10 m-0 drop-shadow">📋 Daftar Warning</h2>
            <p class="text-white/90 text-sm relative z-10 mt-2">Kelola dan pantau semua peringatan pengguna</p>
        </div>

        <div class="flex justify-end mb-2">
            <button onclick="toggleAllMasks()" class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded transition">
                 Buka/Tutup Semua Sensor
            </button>
        </div>

        <table class="border-collapse w-full bg-white shadow rounded-lg overflow-hidden">
            <thead class="bg-slate-700 text-white">
                <tr>
                    <th class="p-3 border border-gray-600 text-left font-bold">ID</th>
                    <th class="p-3 border border-gray-600 text-left font-bold">Author</th>
                    <th class="p-3 border border-gray-600 text-left font-bold">User ID</th>
                    <th class="p-3 border border-gray-600 text-left font-bold">Nama Group</th>
                    <th class="p-3 border border-gray-600 text-center font-bold">Count</th>
                    <th class="p-3 border border-gray-600 text-left font-bold">Last Warning</th>
                    <th class="p-3 border border-gray-600 text-left font-bold w-1/4">Message (Sensor)</th>
                    <th class="p-3 border border-gray-600 text-left font-bold">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    // ID unik untuk JS
                    $real_id = "real_" . $row['id'];
                    $mask_id = "mask_" . $row['id'];
                    
                    $safe_message = htmlspecialchars($row['message']);
                    
                    // Buat string bintang sepanjang pesan asli (maksimal 20 bintang biar rapi)
                    $msg_length = strlen($safe_message);
                    $stars = str_repeat("*", ($msg_length > 30 ? 30 : $msg_length)); 
                    if ($msg_length > 30) $stars .= "..."; 

                    echo "<tr class='hover:bg-gray-50 border-b border-gray-200'>
                        <td class='p-3 border-r border-gray-200 text-left'>{$row['id']}</td>
                        <td class='p-3 border-r border-gray-200 text-left'>{$row['author']}</td>
                        <td class='p-3 border-r border-gray-200 text-left text-xs'>{$row['user_id']}</td>
                        <td class='p-3 border-r border-gray-200 text-left text-sm'><strong>{$row['group_name']}</strong></td>
                        <td class='p-3 border-r border-gray-200 text-center'>{$row['warning_count']}</td>
                        <td class='p-3 border-r border-gray-200 text-left text-xs'>{$row['last_warning_at']}</td>
                        
                        <td class='p-3 border-r border-gray-200 text-left cursor-pointer select-none' onclick='toggleText(\"{$row['id']}\")'>
                            
                            <span id='$mask_id' class='font-mono text-gray-400 font-bold text-lg tracking-widest'>
                                $stars
                            </span>
                            
                            <span id='$real_id' class='hidden text-gray-800 font-medium bg-yellow-50 px-2 py-1 rounded border border-yellow-200'>
                                $safe_message
                            </span>
                            
                            <div class='text-[10px] text-gray-400 mt-1 italic'>(Klik untuk lihat)</div>
                        </td>

                        <td class='p-3 text-left'>
                            <div class='flex gap-1'>
                                <a class='px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition' href='edit.php?id={$row['id']}'>Edit</a>
                                <a class='px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition' href='delete.php?id={$row['id']}' onclick=\"return confirm('Yakin mau hapus data ini?');\">Del</a>
                            </div>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='8' class='text-center p-4 text-gray-500'>Tidak ada data warning.</td></tr>";
            }
            $conn->close();
            ?>
            </tbody>
        </table>
    </div>

    <script>
        // Fungsi Toggle per pesan
        function toggleText(id) {
            const maskEl = document.getElementById('mask_' + id);
            const realEl = document.getElementById('real_' + id);

            if (realEl.classList.contains('hidden')) {
                // Tampilkan pesan asli
                maskEl.classList.add('hidden');
                realEl.classList.remove('hidden');
            } else {
                // Sembunyikan lagi (kembali ke bintang)
                maskEl.classList.remove('hidden');
                realEl.classList.add('hidden');
            }
        }

        // Fungsi Buka/Tutup Semua
        function toggleAllMasks() {
            // Cek kondisi baris pertama untuk tentukan mau buka semua atau tutup semua
            const allMasks = document.querySelectorAll('[id^="mask_"]');
            const allReals = document.querySelectorAll('[id^="real_"]');
            
            // Jika elemen pertama tersembunyi (berarti sedang menampilkan pesan asli), maka kita tutup semua
            const shouldClose = allMasks[0] && allMasks[0].classList.contains('hidden');

            if (shouldClose) {
                // Tutup Semua (Show Stars)
                allMasks.forEach(el => el.classList.remove('hidden'));
                allReals.forEach(el => el.classList.add('hidden'));
            } else {
                // Buka Semua (Show Text)
                allMasks.forEach(el => el.classList.add('hidden'));
                allReals.forEach(el => el.classList.remove('hidden'));
            }
        }
    </script>

</body>
</html>