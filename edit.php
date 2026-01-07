<?php
include __DIR__ . '/connection.php';

$id = $_GET['id'];

// Ambil data berdasarkan ID dengan prepared statement
$sql = "SELECT * FROM warnings WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

// Ambil daftar groups untuk dropdown
$groups_sql = "SELECT group_id, group_name FROM `groups` ORDER BY group_name";
$groups_result = $conn->query($groups_sql);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Edit Warning</title>
</head>

<body class="bg-gray-100 h-screen overflow-hidden">

    <div class="flex h-full">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-8">
            <!-- Header -->
            <div
                class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-8 mb-8 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-96 h-96 bg-white opacity-10 rounded-full -mr-20 -mt-20"></div>
                <div class="relative z-10">
                    <a href="index.php"
                        class="inline-block mb-4 bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded transition">←
                        Kembali</a>
                    <h2 class="text-4xl font-bold">✏️ Edit Warning</h2>
                    <p class="text-indigo-100 mt-2">ID: <?= htmlspecialchars($id) ?></p>
                </div>
            </div>

            <!-- Form -->
            <div class="max-w-2xl bg-white rounded-lg shadow-md p-8">
                <form action="update.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($data['id']) ?>">

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">User ID *</label>
                        <input type="text" name="user_id" value="<?= htmlspecialchars($data['user_id']) ?>" readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Group ID *</label>
                        <select name="group_id" readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                            <option value="">-- Pilih Group --</option>
                            <?php
                            if ($groups_result->num_rows > 0) {
                                while ($group = $groups_result->fetch_assoc()) {
                                    $selected = ($data['group_id'] == $group['group_id']) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($group['group_id']) . "\" $selected>" . htmlspecialchars($group['group_name']) . " (" . htmlspecialchars($group['group_id']) . ")</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Warning Count *</label>
                        <input type="number" name="warning_count"
                            value="<?= htmlspecialchars($data['warning_count']) ?>" readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Last Warning At *</label>
                        <input type="datetime-local" name="last_warning_at"
                            value="<?= date('Y-m-d\TH:i', strtotime($data['last_warning_at'])) ?>" readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Message *</label>
                        <textarea name="message" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 h-24 resize-none"><?= htmlspecialchars($data['message']) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Author *</label>
                        <input type="text" name="author" value="<?= htmlspecialchars($data['author']) ?>" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="submit"
                            class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-lg transition">💾
                            Update</button>
                        <a href="index.php"
                            class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-6 rounded-lg transition text-center">❌
                            Batal</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

</body>

</html>

<?php
$stmt->close();
$conn->close();
?>