<?php
session_start();
require 'config.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// QUERY untuk Grafik 7 hari
$sql_7days = "SELECT DATE(created_at) as date, normalized_score 
              FROM hasil_tes
              WHERE user_id = ?
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              ORDER BY date ASC";
$stmt_7days = mysqli_prepare($conn, $sql_7days);
mysqli_stmt_bind_param($stmt_7days, "i", $user_id);
mysqli_stmt_execute($stmt_7days);
$result_7days = mysqli_stmt_get_result($stmt_7days);

$dates = [];
$totals = [];
$raw_data = [];

// Menyimpan data mentah dari database
while ($row = mysqli_fetch_assoc($result_7days)) {
    $raw_data[$row['date']] = $row['normalized_score'];
}

// Untuk membuat data 7 hari terakhir 
for ($i = 6; $i >= 0; $i--) {
    $date_ymd = date('Y-m-d', strtotime("-$i days"));
    $date_display = date('d M', strtotime("-$i days"));
    
    $dates[] = $date_display;
    
    // Jika ada data pada tanggal tertentu, gunakan data pada database
    if (isset($raw_data[$date_ymd])) {
        $totals[] = (int)$raw_data[$date_ymd];
    } else {
        
        $totals[] = 0; 
    }
}

// Ambil data dari riwayat sebelumnya untuk membuat prediksi 
$query = "SELECT DATE(created_at) as tanggal, total FROM hasil_tes 
          WHERE user_id = '$user_id' 
          ORDER BY created_at DESC 
          LIMIT 7";
$result = mysqli_query($conn, $query);

$historicalData = [];
$scores = [];
while ($row = mysqli_fetch_assoc($result)) {
    $historicalData[] = $row;
    $scores[] = (int)$row['total'];
}

// Balik urutan agar terlama pertama
$scores = array_reverse($scores);

// Fungsi prediksi Moving Average + deret aritmatika
function predictStress($scores) {
    $n = count($scores);
    if ($n < 3) return array_fill(0, 7, min(100, end($scores)));
    $predicted = [];
    for ($i = 0; $i < 7; $i++) {
        $last3 = array_slice($scores, -3);
        $avg = array_sum($last3) / 3;
        $next = min(100, round($avg));
        $predicted[] = $next;
        $scores[] = $next;
    }
    return $predicted;
}

$predicted = predictStress($scores);

// QUERY untuk Grafik 3D 
$sql_today = "SELECT stress_total, akademik_total, keuangan_total, normalized_score, created_at
              FROM hasil_tes
              WHERE user_id = ?
              AND DATE(created_at) = CURDATE()
              ORDER BY created_at DESC
              LIMIT 1";
$stmt_today = mysqli_prepare($conn, $sql_today);
mysqli_stmt_bind_param($stmt_today, "i", $user_id);
mysqli_stmt_execute($stmt_today);
$result_today = mysqli_stmt_get_result($stmt_today);
$today_data = mysqli_fetch_assoc($result_today);
mysqli_stmt_close($stmt_today);

// Menyiapkan data untuk grafik 3D
$vector_data = null;
if ($today_data) {
    // Menggunakan nilai 0 - 30
    $stress_norm = $today_data['stress_total'];
    $akademik_norm = $today_data['akademik_total'];
    $keuangan_norm = $today_data['keuangan_total'];
    $normalized_score = $today_data['normalized_score'];

    $magnitude = sqrt(pow($stress_norm, 2) + pow($akademik_norm, 2) + pow($keuangan_norm, 2));

    $vector_data = [
        'date' => date('d M Y', strtotime($today_data['created_at'])),
        'total' => $normalized_score,
        'magnitude' => $magnitude,
        'stress' => $stress_norm,
        'akademik' => $akademik_norm,
        'keuangan' => $keuangan_norm,
        'x' => $stress_norm,
        'y' => $akademik_norm,
        'z' => $keuangan_norm
    ];
}
?>

<!DOCTYPE html>
<html>
<header id="navbar">
  <div class="logo">
    <img src="images/logo.png" alt="Mindara Logo" class="logo-img" />
  </div>
  <nav style="margin-right: 3rem;">
    <a href="index.php">Beranda</a>
    <a href="analisis.php">Analisis</a>
    <a href="tentang.php">Tentang</a>

    <?php if (isset($_SESSION['user_name'])): ?>
      <a href="profile.php" class="user-greeting">Halo, <?= htmlspecialchars($_SESSION['user_name']); ?>!</a>
      <a href="logout.php" style="margin-left: 10px;">Logout</a>
    <?php else: ?>
      <a href="sign-in.php" class="login-link">Login</a>
    <?php endif; ?>
  </nav>
</header>
<head>
    <title>Hasil Tes Stres</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/controls/OrbitControls.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f7f6; color: #333; }
        .mindara-wrapper { display: flex; justify-content: center; padding: 20px; }
        .mindara-container { background-color: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); width: 90%; max-width: 1000px; }
        .mindara-heading { color: #3498db; text-align: center; margin-bottom: 30px; font-size: 24px; }
        .chart-container { width: 100%; height: 400px; margin-bottom: 40px; background-color: #fff; padding:10px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);}
        #vector3d-container { width: 100%; height: 450px; margin-top: 20px; margin-bottom:20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: relative; /* Untuk positioning canvas */}
        .controls { text-align: center; margin-bottom: 20px; }
        .controls button { padding: 10px 15px; margin: 0 10px; background-color: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s; }
        .controls button:hover { background-color: #2980b9; }
        .legend { margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 8px; }
        .legend h3 { margin-top: 0; margin-bottom: 10px; font-size: 16px; color: #555;}
        .legend-item { display: flex; align-items: center; margin-bottom: 8px; }
        .legend-color { width: 20px; height: 20px; margin-right: 10px; border-radius: 4px; }
        .vector-info { margin-top: 30px; padding: 20px; background-color: #eaf5ff; border-radius: 8px; }
        .vector-info h3 { margin-top: 0; color: #3498db; }
        .vector-day-card { background-color: #fff; padding: 15px; border-radius: 6px; box-shadow: 0 1px 5px rgba(0,0,0,0.05); }
        .vector-day-card h4 { margin-top: 0; margin-bottom: 10px; color: #2980b9; }
        .vector-components { margin-top: 10px; }
        .vector-component { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee; }
        .vector-component:last-child { border-bottom: none; }
        .vector-component span:first-child { font-weight: bold; color: #555; }
        
        header {
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            background: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 0rem 1.5rem 0rem;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: box-shadow 0.3s ease;
            -webkit-transition: box-shadow 0.3s ease;
            -moz-transition: box-shadow 0.3s ease;
            -ms-transition: box-shadow 0.3s ease;
            -o-transition: box-shadow 0.3s ease;
            }

            header.shadow {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }

            .logo-img {
            width: 100px;
            object-fit: contain;
            margin-left: 3rem;
            }

            .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #7B0000;
            }
            nav a {
            margin-left: 1.5rem;
            text-decoration: none;
            color: #002B45;
            font-weight: 500;
            position: relative;
            }

            nav a:hover {
            color: #002B45;
            text-decoration: underline;
            }

            .login-link {
            border: 2px solid #669BBC; 
            padding: 5px 10px;
            border-radius: 5px;
            transition: border-color 0.3s; 
            }

            .login-link:hover {
            border-color: #002B45; 
            }
        .rekomendasi-box {
            margin-top: 30px;
            background: linear-gradient(135deg, #f8f4ff 0%, #eef2ff 100%);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
            border-left: 5px solid #6c5ce7;
        }
        .rekomendasi-box h3 {
            color: #6c5ce7;
            margin-top: 0;
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif; 
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .rekomendasi-box ul {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
        }
        .rekomendasi-box .kondisi-anda {
            margin-bottom: 18px;
            padding-bottom: 18px;
            border-bottom: 1px dashed #d1d5db;
            font-weight: 500;
            color: #4b5563;
            font-size: 1.1em;
        }
        .rekomendasi-box .kondisi-anda .skor {
            font-weight: normal;
            color: #333;
        }
        .rekomendasi-box li.rekomendasi-item {
            margin-bottom: 12px;
            padding-left: 28px;
            position: relative;
            color: #4b5563;
            line-height: 1.5;
        }
        .rekomendasi-box li.rekomendasi-item svg {
            position: absolute;
            left: 0;
            top: 5px;
            width: 18px;
            height: 18px;
            color: #6c5ce7;
        }
         .no-data-message { color: #6c5ce7; font-style: italic; padding: 15px; text-align: center; background-color: #f0f0f8; border-radius: 8px;}

    </style>
</head>

<body style="margin-top: 7rem;">
    <div class="mindara-wrapper">
        <div class="mindara-container">
            <h2 class="mindara-heading">Visualisasi Perkembangan Stres Anda</h2>
            
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
            
            <?php if ($vector_data): ?>
            <h3>Visualisasi 3D Stres Hari Ini (<?= htmlspecialchars($vector_data['date']) ?>)</h3>
            <div class="controls">
                <button id="rotateToggle">Aktifkan Rotasi Otomatis</button>
                <button id="resetView">Reset Pandangan Kamera</button>
            </div>
            <div id="vector3d-container"></div>
            
            <div class="legend">
                <h3>Legenda Sumbu 3D (Skala 0-30)</h3>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #2ecc71;"></div> <span>Sumbu X: Keuangan (Nilai: <?= number_format($vector_data['keuangan'], 2) ?>)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #3498db;"></div> <span>Sumbu Y: Stres Umum (Nilai: <?= number_format($vector_data['stress'], 2) ?>)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #e74c3c;"></div> <span>Sumbu Z: Tekanan Akademik (Nilai: <?= number_format($vector_data['akademik'], 2) ?>)</span>
                </div>
            </div>
            

            <div class="rekomendasi-box">
                <h3>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20ZM11 16H13V18H11V16ZM12 6C9.79 6 8 7.79 8 10H10C10 8.9 10.9 8 12 8C13.1 8 14 8.9 14 10C14 12 11 11.75 11 15H13C13 12.75 16 12.5 16 10C16 7.79 14.21 6 12 6Z" fill="#6c5ce7"/>
                    </svg>
                    Rekomendasi & Solusi Harian
                </h3>
                <ul>
                    <?php
                    if (!empty($vector_data)) {
                        $total_score_for_level = $vector_data['total'];
                        $stress_level_text = '';
                        $recommendations_list = [];
                        
                        if ($total_score_for_level <= 33) {
                            $stress_level_text = '<span style="color: #00b894; font-weight: bold;">Stres Rendah</span>';
                            $recommendations_list = [
                                "Lanjutkan kebiasaan positif Anda! Pertahankan rutinitas sehat.",
                                "Jaga keseimbangan antara aktivitas dan istirahat yang cukup.",
                                "Terus bangun dan rawat relasi sosial yang mendukung.",
                                "Tetap waspada terhadap pemicu stres ringan agar tidak terakumulasi."
                            ];
                        } elseif ($total_score_for_level <= 66) {
                            $stress_level_text = '<span style="color: #fdcb6e; font-weight: bold;">Stres Sedang</span>';
                            $recommendations_list = [
                                "Prioritaskan tidur berkualitas, minimal 7-8 jam setiap malam.",
                                "Integrasikan teknik relaksasi seperti peregangan, yoga, atau meditasi singkat dalam rutinitas harian.",
                                "Perhatikan asupan kafein dan gula, kurangi jika berlebihan.",
                                "Jadwalkan waktu istirahat fisik dan mental secara teratur."
                            ];
                        } else {
                            $stress_level_text = '<span style="color: #e17055; font-weight: bold;">Stres Tinggi</span>';
                            $recommendations_list = [
                                "Jangan ragu untuk berbagi perasaan dengan orang yang Anda percaya atau seorang profesional.",
                                "Luangkan waktu untuk aktivitas yang Anda nikmati dan memberikan ketenangan.",
                                "Praktikkan teknik pernapasan dalam atau meditasi secara rutin untuk menenangkan sistem saraf.",
                                "Izinkan diri Anda merasakan dan mengelola emosi secara sehat, jangan ditekan."
                            ];
                        }
                        
                        echo '<li class="kondisi-anda">
                                Kondisi Stres Anda Saat Ini: ' . $stress_level_text . 
                                ' <span class="skor">(Skor Keseluruhan: ' . round($total_score_for_level) . '/100)</span>
                              </li>';
                        
                        foreach ($recommendations_list as $rec_item) {
                            echo '<li class="rekomendasi-item">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 16.17L4.83 12L3.41 13.41L9 19L21 7L19.59 5.59L9 16.17Z" fill="currentColor"/>
                                    </svg>
                                    ' . htmlspecialchars($rec_item) . '
                                  </li>';
                        }
                    } else { 
                        echo '<li class="no-data-message">Data tidak cukup untuk menampilkan rekomendasi.</li>';
                    }
                    ?>
                </ul>
            </div>

            <?php else: ?>
            <p class="no-data-message">Belum ada data tes untuk hari ini. Silakan <a href="analisis.php">lakukan tes</a> terlebih dahulu untuk melihat visualisasi 3D dan rekomendasi.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {

        const labels = <?php echo json_encode($dates); ?>;
        const dataAsli = <?php echo json_encode($totals); ?>;
        const dataPrediksi = <?php echo json_encode($predicted); ?>;
        
        // Membuat data untuk prediksi 7 hari 
        const futureDates = [];
        for (let i = 1; i <= 7; i++) {
            const today = new Date();
            today.setDate(today.getDate() + i);
            futureDates.push(today.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
        }
        
        const allDates = [...labels, ...futureDates];
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: allDates,
                datasets: [
                    {
                        label: 'Skor Stres Keseluruhan',
                        data: [...dataAsli, ...Array(7).fill(null)],
                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
                        borderColor: '#3498db',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#3498db',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: '#2980b9'
                    },
                    {
                        label: 'Prediksi Skor Stres',
                        data: [...Array(dataAsli.length).fill(null), ...dataPrediksi],
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        borderColor: '#e74c3c',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#e74c3c',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#c0392b'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true,
                        max: 110,
                        title: { display: true, text: 'Skor Stres (0-100)', font: {size: 14} }
                    },
                    x: {
                        title: { display: true, text: 'Tanggal', font: {size: 14} }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { font: { size: 14 } }
                    },
                    title: {
                        display: true,
                        text: 'Perkembangan & Prediksi Stres (7 Hari)',
                        font: { size: 16, weight: 'bold' }
                    },
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return 'Tanggal: ' + tooltipItems[0].label;
                            },
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

        <?php if (!empty($vector_data)): ?>
        const container = document.getElementById('vector3d-container');
        if (container) {
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0xf0f2f5);
            
            const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.set(15, 15, 25); 
            
            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            renderer.setPixelRatio(window.devicePixelRatio);
            container.appendChild(renderer.domElement);
            
            const controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            controls.minDistance = 10;
            controls.maxDistance = 50;
            
            scene.add(new THREE.AmbientLight(0xffffff, 0.7));
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(5, 10, 7);
            scene.add(directionalLight);
            
            // Sumbu 
            const scaleFactor = 0.5; 
            const axisLength = 30 * scaleFactor; 
            const axisRadius = 0.08; 
            const headRadius = 0.3;  
            const headHeight = 0.8;  
            
            function createAxis(axisParams) {
                const { direction, color, name } = axisParams;
                const group = new THREE.Group();

                const cylinderGeom = new THREE.CylinderGeometry(axisRadius, axisRadius, axisLength, 12);
                const cylinderMat = new THREE.MeshBasicMaterial({ color: color });
                const cylinder = new THREE.Mesh(cylinderGeom, cylinderMat);
                
                const coneGeom = new THREE.ConeGeometry(headRadius, headHeight, 12);
                const cone = new THREE.Mesh(coneGeom, cylinderMat);

                if (name === 'x') {
                    cylinder.rotation.z = -Math.PI / 2;
                    cylinder.position.x = axisLength / 2;
                    cone.rotation.z = -Math.PI / 2;
                    cone.position.x = axisLength;
                } else if (name === 'y') {
                    cylinder.position.y = axisLength / 2;
                    cone.position.y = axisLength;
                } else if (name === 'z') {
                    cylinder.rotation.x = Math.PI / 2;
                    cylinder.position.z = axisLength / 2;
                    cone.rotation.x = Math.PI / 2;
                    cone.position.z = axisLength;
                }
                
                group.add(cylinder);
                group.add(cone);
                scene.add(group);
            }

            createAxis({ direction: new THREE.Vector3(1, 0, 0), color: 0x3498db, name: 'x' });
            createAxis({ direction: new THREE.Vector3(0, 1, 0), color: 0xe74c3c, name: 'y' });
            createAxis({ direction: new THREE.Vector3(0, 0, 1), color: 0x2ecc71, name: 'z' });
            
            const gridSize = 60 * scaleFactor;
            const gridDivisions = 10;
            const gridHelper = new THREE.GridHelper(gridSize, gridDivisions, 0xcccccc, 0xcccccc);
            scene.add(gridHelper);
            
            const vector = <?php echo json_encode($vector_data); ?>;
            const vectorScale = 0.5 * scaleFactor; 
            
            let arrowDirection, arrowLength;
            if (vector.magnitude === 0) {
                arrowDirection = new THREE.Vector3(0, 0, 0);
                arrowLength = 0;
            } else {
                arrowDirection = new THREE.Vector3(vector.x, vector.y, vector.z).normalize();
                arrowLength = vector.magnitude * vectorScale;
            }

            const arrowHelper = new THREE.ArrowHelper(
                arrowDirection,
                new THREE.Vector3(0, 0, 0),
                arrowLength,
                0x8e44ad,
                Math.max(0.5, arrowLength * 0.15),
                Math.max(0.3, arrowLength * 0.1)
            );
            scene.add(arrowHelper);

            function createAxisLabel(text, position, colorHex) {
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.width = 200; 
                canvas.height = 64;
                context.font = 'Bold 20px Arial';
                context.fillStyle = '#' + colorHex.toString(16).padStart(6, '0');
                context.textAlign = 'center';
                context.textBaseline = 'middle';
                context.fillText(text, canvas.width/2, canvas.height/2);
                
                const texture = new THREE.CanvasTexture(canvas);
                const spriteMaterial = new THREE.SpriteMaterial({ 
                    map: texture, 
                    depthTest: false, 
                    transparent: true 
                });
                const sprite = new THREE.Sprite(spriteMaterial);
                sprite.position.copy(position);
                sprite.scale.set(4, 2, 1);
                return sprite;
            }
            
            const labelOffset = 2;
            scene.add(createAxisLabel('Y: Stres', new THREE.Vector3(axisLength + labelOffset, 0, 0), 0x3498db));
            scene.add(createAxisLabel('Z: Akademik', new THREE.Vector3(0, axisLength + labelOffset, 0), 0xe74c3c));
            scene.add(createAxisLabel('X: Keuangan', new THREE.Vector3(0, 0, axisLength + labelOffset), 0x2ecc71));

            const labelCanvas = document.createElement('canvas');
            labelCanvas.width = 220; 
            labelCanvas.height = 110;
            const labelContext = labelCanvas.getContext('2d');
            labelContext.fillStyle = 'rgba(40, 40, 40, 0.75)';
            labelContext.fillRect(0, 0, labelCanvas.width, labelCanvas.height);
            labelContext.font = 'Bold 18px Arial';
            labelContext.fillStyle = '#FFFFFF';
            labelContext.textAlign = 'center';
            labelContext.fillText(`Skor: ${vector.total.toFixed(0)}`, labelCanvas.width/2, 30);
            labelContext.font = '15px Arial';
            labelContext.fillText(`Magnitude: ${vector.magnitude.toFixed(2)}`, labelCanvas.width/2, 60);
            labelContext.fillText(`(X:${vector.x.toFixed(1)}, Y:${vector.y.toFixed(1)}, Z:${vector.z.toFixed(1)})`, labelCanvas.width/2, 90);
            
            const labelTexture = new THREE.CanvasTexture(labelCanvas);
            const labelSprite = new THREE.Sprite(
                new THREE.SpriteMaterial({ 
                    map: labelTexture, 
                    depthTest: false, 
                    transparent: true 
                })
            );
            
            if (arrowLength > 0) {
                labelSprite.position.copy(arrowDirection).multiplyScalar(arrowLength).add(new THREE.Vector3(0, 0.7, 0));
            } else {
                labelSprite.position.set(0, 0.7, 0);
            }
            labelSprite.scale.set(4, 2, 1);
            scene.add(labelSprite);
            
            let autoRotate = false;
            const rotateButton = document.getElementById('rotateToggle');
            if (rotateButton) {
                rotateButton.addEventListener('click', () => {
                    autoRotate = !autoRotate;
                    rotateButton.textContent = autoRotate ? 'Hentikan Rotasi Otomatis' : 'Aktifkan Rotasi Otomatis';
                });
            }
            
            const resetButton = document.getElementById('resetView');
            if (resetButton) {
                resetButton.addEventListener('click', () => {
                    controls.reset();
                    camera.position.set(15, 15, 25);
                    autoRotate = false;
                    if (rotateButton) rotateButton.textContent = 'Aktifkan Rotasi Otomatis';
                });
            }

            function animate() {
                requestAnimationFrame(animate);
                if (autoRotate && arrowLength > 0) {
                    scene.rotation.y += 0.003;
                }
                controls.update();
                renderer.render(scene, camera);
            }
            animate();
            
            window.addEventListener('resize', () => {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            });
        }
        <?php endif; ?>
    });
    </script>
</body>
</html>