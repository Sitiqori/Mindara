prediksi.php :

<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data historis 7 hari terakhir
$query = "SELECT DATE(created_at) as tanggal, total FROM hasil_tes 
          WHERE user_id = '$user_id' 
          ORDER BY created_at DESC 
          LIMIT 7";
$result = mysqli_query($conn, $query);

$historicalData = [];
$dates = [];
$scores = [];
while ($row = mysqli_fetch_assoc($result)) {
    $historicalData[] = $row;
    $dates[] = date('d M', strtotime($row['tanggal']));
    $scores[] = (int)$row['total'];
}

// Balik urutan agar terlama pertama
$historicalData = array_reverse($historicalData);
$dates = array_reverse($dates);
$scores = array_reverse($scores);

// Fungsi prediksi baru berdasarkan MA 3 hari + deret aritmatika
function predictStress($scores) {
    $n = count($scores);
    if ($n < 3) return array_fill(0, 7, end($scores)); // Fallback jika data < 3
    
    // 1. Hitung MA 3 hari untuk data yang ada
    $movingAverages = [];
    for ($i = 2; $i < $n; $i++) {
        $ma = ($scores[$i-2] + $scores[$i-1] + $scores[$i]) / 3;
        $movingAverages[] = round($ma, 2);
    }
    
    // 2. Hitung selisih antar MA sebagai deret
    $deret = [];
    for ($i = 1; $i < count($movingAverages); $i++) {
        $deret[] = $movingAverages[$i] - $movingAverages[$i-1];
    }
    
    // 3. Rata-rata selisih sebagai proyeksi
    $avgDeret = count($deret) > 0 ? array_sum($deret) / count($deret) : 0;
    
    // 4. Proyeksikan 7 MA ke depan
    $predictedMA = [];
    $lastMA = end($movingAverages);
    for ($i = 0; $i < 7; $i++) {
        $nextMA = $lastMA + $avgDeret;
        $nextMA = max(0, min(100, $nextMA)); // Clamp 0-100
        $predictedMA[] = round($nextMA, 2);
        $lastMA = $nextMA;
    }
    
    // 5. Konversi MA prediksi -> skor prediksi
    // Asumsi: skor = MA + random variation (opsional)
    $predictedScores = [];
    foreach ($predictedMA as $ma) {
        // Tambahkan sedikit variasi acak (±5) untuk simulasi fluktuasi alami
        $variation = rand(-5, 5);
        $score = $ma + $variation;
        $score = max(0, min(100, round($score)));
        $predictedScores[] = $score;
    }
    
    return $predictedScores;
}

// Prediksi 7 hari ke depan
$predictedValues = predictStress($scores);

// Generate tanggal prediksi
$lastHistoricalDate = !empty($historicalData) ? end($historicalData)['tanggal'] : date('Y-m-d');
$predictedDates = [];
for ($i = 1; $i <= 7; $i++) {
    $predictedDates[] = date('d M', strtotime($lastHistoricalDate . " +$i days"));
}

// Gabungkan data untuk chart
$chartData = [
    'dates' => array_merge($dates, $predictedDates),
    'historical' => array_merge($scores, array_fill(0, 7, null)),
    'predicted' => array_merge(array_fill(0, count($scores), null), $predictedValues)
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prediksi Stres 7 Hari | Sistem Moving Average</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; background-color: #f5f7fa; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 20px; }
        h1 { color: #4a6baf; text-align: center; margin-bottom: 30px; }
        .chart-container { height: 400px; margin: 30px 0; }
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
        .prediction-date { font-weight: 600; color: #4a6baf; }
        .prediction-value { font-size: 24px; font-weight: bold; margin: 5px 0; }
        .low { color: #00b894; }
        .medium { color: #fdcb6e; }
        .high { color: #e17055; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Prediksi Tingkat Stres 7 Hari Mendatang</h1>
            
            <?php if (count($scores) < 3): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; text-align: center;">
                    Data historis kurang dari 3 hari. Tidak bisa membuat prediksi.
                </div>
            <?php else: ?>
                <!-- Grafik -->
                <div class="chart-container">
                    <canvas id="stressChart"></canvas>
                </div>
                
                <!-- Hasil Prediksi -->
                <h2 style="text-align: center; color: #4a6baf;">Prediksi Hari Depan</h2>
                <div class="prediction-grid">
                    <?php foreach ($predictedValues as $i => $value): 
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
                
                <!-- Script Chart -->
                <script>
                    const ctx = document.getElementById('stressChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?= json_encode($chartData['dates']) ?>,
                            datasets: [
                                {
                                    label: 'Data Historis',
                                    data: <?= json_encode($chartData['historical']) ?>,
                                    borderColor: '#4a6baf',
                                    backgroundColor: 'rgba(74, 107, 175, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    fill: true
                                },
                                {
                                    label: 'Prediksi',
                                    data: <?= json_encode($chartData['predicted']) ?>,
                                    borderColor: '#e17055',
                                    borderDash: [5, 5],
                                    backgroundColor: 'rgba(225, 112, 85, 0.1)',
                                    borderWidth: 2
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, max: 100 }
                            }
                        }
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 