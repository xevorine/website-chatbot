<?php
include __DIR__ . '/connection.php';

$id = (int)($_POST['id'] ?? 0);

$message = $_POST['message'] ?? null;
$author  = $_POST['author'] ?? null;

$sql = "
UPDATE warnings SET
    message = ?,
    author = ?
WHERE id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "ssi",
    $message,
    $author,
    $id
);

if ($stmt->execute()) {
    header("Location: index.php");
    exit;
} else {
    echo "Execute error: " . $stmt->error;
}