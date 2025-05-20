<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email); 
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
        
            if (password_verify($password, $user['password'])) {
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nama'];

                
                mysqli_stmt_close($stmt); 
                header("Location: index.php");
                exit;
            } else {
                mysqli_stmt_close($stmt); 
                header("Location: sign-in.php?error=1");
                exit;
            }
        } else {
            mysqli_stmt_close($stmt); 
            header("Location: sign-in.php?error=1");
            exit;
        }
    } else {
        echo "Terjadi kesalahan dalam koneksi database.";
    }

    mysqli_close($conn);
} else {
    echo "Akses langsung tidak diperbolehkan.";
}
?>
