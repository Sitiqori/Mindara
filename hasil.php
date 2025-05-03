<?php
session_start();
require 'config.php'; // Biar connect ke database

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$jawaban = $_POST['jawaban'];
$total = array_sum($jawaban);

// Ambil masing-masing nilai
$q1 = $jawaban[0];
$q2 = $jawaban[1];
$q3 = $jawaban[2];
$q4 = $jawaban[3];
$q5 = $jawaban[4];
$q6 = $jawaban[5];
$q7 = $jawaban[6];
$q8 = $jawaban[7];
$q9 = $jawaban[8];
$q10 = $jawaban[9];
 
// Simpan ke database
$created_at = date('Y-m-d');
$query = "INSERT INTO hasil_tes (user_id, q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, total, created_at)
VALUES ('$user_id', '$q1', '$q2', '$q3', '$q4', '$q5', '$q6', '$q7', '$q8', '$q9', '$q10', '$total', '$created_at')";
mysqli_query($conn, $query);

// Redirect ke halaman hasil grafik atau kasih pesan
header("Location: grafik.php"); // Atau tampilkan hasil langsung di sini
exit;
?>