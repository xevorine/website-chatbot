<?php
// Tentukan halaman aktif untuk highlight menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-slate-800 text-white overflow-y-auto shadow-lg flex flex-col h-full">
    <div class="border-b border-slate-700 p-6 text-center">
        <h1 class="text-2xl font-bold">Dashboard</h1>
    </div>
    <nav class="flex-1 py-6">
        <ul class="space-y-1">
            <li>
                <a href="index.php" class="block px-6 py-3 hover:bg-slate-700 border-l-4 transition <?php echo $current_page == 'index.php' ? 'bg-blue-600 border-blue-400' : 'border-transparent hover:border-blue-400'; ?>">
                    📊 Daftar Warning
                </a>
            </li>
            <li>
                <a href="analytics.php" class="block px-6 py-3 hover:bg-slate-700 border-l-4 transition <?php echo $current_page == 'analytics.php' ? 'bg-blue-600 border-blue-400' : 'border-transparent hover:border-blue-400'; ?>">
                    📈 Analytics & Insight <span class="bg-red-500 text-xs rounded px-1 ml-2">NEW</span>
                </a>
            </li>
            <li>
                <a href="groups.php" class="block px-6 py-3 hover:bg-slate-700 border-l-4 transition <?php echo $current_page == 'groups.php' || $current_page == 'edit_group.php' ? 'bg-blue-600 border-blue-400' : 'border-transparent hover:border-blue-400'; ?>">
                    📁 Groups
                </a>
            </li>
            <li>
                <a href="broadcast.php" class="block px-6 py-3 hover:bg-slate-700 border-l-4 transition <?php echo $current_page == 'broadcast.php' ? 'bg-blue-600 border-blue-400' : 'border-transparent hover:border-blue-400'; ?>">
                    📢 Broadcast
                </a>
            </li>
            <li>
                <a href="bot_setting.php" class="block px-6 py-3 hover:bg-slate-700 border-l-4 transition <?php echo $current_page == 'bot_setting.php' ? 'bg-blue-600 border-blue-400' : 'border-transparent hover:border-blue-400'; ?>">
                    ⚙️ Bot Settings
                </a>
            </li>
        </ul>
    </nav>

    <div class="border-t border-slate-700 p-4 space-y-3">
        <div class="bg-slate-700 rounded-lg p-3 text-center">
            <p class="text-xs text-slate-300">Logged in as</p>
            <p class="text-sm font-bold text-white"><?= htmlspecialchars($_SESSION["username"] ?? "Admin") ?></p>
        </div>
        <a href="index.php?logout=true" class="block w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-3 rounded-lg text-center transition text-sm">
            🚪 Logout
        </a>
    </div>
</aside>