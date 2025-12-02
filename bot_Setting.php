<?php
session_start();
include __DIR__ . '/connection.php';

// Get QR Code (PNG Binary)
$qr_data = null;
$qr_error = null;
$url_qr_png = 'http://10.147.19.163:3000/api/default/auth/qr';

$ch = curl_init($url_qr_png);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['accept: image/png', 'X-Api-Key: yoursecretkey']);
$qr_response = curl_exec($ch);
$qr_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($qr_http_code === 200 && !empty($qr_response)) {
    file_put_contents('qr-code.png', $qr_response);
    $qr_data = base64_encode($qr_response);
} else {
    $qr_error = "Gagal mengambil QR Code (HTTP $qr_http_code)";
}

// Get Bot Profile
$profile_data = null;
$profile_error = null;
$bot_is_logged_in = false;
$bot_login_time = null;
$url_profile = 'http://10.147.19.163:3000/api/default/profile';

$ch = curl_init($url_profile);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['accept: application/json', 'X-Api-Key: yoursecretkey']);
$profile_response = curl_exec($ch);
$profile_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($profile_http_code === 200) {
    $profile_data = json_decode($profile_response, true);
    if ($profile_data && isset($profile_data['id']) && isset($profile_data['name'])) {
        $bot_is_logged_in = true;
        // Simpan waktu login ke session jika belum ada
        if (!isset($_SESSION['bot_login_time'])) {
            $_SESSION['bot_login_time'] = time();
        }
        $bot_login_time = $_SESSION['bot_login_time'];
    } else {
        // Response 200 tapi data tidak sesuai - berarti belum login
        if (isset($_SESSION['bot_login_time'])) {
            unset($_SESSION['bot_login_time']);
        }
    }
} else {
    // API error (timeout, connection error, etc) - jangan hapus session
    // Gunakan session yang sudah ada jika ada
    if (isset($_SESSION['bot_login_time'])) {
        $bot_is_logged_in = true;
        $bot_login_time = $_SESSION['bot_login_time'];
    }
    $profile_error = "Gagal mengambil profile bot (HTTP $profile_http_code)";
}

// Helper function untuk menghitung durasi
function calculateUptime($login_timestamp) {
    if (!$login_timestamp) return 'N/A';
    
    $current_time = time();
    $duration = $current_time - $login_timestamp;
    
    $days = floor($duration / 86400);
    $hours = floor(($duration % 86400) / 3600);
    $minutes = floor(($duration % 3600) / 60);
    $seconds = $duration % 60;
    
    if ($days > 0) {
        return "{$days}d {$hours}h {$minutes}m";
    } elseif ($hours > 0) {
        return "{$hours}h {$minutes}m {$seconds}s";
    } elseif ($minutes > 0) {
        return "{$minutes}m {$seconds}s";
    } else {
        return "{$seconds}s";
    }
}

// Handle Logout
$logout_success = false;
$logout_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    $url_logout = 'http://10.147.19.163:3000/api/sessions/default/logout';
    $ch = curl_init($url_logout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Api-Key: yoursecretkey'
    ]);
    $logout_response = curl_exec($ch);
    $logout_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($logout_http_code === 200) {
        $logout_success = true;
        sleep(1);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $logout_error = "Gagal logout: HTTP $logout_http_code";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
	<title>Bot Settings - Dashboard</title>
	<script>
		// Store login time in localStorage
		const botLoginTime = <?php echo $bot_is_logged_in ? (isset($_SESSION['bot_login_time']) ? $_SESSION['bot_login_time'] * 1000 : 'Date.now()') : 'null'; ?>;
		const isBotLoggedIn = <?php echo $bot_is_logged_in ? 'true' : 'false'; ?>;

		// Update uptime in real-time
		function updateUptime() {
			if (!isBotLoggedIn || !botLoginTime) {
				document.getElementById('uptime-display').textContent = 'N/A';
				return;
			}

			const now = Date.now();
			const duration = Math.floor((now - botLoginTime) / 1000); // in seconds

			const days = Math.floor(duration / 86400);
			const hours = Math.floor((duration % 86400) / 3600);
			const minutes = Math.floor((duration % 3600) / 60);
			const seconds = duration % 60;

			let uptimeText = '';
			if (days > 0) {
				uptimeText = `${days}d ${hours}h ${minutes}m`;
			} else if (hours > 0) {
				uptimeText = `${hours}h ${minutes}m ${seconds}s`;
			} else if (minutes > 0) {
				uptimeText = `${minutes}m ${seconds}s`;
			} else {
				uptimeText = `${seconds}s`;
			}

			document.getElementById('uptime-display').textContent = uptimeText;
		}

		// Update uptime every second
		setInterval(updateUptime, 1000);
		updateUptime();
		console.log('📊 Uptime tracker initialized - updating every 1 second');
		console.log('Bot Login Time:', new Date(botLoginTime).toLocaleString());
		console.log('Bot Logged In:', isBotLoggedIn);
		console.log('Bot Login Timestamp:', botLoginTime);

		// Auto-refresh QR code every 15 seconds
		function refreshQRCode() {
			if (!isBotLoggedIn) {
				const qrImg = document.getElementById('qr-code-img');
				if (qrImg) {
					// Add timestamp to force refresh
					const newSrc = qrImg.src.split('?')[0] + '?t=' + Date.now();
					console.log('🔄 QR Code refreshing at:', new Date().toLocaleTimeString());
					console.log('Old src:', qrImg.src);
					console.log('New src:', newSrc);
					qrImg.src = newSrc;
					console.log('✅ QR Code refreshed successfully');
				} else {
					console.log('❌ QR Code element not found');
				}
			} else {
				console.log('⏭️ Bot sudah login, skip QR refresh');
			}
		}

		// Refresh QR every 15 seconds (only when not logged in)
		console.log('🚀 QR Code auto-refresh initialized - every 15 seconds');
		setInterval(refreshQRCode, 15000);
	</script>
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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left: QR Code / Login Status -->
            <div>
                <div class="bg-white rounded-lg shadow-md overflow-hidden h-full">
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-bold text-slate-800">📱 <?php echo $bot_is_logged_in ? 'Status Login' : 'QR Code'; ?></h3>
                    </div>
                    <div class="p-6 flex flex-col items-center justify-center min-h-96">
                        <?php if ($bot_is_logged_in): ?>
                            <!-- Bot Sudah Login -->
                            <div class="text-center space-y-6 w-full">
                                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-green-100 border-4 border-green-200">
                                    <span class="text-5xl">✅</span>
                                </div>
                                <div>
                                    <h4 class="text-2xl font-bold text-green-700 mb-2">Bot Telah Login</h4>
                                    <p class="text-gray-600 text-sm">Bot WhatsApp sudah terhubung dan siap digunakan</p>
                                </div>
                                <form method="POST" class="w-full">
                                    <input type="hidden" name="action" value="logout">
                                    <button type="submit" onclick="return confirm('Yakin ingin logout dari bot?')" class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-4 rounded-lg transition shadow-md">
                                        🚪 Logout Bot
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($qr_error): ?>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 w-full text-center">
                                <p class="text-red-700 text-sm font-semibold">⚠️ Error</p>
                                <p class="text-red-600 text-xs mt-2"><?php echo htmlspecialchars($qr_error); ?></p>
                            </div>
                        <?php elseif ($qr_data): ?>
                            <div class="text-center">
                                <img id="qr-code-img" src="data:image/png;base64,<?php echo htmlspecialchars($qr_data); ?>" 
                                     alt="QR Code" 
                                     class="w-56 h-56 rounded-lg border-4 border-indigo-200 shadow-lg mx-auto">
                                <p class="text-sm text-gray-600 mt-4 font-semibold">✅ Scan untuk connect</p>
                                <a href="qr-code.png" download class="inline-block mt-3 bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                                    📥 Download QR
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 w-full text-center">
                                <p class="text-yellow-700 text-sm font-semibold">⏳ Loading QR Code...</p>
                                <div class="mt-4 flex justify-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-yellow-500"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Bot Profile -->
            <div>
                <div class="bg-white rounded-lg shadow-md overflow-hidden h-full">
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-bold text-slate-800">👤 Bot Profile</h3>
                    </div>
                    <div class="p-6">
                        <?php if ($profile_error && !$bot_is_logged_in): ?>
                            <div class="flex flex-col items-center justify-center h-96">
                                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-red-100 border-4 border-red-200 mb-4">
                                    <span class="text-5xl">❌</span>
                                </div>
                                <h4 class="text-2xl font-bold text-red-700 mb-2">Bot Belum Login</h4>
                                <p class="text-gray-600 text-sm text-center">Bot WhatsApp belum terhubung. Silahkan scan QR code di sebelah kiri untuk login</p>
                            </div>
                        <?php elseif ($profile_data && $bot_is_logged_in): ?>
                            <div class="space-y-4 text-center">
                                <?php if (isset($profile_data['picture'])): ?>
                                    <div class="flex justify-center mb-4">
                                        <img src="<?php echo htmlspecialchars($profile_data['picture']); ?>" alt="Bot Profile" class="w-40 h-40 rounded-full border-4 border-indigo-200 object-cover shadow-lg">
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($profile_data['name'])): ?>
                                    <div class="pb-3 border-b border-gray-200">
                                        <span class="text-gray-600 font-semibold text-sm block mb-2">Bot Name</span>
                                        <p class="text-gray-800 font-bold text-lg"><?php echo htmlspecialchars($profile_data['name']); ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($profile_data['id'])): ?>
                                    <div class="pb-3 border-b border-gray-200">
                                        <span class="text-gray-600 font-semibold text-sm block mb-2">ID Bot</span>
                                        <span class="text-gray-800 font-mono bg-gray-100 px-3 py-1 rounded text-xs"><?php echo htmlspecialchars($profile_data['id']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="pt-2">
                                    <span class="inline-flex items-center gap-2 bg-green-500 text-white px-4 py-2 rounded-full text-sm font-semibold">
                                        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                        Online
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center h-96">
                                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-yellow-100 border-4 border-yellow-200 mb-4">
                                    <span class="text-5xl">⏳</span>
                                </div>
                                <h4 class="text-2xl font-bold text-yellow-700 mb-2">Bot Belum Login</h4>
                                <p class="text-gray-600 text-sm text-center">Scan QR code di sebelah kiri untuk memulai login bot</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bot Status & Quick Actions -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Bot Status -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <h4 class="text-lg font-bold text-slate-800">🤖 Bot Status</h4>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                        <span class="text-gray-600 font-semibold">Status</span>
                        <span class="inline-flex items-center gap-2 <?php echo $bot_is_logged_in ? 'bg-green-500' : 'bg-yellow-500'; ?> text-white px-4 py-1 rounded-full text-sm font-semibold">
                            <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                            <?php echo $bot_is_logged_in ? 'Online' : 'Waiting'; ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                        <span class="text-gray-600 font-semibold">Session</span>
                        <span class="text-gray-800 font-mono bg-gray-100 px-3 py-1 rounded">default</span>
                    </div>
                    <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                        <span class="text-gray-600 font-semibold">API Endpoint</span>
                        <span class="text-gray-800 text-sm font-mono bg-gray-100 px-3 py-1 rounded">10.147.19.163:3000</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 font-semibold">Uptime</span>
                        <span id="uptime-display" class="text-gray-800 font-semibold font-mono bg-gray-100 px-3 py-1 rounded">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <h4 class="text-lg font-bold text-slate-800">⚡ Quick Actions</h4>
                </div>
                <div class="p-6 space-y-3">
                    <button onclick="location.reload()" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg transition shadow-md">
                        🔄 Refresh Semua Data
                    </button>
                    <button class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded-lg transition shadow-md <?php echo !$bot_is_logged_in ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo !$bot_is_logged_in ? 'disabled' : ''; ?>>
                        ✅ Restart Bot
                    </button>
                    <button class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-3 px-4 rounded-lg transition shadow-md <?php echo !$bot_is_logged_in ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo !$bot_is_logged_in ? 'disabled' : ''; ?>>
                        📊 View Logs
                    </button>
                    <button class="w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition shadow-md">
                        ⚙️ Settings
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
