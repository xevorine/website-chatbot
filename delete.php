<?php
include __DIR__ . '/connection.php';

$id = $_GET['id'];

$sql = "DELETE FROM warnings WHERE id = $id";

if ($conn->query($sql)) {
    header("Location: index.php");
} else {
    echo "Error: " . $conn->error;
}
?>
