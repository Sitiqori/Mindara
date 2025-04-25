<?php
  session_start();
?>
 
<!-- https://chatgpt.com/c/67f72843-0058-8004-a142-9a44119f50a9 -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Beranda</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/style.css">
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
      <span style="margin-left: 20px;">Halo, <?= htmlspecialchars($_SESSION['user_name']); ?>!</span>
      <a href="logout.php" style="margin-left: 10px;">Logout</a>
    <?php else: ?>
      <a href="sign-in.php">Login</a>
    <?php endif; ?>
  </nav>
</header>


  <section class="hero">
    <div class="hero-content">
      <div class="hero-left">
        <img src="images/mindara-logo.png" alt="Mindara Logo" class="logo">
        <p>“A calm space where anyone can understand their emotions, check their stress levels, and start healing—one breath at a time”</p>
        <a href="analisis.php" class="mulai-button">Mulai Sekarang</a>
      </div>
      <div class="hero-right">
        <div class="circle-bg"></div>
        <img src="images/puzzzle-pikiran.png" alt="Hero Image" class="hero-img" />
      </div>
      
    </div>
  </section>


  <section class="about-section">
    <div class="about-left">
      <img src="images/about.png" alt="Tentang Mindara" class="about-img" />
    </div>
    <div class="about-right">
      <p><strong>Mindara</strong> adalah singkatan dari <em>Mind Radar</em>, sebuah ruang digital yang hadir untuk membantumu memahami perasaanmu sendiri.</p>
      <p>Di dunia yang serba cepat, kami percaya setiap orang berhak untuk berhenti sejenak, mendengarkan isi hatinya, dan mulai menyembuhkan diri—tanpa stigma, tanpa tekanan.</p>
      <p>Dibangun dengan pendekatan kalkulus dan data sederhana, Mindara menganalisis tingkat stres mahasiswa berdasarkan faktor-faktor seperti jumlah tugas, durasi tidur, dan tekanan finansial.</p>
      <p>Melalui fitur cek tingkat stres, journaling ringan, dan visualisasi prediksi, Mindara akan:</p>
      <p><strong>Karena kamu layak untuk merasa baik</strong>—lahir, batin, dan pikiran.</p>
    </div>
  </section>

  <section class="canvas-grafik">
    <div class="canvas-left">
      <p>Lorem ipsum dolor sit amet consectetur, adipisicing elit. Nesciunt corporis veritatis magnam itaque tenetur ut doloremque repellendus illo hic tempora. </p> <br>
      <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Laborum, ullam?</p> <br>
      <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Veritatis optio maiores repudiandae accusamus odit voluptate.</p>
    </div>
    <div class="canvas-right" style="max-width: 500px; ">
    <canvas id="stressChart"></canvas>
  </div>
  </section>
  
  
  <section class="fitur-mindara">
    <div class="container">
      <h2 class="judul-section">Dengan menggabungkan data harian seperti jumlah tugas, durasi tidur, dan tekanan finansial, Mindara akan:</h2>
      <div class="grid-3">
        <div class="card">
          <h3>Menganalisis Nilai Stres</h3>
          <p>Berdasarkan pola yang dihitung dari logika kalkulus.</p>
        </div>
        <div class="card">
          <h3>Prediksi 7 Hari</h3>
          <p>Menampilkan grafik tingkat overthinking selama 7 hari ke depan.</p>
        </div>
        <div class="card">
          <h3>Insight Sederhana</h3>
          <p>Agar pengguna lebih sadar akan kondisi mentalnya.</p>
        </div>
      </div>
    </div>
    <div class="fitur-grid-2">
      <div>
        <h3 class="judul-sub">✨ Apa yang Bisa Dilakukan?</h3>
        <ul class="fitur-list">
          <li>Cek Tingkat Overthinking hanya dengan mengisi beberapa pertanyaan ringan.</li>
          <li>Lihat grafik prediksi stres mingguan yang disusun dengan konsep vektor arah perubahan.</li>
          <li>Evaluasi diri dan atur ulang ritme hidup agar lebih seimbang.</li>
        </ul>
      </div>
    
      <div>
        <h3 class="judul-sub">🛠 Cara Menggunakan</h3>
        <ol class="fitur-list">
          <li>Klik tombol <strong>Mulai Analisis</strong> di halaman awal.</li>
          <li>Isi form dengan jujur sesuai kondisi kamu.</li>
          <li>Klik <strong>Lihat Hasil</strong> untuk menampilkan grafik prediksi overthinking kamu selama seminggu ke depan.</li>
          <li>Gunakan hasil ini sebagai refleksi dan motivasi.</li>
        </ol>
      </div>
    </div>
    
  </section>
  
  <section class="cta-section">
    <div class="cta-container">
      <h2>Ayo Mulai Kenali Dirimu</h2>
      <p>Cek tingkat stresmu sekarang dan dapatkan insight untuk langkah selanjutnya!</p>
      <a href="pertanyaan.php" class="cta-button">Mulai Sekarang</a>
    </div>
  </section>








  
  <footer>
    <div class="brand-footer">
      <div>
        <img src="images/mindara.png" 
          width="70px" height="28px" alt>
      </div>
    </div>
    <main>
      <section class='links-secton' style="color: gray;">
        <a href="">Support</a>
        <a href=""> Design Kit</a>
        <a href=""></a>
        <a href=""> </a>
      </section>
      <section dir="rtl">
        <p class="pt-2" style="color: gray;">© 2025 MINDARA - All rights reserved</p>
      </section>
    </main>

  </footer>
  <script src="js/script.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const ctx = document.getElementById('stressChart').getContext('2d');

    const stressChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Hari 1', 'Hari 2', 'Hari 3', 'Hari 4', 'Hari 5', 'Hari 6', 'Hari 7'],
        datasets: [{
          label: 'Prediksi Tingkat Stres',
          data: [40, 38, 35, 42, 50, 47, 45],
          fill: false,
          borderColor: '#00ffff',
          backgroundColor: '#00ffff',
          pointBorderColor: '#fff',
          pointBackgroundColor: '#00ffff',
          pointBorderWidth: 2,
          borderWidth: 3,

        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            min: 0,
            max: 100,
            ticks: {
              color: '#fff',
              stepSize: 20,
              callback: function(value) {
                return value + '%';
              }
            },
            grid: {
              color: 'rgba(255, 255, 255, 0.2)',
            },
            backgroundColor: (ctx) => {
              const { chart } = ctx;
              const { ctx: canvasCtx, chartArea } = chart;
      
              if (!chartArea) return;
      
              const gradient = canvasCtx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
              // Solid color zones dari bawah (0%) ke atas (100%)
              gradient.addColorStop(0, '#008000');  // Hijau solid
              gradient.addColorStop(0.5, '#FFA500'); // Oranye solid
              gradient.addColorStop(1, '#FF0000');  // Merah solid
              return gradient;
            },
          },
          x: {
            ticks: {
              color: '#fff'
            },
            grid: {
              color: 'rgba(255, 255, 255, 0.1)',
            }
          }
        },
        plugins: {
          legend: {
            labels: {
              color: '#fff'
            }
          }
        }
      }
      
    });
  </script>
</body>
</html>
