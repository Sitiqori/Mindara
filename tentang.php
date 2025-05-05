<?php
  session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tentang</title>
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
      <a href="profile.php" class="user-greeting">Halo, <?= htmlspecialchars($_SESSION['user_name']); ?>!</a>
      <a href="logout.php" style="margin-left: 10px;">Logout</a>
    <?php else: ?>
      <a href="sign-in.php">Login</a>
    <?php endif; ?>
  </nav>
</header>

<!-- SECTION 0: Tentang Mindara -->
<section class="tentang-intro" >
  <img src="images/mindara-logo.png" alt="Mindara Logo" class="tentang-logo">
  <div class="tentang-content">
    <h1> <span style="color: #C1121F;">Mindara</span>: Ruang untuk Mendengarkan Diri Sendiri</h1>
    <p>Mindara adalah ruang digital yang hadir untuk membantumu memahami perasaanmu sendiri.</p>
    <p>Di dunia yang serba cepat, kami percaya setiap orang berhak untuk berhenti sejenak, mendengarkan isi hatinya, dan mulai menyembuhkan diri—tanpa stigma, tanpa tekanan.</p>
    <p>Dengan fitur <span style="color: #456990;"> cek tingkat stres, journaling, dan edukasi ringan,</span> kami ingin jadi teman perjalananmu menuju ketenangan dan keseimbangan mental.</p>
    <p style="color: #456990;"><strong>Karena kamu layak untuk merasa baik—lahir, batin, dan pikiran.</strong></p>
  </div>
</section>

  
<section class="mindara-kalkulus">
  <div class="mk-container">
    <div class="mk-grid">
      <div class="mk-image-stack">
        <img src="images/grafik-stress-tentang.png" alt="Gambar 1" class="img-back">
        <img src="images/discuss-tentang.png" alt="Gambar 2" class="img-front">
      </div>
      <div class="mk-text-column">
        <h2>Apa Hubungannya dengan Kalkulus?</h2>
        <p>Mindara menggunakan konsep kalkulus untuk membantu memprediksi tingkat stres mahasiswa berdasarkan data harian seperti jumlah tugas, jam tidur, masalah keuangan, dan screen time.</p>
        <p>Setiap faktor diperlakukan sebagai vektor—yang memiliki besar dan arah. Kemudian semua faktor ini digabung dengan rumus vektor untuk menghasilkan total stres:</p>
        <p><strong>Total stres = akar dari jumlah kuadrat semua faktor</strong></p>
      </div>
    </div>
  </div>
</section>

<section class="mindara-cards" style="background-color: #fff;">
  <div class="mk-container">
    <div class="mk-card-row">
      <div class="mk-card">
        <div class="mk-icon">📈</div>
        <h4>Pola Deret Stres</h4>
        <p>Dengan konsep deret, kita bisa melihat pola peningkatan beban dari waktu ke waktu dan membuat prediksi selama 7 hari ke depan. Jadi, kalkulus di sini bukan soal rumus panjang, tapi tentang menyederhanakan hidup menjadi angka yang bisa dianalisis.</p>
      </div>
      <div class="mk-card">
        <div class="mk-icon">🧭</div>
        <h4>Kenapa Vektor?</h4>
        <p><em>Kenapa pakai vektor?</em> Karena hidup mahasiswa itu seperti beban dari banyak arah—dan vektor cocok untuk mewakili semua itu dalam satu nilai terukur.</p>
      </div>
    </div>
  </div>
</section>

<section class="tech-section">
  <img src="images/mindara-logo.png" alt="Mindara Logo" class="tech-logo">
  <h2>Teknologi yang Digunakan</h2>

  <div class="tech-slider-wrapper">
    <div class="tech-slider">
      <div class="tech-track">
        <div class="tech-card card1">
          <img src="images/html.jpg" alt="HTML logo">
          <h3>HTML</h3>
          <p>Struktur & tampilan dasar</p>
        </div>
        <div class="tech-card card2">
          <img src="images/css.jpg" alt="CSS logo">
          <h3>CSS</h3>
          <p>Struktur & tampilan dasar</p>
        </div>
        <div class="tech-card card3">
          <img src="images/js.jpg" alt="JavaScript logo">
          <h3>JavaScript</h3>
          <p>Logika input & output</p>
        </div>
        <div class="tech-card card4">
          <img src="images/Chartjs.png" alt="Chart.js logo">
          <h3>Chart.js</h3>
          <p>Visualisasi grafik stres</p>
        </div>
        <div class="tech-card card5">
          <img src="images/php.png" alt="PHP icon">
          <h3>PHP</h3>
          <p>Struktur & Pengelolaan Database</p>
        </div>
        <div class="tech-card card6">
          <img src="images/vektor.png" alt="Vektor icon">
          <h3>Vektor & Deret</h3>
          <p>Analisis pola beban tugas & Gabungan faktor stres</p>
        </div>

        <!-- ulang buat scroll terus -->
        <div class="tech-card card1">
          <img src="images/html.jpg" alt="HTML logo">
          <h3>HTML</h3>
          <p>Struktur & tampilan dasar</p>
        </div>
        <div class="tech-card card2">
          <img src="images/css.jpg" alt="CSS logo">
          <h3>CSS</h3>
          <p>Struktur & tampilan dasar</p>
        </div>
        <div class="tech-card card3">
          <img src="images/js.jpg" alt="JavaScript logo">
          <h3>JavaScript</h3>
          <p>Logika input & output</p>
        </div>
        <div class="tech-card card4">
          <img src="images/Chartjs.png" alt="Chart.js logo">
          <h3>Chart.js</h3>
          <p>Viisualisasi grafik stres</p>
        </div>
        <div class="tech-card card5">
          <img src="images/php.png" alt="PHP icon">
          <h3>PHP</h3>
          <p>Struktur & Pengelolaan Database</p>
        </div>
        <div class="tech-card card6">
          <img src="images/vektor.png" alt="Vektor icon">
          <h3>Vektor & Deret</h3>
          <p>Analisis pola beban tugas & Gabungan faktor stres</p>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- SECTION: Tim Pengembang (grid biasa) -->
<section class="tim-section">
  <h2>Tim Pengembang</h2>
  <div class="tim-grid">
    <div class="tim-card">
      <img src="images/qori.jpeg" alt="qori">
      <div class="tim-info">
        <h3>Siti Qori'ah Muhafidloh</h3>
        <p>24700611141</p>
      </div>
    </div>
    <div class="tim-card">
      <img src="images/muti.jpeg" alt="muti">
      <div class="tim-info">
        <h3>Muthia Febrahma Khoerunnisa</h3>
        <p>24700611130</p>
      </div>
    </div>
    <div class="tim-card">
      <img src="images/najmi.jpeg" alt="najmi">
      <div class="tim-info">
        <h3>Najmi Sabilla Almusfiroh</h3>
        <p>24700611125</p>
      </div>
    </div>
    <div class="tim-card">
      <img src="images/isma.jpeg" alt="isma">
      <div class="tim-info">
        <h3>Ismatul Ilmi</h3>
        <p>247006111--</p>
      </div>
    </div>
    <div class="tim-card">
      <img src="images/wardah.jpeg" alt="wardah">
      <div class="tim-info">
        <h3>Wardah Nurwafiq</h3>
        <p>24700611150</p>
      </div>
    </div>
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
</body>
</html>
