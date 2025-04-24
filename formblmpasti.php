<form method="POST" action="submit.php">
  <?php
    $opsi = [
      0 => 'Tidak Pernah',
      1 => 'Kadang-kadang',
      2 => 'Sering',
      3 => 'Sangat Sering'
    ];
    for ($i = 1; $i <= 10; $i++) {
      echo "<p>Pertanyaan $i</p>";
      foreach ($opsi as $nilai => $label) {
        echo "<label>
          <input type='radio' name='q$i' value='$nilai' required> $label
        </label><br>";
      }
    }
  ?>
  <input type="submit" value="Kirim">
</form>