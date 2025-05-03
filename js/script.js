const navbar = document.getElementById("navbar");
window.addEventListener("scroll", () => {
  if (window.scrollY > 20) {
    navbar.classList.add("shadow");
  } else {
    navbar.classList.remove("shadow");
  }
});

document.addEventListener("DOMContentLoaded", function() {
  const authSlideContainer = document.getElementById("authSlideContainer");

  // Tombol untuk pindah ke Sign In
  const goToSignIn = document.getElementById("goToSignIn");
  goToSignIn.addEventListener("click", function(event) {
    event.preventDefault();
    authSlideContainer.classList.remove("sign-up-active");
    authSlideContainer.classList.add("sign-in-active");
  });

  // Tombol untuk pindah ke Sign Up
  const goToSignUp = document.getElementById("goToSignUp");
  goToSignUp.addEventListener("click", function(event) {
    event.preventDefault();
    authSlideContainer.classList.remove("sign-in-active");
    authSlideContainer.classList.add("sign-up-active");
  });
});




  const toggleBtn = document.getElementById("toggle-button");
  const authSlide = document.getElementById("auth-slide");
  const signUpForm = document.getElementById("sign-up-form");
  const signInForm = document.getElementById("sign-in-form");
  const panelText = document.getElementById("panel-text");
  const slidePanel = document.getElementById("slide-panel");

  let showingSignUp = true;

  toggleBtn.addEventListener("click", () => {
    // Toggle slide effect
    authSlide.classList.toggle("slide-active");

    // Toggle form visibility
    showingSignUp = !showingSignUp;
    if (showingSignUp) {
      signInForm.classList.add("hidden");
      signUpForm.classList.remove("hidden");
      toggleBtn.textContent = "SIGN IN";
      panelText.textContent = "Already have an account?";
    } else {
      signUpForm.classList.add("hidden");
      signInForm.classList.remove("hidden");
      toggleBtn.textContent = "SIGN UP";
      panelText.textContent = "Don't have an account?";
    }
  });














  const questions = [
    "Seberapa lelah kamu hari ini?",
    "Apakah kamu merasa cemas akhir-akhir ini?",
    "Seberapa baik kualitas tidurmu semalam?",
    "Apakah kamu merasa semangat menjalani hari?",
    "Seberapa sering kamu merasa kewalahan?"
  ];
  
  let currentQuestion = 0;
  const answers = [];
  
  const questionText = document.getElementById("question-text");
  const optionsContainer = document.getElementById("options-container");
  const nextBtn = document.getElementById("next-btn");
  
  function loadQuestion() {
    questionText.textContent = questions[currentQuestion];
    optionsContainer.innerHTML = "";
    nextBtn.disabled = true;
  
    for (let i = 1; i <= 10; i++) {
      const label = document.createElement("label");
      label.classList.add("option-label");
      label.textContent = i;
      label.dataset.value = i;
  
      label.addEventListener("click", () => {
        document.querySelectorAll(".option-label").forEach(el => el.classList.remove("selected"));
        label.classList.add("selected");
        nextBtn.disabled = false;
      });
  
      optionsContainer.appendChild(label);
    }
  }
  
  nextBtn.addEventListener("click", () => {
    const selected = document.querySelector(".option-label.selected");
  
    if (selected) {
      answers.push({
        question: questions[currentQuestion],
        answer: selected.dataset.value
      });
  
      currentQuestion++;
  
      if (currentQuestion < questions.length) {
        loadQuestion();
      } else {
        document.getElementById("question-box").innerHTML = `
          <h2>Terima kasih!</h2>
          <p>Jawaban kamu sudah tercatat.</p>
          <pre>${JSON.stringify(answers, null, 2)}</pre>
        `;
        // nanti bisa kirim ke backend di sini
      }
    }
  });
  
  loadQuestion();
  



// script.js
document.addEventListener("DOMContentLoaded", function () {
  const ctx = document.getElementById("grafik").getContext("2d");
  new Chart(ctx, {
      type: "bar",
      data: {
          labels: ["Total Skor"],
          datasets: [{
              label: "Tingkat Stres",
              data: [parseInt(document.getElementById("skor").innerText)],
              backgroundColor: ["#5c2a9d"]
          }]
      },
  });
});
