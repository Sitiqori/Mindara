<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}


$user_id = $_SESSION['user_id'];

// Get data from the last 7 days
$sql = "SELECT * FROM hasil_tes 
        WHERE user_id = '$user_id' 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY created_at ASC";
$result = mysqli_query($conn, $sql);

$dates = [];
$totals = [];
$daily_vectors = [];

while ($row = mysqli_fetch_assoc($result)) {
  $dates[] = date('d M', strtotime($row['created_at']));
  $totals[] = $row['total'];
  
  // Create vector from answers (q1-q10 as vector components)
  $vector = [];
  for ($i = 1; $i <= 10; $i++) {
    $vector[] = $row['q'.$i];
  }
  $daily_vectors[] = $vector;
}

// Calculate magnitude (vector length) for each day
$magnitudes = array_map(function($vector) {
  return sqrt(array_sum(array_map(function($val) { return $val * $val; }, $vector)));
}, $daily_vectors);

// Prepare data for 3D visualization
$vector_data = [];
foreach ($dates as $index => $date) {
  $vector_data[] = [
    'date' => $date,
    'total' => $totals[$index],
    'magnitude' => $magnitudes[$index],
    'vector' => $daily_vectors[$index],
    'x' => $daily_vectors[$index][0] + $daily_vectors[$index][1], // Combination of q1+q2 for X axis
    'y' => $daily_vectors[$index][2] + $daily_vectors[$index][3], // Combination of q3+q4 for Y axis
    'z' => $daily_vectors[$index][4] + $daily_vectors[$index][5]  // Combination of q5+q6 for Z axis
  ];
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>3D Hasil Tes Stres 7 Hari Terakhir</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/controls/OrbitControls.js"></script>
  <link rel="stylesheet" href="styles/style.css">
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
      <h2 class="mindara-heading">Visualisasi Perkembangan Stress dari hari ke hari</h2>
      <p>Visualisasi ini menunjukkan pola stres Anda dalam bentuk grafik.</p>
      
      <!-- Line Chart for Trend -->
      <div class="chart-container">
        <canvas id="trendChart"></canvas>
      </div>
      
      <!-- 3D Vector Visualization -->
      <h3>Visualisasi  Stres</h3>
      <p>Setiap vektor mewakili profil stres harian dengan komponen X, Y, Z yang berasal dari kombinasi jawaban Anda.</p>
      
      <div class="controls">
        <button id="rotateToggle">Aktifkan Rotasi</button>
        <button id="resetView">Reset Pandangan</button>
      </div>
      
      <div id="vector3d-container"></div>

      <div class="legend">
        <div class="legend-item">
          <div class="legend-color" style="background-color: #3498db;"></div>
          <span>Sumbu X (Q1+Q2): Ketegangan & Kesulitan Relaksasi</span>
        </div>
        <div class="legend-item">
          <div class="legend-color" style="background-color: #e74c3c;"></div>
          <span>Sumbu Y (Q3+Q4): Kecemasan & Iritabilitas</span>
        </div>
        <div class="legend-item">
          <div class="legend-color" style="background-color: #2ecc71;"></div>
          <span>Sumbu Z (Q5+Q6): Kelelahan Emosional & Gangguan Tidur</span>
        </div>
      </div>

      <!-- Rekomendasi Harian -->
      <div style="margin-top: 10px;">
          <p><strong>Solusi dan Rekomendasi:</strong></p>
          <ul style="font-size: 0.9em;">
            <?php
              if (!empty($vector_data)) {
                $latest = end($vector_data);
                $total = $latest['total'];
              }

              if ($total <= 10) {
                echo "
                  <strong>Kondisi fisik, emosional, dan mental Anda stabil.(Stres Rendah)</strong>
                  <li>Teruskan kebiasaan sehat seperti makan teratur, tidur cukup, dan olahraga ringan.</li>
                  <li>Jaga keseimbangan antara pekerjaan dan waktu istirahat.</li>
                  <li>Bangun relasi sosial yang positif.</li>
                  <li>Waspadai stres ringan agar tidak menumpuk.</li>
                ";
              } elseif ($total <= 20) {
                echo "
                  <strong>Tubuh Anda mungkin mengalami kelelahan atau tekanan (Stres Fisik Ringan)</strong>
                  <li>Pastikan Anda cukup tidur (minimal 7-8 jam).</li>
                  <li>Lakukan relaksasi fisik seperti stretching, pijat, atau yoga.</li>
                  <li>Kurangi konsumsi kafein dan gula berlebihan.</li>
                  <li>Luangkan waktu untuk istirahat fisik yang berkualitas.</li>
                ";
              } elseif ($total <= 30) {
                echo "
                  <strong>Anda mungkin sedang merasa cemas, mudah marah, atau tidak stabil secara emosional (Stres Emosional Sedang)</strong>
                  <li>Ceritakan perasaan Anda kepada orang yang dipercaya.</li>
                  <li>Lakukan aktivitas yang menyenangkan dan menenangkan hati.</li>
                  <li>Latih pernapasan dalam atau meditasi secara rutin.</li>
                  <li>Jangan menekan emosi—pelajari cara mengelolanya dengan sehat.</li>
                ";
              } else {
                echo "
                  <strong>Kemungkinan Anda mengalami kelelahan pikiran, kesulitan fokus, atau kejenuhan (Stres Mental Tinggi)</strong>
                  <li></li>
                  <li>Istirahatlah dari aktivitas berat secara mental.</li>
                  <li>Kurangi multitasking dan fokus pada satu hal dalam satu waktu.</li>
                  <li>Lakukan aktivitas ringan seperti berjalan-jalan atau menonton film ringan.</li>
                  <li>Tulis hal-hal kecil yang membuat Anda bersyukur setiap hari.</li>
                ";
              }
            ?>
          </ul>
        </div>
      
      <!-- Detailed Vector Information -->
      <h3>Detail Vektor Harian</h3>
      <div class="vector-info">
        <?php foreach ($vector_data as $index => $day): ?>
          <div class="vector-day-card">
            <h4><?= $day['date'] ?></h4>
            <p><strong>Total Skor:</strong> <?= $day['total'] ?></p>
            <p><strong>Magnitude:</strong> <?= number_format($day['magnitude'], 2) ?></p>
            <p><strong>Komponen 3D:</strong></p>
            <div class="vector-components">
              <div class="vector-component">
                <span>X:</span>
                <span><?= number_format($day['x'], 2) ?></span>
              </div>
              <div class="vector-component">
                <span>Y:</span>
                <span><?= number_format($day['y'], 2) ?></span>
              </div>
              <div class="vector-component">
                <span>Z:</span>
                <span><?= number_format($day['z'], 2) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      
      <!-- Vector Concept Explanation -->
      <div style="margin-top: 30px;">
        <h3>Interpretasi Visualisasi 3D</h3>
        <p>Dalam model ini, kami memetakan jawaban Anda ke dalam ruang 3D:</p>
        <ul>
          <li><strong>Sumbu X</strong>: Kombinasi Ketegangan (Q1) dan Kesulitan Relaksasi (Q2)</li>
          <li><strong>Sumbu Y</strong>: Kombinasi Kecemasan (Q3) dan Iritabilitas (Q4)</li>
          <li><strong>Sumbu Z</strong>: Kombinasi Kelelahan Emosional (Q5) dan Gangguan Tidur (Q6)</li>
        </ul>
        <p>Panjang vektor menunjukkan intensitas stres, sedangkan arahnya menunjukkan dominansi jenis stres tertentu. Vektor yang mengarah ke:</p>
        <ul>
          <li><strong>X positif</strong>: Lebih banyak ketegangan dan sulit relaksasi</li>
          <li><strong>Y positif</strong>: Lebih banyak kecemasan dan iritabilitas</li>
          <li><strong>Z positif</strong>: Lebih banyak kelelahan emosional dan gangguan tidur</li>
        </ul>
      </div>
    </div>
  </div>

  <script>
    // Line Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const trendChart = new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($dates); ?>,
        datasets: [{
          label: 'Total Skor Stres Harian',
          data: <?= json_encode($totals); ?>,
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

    // 3D Vector Visualization with Three.js
    const container = document.getElementById('vector3d-container');
    
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
    const ambientLight = new THREE.AmbientLight(0x404040);
    scene.add(ambientLight);
    
    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(1, 1, 1);
    scene.add(directionalLight);
    
    // Axes helper
    const axesHelper = new THREE.AxesHelper(8);
    scene.add(axesHelper);
    
    // Grid helper
    const gridHelper = new THREE.GridHelper(20, 20);
    scene.add(gridHelper);
    
    // Vector data from PHP
    const vectorData = <?= json_encode($vector_data); ?>;
    
    // Normalize data for visualization
    const maxMagnitude = Math.max(...vectorData.map(v => v.magnitude));
    const scaleFactor = 5 / maxMagnitude;
    
    // Colors for each day
    const colors = [
      0x3498db, 0xe74c3c, 0x2ecc71, 0xf39c12, 0x9b59b6, 
      0x1abc9c, 0xd35400, 0x34495e, 0x27ae60, 0xc0392b
    ];
    
    // Create vectors
    const vectors = [];
    vectorData.forEach((day, index) => {
      const color = colors[index % colors.length];
      
      // Create arrow for the vector
      const origin = new THREE.Vector3(0, 0, 0);
      const direction = new THREE.Vector3(
        day.x * scaleFactor,
        day.y * scaleFactor,
        day.z * scaleFactor
      );
      
      const arrowHelper = new THREE.ArrowHelper(
        direction.clone().normalize(),
        origin,
        direction.length(),
        color,
        0.2,
        0.1
      );
      
      // Add date label
      const loader = new THREE.TextureLoader();
      const canvas = document.createElement('canvas');
      canvas.width = 128;
      canvas.height = 64;
      const context = canvas.getContext('2d');
      context.fillStyle = 'rgba(255, 255, 255, 0.7)';
      context.fillRect(0, 0, canvas.width, canvas.height);
      context.font = 'Bold 14px Arial';
      context.fillStyle = '#000000';
      context.textAlign = 'center';
      context.fillText(day.date, canvas.width/2, 20);
      context.fillText(`Skor: ${day.total}`, canvas.width/2, 40);
      
      const texture = new THREE.CanvasTexture(canvas);
      const spriteMaterial = new THREE.SpriteMaterial({ map: texture });
      const sprite = new THREE.Sprite(spriteMaterial);
      sprite.position.copy(direction);
      sprite.scale.set(2, 1, 1);
      
      // Group arrow and label
      const group = new THREE.Group();
      group.add(arrowHelper);
      group.add(sprite);
      
      scene.add(group);
      vectors.push(group);
    });
    
    // Animation loop
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
    
    // ... kode Three.js sebelumnya ...

function animate() {
  requestAnimationFrame(animate);
  
  if (rotate) {
    vectors.forEach(group => {
      group.rotation.y += 0.005;
    });
  }
  
  controls.update();
  renderer.render(scene, camera);
}

animate(); // <-- INI LINE animate() YANG DIMAKSUD

// Handle window resize
window.addEventListener('resize', () => {
  camera.aspect = container.clientWidth / container.clientHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(container.clientWidth, container.clientHeight);
});
    
    animate();
  </script>
</body>
</html>