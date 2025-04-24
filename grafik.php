<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data 7 hari terakhir
$sql = "SELECT * FROM hasil_tes 
        WHERE user_id = '$user_id' 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY created_at ASC";
$result = mysqli_query($conn, $sql);

$dates = [];
$totals = [];
$daily_vectors = []; // Untuk menyimpan vektor per hari

while ($row = mysqli_fetch_assoc($result)) {
  $dates[] = date('d M', strtotime($row['created_at']));
  $totals[] = $row['total'];
  
  // Buat vektor dari jawaban (q1-q10 sebagai komponen vektor)
  $vector = [];
  for ($i = 1; $i <= 10; $i++) {
    $vector[] = $row['q'.$i];
  }
  $daily_vectors[] = $vector;
}

// Hitung magnitude (panjang vektor) untuk setiap hari
$magnitudes = array_map(function($vector) {
  return sqrt(array_sum(array_map(function($val) { return $val * $val; }, $vector)));
}, $daily_vectors);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Hasil Tes Stres 7 Hari Terakhir</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="styles/style.css">
  <style>
    .vector-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 10px;
      margin-top: 30px;
    }
    .vector-day {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: center;
    }
    .vector-visual {
      height: 100px;
      position: relative;
      margin: 15px 0;
    }
    .vector-arrow {
      position: absolute;
      bottom: 0;
      left: 50%;
      transform-origin: bottom left;
    }
  </style>
</head>
<body>
  <div class="mindara-wrapper">
    <div class="mindara-container">
      <h2 class="mindara-heading">Perkembangan Stres 7 Hari Terakhir</h2>
      
      <!-- Grafik Garis untuk Trend -->
      <canvas id="trendChart" width="400" height="200"></canvas>
      
      <!-- Grafik Vektor -->
      <h3 style="margin-top: 30px;">Visualisasi Vektor Stres Harian</h3>
      <p>Setiap panah mewakili profil stres harian (10 pertanyaan sebagai komponen vektor)</p>
      <div class="vector-grid">
        <?php foreach ($dates as $index => $date): ?>
          <div class="vector-day">
            <div><?= $date ?></div>
            <div class="vector-visual">
              <div class="vector-arrow" 
                   style="height: <?= $magnitudes[$index] * 10 ?>px;
                          transform: rotate(<?= ($index % 360) ?>deg);
                          border-left: 2px solid #ff5722;
                          border-bottom: 2px solid #ff5722;
                          width: 2px;"></div>
            </div>
            <div>Skor: <?= $totals[$index] ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      
      <!-- Penjelasan Konsep Vektor -->
      <div class="mindara-result" style="margin-top: 30px;">
        <h3>Konsep Vektor dalam Analisis Stres</h3>
        <p>Dalam matematika, vektor adalah besaran yang memiliki magnitude (besar) dan arah.</p>
        <p>Dalam konteks ini:</p>
        <ul>
          <li><strong>Magnitude</strong>: √(q1² + q2² + ... + q10²) - Menunjukkan intensitas total stres</li>
          <li><strong>Arah</strong>: Komposisi jawaban (q1-q10) menentukan orientasi vektor</li>
          <li><strong>Perubahan vektor</strong>: Menunjukkan pola stres yang berubah dari hari ke hari</li>
        </ul>
      </div>
    </div>
  </div>

  <script>
    // Grafik trend garis
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const trendChart = new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($dates); ?>,
        datasets: [{
          label: 'Total Skor Stres Harian',
          data: <?= json_encode($totals); ?>,
          backgroundColor: 'rgba(255, 138, 92, 0.2)',
          borderColor: '#ff8a5c',
          borderWidth: 2,
          tension: 0.3,
          fill: true
        }]
      },
      options: {
        scales: {
          y: { 
            beginAtZero: true,
            max: 30,
            title: { display: true, text: 'Skor Stres' }
          },
          x: {
            title: { display: true, text: 'Tanggal' }
          }
        }
      }
    });
  </script>
</body>
</html>