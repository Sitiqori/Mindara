<?php
session_start();
require 'config.php'; // Koneksi ke database

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil nilai jawaban satu-satu
$q1 = isset($_POST['q1']) ? (int)$_POST['q1'] : 0;
$q2 = isset($_POST['q2']) ? (int)$_POST['q2'] : 0;
$q3 = isset($_POST['q3']) ? (int)$_POST['q3'] : 0;
$q4 = isset($_POST['q4']) ? (int)$_POST['q4'] : 0;
$q5 = isset($_POST['q5']) ? (int)$_POST['q5'] : 0;
$q6 = isset($_POST['q6']) ? (int)$_POST['q6'] : 0;
$q7 = isset($_POST['q7']) ? (int)$_POST['q7'] : 0;
$q8 = isset($_POST['q8']) ? (int)$_POST['q8'] : 0;
$q9 = isset($_POST['q9']) ? (int)$_POST['q9'] : 0;
$q10 = isset($_POST['q10']) ? (int)$_POST['q10'] : 0;

$total = $q1 + $q2 + $q3 + $q4 + $q5 + $q6 + $q7 + $q8 + $q9 + $q10;

$created_at = date('Y-m-d');

// Simpan ke database
$query = "INSERT INTO hasil_tes (user_id, q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, total, created_at)
VALUES ('$user_id', '$q1', '$q2', '$q3', '$q4', '$q5', '$q6', '$q7', '$q8', '$q9', '$q10', '$total', '$created_at')";

mysqli_query($conn, $query);

// Arahkan ke grafik
header("Location: grafik.php");
exit;
?>
