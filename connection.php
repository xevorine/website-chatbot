<?php
$host = "10.147.19.163";
$user = "n8nuser";
$pass = "n8npass";
$db = "log_badwords";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>