<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/style.css">
  </head>
  <body class="sign-up">
    <div class="auth-slide-container" id="auth-slide">
     
      <div class="auth-form-section auth-form-left">
        <h2 class="auth-title-green">Sign Up</h2>
        <div class="auth-social-icons">
          <button class="auth-icon-btn">f</button>
          <button class="auth-icon-btn">G+</button>
          <button class="auth-icon-btn">in</button>
        </div>
        <p class="auth-small-text">New here? Fill in your details to get started.</p>
        <form action="register.php" method="POST">
          <input type="text" name="nama" placeholder="Nama" class="auth-input" required>
          <input type="email" name="email" placeholder="Email" class="auth-input" required>
          <input type="password" name="password" placeholder="Password" class="auth-input" required>
          <button type="submit" class="auth-btn-solid">Daftar</button>
        </form>
        
      </div>

      
      <div class="auth-info-section auth-info-right">
        <div class="auth-logo">MINDARA</div>
        <h2 class="auth-title">Welcome Back!</h2>
        <p class="auth-desc">Your next chapter starts here. Sign in!</p>
        <button class="auth-btn-outline" onclick="window.location.href='sign-in.php'">SIGN IN</button>
      </div>
    </div>

  </body>
</html>
