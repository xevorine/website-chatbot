<?php
// Tentukan halaman aktif untuk highlight menu
$current_page = basename($_SERVER['PHP_SELF']);

// Helper sederhana untuk class active/inactive agar kodingan HTML lebih rapi
function getMenuClass($pageName, $currentPage)
{
    // Cek jika halaman saat ini sama dengan link, atau khusus untuk groups ada sub-halaman edit
    $isActive = ($pageName == $currentPage) || ($pageName == 'groups.php' && $currentPage == 'edit_group.php');

    // Style Active (Biru muda background, Teks biru tua) vs Inactive (Abu-abu)
    return $isActive
        ? 'bg-blue-100 text-blue-600  '
        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-700  ';
}
?>

<aside class="flex flex-col w-64 h-screen overflow-y-auto">
    <div class="bg-[#7286D3] p-8 shadow flex items-center justify-between">
        <i class="fa-solid fa-dice-d20 text-white text-2xl"></i>
        <span class="font-bold text-white text-xl">Bot Dashboard</span>
    </div>

    <div class="flex flex-col justify-between flex-1 px-5 py-3 shadow-xl bg-white">
        <nav class="-mx-3 space-y-6">

            <div class="space-y-3">
                <label class="px-3 text-xs text-gray-500 uppercase">Main Menu</label>

                <a class="flex items-center mt-3 px-3 py-2 transition-colors duration-300 transform rounded-lg <?= getMenuClass('index.php', $current_page) ?>"
                    href="index.php">
                    <i class="fa-solid fa-triangle-exclamation w-5 h-5 text-center"></i>
                    <span class="mx-2 text-sm font-medium">Daftar Warning</span>
                </a>

                <a class="flex items-center px-3 py-2 transition-colors duration-300 transform rounded-lg <?= getMenuClass('analytics.php', $current_page) ?>"
                    href="analytics.php">
                    <i class="fa-solid fa-chart-line w-5 h-5 text-center"></i>
                    <span class="mx-2 text-sm font-medium">Analytics & Insight</span>
                </a>
            </div>

            <div class="space-y-3">
                <label class="px-3 text-xs text-gray-500 uppercase ">Management</label>

                <a class="flex items-center mt-3 px-3 py-2 transition-colors duration-300 transform rounded-lg <?= getMenuClass('groups.php', $current_page) ?>"
                    href="groups.php">
                    <i class="fa-solid fa-folder-open w-5 h-5 text-center"></i>
                    <span class="mx-2 text-sm font-medium">Groups</span>
                </a>

                <a class="flex items-center px-3 py-2 transition-colors duration-300 transform rounded-lg <?= getMenuClass('bot_setting.php', $current_page) ?>"
                    href="bot_setting.php">
                    <i class="fa-solid fa-gears w-5 h-5 text-center"></i>
                    <span class="mx-2 text-sm font-medium">Bot Settings</span>
                </a>
            </div>
        </nav>

        <div class="mt-6 border-t border-gray-200  pt-4">
            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50  transition">
                <div class="flex items-center gap-x-2">
                    <div
                        class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xs">
                        <?= strtoupper(substr($_SESSION["username"] ?? "A", 0, 1)) ?>
                    </div>
                    <div>
                        <h2 class="text-sm font-medium text-gray-800  capitalize">
                            <?= htmlspecialchars($_SESSION["username"] ?? "Admin") ?>
                        </h2>
                        <span class="text-xs text-gray-500 ">Online</span>
                    </div>
                </div>

                <a href="index.php?logout=true"
                    class="text-gray-500 transition-colors duration-200 hover:text-red-500 focus:outline-none"
                    title="Logout">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </div>
</aside>