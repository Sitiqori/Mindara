<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
} 
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Analisis</title>
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

<div class="mindara-wrapper">
<form action="hasil.php" method="POST" class="mindara-container">
  
  <!-- Tes Tingkat Stres -->
  <h1 class="mindara-heading">Tes Tingkat Stres</h1>

  <!-- Soal 1 -->
  <div class="mindara-question">
    <label class="mindara-label">1. Saya merasa tegang atau tertekan:</label>
    <div class="mindara-options">
      <label class="mindara-option-label"><input type="radio" name="jawaban[0]" value="0" required> Tidak Pernah</label>
      <label class="mindara-option-label"><input type="radio" name="jawaban[0]" value="1"> Kadang-kadang</label>
      <label class="mindara-option-label"><input type="radio" name="jawaban[0]" value="2"> Sering</label>
      <label class="mindara-option-label"><input type="radio" name="jawaban[0]" value="3"> Sangat Sering</label>
    </div>
  </div>

  <!-- Soal 2-10 -->
  <?php
    $pertanyaan = [
      "2. Saya merasa kesulitan untuk rileks.",
      "3. Saya cemas tanpa alasan yang jelas.",
      "4. Saya merasa mudah tersinggung.",
      "5. Saya merasa kelelahan secara emosional.",
      "6. Saya sulit tidur karena pikiran terus berjalan.",
      "7. Saya merasa kewalahan oleh tanggung jawab.",
      "8. Saya kehilangan minat dalam hal-hal yang biasanya saya nikmati.",
      "9. Saya merasa tidak berdaya atau putus asa.",
      "10. Saya mengalami gejala fisik seperti sakit kepala atau jantung berdebar karena stres."
    ];
    for ($i = 0; $i < count($pertanyaan); $i++): ?>
      <div class="mindara-question">
        <label class="mindara-label"><?= $pertanyaan[$i] ?></label>
        <div class="mindara-options">
          <label class="mindara-option-label"><input type="radio" name="jawaban[<?= $i+1 ?>]" value="0" required> Tidak Pernah</label>
          <label class="mindara-option-label"><input type="radio" name="jawaban[<?= $i+1 ?>]" value="1"> Kadang-kadang</label>
          <label class="mindara-option-label"><input type="radio" name="jawaban[<?= $i+1 ?>]" value="2"> Sering</label>
          <label class="mindara-option-label"><input type="radio" name="jawaban[<?= $i+1 ?>]" value="3"> Sangat Sering</label>
        </div>
      </div>
  <?php endfor; ?>

  <hr class="mindara-hr">

  <!-- Tes Tekanan Akademik -->
  <h1 class="mindara-heading">Tes Tekanan Akademik</h1>

  <?php
    $pertanyaan_akademik = [
      "1. Saya merasa kewalahan dengan tugas-tugas kuliah.",
      "2. Saya kesulitan mengatur waktu antara kuliah, tugas, dan aktivitas lainnya.",
      "3. Saya sering menunda-nunda (prokrastinasi) tugas akademik.",
      "4. Saya merasa cemas saat menghadapi ujian atau presentasi.",
      "5. Saya mengalami kesulitan memahami materi kuliah.",
      "6. Saya merasa kurang termotivasi untuk belajar.",
      "7. Saya merasa tekanan dari orang tua atau lingkungan untuk berprestasi.",
      "8. Saya sulit berkonsentrasi saat belajar.",
      "9. Saya merasa tidak percaya diri dengan kemampuan akademik saya.",
      "10. Saya merasa kelelahan karena beban akademik yang berlebihan."
    ];
    for ($i = 0; $i < count($pertanyaan_akademik); $i++): ?>
      <div class="mindara-question">
        <label class="mindara-label"><?= $pertanyaan_akademik[$i] ?></label>
        <div class="mindara-options">
          <label class="mindara-option-label"><input type="radio" name="jawaban_akademik[<?= $i ?>]" value="0" required> Tidak Pernah</label>
          <label class="mindara-option-label"><input type="radio" name="jawaban_akademik[<?= $i ?>]" value="1"> Kadang-kadang</label>
          <label class="mindara-option-label"><input type="radio" name="jawaban_akademik[<?= $i ?>]" value="2"> Sering</label>
          <label class="mindara-option-label"><input type="radio" name="jawaban_akademik[<?= $i ?>]" value="3"> Sangat Sering</label>
        </div>
      </div>
  <?php endfor; ?>

  <hr class="mindara-hr">

  <!-- Tes Kesehatan Keuangan Mahasiswa -->
  <h1 class="mindara-heading">Tes Kesehatan Keuangan Mahasiswa</h1>

  <?php
    $pertanyaan_keuangan = [
      "1. Saya merasa cukup dengan uang bulanan yang saya miliki.",
      "2. Saya kesulitan mengatur uang bulanan agar cukup sampai akhir bulan.",
      "3. Saya sering merasa stres karena masalah keuangan.",
      "4. Saya bisa menabung sebagian dari uang bulanan saya.",
      "5. Saya memiliki rencana keuangan jangka panjang.",
      "6. Saya menggunakan aplikasi atau alat untuk mencatat pengeluaran saya.",
      "7. Saya sering meminjam uang untuk memenuhi kebutuhan sehari-hari.",
      "8. Saya merasa puas dengan kondisi keuangan saya saat ini.",
      "9. Saya paham tentang konsep investasi dasar.",
      "10. Saya pernah mengikuti seminar atau pelatihan tentang literasi keuangan."
    ];
    for ($i = 0; $i < count($pertanyaan_keuangan); $i++): ?>
      <div class="mindara-question">
        <label class="mindara-label"><?= $pertanyaan_keuangan[$i] ?></label>
        <div class="mindara-options">
          <label class="mindara-option-label"><input type="radio" name="jawaban_keuangan[<?= $i ?>]" value="0" required> Sangat Setuju</label>
          <label class="mindara-option-label"><input type="radio" name="jawaban_keuangan[<?= $i ?>]" value="1"> Setuju</label>
          <label class="mindara-option-label"><input type="radio" name="jawaban_keuangan[<?= $i ?>]" value="2"> Tidak Setuju</label>
          <label class="mindara-option-label"><input type="radio" name="jawaban_keuangan[<?= $i ?>]" value="3"> Sangat Tidak Setuju</label>
        </div>
      </div>
  <?php endfor; ?>

  <hr class="mindara-hr">
  <button class="mindara-submit-btn" type="submit">Kirim</button>
</form>

</div>

<script src="js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
