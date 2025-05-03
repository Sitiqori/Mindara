<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "User tidak login.";
    exit;
}

// Gunakan $_SESSION['user_id'] untuk query profil
$user_id = $_SESSION['user_id'];

// Menghubungkan ke database
include('config.php');

$error_message = '';  // Definisikan variabel error_message
$success_message = '';  // Definisikan variabel success_message

// Misalnya kita mendapatkan user_id dari session atau request
$user_id = 1; // Ganti dengan ID yang valid, bisa dari session

// Query untuk mengambil data pengguna
$query = "SELECT * FROM user_profile WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);  // Menggunakan parameter yang aman
$stmt->execute();
$result = $stmt->get_result();

// Cek apakah data ditemukan
if ($result->num_rows > 0) {
    // Ambil data pengguna
    $row = $result->fetch_assoc();
    // Mengisi variabel dengan data dari database
    $fullname = $row['fullname'];
    $email = $row['email'];
    $phone = $row['phone'];
    $birthdate = $row['birthdate'];
    $gender = $row['gender'];
    $bio = $row['bio'];
    $preferences = $row['preferences'];
    $profile_pic = $row['profile_pic'];
} else {
    $error_message = "Data profil tidak ditemukan!";
}

// Fungsi untuk upload foto
function uploadProfilePic($file) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validasi file gambar
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return false; // Bukan file gambar
    }

    // Cek apakah file sudah ada
    if (file_exists($target_file)) {
        return false; // File sudah ada
    }

    // Batasan ukuran file (misal: 5MB)
    if ($file["size"] > 5000000) {
        return false; // File terlalu besar
    }

    // Hanya izinkan format gambar tertentu
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" ) {
        return false; // Format file tidak valid
    }

    // Jika lolos semua pengecekan, upload gambar
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file; // Return path file yang diupload
    }

    return false; // Gagal upload
}

// Menangani form submission untuk update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id']; // Asumsi user_id ada di session

    // Menyiapkan data yang akan diupdate
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $bio = $_POST['bio'];
    $preferences = $_POST['preferences'];
    $profile_pic_path = null;
    $error_message = "";
    $success_message = "";

    // Cek apakah ada gambar yang diupload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $profile_pic_path = uploadProfilePic($_FILES['profile_pic']);
        if (!$profile_pic_path) {
            $error_message = "Gagal mengupload gambar. Pastikan gambar valid dan tidak melebihi ukuran maksimum.";
        }
    }

    // Jika tingkat stres diupdate, validasi dan update
    if (isset($_POST['stress_level']) && is_numeric($_POST['stress_level'])) {
        $stress_level = $_POST['stress_level'];
    } else {
        $stress_level = null;
        $error_message = "Tingkat stres tidak valid.";
    }

    // Persiapkan query untuk update data profil
    if (empty($error_message)) {
        $stmt = $conn->prepare("UPDATE user_profile SET fullname = ?, email = ?, phone = ?, birthdate = ?, gender = ?, bio = ?, preferences = ?, profile_pic = ? WHERE user_id = ?");
        
        $params = [$fullname, $email, $phone, $birthdate, $gender, $bio, $preferences, $profile_pic_path, $user_id];
        $types = "ssssssssi"; // s = string, i = integer
        if ($profile_pic_path) {
            $types .= "s"; // Tambah satu untuk profile_pic
        }
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $success_message = "Profil berhasil diperbarui.";
        } else {
            $error_message = "Gagal memperbarui profil. Coba lagi.";
        }
    }

    // Update tingkat stres jika valid
    if (isset($stress_level) && is_numeric($stress_level)) {
        $stmt_stress = $conn->prepare("SELECT total, created_at FROM hasil_tes WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt_stress->bind_param("i", $user_id);
        $stmt_stress->execute();
        $result = $stmt_stress->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            // Update hasil tes stres terakhir jika ada
            $stmt_update_stress = $conn->prepare("UPDATE hasil_tes SET total = ?, created_at = NOW() WHERE user_id = ?");
            $stmt_update_stress->bind_param("ii", $stress_level, $user_id);
            if ($stmt_update_stress->execute()) {
                $success_message = "Tingkat stres berhasil diperbarui.";
            } else {
                $error_message = "Gagal memperbarui tingkat stres.";
            }
        }
    }
}

// Ambil data profil pengguna
$stmt = $conn->prepare("SELECT fullname, email, phone, birthdate, gender, bio, preferences, profile_pic FROM user_profile WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_profile = $result->fetch_assoc();

// Ambil data tingkat stres terakhir
$stmt_stress = $conn->prepare("SELECT total FROM hasil_tes WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt_stress->bind_param("i", $_SESSION['user_id']);
$stmt_stress->execute();
$result_stress = $stmt_stress->get_result();
$stress_data = $result_stress->fetch_assoc();
?>

<!-- ================== CSS TAMBAHAN UNTUK FORM =================== -->
<style>
    form {
        max-width: 600px;
        margin: 20px auto;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 10px;
        font-family: Arial, sans-serif;
        background-color: #f9f9f9;
    }

    form h2 {
        text-align: center;
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
    }

    input[type="text"],
    input[type="email"],
    input[type="date"],
    input[type="number"],
    select,
    textarea {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    textarea {
        resize: vertical;
    }

    .error {
        background-color: #ffe0e0;
        color: #900;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    .success {
        background-color: #e0ffe0;
        color: #090;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    button[type="submit"] {
        margin-top: 15px;
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    button[type="submit"]:hover {
        background-color: #0056b3;
    }
</style>

<!-- HTML Form untuk update profil -->
<form action="update_profile.php" method="post" enctype="multipart/form-data">
    <h2>Update Profil</h2>

    <?php if ($error_message): ?>
        <div class="error"><?= $error_message ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="success"><?= $success_message ?></div>
    <?php endif; ?>

    <label for="fullname">Nama Lengkap:</label>
    <input type="text" id="fullname" name="fullname" value="<?= isset($user_profile['fullname']) ? htmlspecialchars($user_profile['fullname']) : '' ?>" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= isset($user_profile['email']) ? htmlspecialchars($user_profile['email']) : '' ?>" required>

    <label for="phone">Telepon:</label>
    <input type="text" id="phone" name="phone" value="<?= isset($user_profile['phone']) ? htmlspecialchars($user_profile['phone']) : '' ?>" required>

    <label for="birthdate">Tanggal Lahir:</label>
    <input type="date" id="birthdate" name="birthdate" value="<?= isset($user_profile['birthdate']) ? htmlspecialchars($user_profile['birthdate']) : '' ?>" required>

    <label for="gender">Jenis Kelamin:</label>
    <select id="gender" name="gender">
        <option value="L" <?= isset($user_profile['gender']) && $user_profile['gender'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
        <option value="P" <?= isset($user_profile['gender']) && $user_profile['gender'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
    </select>

    <label for="bio">Bio:</label>
    <textarea id="bio" name="bio"><?= isset($user_profile['bio']) ? htmlspecialchars($user_profile['bio']) : '' ?></textarea>

    <label for="preferences">Preferensi:</label>
    <textarea id="preferences" name="preferences"><?= isset($user_profile['preferences']) ? htmlspecialchars($user_profile['preferences']) : '' ?></textarea>

    <label for="profile_pic">Foto Profil:</label>
    <input type="file" id="profile_pic" name="profile_pic">

    <label for="stress_level">Tingkat Stres (1-10):</label>
    <input type="number" id="stress_level" name="stress_level" value="<?= isset($stress_data['total']) ? htmlspecialchars($stress_data['total']) : '' ?>" min="1" max="10">

    <button type="submit">Update Profil</button>
</form>


