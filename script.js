// Terminal Typing Effect
const phrases = ["Software Engineer", "Data Analyst", "STEM Educator"];
let currentPhrase = 0;
let currentLetter = 0;
let isDeleting = false;
const typingText = document.getElementById('typing-text');

function type() {
  const currentText = phrases[currentPhrase];
  
  if (!isDeleting) {
    typingText.textContent = currentText.substring(0, currentLetter + 1);
    currentLetter++;
    
    if (currentLetter === currentText.length) {
      isDeleting = true;
      setTimeout(type, 1000);
      return;
    }
  } else {
    typingText.textContent = currentText.substring(0, currentLetter - 1);
    currentLetter--;
    
    if (currentLetter === 0) {
      isDeleting = false;
      currentPhrase = (currentPhrase + 1) % phrases.length;
    }
  }
  
  setTimeout(type, isDeleting ? 50 : 100);
}

// Start animation
type();