<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

require __DIR__ . "/connection.php";

/**
 * 1️⃣ Ambil semua warnings
 */
$sql = "SELECT id, author, user_id, group_id, warning_count, last_warning_at, message
        FROM warnings
        ORDER BY id DESC";

$result = $conn->query($sql);
if (!$result) {
    die("Query Error: " . $conn->error);
}

/**
 * 2️⃣ Ambil DISTINCT group_id (untuk mapping JS)
 */
$groupIds = [];
$q = $conn->query(
    "SELECT DISTINCT group_id FROM warnings WHERE group_id IS NOT NULL AND group_id != ''"
);
while ($r = $q->fetch_assoc()) {
    $groupIds[] = $r["group_id"];
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Dashboard Warning</title>
</head>

<body class="flex h-screen bg-gray-100 font-sans">

<?php include "sidebar.php"; ?>

<div class="flex-1 p-8 overflow-y-auto">

    <div class="mb-8 bg-gradient-to-r from-indigo-500 to-purple-600 p-8 rounded-lg shadow">
        <h2 class="text-white text-3xl font-bold">Daftar Warning</h2>
        <p class="text-white/80 text-sm mt-2">
            Jam lokal Anda (WIB): <span id="localClock"></span>
        </p>
    </div>

    <div class="flex justify-end mb-3">
        <button onclick="toggleAllMasks()"
            class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded text-sm font-semibold">
            Buka / Tutup Semua Pesan
        </button>
    </div>

    <table class="w-full bg-white shadow rounded-lg overflow-hidden text-sm">
        <thead class="bg-slate-700 text-white">
            <tr>
                <th class="p-3 text-left">No</th>
                <th class="p-3 text-left">Author</th>
                <th class="p-3 text-left">User ID</th>
                <th class="p-3 text-left">Group</th>
                <th class="p-3 text-center">Count</th>
                <th class="p-3 text-left">Last Warning (WIB)</th>
                <th class="p-3 text-left w-1/3">Message</th>
                <th class="p-3 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        while ($row = $result->fetch_assoc()):
            $safeMsg = htmlspecialchars($row["message"]);
            $stars = str_repeat("*", min(strlen($safeMsg), 30)) . (strlen($safeMsg) > 30 ? "..." : "");
        ?>
            <tr class="border-b hover:bg-gray-50">
                <td class="p-3 text-center font-semibold"><?= $no ?></td>
                <td class="p-3"><?= htmlspecialchars($row["author"]) ?></td>
                <td class="p-3 text-xs"><?= htmlspecialchars($row["user_id"]) ?></td>

                <td class="p-3 font-semibold text-indigo-600"
                    data-group-id="<?= htmlspecialchars($row["group_id"]) ?>">
                    Loading...
                </td>

                <td class="p-3 text-center"><?= (int)$row["warning_count"] ?></td>

                <!-- VISUAL TIME FIX -->
                <td class="p-3 text-xs warning-time"
                    data-time="<?= htmlspecialchars($row["last_warning_at"]) ?>">
                    Loading...
                </td>

                <td class="p-3 cursor-pointer select-none" onclick="toggleText(<?= $row["id"] ?>)">
                    <span id="mask_<?= $row["id"] ?>" class="font-mono text-gray-400"><?= $stars ?></span>
                    <span id="real_<?= $row["id"] ?>"
                          class="hidden bg-yellow-50 px-2 py-1 rounded border">
                        <?= $safeMsg ?>
                    </span>
                </td>

                <td class="p-3">
                    <a href="edit.php?id=<?= $row["id"] ?>"
                       class="px-2 py-1 bg-blue-500 text-white rounded text-xs">Edit</a>
                    <a href="delete.php?id=<?= $row["id"] ?>"
                       onclick="return confirm('Yakin hapus?')"
                       class="px-2 py-1 bg-red-500 text-white rounded text-xs">Del</a>
                </td>
            </tr>
        <?php
            $no++;
        endwhile;
        ?>
        </tbody>
    </table>
</div>

<!-- =============================
     DATA PHP → JS
============================= -->
<script>
const USED_GROUP_IDS = <?= json_encode($groupIds, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script>
/* =============================
   JAM LOKAL USER (WIB)
============================= */
function updateClock() {
    const now = new Date();
    document.getElementById("localClock").textContent =
        now.toLocaleTimeString("id-ID", { timeZone: "Asia/Jakarta" });
}
setInterval(updateClock, 1000);
updateClock();

/* =============================
   LOAD GROUP NAMES
============================= */
const API_URL = "https://bwaha.004090.xyz/api/default/groups";
const API_KEY = "lewishamilton";
const USED_SET = new Set(USED_GROUP_IDS);

(async function loadGroups() {
    try {
        const res = await fetch(API_URL, {
            headers: {
                "X-Api-Key": API_KEY,
                "Accept": "application/json"
            }
        });

        if (!res.ok) return;

        const data = await res.json();
        const map = {};

        data.forEach(g => {
            const gid = g?.id?._serialized || g?.id;
            if (USED_SET.has(gid)) {
                map[gid] = g.name || g.subject || gid;
            }
        });

        document.querySelectorAll("[data-group-id]").forEach(td => {
            const gid = td.dataset.groupId;
            td.textContent = map[gid] || "Unknown Group";
        });
    } catch (e) {
        console.error(e);
    }
})();

/* =============================
   KONVERSI WAKTU → WIB (VISUAL)
============================= */
document.querySelectorAll(".warning-time").forEach(td => {
    const raw = td.dataset.time;
    if (!raw) return;

    const date = new Date(raw.replace(" ", "T") + "Z");
    if (isNaN(date)) {
        td.textContent = raw;
        return;
    }

    td.textContent = date.toLocaleString("id-ID", {
        timeZone: "Asia/Jakarta",
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit"
    });
});

/* =============================
   MESSAGE TOGGLE
============================= */
function toggleText(id) {
    document.getElementById("mask_" + id)?.classList.toggle("hidden");
    document.getElementById("real_" + id)?.classList.toggle("hidden");
}

function toggleAllMasks() {
    const masks = document.querySelectorAll('[id^="mask_"]');
    const reals = document.querySelectorAll('[id^="real_"]');
    const open = masks[0]?.classList.contains("hidden");

    masks.forEach(m => m.classList.toggle("hidden", !open));
    reals.forEach(r => r.classList.toggle("hidden", open));
}
</script>

</body>
</html>