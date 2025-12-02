document.addEventListener("DOMContentLoaded", () => {
    // ===== HAMBURGER MENU =====
    const hamburgerToggle = document.getElementById("hamburger-toggle");
    const mainNav = document.getElementById("main-nav");

    if (hamburgerToggle && mainNav) {
      hamburgerToggle.addEventListener("click", (e) => {
        e.preventDefault();
        hamburgerToggle.classList.toggle("active");
        mainNav.classList.toggle("active");
        hamburgerToggle.setAttribute("aria-expanded", mainNav.classList.contains("active"));
      });

      // Close menu when a link is clicked
      const navLinks = mainNav.querySelectorAll("a");
      navLinks.forEach(link => {
        link.addEventListener("click", () => {
          hamburgerToggle.classList.remove("active");
          mainNav.classList.remove("active");
          hamburgerToggle.setAttribute("aria-expanded", "false");
        });
      });

      // Close menu when clicking outside
      document.addEventListener("click", (e) => {
        if (!hamburgerToggle.contains(e.target) && !mainNav.contains(e.target)) {
          if (mainNav.classList.contains("active")) {
            hamburgerToggle.classList.remove("active");
            mainNav.classList.remove("active");
            hamburgerToggle.setAttribute("aria-expanded", "false");
          }
        }
      });
    }

    // ===== IMAGE CAROUSEL =====
    const images = document.querySelectorAll(".image-wrapper img");
    const leftArrow = document.querySelector(".switch-arrow.left");
    const rightArrow = document.querySelector(".switch-arrow.right");
    
    // Only run if image carousel exists
    if (images.length > 0 && leftArrow && rightArrow) {
      let currentIndex = 0;

      function updateImages(index) {
        images.forEach((img, i) => {
          img.classList.toggle("visible", i === index);
          img.classList.toggle("hidden", i !== index);
        });
      }

      leftArrow.addEventListener("click", () => {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        updateImages(currentIndex);
      });

      rightArrow.addEventListener("click", () => {
        currentIndex = (currentIndex + 1) % images.length;
        updateImages(currentIndex);
      });

      // Initialize the first image as visible
      updateImages(currentIndex);
    }
});  