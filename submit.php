<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$tanggal = date('Y-m-d');

$total = 0;
$values = [];

for ($i = 1; $i <= 10; $i++) {
    $q = $_POST["q$i"];
    $values[] = $q;
    $total += $q;
}

if ($total <= 20) {
    $tingkat = 'Rendah';
} elseif ($total <= 35) {
    $tingkat = 'Sedang';
} else {
    $tingkat = 'Tinggi';
}

$sql = "INSERT INTO analisis (user_id, tanggal, q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, skor_total, tingkat_stres)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issiiiiiiiiiis", $user_id, $tanggal, 
    $values[0], $values[1], $values[2], $values[3], $values[4], 
    $values[5], $values[6], $values[7], $values[8], $values[9], 
    $total, $tingkat);
$stmt->execute();

header("Location: analisis.php?result=done");
exit;
?>