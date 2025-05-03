<?php
session_start();
if (isset($_SESSION['error'])) {
    echo '<div class="error">'.$_SESSION['error'].'</div>';
    unset($_SESSION['error']);
    // Pilih untuk menampilkan error terlebih dahulu, atau hapus baris header ini untuk tidak melakukan redirect
    // header("Location: login.php");
    // exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tes Tingkat Stres</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .question { margin-bottom: 20px; }
        label { display: block; margin: 5px 0; }
        button { padding: 10px 15px; background: #4a6fa5; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Tes Tingkat Stres</h1>
    <form action="hasil.php" method="POST">
        <?php
        $questions = [
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
        
        foreach ($questions as $i => $question) {
            echo '<div class="question">';
            echo '<p>'.($i+1).'. '.$question.'</p>';
            for ($j=0; $j<=3; $j++) {
                $labels = ["Tidak Pernah", "Kadang-kadang", "Sering", "Sangat Sering"];
                echo '<label><input type="radio" name="jawaban['.$i.']" value="'.$j.'" required> '.$labels[$j].'</label>';
            }
            echo '</div>';
        }
        ?>
        
        <div class="question">
            <label for="penyebab">Faktor Penyebab Stres Terbesar:</label>
            <select name="penyebab" id="penyebab" required>
                <option value="Akademik">Masalah Akademik</option>
                <option value="Keuangan">Masalah Keuangan</option>
                <option value="Sosial">Masalah Sosial</option>
                <option value="Lingkungan">Lingkungan</option>
            </select>
        </div>
        
        <button type="submit">Kirim Jawaban</button>
    </form>
</body>
</html>
