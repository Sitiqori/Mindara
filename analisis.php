<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save answers to session
    if ($step === 1) {
        $_SESSION['jawaban_stres'] = $_POST['jawaban'];
    } elseif ($step === 2) {
        $_SESSION['jawaban_akademik'] = $_POST['jawaban_akademik'];
    } elseif ($step === 3) {
        $_SESSION['jawaban_keuangan'] = $_POST['jawaban_keuangan'];
        
        // Combine all answers
        $jawaban = array_merge(
            $_SESSION['jawaban_stres'],
            $_SESSION['jawaban_akademik'],
            $_SESSION['jawaban_keuangan']
        );

        // Calculate total scores per category
        $stress_total = array_sum(array_slice($jawaban, 0, 10));
        $akademik_total = array_sum(array_slice($jawaban, 10, 10));
        $keuangan_total = array_sum(array_slice($jawaban, 20, 10));
        $total = $stress_total + $akademik_total + $keuangan_total;

        // Calculate vector magnitude
        $max_input = 30; // Maximum score for each question (3 * 10 questions)
        $max_magnitude = sqrt(3 * pow($max_input, 2)); // ~173.205
        $magnitude = sqrt(pow($stress_total, 2) + pow($akademik_total, 2) + pow($keuangan_total, 2));
        $normalized_score = ($magnitude / $max_magnitude) * 100;

        // Round the normalized score to the nearest integer
        $normalized_score = round($normalized_score);

        // Determine stress level
        if ($normalized_score <= 33) {
            $level = 'Rendah';
            $level_class = 'level-low';
            $recommendation = "Anda memiliki tingkat stress rendah. Jaga pola hidup sehat dan rutin relaksasi.";
        } elseif ($normalized_score <= 66) {
            $level = 'Sedang';
            $level_class = 'level-medium';
            $recommendation = "Anda memiliki tingkat stress sedang. Perhatikan manajemen waktu dan coba teknik relaksasi.";
        } else {
            $level = 'Tinggi';
            $level_class = 'level-high';
            $recommendation = "Anda memiliki tingkat stress tinggi. Disarankan untuk konsultasi dengan profesional atau praktikkan teknik relaksasi yang intensif.";
        }

        // Prepare SQL with all columns
        $query = "INSERT INTO hasil_tes (user_id, 
                  q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, 
                  q11, q12, q13, q14, q15, q16, q17, q18, q19, q20,
                  q21, q22, q23, q24, q25, q26, q27, q28, q29, q30, 
                  total, stress_total, akademik_total, keuangan_total, normalized_score, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                          ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        // Prepare the statement
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        // Bind parameters
        $params = array_merge([$user_id], $jawaban, [$total, $stress_total, $akademik_total, $keuangan_total, $normalized_score]);
        
        $types = str_repeat("i", count($params)); // Assuming all are integers
        $stmt->bind_param($types, ...$params);

        // Execute the statement
        if ($stmt->execute()) {
            header("Location: grafik.php");
            exit;
        } else {
            die("Error saving data: " . $stmt->error);
        }
    }

    if ($step < 3) {
        header("Location: analisis.php?step=" . ($step + 1));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analisis Stress Berdasarkan Vektor 3D</title>
<style>
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }
  
  body {
    background-color: #f5f7fa;
    color: #333;
    line-height: 1.6;
  }
  
  .container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
  }
  
  .card {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    padding: 30px;
    margin-bottom: 20px;
  }
  
  h1 {
    color: #4a6baf;
    text-align: center;
    margin-bottom: 30px;
    font-size: 28px;
  }
  
  h2 {
    color: #4a6baf;
    margin-bottom: 20px;
    font-size: 22px;
    text-align: center;
  }
  
  h3 {
    color: #555;
    margin: 20px 0 15px;
    font-size: 18px;
  }
  
  .progress-container {
    width: 100%;
    background-color: #e0e5ec;
    border-radius: 10px;
    margin: 20px 0;
    height: 10px;
  }
  
  .progress-bar {
    height: 100%;
    border-radius: 10px;
    background: linear-gradient(to right, #4a6baf, #6a8fd8);
    transition: width 0.3s ease;
  }
  
  .question {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
  }
  
  .question:last-child {
    border-bottom: none;
  }
  
  .question-text {
    font-weight: 500;
    margin-bottom: 15px;
    color: #444;
  }
  
  .options {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
  }
  
  @media (max-width: 768px) {
    .options {
      grid-template-columns: 1fr;
    }
  }
  
  .option {
    display: flex;
    align-items: center;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .option:hover {
    background-color: #e9ecef;
  }
  
  .option input {
    margin-right: 10px;
  }
  
  .btn-container {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
  }
  
  .btn {
    padding: 12px 25px;
    border: none;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-next {
    background: linear-gradient(to right, #4a6baf, #6a8fd8);
    color: white;
    margin-left: auto;
  }
  
  .btn-next:hover {
    background: linear-gradient(to right, #3a5a9f, #5a7fc8);
  }
  
  .btn-prev {
    background-color: #e9ecef;
    color: #555;
  }
  
  .btn-prev:hover {
    background-color: #dee2e6;
  }
  
  .step-indicator {
    text-align: center;
    color: #6c757d;
    margin-bottom: 20px;
    font-weight: 500;
  }
</style>
</head>
<body>
<div class="container">
  <div class="card">
    <h1>Analisis Stress</h1>
    
    <div class="progress-container">
      <div class="progress-bar" style="width: <?= ($step/3)*100 ?>%"></div>
    </div>
    
    <div class="step-indicator">
      Langkah <?= $step ?> dari 3 - 
      <?php 
        if($step == 1) echo "Stres Umum";
        elseif($step == 2) echo "Stres Akademik";
        else echo "Stres Keuangan";
      ?>
    </div>
    
    <?php if ($step === 1): ?>
    <form method="POST" action="">
      <h2>Pertanyaan Stres Umum</h2>
      
      <?php
      $stress_questions = [
        "Saya merasa tegang atau tertekan",
        "Saya kesulitan untuk rileks",
        "Saya cemas tanpa alasan jelas",
        "Saya mudah tersinggung",
        "Saya merasa kelelahan emosional",
        "Saya sulit tidur karena banyak pikiran",
        "Saya kewalahan dengan tanggung jawab",
        "Saya kehilangan minat pada hal yang biasa dinikmati",
        "Saya merasa tidak berdaya",
        "Saya mengalami gejala fisik (sakit kepala, jantung berdebar)"
      ];
      
      foreach ($stress_questions as $i => $question) {
        echo '<div class="question">';
        echo '<div class="question-text">'.($i+1).'. '.$question.'</div>';
        echo '<div class="options">';
        
        $options = [
          ["value" => 0, "label" => "Tidak Pernah"],
          ["value" => 1, "label" => "Kadang-kadang"],
          ["value" => 2, "label" => "Sering"],
          ["value" => 3, "label" => "Sangat Sering"]
        ];
        
        foreach ($options as $option) {
          echo '<label class="option">';
          echo '<input type="radio" name="jawaban['.$i.']" value="'.$option['value'].'" required>';
          echo $option['label'];
          echo '</label>';
        }
        
        echo '</div></div>';
      }
      ?>
      
      <div class="btn-container">
        <button type="submit" class="btn btn-next">Lanjut ke Stres Akademik →</button>
      </div>
    </form>
    
    <?php elseif ($step === 2): ?>
    <form method="POST" action="">
      <h2>Pertanyaan Stres Akademik</h2>
      
      <?php
      $akademik_questions = [
        "Saya merasa kewalahan dengan jumlah tugas",
        "Saya khawatir tidak bisa memenuhi deadline",
        "Saya merasa nilai saya tidak memuaskan",
        "Saya kesulitan memahami materi kuliah",
        "Saya merasa tidak siap untuk ujian",
        "Saya sering begadang untuk menyelesaikan tugas",
        "Saya merasa dosen/teman lebih pintar dari saya",
        "Saya khawatir tentang masa depan karir saya",
        "Saya merasa tekanan untuk berprestasi",
        "Saya kesulitan menyeimbangkan akademik dan kehidupan pribadi"
      ];
      
      foreach ($akademik_questions as $i => $question) {
        echo '<div class="question">';
        echo '<div class="question-text">'.($i+1).'. '.$question.'</div>';
        echo '<div class="options">';
        
        $options = [
          ["value" => 0, "label" => "Tidak Pernah"],
          ["value" => 1, "label" => "Kadang-kadang"],
          ["value" => 2, "label" => "Sering"],
          ["value" => 3, "label" => "Sangat Sering"]
        ];
        
        foreach ($options as $option) {
          echo '<label class="option">';
          echo '<input type="radio" name="jawaban_akademik['.$i.']" value="'.$option['value'].'" required>';
          echo $option['label'];
          echo '</label>';
        }
        
        echo '</div></div>';
      }
      ?>
      
      <div class="btn-container">
        <a href="analisis.php?step=1" class="btn btn-prev">← Kembali</a>
        <button type="submit" class="btn btn-next">Lanjut ke Stres Keuangan →</button>
      </div>
    </form>
    
    <?php elseif ($step === 3): ?>
    <form method="POST" action="">
      <h2>Pertanyaan Stres Keuangan</h2>
      
      <?php
      $keuangan_questions = [
        "Saya khawatir tentang biaya kuliah",
        "Saya kesulitan memenuhi kebutuhan sehari-hari",
        "Saya merasa terbebani dengan hutang",
        "Saya tidak bisa membeli buku/bahan kuliah yang diperlukan",
        "Saya khawatir tidak bisa membayar uang kuliah bulan depan",
        "Saya merasa tidak memiliki cukup uang untuk bersosialisasi",
        "Saya merasa tekanan untuk bekerja sambil kuliah",
        "Saya khawatir tentang masa depan finansial saya",
        "Saya merasa malu dengan kondisi keuangan saya",
        "Saya kesulitan mengelola keuangan pribadi"
      ];
      
      foreach ($keuangan_questions as $i => $question) {
        echo '<div class="question">';
        echo '<div class="question-text">'.($i+1).'. '.$question.'</div>';
        echo '<div class="options">';
        
        $options = [
          ["value" => 0, "label" => "Tidak Pernah"],
          ["value" => 1, "label" => "Kadang-kadang"],
          ["value" => 2, "label" => "Sering"],
          ["value" => 3, "label" => "Sangat Sering"]
        ];
        
        foreach ($options as $option) {
          echo '<label class="option">';
          echo '<input type="radio" name="jawaban_keuangan['.$i.']" value="'.$option['value'].'" required>';
          echo $option['label'];
          echo '</label>';
        }
        
        echo '</div></div>';
      }
      ?>
      
      <div class="btn-container">
        <a href="analisis.php?step=2" class="btn btn-prev">← Kembali</a>
        <button type="submit" class="btn btn-next">Lihat Hasil Tes</button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>