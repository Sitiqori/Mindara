<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "mindara";

// Koneksi ke MySQL
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
echo "Connected successfully";
?>
