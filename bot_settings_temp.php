<?php
include __DIR__ . '/connection.php';

// Get screenshot (Base64)
$screenshot_data = null;
$screenshot_error = null;
$url_screenshot = 'http://10.147.19.163:3000/api/screenshot?session=default';

$ch = curl_init($url_screenshot);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json',
    'X-Api-Key: yoursecretkey'
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    if (isset($data['data'])) {
        $screenshot_data = $data['data'];
    } else {
        $screenshot_error = "Format respons screenshot tidak sesuai";
    }
} else {
    $screenshot_error = "Gagal mengambil screenshot (HTTP $http_code)";
}

// Get QR Code (PNG Binary)
$qr_data = null;
$qr_error = null;
$url_qr = 'http://10.147.19.163:3000/api/qr?session=default';

$ch = curl_init($url_qr);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: image/png',
    'X-Api-Key: yoursecretkey'
]);
$qr_response = curl_exec($ch);
$qr_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($qr_http_code === 200 && !empty($qr_response)) {
    $qr_data = base64_encode($qr_response);
} else {
    $qr_error = "Gagal mengambil QR Code (HTTP $qr_http_code)";
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
	<title>Bot Settings - Dashboard</title>
</head>
<body class="bg-gray-100 h-screen overflow-hidden">

<div class="flex h-full">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-800 text-white overflow-y-auto shadow-lg">
        <div class="border-b border-slate-700 p-6 text-center">
            <h1 class="text-2xl font-bold">Dashboard</h1>
        </div>
        <nav class="py-6">
            <ul class="space-y-1">
                <li><a href="index.php" class="block px-6 py-3 hover:bg-slate-700 border-l-4 border-transparent hover:border-blue-400 transition">📊 Daftar Warning</a></li>
                <li><a href="#" class="block px-6 py-3 hover:bg-slate-700 border-l-4 border-transparent hover:border-blue-400 transition">👥 User Management</a></li>
                <li><a href="groups.php" class="block px-6 py-3 hover:bg-slate-700 border-l-4 border-transparent hover:border-blue-400 transition">📁 Groups</a></li>
                <li><a href="bot_Setting.php" class="block px-6 py-3 bg-blue-600 border-l-4 border-blue-400 text-white hover:bg-blue-700 transition">⚙️ Bot Settings</a></li>
                <li><a href="#" class="block px-6 py-3 hover:bg-slate-700 border-l-4 border-transparent hover:border-blue-400 transition">📋 Reports</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg shadow-lg p-8 mb-8 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white opacity-10 rounded-full -mr-20 -mt-20"></div>
            <h2 class="text-4xl font-bold relative z-10">⚙️ Bot Settings</h2>
            <p class="text-green-100 mt-2 relative z-10">Pantau status bot, QR code, dan screenshot secara real-time</p>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- QR Code Section -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md overflow-hidden h-full">
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-bold text-slate-800">📱 QR Code</h3>
                    </div>
                    <div class="p-6 flex flex-col items-center justify-center min-h-96">
                        <?php if ($qr_error): ?>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 w-full text-center">
                                <p class="text-red-700 text-sm">⚠️ <?php echo htmlspecialchars($qr_error); ?></p>
                            </div>
                        <?php elseif ($qr_data): ?>
                            <img src="data:image/png;base64,<?php echo htmlspecialchars($qr_data); ?>" 
                                 alt="QR Code" 
                                 class="w-56 h-56 rounded-lg border-4 border-indigo-200 shadow-lg">
                            <p class="text-sm text-gray-600 mt-4 text-center font-semibold">✅ Scan untuk connect</p>
                        <?php else: ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 w-full text-center">
                                <p class="text-yellow-700 text-sm">⏳ Loading QR Code...</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Screenshot Section -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg shadow-md overflow-hidden h-full flex flex-col">
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-bold text-slate-800">📸 Bot Screenshot</h3>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <?php if ($screenshot_error): ?>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                                <p class="text-red-700 font-semibold">⚠️ Error</p>
                                <p class="text-red-600 text-sm"><?php echo htmlspecialchars($screenshot_error); ?></p>
                            </div>
                        <?php elseif ($screenshot_data): ?>
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 flex-1 flex items-center justify-center overflow-auto">
                                <img src="data:image/png;base64,<?php echo htmlspecialchars($screenshot_data); ?>" 
                                     alt="Bot Screenshot" 
                                     class="max-w-full max-h-full rounded-lg border border-gray-300 shadow-md">
                            </div>
                            <div class="mt-4 text-sm text-gray-600 text-center">
                                <p>✅ Screenshot berhasil diambil pada: <span class="font-semibold"><?php echo date('Y-m-d H:i:s'); ?></span></p>
                            </div>
                        <?php else: ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex-1 flex items-center justify-center">
                                <p class="text-yellow-700 font-semibold">⏳ Loading screenshot...</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-6 bg-gray-50 border-t border-gray-200\">
                        <button onclick="location.reload()" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition shadow-md hover:shadow-lg">
                            🔄 Refresh All
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bot Status & Settings -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Bot Status -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <h4 class="text-lg font-bold text-slate-800">🤖 Bot Status</h4>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 font-semibold">Status</span>
                        <span class="inline-block bg-green-500 text-white px-4 py-1 rounded-full text-sm font-semibold">🟢 Online</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                        <span class="text-gray-600 font-semibold">Session</span>
                        <span class="text-gray-800 font-semibold">default</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                        <span class="text-gray-600 font-semibold">API Endpoint</span>
                        <span class="text-gray-800 font-semibold text-sm">10.147.19.163:3000</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                        <span class="text-gray-600 font-semibold">Uptime</span>
                        <span class="text-gray-800 font-semibold">99.9%</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <h4 class="text-lg font-bold text-slate-800">⚡ Quick Actions</h4>
                </div>
                <div class="p-6 space-y-3">
                    <button onclick="location.reload()" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                        🔄 Refresh Data
                    </button>
                    <button class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                        ✅ Restart Bot
                    </button>
                    <button class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                        📊 View Logs
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
