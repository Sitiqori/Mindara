<?php
session_start();
require 'config.php'; 


$fullname = $email = $phone = $birthdate = $gender = $bio = $preferences = "";
$fullname_err = $email_err = $phone_err = $birthdate_err = $gender_err = $bio_err = $preferences_err = "";
$success_message = "";
$error_message = "";


$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["fullname"]))) {
        $fullname_err = "Silakan masukkan nama lengkap Anda.";
    } else {
        $fullname = trim($_POST["fullname"]);
    }

    if (empty(trim($_POST["email"]))) {
        $email_err = "Silakan masukkan email Anda.";
    } else {
        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            $email_err = "Format email tidak valid.";
        } else {
            $email = trim($_POST["email"]);
        }
    }

    if (!empty(trim($_POST["phone"]))) {
        if (!preg_match("/^[0-9]{10,13}$/", trim($_POST["phone"]))) {
            $phone_err = "Nomor telepon harus berisi 10-13 digit angka.";
        } else {
            $phone = trim($_POST["phone"]);
        }
    } else {
        $phone = NULL;
    }
    

    if (!empty(trim($_POST["birthdate"]))) {
        $birthdate = trim($_POST["birthdate"]);
    } else {
        $birthdate = NULL;
    }
    

    if (!empty(trim($_POST["gender"]))) {
        $gender = trim($_POST["gender"]);
    } else {
        $gender = NULL;
    }
    

    $bio = trim($_POST["bio"]);
    
    
    $preferences = !empty($_POST["preferences"]) ? trim($_POST["preferences"]) : "all";
    

    if (empty($fullname_err) && empty($email_err) && empty($phone_err) && empty($birthdate_err)) {
        

        $profile_pic_path = NULL;
        if (isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] == 0) {
            $target_dir = "uploads/profile_pics/";
            

            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
            $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            

            $allowed_types = array("jpg", "jpeg", "png", "gif");
            if (in_array($file_extension, $allowed_types)) {

                if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                    $profile_pic_path = $target_file;
                } else {
                    $error_message = "Maaf, terjadi kesalahan saat mengunggah file.";
                }
            } else {
                $error_message = "Hanya file JPG, JPEG, PNG, dan GIF yang diizinkan.";
            }
        }
        

        $sql = "UPDATE users SET nama = ?, email = ?, phone = ?, birthdate = ?, gender = ?, bio = ?, notification_preferences = ?";
        $params = array($fullname, $email, $phone, $birthdate, $gender, $bio, $preferences);

        if ($profile_pic_path) {
            $sql .= ", profile_pic = ?";
            $params[] = $profile_pic_path;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $user_id;
        

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error_message = "Terjadi kesalahan: " . $conn->error;
        } else {

            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $success_message = "Profil berhasil diperbarui!";

                if (isset($_POST["stress_level"]) && is_numeric($_POST["stress_level"])) {
                    $stress_level = $_POST["stress_level"];

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

            }
        }

if ($user_id && $_SERVER["REQUEST_METHOD"] != "POST") {
    $sql = "SELECT nama, email, phone, birthdate, gender, bio, notification_preferences, profile_pic FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($fullname, $email, $phone, $birthdate, $gender, $bio, $preferences, $profile_pic);
    $stmt->fetch();
    $stmt->close();
   
    

    $stmt_stress = $conn->prepare("SELECT tingkat_stres, tanggal FROM hasil_stres WHERE user_id = ? ORDER BY tanggal DESC LIMIT 1");
    $stmt_stress->bind_param("i", $user_id);
    $stmt_stress->execute();
    $stmt_stress->bind_result($latest_stress, $stress_recorded_at);
    $stmt_stress->fetch();
    $stmt_stress->close();
}


$dates = [];
$totals = [];
$normalizedScores = []; 

if ($user_id) {
    $sql_7days = "SELECT DATE(created_at) as date, total, normalized_score 
                  FROM hasil_tes
                  WHERE user_id = ?
                  AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                  ORDER BY date ASC";
    $stmt_7days = mysqli_prepare($conn, $sql_7days);
    mysqli_stmt_bind_param($stmt_7days, "i", $user_id);
    mysqli_stmt_execute($stmt_7days);
    $result_7days = mysqli_stmt_get_result($stmt_7days);


    $raw_data = [];
    while ($row = mysqli_fetch_assoc($result_7days)) {
        $raw_data[$row['date']] = $row;
    }


    for ($i = 6; $i >= 0; $i--) {
        $date_ymd = date('Y-m-d', strtotime("-$i days"));
        $date_display = date('d M', strtotime("-$i days"));
        
        $dates[] = $date_display;

        if (isset($raw_data[$date_ymd])) {
            $totals[] = (int)$raw_data[$date_ymd]['total'];
            $normalizedScores[] = (int)$raw_data[$date_ymd]['normalized_score'];
        } else {

            $totals[] = null;
            $normalizedScores[] = null;
        }
    }
    mysqli_stmt_close($stmt_7days);
}


function predictStress($scores) {
    $n = count($scores);
    if ($n < 3) return array_fill(0, 7, min(100, end($scores))); 
    


    $filteredScores = array_filter($scores, function($value) {
        return $value !== null;
    });
    
    if (count($filteredScores) < 3) return array_fill(0, 7, min(100, end($filteredScores)));

    $predicted = [];
    for ($i = 0; $i < 7; $i++) {
        $last3 = array_slice($filteredScores, -3);
        $avg = array_sum($last3) / 3;
        $next = min(100, round($avg));
        $predicted[] = $next;
        $filteredScores[] = $next;
    }
    return $predicted;
}

$predicted = predictStress($normalizedScores);


$lastHistoricalDate = !empty($dates) ? end($dates) : date('d M');
$predictedDates = [];
for ($i = 1; $i <= 7; $i++) {
    $predictedDates[] = date('d M', strtotime("+$i days"));
}


$chartData = [
    'dates' => array_merge($dates, $predictedDates),
    'historical' => array_merge($normalizedScores, array_fill(0, 7, null)),
    'predicted' => array_merge(array_fill(0, count($normalizedScores), null), $predicted)
];

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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 100px auto;
            padding: 20px;
        }
        
        .profile-form {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        h1 {
            color: #4a6baf;
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
            color: #4a6baf;
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
            background-color: #4a6baf;
            color: white;
            border: none;
            padding: 14px 28px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        button:hover, .upload-btn:hover {
            background-color: #3a5a9f;
        }
        
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .secondary-button {
            background-color: #e9e9e9;
            width: 5rem;
            height: 3rem; 
            color: #333;
            border-radius:5px;
            display: flex;               
            justify-content: center; 
            align-items: center;       
            border: none;              
            cursor: pointer;            
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
            background-color: #4a6baf;
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
            justify-content: space-between;
        }

        #stress_percentage {
            font-weight: bold;
            color: #4a6baf;
            margin-left: 10px;
        }

        /* Chart Section */
        .chart-section {
            margin-top: 40px;
            margin-bottom: 30px;
            border-top: 1px solid #eaeaea;
            padding-top: 30px;
        }
        
        .chart-container {
            width: 100%;
            height: 400px;
            margin: 30px 0;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            color: #4a6baf;
            font-size: 18px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .no-data-message {
            color: #6c5ce7;
            font-style: italic;
            padding: 15px;
            text-align: center;
            background-color: #f0f0f8;
            border-radius: 8px;
        }

        /* Prediction Section */
        .prediction-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .prediction-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .prediction-date {
            font-weight: 600;
            color: #4a6baf;
        }
        
        .prediction-value {
            font-size: 24px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .low { color: #00b894; }
        .medium { color: #fdcb6e; }
        .high { color: #e17055; }

    </style>
    
</head>
<body>
<header id="navbar">
  <div class="logo">
    <img src="images/logo.png" alt="Mindara Logo" class="logo-img" />
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
                
                <div class="form-group">
                    <label for="bio">Tentang Saya</label>
                    <textarea id="bio" name="bio" placeholder="Ceritakan sedikit tentang diri Anda..."><?php echo htmlspecialchars($bio); ?></textarea>
                </div>
                
                <div class="button-group">
                    <a href="beranda.php" class="secondary-button" style="text-decoration: none; text-align: center;">Batal</a>
                    <button type="submit">Simpan Perubahan</button>
                </div>
            </form>
            
            <!-- Chart Section -->
            <div class="chart-section">
                <h3 class="chart-title">Perkembangan & Prediksi Tingkat Stres</h3>
                <?php if ($user_id && count(array_filter($normalizedScores)) > 0): ?>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
                
                <!-- Prediction Grid -->
                <div style="margin-top: 30px;">
                    <h4 style="text-align: center; color: #4a6baf;">Prediksi 7 Hari Mendatang</h4>
                    <div class="prediction-grid">
                        <?php foreach ($predicted as $i => $value): 
                            $levelClass = '';
                            if ($value <= 33) $levelClass = 'low';
                            elseif ($value <= 66) $levelClass = 'medium';
                            else $levelClass = 'high';
                        ?>
                            <div class="prediction-card">
                                <div class="prediction-date"><?= $predictedDates[$i] ?></div>
                                <div class="prediction-value <?= $levelClass ?>"><?= $value ?></div>
                                <div>
                                    <?= $levelClass === 'low' ? 'Rendah' : ($levelClass === 'medium' ? 'Sedang' : 'Tinggi') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <p class="no-data-message">Data tidak cukup untuk menampilkan grafik stres. Silakan <a href="analisis.php">lakukan tes</a> terlebih dahulu.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
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

        // Chart setup
        document.addEventListener('DOMContentLoaded', function() {
            const trendCtx = document.getElementById('trendChart');
            if (trendCtx) {
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($chartData['dates']); ?>,
                        datasets: [
                            {
                                label: 'Data Historis',
                                data: <?php echo json_encode($chartData['historical']); ?>,
                                borderColor: '#4a6baf',
                                backgroundColor: 'rgba(74, 107, 175, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointBackgroundColor: '#4a6baf',
                                pointBorderColor: '#fff',
                                pointHoverRadius: 7
                            },
                            {
                                label: 'Prediksi',
                                data: <?php echo json_encode($chartData['predicted']); ?>,
                                borderColor: '#e17055',
                                borderDash: [5, 5],
                                backgroundColor: 'rgba(225, 112, 85, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                pointBackgroundColor: '#e17055',
                                pointBorderColor: '#fff',
                                pointHoverRadius: 5
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { 
                                beginAtZero: true,
                                max: 100,
                                title: { 
                                    display: true, 
                                    text: 'Skor Stres (0-100)', 
                                    font: {size: 12} 
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                title: { 
                                    display: true, 
                                    text: 'Tanggal', 
                                    font: {size: 12} 
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>