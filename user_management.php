<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include __DIR__ . '/connection.php';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>User Management - Dashboard</title>
</head>

<body class="m-0 p-0 box-border font-sans flex h-screen bg-gray-100">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 p-8 overflow-y-auto">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-800">👥 User Management</h1>
                <p class="text-gray-600 mt-2">Kelola anggota grup WhatsApp</p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Grup</label>
                        <select id="group-select"
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">-- Pilih Grup --</option>
                        </select>
                    </div>
                    <button id="btn-refresh-groups"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg transition">
                        🔄 Refresh Grup
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 bg-gray-50 border-b">
                    <h2 class="text-lg font-bold">📋 Daftar Anggota</h2>
                </div>

                <div id="members-container" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">No</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Nama</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Nomor ID</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Grup ID</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-700">Action</th>
                            </tr>
                        </thead>
                        <tbody id="members-table-body">
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    Pilih grup untuk melihat anggota
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold">➕ Tambah Anggota</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor WhatsApp</label>
                        <input type="text" id="input-phone" placeholder="Contoh: 6281234567890"
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Tanpa simbol + atau -</p>
                    </div>
                    <div class="flex items-end">
                        <button id="btn-add-member"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                            ✅ Tambah Anggota
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-kick-confirm"
        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-sm w-96 p-6">
            <h3 class="text-xl font-bold mb-4 text-gray-800">🚫 Konfirmasi Kick Anggota</h3>

            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-gray-700">
                    Yakin ingin mengeluarkan anggota?
                </p>
                <p id="modal-member-info" class="text-sm font-semibold text-red-600 mt-2">-</p>
            </div>

            <div class="flex gap-3">
                <button id="btn-modal-cancel"
                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition">
                    Batal
                </button>
                <button id="btn-modal-confirm-kick"
                    class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                    🚫 Kick
                </button>
            </div>
        </div>
    </div>

    <div id="loading-spinner" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-700 font-semibold">Memproses...</p>
        </div>
    </div>

    <script>
        const API_BASE = "http://10.242.61.248:3000/api";
        const API_KEY = "yoursecretkey";
        const SESSION = "default";

        let currentGroupId = null;
        let currentMemberId = null;
        let membersList = [];

        // --------------------------
        //  LOAD GROUPS
        // --------------------------
        async function loadGroups() {
            try {
                showLoading(true);

                let res = await fetch(`${API_BASE}/${SESSION}/groups`, {
                    headers: {
                        "X-Api-Key": API_KEY,
                    }
                });

                if (res.status === 200) {
                    let data = await res.json();

                    let groupSelect = document.getElementById("group-select");
                    groupSelect.innerHTML = '<option value="">-- Pilih Grup --</option>';

                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach((group, idx) => {
                            let groupId;
                            if (group.id && typeof group.id === 'object' && group.id._serialized) {
                                groupId = group.id._serialized;
                            } else if (typeof group.id === 'string') {
                                groupId = group.id;
                            } else {
                                groupId = group._serialized;
                            }

                            let groupName = group.name || group.subject || groupId;
                            let option = document.createElement("option");
                            option.value = groupId;
                            option.textContent = groupName;
                            groupSelect.appendChild(option);
                        });
                    }
                }
                showLoading(false);
            } catch (e) {
                alert("❌ Gagal memuat grup: " + e.message);
                showLoading(false);
            }
        }

        // --------------------------
        //  LOAD MEMBERS
        // --------------------------
        async function loadMembers() {
            if (!currentGroupId) {
                document.getElementById("members-table-body").innerHTML = `
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Pilih grup untuk melihat anggota</td></tr>`;
                return;
            }

            try {
                showLoading(true);
                let res = await fetch(`${API_BASE}/${SESSION}/groups/${currentGroupId}/participants`, {
                    headers: { "X-Api-Key": API_KEY }
                });

                if (res.status === 200) {
                    let data = await res.json();
                    let participants = data.participants || data;
                    if (!Array.isArray(participants)) participants = [participants];

                    if (participants.length === 0) {
                        document.getElementById("members-table-body").innerHTML = `
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada anggota</td></tr>`;
                        showLoading(false);
                        return;
                    }

                    membersList = participants;
                    let membersHtml = "";

                    for (let i = 0; i < membersList.length; i++) {
                        let member = membersList[i];
                        let memberId = "";

                        if (member.id && typeof member.id === 'object' && member.id._serialized) {
                            memberId = member.id._serialized;
                        } else if (typeof member.id === 'string') {
                            memberId = member.id;
                        } else {
                            memberId = "unknown_id";
                        }
                        if (typeof memberId !== 'string') memberId = String(memberId);

                        let displayName = memberId.split('@')[0]; // Default nama pakai nomor HP
                        
                        membersHtml += `
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4">${i + 1}</td>
                            <td class="px-6 py-4 font-semibold">${displayName}</td>
                            <td class="px-6 py-4 font-mono text-sm break-all">${memberId}</td>
                            <td class="px-6 py-4 font-mono text-sm break-all">${currentGroupId}</td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="confirmKickMember('${memberId}', '${displayName}')" 
                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition">
                                    🚫 Kick
                                </button>
                            </td>
                        </tr>`;
                    }
                    document.getElementById("members-table-body").innerHTML = membersHtml;
                } else {
                    document.getElementById("members-table-body").innerHTML = `
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">❌ Gagal memuat anggota (Status: ${res.status})</td></tr>`;
                }
                showLoading(false);
            } catch (e) {
                console.error(e);
                document.getElementById("members-table-body").innerHTML = `
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">❌ Error: ${e.message}</td></tr>`;
                showLoading(false);
            }
        }

        // --------------------------
        //  KICK MEMBER
        // --------------------------
        async function kickMember(memberId) {
            if (!currentGroupId || !memberId) { alert("❌ Data tidak valid"); return; }
            
            let cleanId = String(memberId).trim();
            if (!cleanId.includes('@')) cleanId = cleanId + '@c.us';

            showLoading(true);
            try {
                let payload = { "participants": [{ "id": cleanId }] };
                let res = await fetch(`${API_BASE}/${SESSION}/groups/${currentGroupId}/participants/remove`, {
                    method: "POST",
                    headers: {
                        "X-Api-Key": API_KEY,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(payload)
                });

                if (res.status === 200 || res.status === 204) {
                    alert("✅ Anggota berhasil dikeluarkan!");
                    document.getElementById("modal-kick-confirm").classList.add("hidden");
                    await loadMembers();
                } else {
                    alert(`❌ Gagal Kick. Status: ${res.status}`);
                }
            } catch (e) {
                alert("❌ ERROR SYSTEM: " + e.message);
            } finally {
                showLoading(false);
            }
        }

        // --------------------------
        //  ADD MEMBER
        // --------------------------
        document.getElementById("btn-add-member").addEventListener("click", async () => {
            if (!currentGroupId) { alert("⚠️ Pilih grup terlebih dahulu!"); return; }
            let phone = document.getElementById("input-phone").value.trim();
            if (!phone) { alert("⚠️ Masukkan nomor WhatsApp!"); return; }

            try {
                showLoading(true);
                let contactId = phone + "@c.us";
                let res = await fetch(`${API_BASE}/${SESSION}/groups/${currentGroupId}/participants`, {
                    method: "POST",
                    headers: {
                        "X-Api-Key": API_KEY,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ participants: [{ id: contactId }] })
                });

                if (res.status === 200 || res.status === 204) {
                    alert("✅ Anggota berhasil ditambahkan!");
                    document.getElementById("input-phone").value = "";
                    loadMembers();
                } else {
                    alert("❌ Gagal menambah anggota.");
                }
                showLoading(false);
            } catch (e) {
                alert("❌ ERROR: " + e.message);
                showLoading(false);
            }
        });

        // Event Listeners
        document.getElementById("group-select").addEventListener("change", (e) => {
            currentGroupId = e.target.value;
            if (currentGroupId) loadMembers();
        });

        document.getElementById("btn-refresh-groups").addEventListener("click", loadGroups);

        function confirmKickMember(memberId, memberName) {
            currentMemberId = memberId;
            document.getElementById("modal-member-info").textContent = `${memberName} (${memberId})`;
            document.getElementById("modal-kick-confirm").classList.remove("hidden");
        }

        document.getElementById("btn-modal-cancel").addEventListener("click", () => {
            document.getElementById("modal-kick-confirm").classList.add("hidden");
        });

        document.getElementById("btn-modal-confirm-kick").addEventListener("click", () => {
            if (currentMemberId) kickMember(currentMemberId);
        });

        function showLoading(show) {
            document.getElementById("loading-spinner").classList.toggle("hidden", !show);
        }

        // Init
        loadGroups();
    </script>

</body>
</html>