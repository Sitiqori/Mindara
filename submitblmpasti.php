<?php
include 'config.php';
$user_id = 4;

$total = 0;
$jawaban = [];

for ($i = 1; $i <= 10; $i++) {
    $nilai = (int)$_POST["q$i"];
    $jawaban[] = $nilai;
    $total += $nilai;
}

$sql = "INSERT INTO hasil_tes (user_id, q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, total)
        VALUES ($user_id, " . implode(',', $jawaban) . ", $total)";

$conn = new mysqli("localhost", "root", "", "mindara");
if ($conn->query($sql) === TRUE) {
    echo "Data berhasil disimpan!";
} else {
    echo "Error: " . $conn->error;
}
?>
