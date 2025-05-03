<?php
// Start session to maintain user data
session_start();
require 'config.php'; // Biar connect ke database

// Initialize variables
$fullname = $email = $phone = $birthdate = $gender = $bio = $preferences = "";
$fullname_err = $email_err = $phone_err = $birthdate_err = $gender_err = $bio_err = $preferences_err = "";
$success_message = "";
$error_message = "";

// Get current user ID from session (adjust according to your authentication system)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate fullname
    if (empty(trim($_POST["fullname"]))) {
        $fullname_err = "Silakan masukkan nama lengkap Anda.";
    } else {
        $fullname = trim($_POST["fullname"]);
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Silakan masukkan email Anda.";
    } else {
        // Check if email is valid
        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            $email_err = "Format email tidak valid.";
        } else {
            $email = trim($_POST["email"]);
        }
    }
    
    // Validate phone (optional)
    if (!empty(trim($_POST["phone"]))) {
        // Basic Indonesian phone number validation
        if (!preg_match("/^[0-9]{10,13}$/", trim($_POST["phone"]))) {
            $phone_err = "Nomor telepon harus berisi 10-13 digit angka.";
        } else {
            $phone = trim($_POST["phone"]);
        }
    } else {
        $phone = NULL;
    }
    
    // Validate birthdate (optional)
    if (!empty(trim($_POST["birthdate"]))) {
        $birthdate = trim($_POST["birthdate"]);
    } else {
        $birthdate = NULL;
    }
    
    // Validate gender (optional)
    if (!empty(trim($_POST["gender"]))) {
        $gender = trim($_POST["gender"]);
    } else {
        $gender = NULL;
    }
    
    // Bio is optional
    $bio = trim($_POST["bio"]);
    
    // Preferences is optional but defaulted
    $preferences = !empty($_POST["preferences"]) ? trim($_POST["preferences"]) : "all";
    
    // Check if no errors, then update profile
    if (empty($fullname_err) && empty($email_err) && empty($phone_err) && empty($birthdate_err)) {
        
        // Handle profile picture upload
        $profile_pic_path = NULL;
        if (isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] == 0) {
            $target_dir = "uploads/profile_pics/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
            $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Check file type
            $allowed_types = array("jpg", "jpeg", "png", "gif");
            if (in_array($file_extension, $allowed_types)) {
                // Upload file
                if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                    $profile_pic_path = $target_file;
                } else {
                    $error_message = "Maaf, terjadi kesalahan saat mengunggah file.";
                }
            } else {
                $error_message = "Hanya file JPG, JPEG, PNG, dan GIF yang diizinkan.";
            }
        }
        
        // Prepare an UPDATE statement
$sql = "UPDATE users SET nama = ?, email = ?, phone = ?, birthdate = ?, gender = ?, bio = ?, notification_preferences = ?";
        $params = array($fullname, $email, $phone, $birthdate, $gender, $bio, $preferences);
        
        // Add profile_pic to update if one was uploaded
        if ($profile_pic_path) {
            $sql .= ", profile_pic = ?";
            $params[] = $profile_pic_path;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $user_id;
        
        // Prepare statement
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error_message = "Terjadi kesalahan: " . $conn->error;
        } else {
            // Create the appropriate bind_param arguments
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
            
            // Execute the prepared statement
            if ($stmt->execute()) {
                $success_message = "Profil berhasil diperbarui!";
                
                // Record the stress level if provided
                if (isset($_POST["stress_level"]) && is_numeric($_POST["stress_level"])) {
                    $stress_level = $_POST["stress_level"];
                
                    // Validasi agar tingkat stres berada di antara 0 dan 10
                    if ($stress_level >= 0 && $stress_level <= 10) {
                        $stmt_stress = $conn->prepare("INSERT INTO hasil_tes (user_id, total, created_at) VALUES (?, ?, NOW())");
                        $stmt_stress->bind_param("ii", $user_id, $stress_level);
                        $stmt_stress->execute();
                        $stmt_stress->close();
                    } else {
                        $error_message = "Tingkat stres harus berada antara 0 dan 10.";
                    }
                } else {
                    $error_message = "Tingkat stres tidak valid.";
                }
            }
            }
    // If there are errors or after successful update, the form will be shown again with feedback
            }
        }
// Fetch user data if form not submitted
if ($user_id && $_SERVER["REQUEST_METHOD"] != "POST") {
    $sql = "SELECT nama, email, phone, birthdate, gender, bio, notification_preferences, profile_pic FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($fullname, $email, $phone, $birthdate, $gender, $bio, $preferences, $profile_pic);
    $stmt->fetch();
    $stmt->close();
    
    // Get latest stress level
    $stmt_stress = $conn->prepare("SELECT tingkat_stres, tanggal FROM hasil_stres WHERE user_id = ? ORDER BY tanggal DESC LIMIT 1");
    $stmt_stress->bind_param("i", $user_id);
    $stmt_stress->execute();
    $stmt_stress->bind_result($latest_stress, $stress_recorded_at);
    $stmt_stress->fetch();
    $stmt_stress->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Beranda</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 100px auto; /* Menambah jarak atas agar tidak tertutup header */
            padding: 20px;
        }
        
        .profile-form {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #2a9d8f;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2a9d8f;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        button, .upload-btn {
            background-color: #2a9d8f;
            color: white;
            border: none;
            padding: 14px 28px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        button:hover, .upload-btn:hover {
            background-color: #238b7e;
        }
        
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .secondary-button {
            background-color: #e9e9e9;
            color: #333;
        }
        
        .secondary-button:hover {
            background-color: #d9d9d9;
        }
        
        .avatar-section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #e9e9e9;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .upload-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .upload-btn {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .stress-level-section {
            margin-bottom: 20px;
        }
        
        .progress-container {
            height: 10px;
            background-color: #e9e9e9;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .progress-bar {
            height: 100%;
            width: <?php echo isset($latest_stress) ? ($latest_stress * 10) : 0; ?>%;
            background-color: #2a9d8f;
            border-radius: 5px;
        }
        
        .error-text {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .hidden-file-input {
            display: none;
        }

        .stress-level-section {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between; /* Membuat slider dan persentase berjauhan */
        }

        #stress_percentage {
            font-weight: bold;
            color: #2a9d8f;
            margin-left: 10px;
        }

    </style>
    
</head>
<body>
<header id="navbar">
  <div class="logo">
    <img src="images/mindara.png" alt="Mindara Logo" class="logo-img" />
  </div>
  <nav>
    <a href="index.php">Beranda</a>
    <a href="analisis.php">Analisis</a>
    <a href="tentang.php">Tentang</a>

    <?php if (isset($_SESSION['user_name'])): ?>
      <a href="profile.php" class="user-greeting">Halo, <?= htmlspecialchars($_SESSION['user_name']); ?>!</a>
      <a href="logout.php" style="margin-left: 10px;">Logout</a>
    <?php else: ?>
      <a href="sign-in.php">Login</a>
    <?php endif; ?>
  </nav>
</header>
    <div class="container">
        <div class="profile-form">
            <h1>Profile</h1>
            
            <?php
            // Display success message if any
            if (!empty($success_message)) {
                echo '<div class="success-message">' . $success_message . '</div>';
            }
            
            // Display error message if any
            if (!empty($error_message)) {
                echo '<div class="error-message">' . $error_message . '</div>';
            }
            ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="avatar-section">
                    <div class="avatar-preview">
                        <?php if(isset($profile_pic) && !empty($profile_pic)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Foto Profil">
                        <?php else: ?>
                            <img src="assets/images/default-avatar.png" alt="Default Avatar">
                        <?php endif; ?>
                    </div>
                    <div class="upload-buttons">
                        <label for="profile_pic" class="upload-btn">Unggah Foto</label>
                        <input type="file" name="profile_pic" id="profile_pic" class="hidden-file-input" accept="image/*">
                        <?php if(isset($profile_pic) && !empty($profile_pic)): ?>
                            <button type="submit" name="delete_pic" class="upload-btn secondary-button">Hapus Foto</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fullname">Nama Lengkap</label>
                    <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>">
                    <?php if (!empty($fullname_err)): ?>
                        <span class="error-text"><?php echo $fullname_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <?php if (!empty($email_err)): ?>
                        <span class="error-text"><?php echo $email_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="phone">Nomor Telepon</label>
                    <input type="text" id="phone" name="phone" placeholder="Contoh: 081234567890" value="<?php echo htmlspecialchars($phone); ?>">
                    <?php if (!empty($phone_err)): ?>
                        <span class="error-text"><?php echo $phone_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="birthdate">Tanggal Lahir</label>
                    <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>">
                    <?php if (!empty($birthdate_err)): ?>
                        <span class="error-text"><?php echo $birthdate_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="gender">Jenis Kelamin</label>
                    <select id="gender" name="gender">
                        <option value="">Pilih</option>
                        <option value="male" <?php echo ($gender == "male") ? "selected" : ""; ?>>Laki-laki</option>
                        <option value="female" <?php echo ($gender == "female") ? "selected" : ""; ?>>Perempuan</option>
                        <option value="other" <?php echo ($gender == "other") ? "selected" : ""; ?>>Lainnya</option>
                        <option value="prefer_not_to_say" <?php echo ($gender == "prefer_not_to_say") ? "selected" : ""; ?>>Tidak ingin menyebutkan</option>
                    </select>
                </div>
                
                <div class="stress-level-section">
                    <label for="stress_level">Tingkat Stres Terkini</label>
                    <input type="range" id="stress_level" name="stress_level" min="0" max="10" value="<?php echo isset($latest_stress) ? $latest_stress : 5; ?>">
                    <div class="progress-container">
                        <div class="progress-bar"></div>
                    </div>
                    <span id="stress_percentage"><?php echo isset($latest_stress) ? $latest_stress * 10 : 50; ?>%</span> <!-- Menampilkan persentase -->
                    <?php if(isset($stress_recorded_at)): ?>
                        <p>Terakhir diperbarui: <?php echo date('d F Y, H:i', strtotime($stress_recorded_at)); ?></p>
                    <?php endif; ?>
                            
                </div>
                
                <div class="form-group">
                    <label for="bio">Tentang Saya</label>
                    <textarea id="bio" name="bio" placeholder="Ceritakan sedikit tentang diri Anda..."><?php echo htmlspecialchars($bio); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="preferences">Preferensi Notifikasi</label>
                    <select id="preferences" name="preferences">
                        <option value="all" <?php echo ($preferences == "all") ? "selected" : ""; ?>>Semua notifikasi</option>
                        <option value="important" <?php echo ($preferences == "important") ? "selected" : ""; ?>>Hanya notifikasi penting</option>
                        <option value="none" <?php echo ($preferences == "none") ? "selected" : ""; ?>>Tidak ada notifikasi</option>
                    </select>
                </div>
                
                <div class="button-group">
                    <a href="beranda.php" class="secondary-button" style="text-decoration: none; text-align: center;">Batal</a>
                    <button type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Update the progress bar when the stress level slider changes
        document.getElementById('stress_level').addEventListener('input', function() {
            document.querySelector('.progress-bar').style.width = (this.value * 10) + '%';
        });
        
        // Preview uploaded image before submission
        document.getElementById('profile_pic').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.avatar-preview img').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

            document.getElementById('stress_level').addEventListener('input', function() {
        // Update progress bar width
        document.querySelector('.progress-bar').style.width = (this.value * 10) + '%';
        
        // Update stress percentage display
        document.getElementById('stress_percentage').textContent = this.value * 10 + '%';
    });

    </script>
</body>
</html>