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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Dashboard Warning</title>
</head>

<body class="flex h-screen bg-gray-100 font-sans">

<?php include "sidebar.php"; ?>

<div class="flex-1 overflow-y-auto">

<div class="bg-[#8EA7E9] p-8 shadow flex items-center justify-between mb-8">
            <p class="text-white font-bold text-xl">Daftar Warning</h2>
    <p class="text-white/80 text-sm">
        Jam lokal Anda (WIB): <span id="localClock"></span>
    </p>
    </div>

<div  class="px-8">

    <!-- <div class="flex justify-end mb-3">
        <button onclick="toggleAllMasks()"
            class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded text-sm font-semibold">
            Buka / Tutup Semua Pesan
        </button>
    </div> -->

<section class=" px-4 mx-auto">
    <div class="flex flex-col mt-6">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden border border-gray-200 md:rounded-lg">
                    
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3.5 text-sm font-medium text-left text-gray-500">No</th>
                                <th class="px-4 py-3.5 text-sm font-medium text-left text-gray-500">Author</th>
                                <th class="px-4 py-3.5 text-sm font-medium text-left text-gray-500">User ID</th>
                                <th class="px-4 py-3.5 text-sm font-medium text-left text-gray-500">Group</th>
                                <th class="px-4 py-3.5 text-sm font-medium text-center text-gray-500">Count</th>
                                <th class="px-4 py-3.5 text-sm font-medium text-left text-gray-500">Last Warning (WIB)</th>
                                <th class="px-4 py-3.5 text-sm font-medium text-left text-gray-500">Message</th>
                                <th class="px-4 py-3.5 text-sm font-medium text-left text-gray-500">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $no = 1;
                        while ($row = $result->fetch_assoc()):
                            $safeMsg = htmlspecialchars($row["message"]);
                            $stars = str_repeat("*", min(strlen($safeMsg), 30)) . (strlen($safeMsg) > 30 ? "..." : "");
                        ?>
                            <tr>
                                <td class="px-4 py-4 text-sm font-medium text-gray-700 text-center"><?= $no ?></td>
                                
                                <td class="px-4 py-4 text-sm text-gray-700"><?= htmlspecialchars($row["author"]) ?></td>
                                
                                <td class="px-4 py-4 text-sm text-gray-500"><?= htmlspecialchars($row["user_id"]) ?></td>

                                <td class="px-4 py-4 text-sm font-semibold text-indigo-600"
                                    data-group-id="<?= htmlspecialchars($row["group_id"]) ?>">
                                    Loading...
                                </td>

                                <td class="px-4 py-4 text-sm text-center"><?= (int)$row["warning_count"] ?></td>

                                <td class="px-4 py-4 text-sm text-gray-500 warning-time"
                                    data-time="<?= htmlspecialchars($row["last_warning_at"]) ?>">
                                    Loading...
                                </td>

                                <td class="px-4 py-4 text-sm cursor-pointer select-none" onclick="toggleText(<?= $row["id"] ?>)">
                                    <span id="mask_<?= $row["id"] ?>" class="font-mono text-gray-400"><?= $stars ?></span>
                                    <span id="real_<?= $row["id"] ?>" class="hidden bg-yellow-50 px-2 py-1 rounded border">
                                        <?= $safeMsg ?>
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-sm whitespace-nowrap">
                                    <div class="flex items-center gap-x-3">
                                        <a href="edit.php?id=<?= $row["id"] ?>" 
                                           class="text-blue-600 hover:text-blue-900 text-xs font-medium">
                                           Edit
                                        </a>
                                        <a href="delete.php?id=<?= $row["id"] ?>" 
                                           onclick="return confirm('Yakin hapus?')"
                                           class="text-red-600 hover:text-red-900 text-xs font-medium">
                                           Del
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php
                            $no++;
                        endwhile;
                        ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
</section>


</div>

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