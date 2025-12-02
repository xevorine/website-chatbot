<?php
include __DIR__ . '/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'] ?? null;
    $group_name = $_POST['group_name'] ?? null;
    $description = $_POST['description'] ?? null;
    
    if (!$group_id || !$group_name) {
        header("Location: edit_group.php?id=$group_id&error=missing");
        exit;
    }
    
    // Update group
    $sql = "UPDATE `groups` SET group_name = ?, description = ? WHERE group_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $group_name, $description, $group_id);
    
    if ($stmt->execute()) {
        header("Location: group.php?success=1");
        exit;
    } else {
        header("Location: edit_group.php?id=$group_id&error=1");
        exit;
    }
} else {
    header("Location: group.php");
    exit;
}
?>
