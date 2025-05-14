<?php
session_start();
require 'config.php'; // Make sure config.php is in the same directory or provide the correct path

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// QUERY 1: Untuk Grafik Garis (7 Hari Terakhir)
$sql_7days = "SELECT DATE(created_at) as date, SUM(total) as total
              FROM hasil_tes
              WHERE user_id = ?
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              GROUP BY DATE(created_at)
              ORDER BY date ASC";
$stmt_7days = mysqli_prepare($conn, $sql_7days);
mysqli_stmt_bind_param($stmt_7days, "i", $user_id);
mysqli_stmt_execute($stmt_7days);
$result_7days = mysqli_stmt_get_result($stmt_7days);

$dates = [];
$totals = [];

// Initialize with default data for the last 7 days
for ($i = 6; $i >= 0; $i--) {
    $date = date('d M', strtotime("-$i days"));
    $dates[] = $date;
    $totals[] = 0; // Default value
}

// Populate with data from the database
while ($row = mysqli_fetch_assoc($result_7days)) {
    $date_index = array_search(date('d M', strtotime($row['date'])), $dates);
    if ($date_index !== false) {
        $totals[$date_index] = $row['total'];
    }
}
mysqli_stmt_close($stmt_7days);

// QUERY 2: Untuk Grafik 3D (Hari Ini Saja)
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

// Prepare data for 3D graph
$vector_data = null;
if ($today_data) {
    $stress_total_raw = $today_data['stress_total'];
    $akademik_total_raw = $today_data['akademik_total'];
    $keuangan_total_raw = $today_data['keuangan_total'];
    $normalized_score = $today_data['normalized_score'];

    // Normalize to 0-10 scale (assuming max raw score for each category is 30)
    $stress_norm = ($stress_total_raw / 30) * 10;
    $akademik_norm = ($akademik_total_raw / 30) * 10;
    $keuangan_norm = ($keuangan_total_raw / 30) * 10;

    // Calculate magnitude of the 0-10 scaled vector
    $magnitude = sqrt(pow($stress_norm, 2) + pow($akademik_norm, 2) + pow($keuangan_norm, 2));

    $vector_data = [
        'date' => date('d M Y', strtotime($today_data['created_at'])), // Full date for clarity
        'total' => $normalized_score, // This is the overall normalized score (0-100)
        'magnitude' => $magnitude,     // Magnitude of the (stress_norm, akademik_norm, keuangan_norm) vector
        'stress' => $stress_norm,     // Component value (0-10)
        'akademik' => $akademik_norm,   // Component value (0-10)
        'keuangan' => $keuangan_norm,   // Component value (0-10)
        'x' => $stress_norm,           // For 3D plot x-axis
        'y' => $akademik_norm,         // For 3D plot y-axis
        'z' => $keuangan_norm          // For 3D plot z-axis
    ];
}
?>

<!DOCTYPE html>
<html>
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
        #vector3d-container { width: 100%; height: 450px; margin-top: 20px; margin-bottom:20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
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
        /* Styles for Recommendation Box */
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
            font-family: 'Playfair Display', serif; /* Ensure this font is loaded or use a fallback */
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
            top: 5px; /* Adjusted for better alignment */
            width: 18px;
            height: 18px;
            color: #6c5ce7;
        }
         .no-data-message { color: #6c5ce7; font-style: italic; padding: 15px; text-align: center; background-color: #f0f0f8; border-radius: 8px;}

    </style>
</head>
<body>
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
                <h3>Legenda Sumbu 3D (Skala 0-10)</h3>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #3498db;"></div> <span>Sumbu X: Stres Umum (Nilai: <?= number_format($vector_data['stress'], 2) ?>)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #e74c3c;"></div> <span>Sumbu Y: Tekanan Akademik (Nilai: <?= number_format($vector_data['akademik'], 2) ?>)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #2ecc71;"></div> <span>Sumbu Z: Stres Keuangan (Nilai: <?= number_format($vector_data['keuangan'], 2) ?>)</span>
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
                        $total_score_for_level = $vector_data['total']; // This is normalized_score (0-100)
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
                    } else { // Should not happen if $vector_data is true, but as a fallback
                        echo '<li class="no-data-message">Data tidak cukup untuk menampilkan rekomendasi.</li>';
                    }
                    ?>
                </ul>
            </div>

            <div class="vector-info">
                <h3>Detail Vektor Stres Harian</h3>
                <div class="vector-day-card">
                    <h4>Tanggal: <?= htmlspecialchars($vector_data['date']) ?></h4>
                    <p><strong>Skor Stres Keseluruhan (0-100):</strong> <?= round($vector_data['total']) ?></p>
                    <p><strong>Besaran Vektor (Magnitude 0-10 komponen):</strong> <?= number_format($vector_data['magnitude'], 2) ?></p>
                    <p><strong>Komponen Vektor (Skala 0-10):</strong></p>
                    <div class="vector-components">
                        <div class="vector-component">
                            <span>X (Stres Umum):</span>
                            <span><?= number_format($vector_data['x'], 2) ?></span>
                        </div>
                        <div class="vector-component">
                            <span>Y (Tekanan Akademik):</span>
                            <span><?= number_format($vector_data['y'], 2) ?></span>
                        </div>
                        <div class="vector-component">
                            <span>Z (Stres Keuangan):</span>
                            <span><?= number_format($vector_data['z'], 2) ?></span>
                        </div>
                    </div>
                </div>
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
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Skor Stres Keseluruhan (7 Hari Terakhir)',
                        data: <?php echo json_encode($totals); ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
                        borderColor: '#3498db',
                        borderWidth: 2,
                        tension: 0.4, // Smoother curve
                        fill: true,
                        pointBackgroundColor: '#3498db',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: '#2980b9'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: true,
                            max: 100, // Assuming total score is 0-100
                            title: { display: true, text: 'Skor Stres (0-100)', font: {size: 14} }
                        },
                        x: {
                            title: { display: true, text: 'Tanggal', font: {size: 14} }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: { font: { size: 14 } }
                        }
                    }
                }
            });
        }

        <?php if (!empty($vector_data)): ?>
        const container = document.getElementById('vector3d-container');
        if (container) {
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0xf0f2f5); // Light grey background
            
            const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.set(8, 8, 15); // Adjusted camera position for better initial view
            
            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            renderer.setPixelRatio(window.devicePixelRatio); // For sharper rendering on high DPI screens
            container.appendChild(renderer.domElement);
            
            const controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            controls.minDistance = 5; // Zoom constraints
            controls.maxDistance = 50;
            
            scene.add(new THREE.AmbientLight(0xffffff, 0.7)); // Softer ambient light
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(5, 10, 7);
            scene.add(directionalLight);
            
            // Axes Helper: X (Red), Y (Green), Z (Blue) standard
            const axesHelper = new THREE.AxesHelper(10); // Length of axes lines
            scene.add(axesHelper);
            
            // Grid Helper
            const gridHelper = new THREE.GridHelper(20, 20, 0xcccccc, 0xcccccc); // Size, divisions, colorCenterLine, colorGrid
            scene.add(gridHelper);
            
            const vector = <?php echo json_encode($vector_data); ?>;
            
            let arrowDirection, arrowLength;
            const visualScale = 0.7; // Adjust this to scale the arrow's visual length if needed (e.g. if max value is 10, arrow could be length 7)

            if (vector.magnitude === 0) {
                // For zero magnitude, direction can be (0,0,0) or a default like (0,1,0) for consistency if an object must be added.
                // An arrow of length 0 is invisible.
                arrowDirection = new THREE.Vector3(0, 0, 0); // Vector3's normalize() handles (0,0,0) by returning (0,0,0)
                arrowLength = 0;
            } else {
                // vector.x, .y, .z are already the 0-10 scaled values
                arrowDirection = new THREE.Vector3(vector.x, vector.y, vector.z).normalize();
                // Let arrow length be proportional to magnitude, but capped or scaled for visualization
                arrowLength = vector.magnitude * visualScale; // Scale magnitude for visual length
            }

            // Arrow colors matching legend
            // The main arrow can represent overall magnitude, or we can show component arrows.
            // For a single vector representing the combined stress:
            const arrowColor = 0x8e44ad; // A distinct color for the main vector, e.g., purple

            const headLength = arrowLength > 0 ? Math.max(0.5, arrowLength * 0.1) : 0; // Proportional head, min size
            const headWidth = arrowLength > 0 ? Math.max(0.3, arrowLength * 0.07) : 0; // Proportional head, min size

            const mainArrowHelper = new THREE.ArrowHelper(
                arrowDirection,
                new THREE.Vector3(0, 0, 0), // Origin
                arrowLength,
                arrowColor, 
                headLength,
                headWidth 
            );
            scene.add(mainArrowHelper);

            // Add labels for axes
            function createAxisLabel(text, position, color) {
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.width = 128; canvas.height = 64;
                context.font = 'Bold 20px Arial';
                context.fillStyle = color;
                context.textAlign = 'center';
                context.fillText(text, canvas.width/2, canvas.height/2 + 8);
                const texture = new THREE.CanvasTexture(canvas);
                const spriteMaterial = new THREE.SpriteMaterial({ map: texture, depthTest: false });
                const sprite = new THREE.Sprite(spriteMaterial);
                sprite.position.copy(position);
                sprite.scale.set(2, 1, 1);
                return sprite;
            }
            scene.add(createAxisLabel('X (Stres)', new THREE.Vector3(11, 0, 0), '#3498db'));
            scene.add(createAxisLabel('Y (Akademik)', new THREE.Vector3(0, 11, 0), '#e74c3c'));
            scene.add(createAxisLabel('Z (Keuangan)', new THREE.Vector3(0, 0, 11), '#2ecc71'));


            // Add data label at the tip of the arrow
            const labelCanvas = document.createElement('canvas');
            labelCanvas.width = 200; 
            labelCanvas.height = 100;
            const labelContext = labelCanvas.getContext('2d');
            labelContext.fillStyle = 'rgba(0, 0, 0, 0.7)'; // Semi-transparent background
            labelContext.fillRect(0, 0, labelCanvas.width, labelCanvas.height);
            labelContext.font = 'Bold 16px Arial';
            labelContext.fillStyle = '#FFFFFF';
            labelContext.textAlign = 'center';
            labelContext.fillText(`Skor: ${vector.total.toFixed(0)}`, labelCanvas.width/2, 30);
            labelContext.font = '14px Arial';
            labelContext.fillText(`Mag: ${vector.magnitude.toFixed(2)}`, labelCanvas.width/2, 55);
            labelContext.fillText(`(${vector.x.toFixed(1)}, ${vector.y.toFixed(1)}, ${vector.z.toFixed(1)})`, labelCanvas.width/2, 80);
            
            const labelTexture = new THREE.CanvasTexture(labelCanvas);
            const labelSprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: labelTexture, depthTest: false }));
            
            if (arrowLength > 0) {
                labelSprite.position.copy(arrowDirection).multiplyScalar(arrowLength).add(new THREE.Vector3(0, 0.5, 0)); // Position slightly above arrow tip
            } else {
                labelSprite.position.set(0, 0.5, 0); // At origin if no arrow
            }
            labelSprite.scale.set(3, 1.5, 1); // Adjust sprite scale
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
                    camera.position.set(8, 8, 15); // Reset to initial position
                    autoRotate = false; // Stop rotation on reset
                     if(rotateButton) rotateButton.textContent = 'Aktifkan Rotasi Otomatis';
                });
            }
            
            function animate() {
                requestAnimationFrame(animate);
                if (autoRotate && arrowLength > 0) { // Only rotate if there's something to see and autoRotate is on
                    scene.rotation.y += 0.003; // Rotate the whole scene for a turntable effect
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