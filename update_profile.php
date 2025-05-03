<?php
session_start();
include('config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];

    // Simpan input awal ke session agar tidak hilang
    $_SESSION['form_data'] = $_POST;

    // Ambil data input
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $bio = $_POST['bio'];
    $preferences = $_POST['preferences'];
    $stress_level = isset($_POST['stress_level']) ? intval($_POST['stress_level']) : null;

    // Upload foto
    $profile_pic_path = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $upload_dir = 'uploads/';
        $filename = basename($_FILES["profile_pic"]["name"]);
        $target_file = $upload_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $is_valid = getimagesize($_FILES["profile_pic"]["tmp_name"]) &&
                    in_array($imageFileType, ['jpg', 'jpeg', 'png']) &&
                    $_FILES["profile_pic"]["size"] <= 5000000;

        if ($is_valid && move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            $profile_pic_path = $target_file;
        } else {
            $_SESSION['error_message'] = "Gagal mengupload foto profil.";
            header("Location: profile.php");
            exit;
        }
    }

    // Update data profil
    $stmt = $conn->prepare("UPDATE user_profile SET fullname=?, email=?, phone=?, birthdate=?, gender=?, bio=?, preferences=?, profile_pic=? WHERE user_id=?");
    $stmt->bind_param("ssssssssi", $fullname, $email, $phone, $birthdate, $gender, $bio, $preferences, $profile_pic_path, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profil berhasil diperbarui.";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui profil.";
        header("Location: profile.php");
        exit;
    }

    // Update stres terakhir
    if ($stress_level !== null) {
        $check_stmt = $conn->prepare("SELECT id FROM hasil_tes WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $update_stress = $conn->prepare("UPDATE hasil_tes SET total=?, created_at=NOW() WHERE id=?");
            $update_stress->bind_param("ii", $stress_level, $row['id']);
            $update_stress->execute();
        }
    }

    // Kosongkan session form data setelah berhasil
    unset($_SESSION['form_data']);

    header("Location: profile.php");
    exit;
}
?>