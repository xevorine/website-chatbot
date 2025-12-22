<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
set_time_limit(0); // Mencegah time out
/*********************************************************
 * VISUAL DEBUG ONLY
 * - DB: simpan group_id
 * - UI: resolve group_name realtime (WAHA)
 *********************************************************/

session_start();
require __DIR__ . '/connection.php';

/* ===============================
   WAHA CONFIG
================================ */
$WAHA_API_BASE = "https://bwaha.004090.xyz/api";
$WAHA_SESSION  = "default";
$WAHA_API_KEY  = "0b08c9d2a8f6405d87c83538bc3892bc";

/* ===============================
   WAHA HELPERS
================================ */
/* ===============================
   WAHA HELPERS (VERSI FILE_GET_CONTENTS)
================================ */
function wahaGet(string $url): array
{
    global $WAHA_API_KEY;

    // Konfigurasi Header & SSL
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "X-Api-Key: $WAHA_API_KEY\r\n" .
                        "Accept: application/json\r\n",
            "timeout" => 10, // Timeout 10 detik
            "ignore_errors" => true // Tetap baca meski error 404/500
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ];

    $context = stream_context_create($opts);
    
    // Ambil data tanpa cURL
    $res = @file_get_contents($url, false, $context);

    if ($res === false) {
        return []; // Jika gagal konek, kembalikan array kosong
    }

    return json_decode($res, true) ?? [];
}

/* ===============================
   FETCH GROUPS
================================ */
function fetchGroups(): array
{
    global $WAHA_API_BASE, $WAHA_SESSION;
    return wahaGet("$WAHA_API_BASE/$WAHA_SESSION/groups");
}

/* ===============================
   FETCH PARTICIPANTS
================================ */
function fetchParticipants(string $groupId): array
{
    global $WAHA_API_BASE, $WAHA_SESSION;
    return wahaGet(
        "$WAHA_API_BASE/$WAHA_SESSION/groups/" .
        urlencode($groupId) . "/participants"
    );
}

/* ===============================
   BUILD MAPS
================================ */
function buildGroupMap(array $groups): array
{
    $map = [];

    foreach ($groups as $g) {
        if (isset($g['id']['_serialized'])) {
            $map[$g['id']['_serialized']] =
                $g['name'] ?? $g['subject'] ?? 'UNKNOWN GROUP';
        }
    }

    return $map;
}

function buildPushnamePhoneMap(array $participants): array
{
    $map = [];

    foreach ($participants as $p) {
        if (
            isset($p['pushname'], $p['id']['_serialized']) &&
            str_ends_with($p['id']['_serialized'], '@c.us')
        ) {
            $name = strtolower(trim($p['pushname']));
            $phone = explode('@', $p['id']['_serialized'])[0];

            if (!isset($map[$name])) {
                $map[$name] = $phone;
            }
        }
    }

    return $map;
}

function resolvePhone(string $author, array $map, bool &$byName): string
{
    $byName = false;
    $key = strtolower(trim($author));

    if (isset($map[$key])) {
        $byName = true;
        return $map[$key];
    }

    return 'UNKNOWN';
}

/* ===============================
   FETCH ALL WARNINGS
================================ */
$stmt = $conn->prepare("
    SELECT
        id,
        user_id,
        group_id,
        warning_count,
        last_warning_at,
        message,
        author
    FROM warnings
    ORDER BY id DESC
");
$stmt->execute();
$warnings = $stmt->get_result();

/* ===============================
   PRELOAD GROUP MAP
================================ */
$groupMap = buildGroupMap(fetchGroups());

/* ===============================
   PARTICIPANT CACHE
================================ */
$participantCache = [];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Warnings Debug</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="bg-gray-100 min-h-screen p-6">

<div class="max-w-7xl mx-auto bg-white shadow rounded p-6">

    <h1 class="text-2xl font-bold mb-4">Warnings Debug</h1>

    <table class="w-full border-collapse border text-sm">
        <thead class="bg-slate-700 text-white">
            <tr>
                <th class="p-2 border">Group</th>
                <th class="p-2 border">LID</th>
                <th class="p-2 border">Author</th>
                <th class="p-2 border">Phone</th>
                <th class="p-2 border">Method</th>
                <th class="p-2 border">Warning</th>
                <th class="p-2 border">Last At</th>
                <th class="p-2 border">Message</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $warnings->fetch_assoc()): ?>
            <?php
                $groupId   = $row['group_id'];
                $groupName = $groupMap[$groupId] ?? 'UNKNOWN GROUP';

                // cache participants per group
                if (!isset($participantCache[$groupId])) {
                    $participants = fetchParticipants($groupId);
                    $participantCache[$groupId] =
                        buildPushnamePhoneMap($participants);
                }

                $byName = false;
                $phone = resolvePhone(
                    $row['author'],
                    $participantCache[$groupId],
                    $byName
                );
            ?>
            <tr class="hover:bg-gray-50">
                <td class="p-2 border">
                    <div class="font-semibold">
                        <?= htmlspecialchars($groupName) ?>
                    </div>
                    <div class="text-xs text-gray-500 font-mono">
                        <?= htmlspecialchars($groupId) ?>
                    </div>
                </td>

                <td class="p-2 border font-mono">
                    <?= htmlspecialchars(str_replace('@lid', '', $row['user_id'])) ?>
                </td>

                <td class="p-2 border">
                    <?= htmlspecialchars($row['author']) ?>
                </td>

                <td class="p-2 border font-mono">
                    <?= htmlspecialchars($phone) ?>
                </td>

                <td class="p-2 border text-xs">
                    <?= $byName
                        ? '<span class="text-green-600">pushname</span>'
                        : '<span class="text-red-600">unknown</span>' ?>
                </td>

                <td class="p-2 border text-center">
                    <?= (int)$row['warning_count'] ?>
                </td>

                <td class="p-2 border text-xs">
                    <?= htmlspecialchars($row['last_warning_at']) ?>
                </td>

                <td class="p-2 border">
                    <?= htmlspecialchars($row['message']) ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</div>

</body>
</html>