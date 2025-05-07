<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// QUERY 1: Untuk Grafik Garis (7 Hari Terakhir)
$sql_7days = "SELECT DATE(created_at) as date, SUM(total) as total 
              FROM hasil_tes 
              WHERE user_id = '$user_id'
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              GROUP BY DATE(created_at)
              ORDER BY date ASC";
$result_7days = mysqli_query($conn, $sql_7days);

$dates = [];
$totals = [];

// Isi data default jika tidak ada hasil
for ($i = 6; $i >= 0; $i--) {
    $date = date('d M', strtotime("-$i days"));
    $dates[] = $date;
    $totals[] = 0; // Nilai default
}

// Timpa dengan data dari database jika ada
while ($row = mysqli_fetch_assoc($result_7days)) {
    $date_index = array_search(date('d M', strtotime($row['date'])), $dates);
    if ($date_index !== false) {
        $totals[$date_index] = $row['total'];
    }
}

// QUERY 2: Untuk Grafik 3D (Hari Ini Saja)
$sql_today = "SELECT q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, total, created_at 
              FROM hasil_tes 
              WHERE user_id = '$user_id'
              AND DATE(created_at) = CURDATE()
              ORDER BY created_at DESC
              LIMIT 1";
$result_today = mysqli_query($conn, $sql_today);
$today_data = mysqli_fetch_assoc($result_today);

// Siapkan data untuk grafik 3D
$vector_data = null;
if ($today_data) {
    // Hitung komponen stres
    $stress_total = 0;
    for ($i = 1; $i <= 10; $i++) {
        $stress_total += isset($today_data['q'.$i]) ? (int)$today_data['q'.$i] : 0;
    }
    
    // Normalisasi ke skala 0-10
    $stress_total = ($stress_total / 30) * 10;
    
    // Untuk demo, kita asumsikan komponen akademik dan keuangan sama dengan stres
    // Di aplikasi nyata, ini harus dihitung dari pertanyaan yang sesuai
    $akademik_total = $stress_total * 0.8;
    $keuangan_total = $stress_total * 0.6;
    
    $magnitude = sqrt($stress_total*$stress_total + $akademik_total*$akademik_total + $keuangan_total*$keuangan_total);
    
    $vector_data = [
        'date' => date('d M', strtotime($today_data['created_at'])),
        'total' => $today_data['total'],
        'magnitude' => $magnitude,
        'stress' => $stress_total,
        'akademik' => $akademik_total,
        'keuangan' => $keuangan_total,
        'x' => $stress_total,
        'y' => $akademik_total,
        'z' => $keuangan_total
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
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .mindara-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .mindara-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .mindara-heading {
            font-family: 'Playfair Display', serif;
            color: #2c3e50;
            margin-top: 0;
        }
        
        .chart-container {
            margin: 30px 0;
            position: relative;
            height: 300px;
        }
        
        #vector3d-container {
            width: 100%;
            height: 500px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 30px 0;
        }
        
        .vector-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }
        
        .vector-day-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            flex: 1;
            min-width: 200px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .vector-day-card h4 {
            margin-top: 0;
            color: #3498db;
        }
        
        .vector-components {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5px;
            font-size: 0.9em;
        }
        
        .vector-component {
            display: flex;
            justify-content: space-between;
        }
        
        .controls {
            margin: 20px 0;
            padding: 15px;
            background: #f0f4f8;
            border-radius: 8px;
        }
        
        .controls button {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            margin-right: 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .controls button:hover {
            background: #2980b9;
        }
        
        .legend {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="mindara-wrapper">
        <div class="mindara-container">
            <h2 class="mindara-heading">Visualisasi Perkembangan Stress</h2>
            
            <!-- Grafik Garis 7 Hari -->
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
            
            <!-- Grafik 3D Hari Ini -->
            <?php if ($vector_data): ?>
            <h3>Visualisasi 3D Stres Hari Ini</h3>
            <div class="controls">
                <button id="rotateToggle">Aktifkan Rotasi</button>
                <button id="resetView">Reset Pandangan</button>
            </div>
            <div id="vector3d-container"></div>
            
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #3498db;"></div>
                    <span>Sumbu X: Tingkat Stres (<?= $vector_data['stress'] ?>)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #e74c3c;"></div>
                    <span>Sumbu Y: Tekanan Akademik (<?= $vector_data['akademik'] ?>)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #2ecc71;"></div>
                    <span>Sumbu Z: Kesehatan Keuangan (<?= $vector_data['keuangan'] ?>)</span>
                </div>
            </div>
            
            <!-- Rekomendasi Harian -->
            <div style="margin-top: 30px;">
                <div style="
                    background: linear-gradient(135deg, #f8f4ff 0%, #eef2ff 100%);
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                    border-left: 4px solid #6c5ce7;
                ">
                    <h3 style="
                        color: #6c5ce7;
                        margin-top: 0;
                        font-family: 'Playfair Display', serif;
                        font-size: 1.3em;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    ">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20ZM11 16H13V18H11V16ZM12 6C9.79 6 8 7.79 8 10H10C10 8.9 10.9 8 12 8C13.1 8 14 8.9 14 10C14 12 11 11.75 11 15H13C13 12.75 16 12.5 16 10C16 7.79 14.21 6 12 6Z" fill="#6c5ce7"/>
                        </svg>
                        Rekomendasi & Solusi
                    </h3>
                    
                    <div style="margin-top: 15px;">
                        <ul style="
                            list-style-type: none;
                            padding-left: 0;
                            margin: 0;
                        ">
                            <?php
                            if (!empty($vector_data)) {
                                $total = $vector_data['total'];
                                $stress_level = '';
                                $recommendations = [];
                                
                                // Tentukan level stres dan rekomendasi
                                if ($total <= 10) {
                                    $stress_level = '<span style="color: #00b894; font-weight: bold;">Stres Rendah</span>';
                                    $recommendations = [
                                        "Teruskan kebiasaan sehat seperti makan teratur, tidur cukup, dan olahraga ringan.",
                                        "Jaga keseimbangan antara pekerjaan dan waktu istirahat.",
                                        "Bangun relasi sosial yang positif.",
                                        "Waspadai stres ringan agar tidak menumpuk."
                                    ];
                                } elseif ($total <= 20) {
                                    $stress_level = '<span style="color: #fdcb6e; font-weight: bold;">Stres Sedang</span>';
                                    $recommendations = [
                                        "Pastikan Anda cukup tidur (minimal 7-8 jam).",
                                        "Lakukan relaksasi fisik seperti stretching, pijat, atau yoga.",
                                        "Kurangi konsumsi kafein dan gula berlebihan.",
                                        "Luangkan waktu untuk istirahat fisik yang berkualitas."
                                    ];
                                } elseif ($total <= 30) {
                                    $stress_level = '<span style="color: #e17055; font-weight: bold;">Stres Tinggi</span>';
                                    $recommendations = [
                                        "Ceritakan perasaan Anda kepada orang yang dipercaya.",
                                        "Lakukan aktivitas yang menyenangkan dan menenangkan hati.",
                                        "Latih pernapasan dalam atau meditasi secara rutin.",
                                        "Jangan menekan emosi—pelajari cara mengelolanya dengan sehat."
                                    ];
                                } else {
                                    $stress_level = '<span style="color: #d63031; font-weight: bold;">Stres Sangat Tinggi</span>';
                                    $recommendations = [
                                        "Pertimbangkan untuk berkonsultasi dengan profesional kesehatan mental.",
                                        "Istirahatlah dari aktivitas berat secara mental.",
                                        "Kurangi multitasking dan fokus pada satu hal dalam satu waktu.",
                                        "Lakukan aktivitas ringan seperti berjalan-jalan atau menonton film ringan.",
                                        "Tulis hal-hal kecil yang membuat Anda bersyukur setiap hari."
                                    ];
                                }
                                
                                // Tampilkan rekomendasi
                                echo '<li style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #d1d5db;">
                                        <div style="font-weight: 500; color: #4b5563;">
                                            Kondisi Anda: ' . $stress_level . ' (Skor: ' . $total . ')
                                        </div>
                                      </li>';
                                
                                foreach ($recommendations as $rec) {
                                    echo '<li style="
                                            margin-bottom: 10px;
                                            padding-left: 24px;
                                            position: relative;
                                            color: #4b5563;
                                        ">
                                            <svg style="
                                                position: absolute;
                                                left: 0;
                                                top: 4px;
                                                width: 16px;
                                                height: 16px;
                                                color: #6c5ce7;
                                            " viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9 16.17L4.83 12L3.41 13.41L9 19L21 7L19.59 5.59L9 16.17Z" fill="currentColor"/>
                                            </svg>
                                            ' . $rec . '
                                        </li>';
                                }
                            } else {
                                echo '<li style="color: #6c5ce7; font-style: italic;">Belum ada data untuk menampilkan rekomendasi. Silakan lakukan tes terlebih dahulu.</li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Detail Vektor -->
            <h3>Detail Vektor Harian</h3>
            <div class="vector-info">
                <div class="vector-day-card">
                    <h4><?= $vector_data['date'] ?></h4>
                    <p><strong>Total Skor:</strong> <?= $vector_data['total'] ?></p>
                    <p><strong>Magnitude:</strong> <?= number_format($vector_data['magnitude'], 2) ?></p>
                    <p><strong>Komponen 3D:</strong></p>
                    <div class="vector-components">
                        <div class="vector-component">
                            <span>X (Stres):</span>
                            <span><?= number_format($vector_data['x'], 2) ?></span>
                        </div>
                        <div class="vector-component">
                            <span>Y (Akademik):</span>
                            <span><?= number_format($vector_data['y'], 2) ?></span>
                        </div>
                        <div class="vector-component">
                            <span>Z (Keuangan):</span>
                            <span><?= number_format($vector_data['z'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <p>Belum ada data untuk hari ini. Silakan lakukan tes terlebih dahulu.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Grafik Garis 7 Hari
    document.addEventListener('DOMContentLoaded', function() {
        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Skor Stres 7 Hari Terakhir',
                        data: <?php echo json_encode($totals); ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
                        borderColor: '#3498db',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
        }

        // Grafik 3D (Hari Ini)
        <?php if (!empty($vector_data)): ?>
        const container = document.getElementById('vector3d-container');
        if (container) {
            // Scene setup
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0xf5f7fa);
            
            // Camera setup
            const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.z = 15;
            camera.position.y = 5;
            
            // Renderer
            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            container.appendChild(renderer.domElement);
            
            // Controls
            const controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            
            // Lighting
            scene.add(new THREE.AmbientLight(0x404040));
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(1, 1, 1);
            scene.add(directionalLight);
            
            // Helpers
            scene.add(new THREE.AxesHelper(8));
            scene.add(new THREE.GridHelper(20, 20));
            
            // Create vector arrow
            const vector = <?php echo json_encode($vector_data); ?>;
            const scaleFactor = 5 / vector.magnitude;
            
            const arrowHelper = new THREE.ArrowHelper(
                new THREE.Vector3(
                    vector.x * scaleFactor,
                    vector.y * scaleFactor,
                    vector.z * scaleFactor
                ).normalize(),
                new THREE.Vector3(0, 0, 0),
                vector.magnitude * scaleFactor,
                0x3498db,
                0.2,
                0.1
            );
            scene.add(arrowHelper);
            
            // Add date label
            const canvas = document.createElement('canvas');
            canvas.width = 128;
            canvas.height = 64;
            const context = canvas.getContext('2d');
            context.fillStyle = 'rgba(255, 255, 255, 0.7)';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.font = 'Bold 14px Arial';
            context.fillStyle = '#000000';
            context.textAlign = 'center';
            context.fillText(vector.date, canvas.width/2, 20);
            context.fillText(`Skor: ${vector.total}`, canvas.width/2, 40);
            
            const texture = new THREE.CanvasTexture(canvas);
            const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: texture }));
            sprite.position.set(
                vector.x * scaleFactor,
                vector.y * scaleFactor,
                vector.z * scaleFactor
            );
            sprite.scale.set(2, 1, 1);
            scene.add(sprite);
            
            // Animation
            let rotate = false;
            document.getElementById('rotateToggle').addEventListener('click', () => {
                rotate = !rotate;
                document.getElementById('rotateToggle').textContent = 
                    rotate ? 'Hentikan Rotasi' : 'Aktifkan Rotasi';
            });
            
            document.getElementById('resetView').addEventListener('click', () => {
                controls.reset();
                camera.position.z = 15;
                camera.position.y = 5;
            });
            
            function animate() {
                requestAnimationFrame(animate);
                if (rotate) {
                    arrowHelper.rotation.y += 0.005;
                    sprite.rotation.y += 0.005;
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