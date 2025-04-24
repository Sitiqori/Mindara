<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
    
        if (password_verify($password, $user['password'])) {
            // Simpan ke session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nama'];

            // Redirect ke index
            header("Location: index.php");
            exit;
        } else {
            header("Location: sign-in.php?error=1");
            exit;
        }
    } else {
        header("Location: sign-in.php?error=1");
        exit;
    }

    mysqli_close($conn);
} else {
    echo "Akses langsung tidak diperbolehkan.";
}
?>
