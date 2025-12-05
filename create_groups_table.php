<?php
include __DIR__ . '/connection.php';

// Buat tabel groups jika belum ada
$sql = "CREATE TABLE IF NOT EXISTS `groups` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id VARCHAR(255) NOT NULL UNIQUE,
    group_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabel groups berhasil dibuat atau sudah ada!<br>";
} else {
    echo "Error membuat tabel: " . $conn->error . "<br>";
}

// Sync existing group_id dari tabel warnings ke tabel groups
$sync_sql = "INSERT IGNORE INTO `groups` (group_id, group_name) 
             SELECT DISTINCT group_id, CONCAT('Group - ', group_id) FROM warnings";

if ($conn->query($sync_sql) === TRUE) {
    echo "Sinkronisasi group_id berhasil!<br>";
} else {
    echo "Error sinkronisasi: " . $conn->error . "<br>";
}

echo "<br><a href='group.php'>Kembali ke halaman Groups</a>";

$conn->close();
?>