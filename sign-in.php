<?php
session_start();
?> 
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/style.css">
  </head>
  <body class="sign-page">

    <div class="auth-slide-container" id="auth-slide">
      <div class="auth-form-section auth-form-left">
        <h2 class="auth-title-green">Sign In</h2>
        <div class="auth-social-icons">
          <button class="auth-icon-btn">f</button>
          <button class="auth-icon-btn">G+</button>
          <button class="auth-icon-btn">in</button>
        </div>
        <p class="auth-small-text">Good to see you again — Sign in to continue.</p>
        <form action="login.php" method="POST">
          <input type="email" name="email" placeholder="Email" class="auth-input" required>
          <input type="password" name="password" placeholder="Password" class="auth-input" required>
          <button type="submit" class="auth-btn-solid">Masuk</button>
        </form>        
      </div>

      <div class="auth-info-section auth-info-right">
        <div class="auth-logo">MINDARA</div>
        <h2 class="auth-title">Hello, Friend!</h2>
        <p class="auth-desc">Let’s build something great together — sign up now!</p>
        <button class="auth-btn-outline" onclick="window.location.href='sign-up.php'">SIGN UP</button>
      </div>
    </div>

    <script>
      const params = new URLSearchParams(window.location.search);
      if (params.has("error")) {
        const err = document.createElement("p");
        err.style.color = "red";
        err.style.marginTop = "10px";
        err.textContent = "Email atau password salah.";
        document.querySelector("form").prepend(err);
      }
    </script>
  </body>
</html>
