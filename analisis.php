<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save answers to session
    if ($step === 1) {
        $_SESSION['jawaban_stres'] = $_POST['jawaban'];
    } elseif ($step === 2) {
        $_SESSION['jawaban_akademik'] = $_POST['jawaban_akademik'];
    } elseif ($step === 3) {
        $_SESSION['jawaban_keuangan'] = $_POST['jawaban_keuangan'];
        
        // Combine all answers
        $jawaban = array_merge(
            $_SESSION['jawaban_stres'],
            $_SESSION['jawaban_akademik'],
            $_SESSION['jawaban_keuangan']
        );

        // Calculate total scores per category
        $stress_total = array_sum(array_slice($jawaban, 0, 10));
        $akademik_total = array_sum(array_slice($jawaban, 10, 10));
        $keuangan_total = array_sum(array_slice($jawaban, 20, 10));
        $total = $stress_total + $akademik_total + $keuangan_total;

        // Calculate vector magnitude
        $max_input = 30; // Maximum score for each question (3 * 10 questions)
        $max_magnitude = sqrt(3 * pow($max_input, 2)); // ~173.205
        $magnitude = sqrt(pow($stress_total, 2) + pow($akademik_total, 2) + pow($keuangan_total, 2));
        $normalized_score = ($magnitude / $max_magnitude) * 100;

        // Round the normalized score to the nearest integer
        $normalized_score = round($normalized_score);

        // Determine stress level
        if ($normalized_score <= 33) {
            $level = 'Rendah';
            $level_class = 'level-low';
            $recommendation = "Anda memiliki tingkat stress rendah. Jaga pola hidup sehat dan rutin relaksasi.";
        } elseif ($normalized_score <= 66) {
            $level = 'Sedang';
            $level_class = 'level-medium';
            $recommendation = "Anda memiliki tingkat stress sedang. Perhatikan manajemen waktu dan coba teknik relaksasi.";
        } else {
            $level = 'Tinggi';
            $level_class = 'level-high';
            $recommendation = "Anda memiliki tingkat stress tinggi. Disarankan untuk konsultasi dengan profesional atau praktikkan teknik relaksasi yang intensif.";
        }

        // Prepare SQL with all columns
        $query = "INSERT INTO hasil_tes (user_id, 
                  q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, 
                  q11, q12, q13, q14, q15, q16, q17, q18, q19, q20,
                  q21, q22, q23, q24, q25, q26, q27, q28, q29, q30, 
                  total, stress_total, akademik_total, keuangan_total, normalized_score)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                          ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Prepare the statement
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        // Bind parameters
        $params = array_merge([$user_id], $jawaban, [$total, $stress_total, $akademik_total, $keuangan_total, $normalized_score]);
        
        $types = str_repeat("i", count($params));
        $stmt->bind_param($types, ...$params);

        // Execute the statement
        if ($stmt->execute()) {
            header("Location: grafik.php");
            exit;
        } else {
            die("Error saving data: " . $stmt->error);
        }
    }

    if ($step < 3) {
        header("Location: analisis.php?step=" . ($step + 1));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<title>Analisis Stress Berdasarkan Vektor 3D</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    margin: 0;
    padding: 0;
    display: flex;
    min-height: 100vh;
    justify-content: center;
    align-items: center;
  }
  .container {
    background-color: rgba(0,0,0,0.6);
    padding: 2rem;
    border-radius: 10px;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
  }
  h1 {
    text-align: center;
    margin-bottom: 1.5rem;
    font-weight: 700;
    font-size: 1.8rem;
    letter-spacing: 1px;
  }
  form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  label {
    font-weight: 600;
    font-size: 1rem;
  }
  input[type=number] {
    padding: 0.6rem 1rem;
    font-size: 1rem;
    border-radius: 6px;
    border: none;
    outline: none;
    -webkit-appearance: none;
    -moz-appearance: textfield;
    width: 100%;
  }
  input::-webkit-outer-spin-button,
  input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }
  button {
    margin-top: 1rem;
    padding: 0.7rem 1rem;
    background: #8ec5fc;  /* blue gradient */
    background: linear-gradient(to right, #67e8f9, #2563eb);
    border: none;
    color: #fff;
    font-weight: 700;
    font-size: 1.1rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
  }
  button:hover {
    background: linear-gradient(to right, #2563eb, #67e8f9);
  }
  .result {
    margin-top: 2rem;
    padding: 1rem;
    border-radius: 8px;
    background-color: rgba(255,255,255,0.1);
    text-align: center;
  }
  .level-low {
    color: #4ade80; /* green */
    font-weight: 700;
  }
  .level-medium {
    color: #facc15; /* yellow */
    font-weight: 700;
  }
  .level-high {
    color: #f87171; /* red */
    font-weight: 700;
  }
  @media (max-width: 400px) {
    body {
      padding: 1rem;
    }
    .container {
      padding: 1.5rem;
    }
  }
</style>
</head>
<body>
<div class="container">
  <h1>Analisis Stress 3D</h1>
  <?php
  // Initialize variables
  $x = $y = $z = null;
  $error = '';
  $result = '';
  $score = null;

  // Define max input value and max vector magnitude
  $max_input = 100;
  $max_magnitude = sqrt(3 * pow($max_input, 2)); // ~173.205

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
      // Validate inputs
      $x_raw = $_POST['x'] ?? '';
      $y_raw = $_POST['y'] ?? '';
      $z_raw = $_POST['z'] ?? '';

      // Check if numeric and integers with no decimals
      if (
          is_numeric($x_raw) && is_numeric($y_raw) && is_numeric($z_raw) &&
          ctype_digit(strval($x_raw)) && ctype_digit(strval($y_raw)) && ctype_digit(strval($z_raw))
      ) {
          // Cast to int
          $x = (int)$x_raw;
          $y = (int)$y_raw;
          $z = (int)$z_raw;

          // Check range 0 to max_input
          if ($x < 0 || $x > $max_input || $y < 0 || $y > $max_input || $z < 0 || $z > $max_input) {
              $error = "Nilai X, Y, dan Z harus antara 0 sampai $max_input.";
          } else {
              // Calculate vector magnitude
              $magnitude = sqrt($x*$x + $y*$y + $z*$z);

              // Normalize to 100 scale
              $score = ($magnitude / $max_magnitude) * 100;
              $score = round($score); // Round to nearest integer

              // Determine stress level
              if ($score <= 33) {
                  $level = 'Rendah';
                  $level_class = 'level-low';
                  $recommendation = "Anda memiliki tingkat stress rendah. Jaga pola hidup sehat dan rutin relaksasi.";
              } elseif ($score <= 66) {
                  $level = 'Sedang';
                  $level_class = 'level-medium';
                  $recommendation = "Anda memiliki tingkat stress sedang. Perhatikan manajemen waktu dan coba teknik relaksasi.";
              } else {
                  $level = 'Tinggi';
                  $level_class = 'level-high';
                  $recommendation = "Anda memiliki tingkat stress tinggi. Disarankan untuk konsultasi dengan profesional atau praktikkan teknik relaksasi yang intensif.";
              }

              // Prepare result html
              $result = "<div>
                <p>Nilai vektor hasil analisis: <strong>$score</strong> (Skala 0-100)</p>
                <p>Tingkat Stress: <span class=\"$level_class\">$level</span></p>
                <p><em>Rekomendasi:</em> $recommendation</p>
              </div>";
          }
      } else {
          $error = "Mohon masukkan nilai bilangan bulat (tanpa desimal) untuk X, Y, dan Z.";
      }
  }
  ?>

  <form method="POST" action="">
    <label for="x">Nilai X (0 - <?php echo $max_input; ?>):</label>
    <input type="number" id="x" name="x" min="0" max="<?php echo $max_input; ?>" step="1" value="<?php echo htmlspecialchars($x ?? ''); ?>" required />
    
    <label for="y">Nilai Y (0 - <?php echo $max_input; ?>):</label>
    <input type="number" id="y" name="y" min="0" max="<?php echo $max_input; ?>" step="1" value="<?php echo htmlspecialchars($y ?? ''); ?>" required />
    
    <label for="z">Nilai Z (0 - <?php echo $max_input; ?>):</label>
    <input type="number" id="z" name="z" min="0" max="<?php echo $max_input; ?>" step="1" value="<?php echo htmlspecialchars($z ?? ''); ?>" required />
    
    <button type="submit">Analisis</button>
  </form>
  
  <?php if ($error): ?>
    <div class="result" style="color:#f87171; font-weight:700; margin-top:1rem;">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php elseif ($result): ?>
    <div class="result">
      <?php echo $result; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
