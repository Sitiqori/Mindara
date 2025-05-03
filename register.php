<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $nama     = $_POST['nama'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

    // Simpan ke database
    $sql = "INSERT INTO users (nama, email, password) VALUES ('$nama', '$email', '$password')";

    if (mysqli_query($conn, $sql)) {
        header("Location: sign-in.php");
        exit;
    } else {
        echo "Gagal daftar. Error: " . mysqli_error($conn);
    }

    mysqli_close($conn);
} else {
    echo "Akses langsung tidak diperbolehkan.";
}
?>
