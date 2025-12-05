<?php
include __DIR__ . '/connection.php';

$id = $_POST['id'];
$user_id = $_POST['user_id'];
$group_id = $_POST['group_id'];
$warning_count = $_POST['warning_count'];
$last_warning_at = $_POST['last_warning_at'];
$message = $_POST['message'];
$author = $_POST['author'];

$sql = "UPDATE warnings SET 
    user_id='$user_id',
    group_id='$group_id',
    warning_count='$warning_count',
    last_warning_at='$last_warning_at',
    message='$message',
    author='$author'
    WHERE id=$id";

if ($conn->query($sql)) {
    header("Location: index.php");
} else {
    echo "Error: " . $conn->error;
}
?>