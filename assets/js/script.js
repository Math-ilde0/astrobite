/**
 * script.js - Main Client-Side JavaScript
 * 
 * Provides core interactive functionality:
 * - Mobile hamburger menu toggle with click-outside close
 * - Product image carousel navigation (left/right arrows)
 * 
 * Dependencies: HTML structure with required IDs and classes
 * Performance: All code runs after DOMContentLoaded event
 * Accessibility: ARIA attributes updated for screen readers
 */

document.addEventListener("DOMContentLoaded", () => {
    // ========== HAMBURGER MENU TOGGLE ========== 
    // Mobile navigation menu with outside-click close
    const hamburgerToggle = document.getElementById("hamburger-toggle");
    const mainNav = document.getElementById("main-nav");

    if (hamburgerToggle && mainNav) {
      // ========== MENU TOGGLE CLICK HANDLER ========== 
      // Toggle active state and update ARIA attribute for screen readers
      hamburgerToggle.addEventListener("click", (e) => {
        e.preventDefault();
        hamburgerToggle.classList.toggle("active");
        mainNav.classList.toggle("active");
        hamburgerToggle.setAttribute("aria-expanded", mainNav.classList.contains("active"));
      });

      // ========== CLOSE MENU ON LINK CLICK ========== 
      // Auto-close menu after navigating to page
      const navLinks = mainNav.querySelectorAll("a");
      navLinks.forEach(link => {
        link.addEventListener("click", () => {
          hamburgerToggle.classList.remove("active");
          mainNav.classList.remove("active");
          hamburgerToggle.setAttribute("aria-expanded", "false");
        });
      });

      // ========== CLOSE MENU ON OUTSIDE CLICK ========== 
      // Click anywhere outside menu/button closes menu
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

    // ========== PRODUCT IMAGE CAROUSEL ========== 
    // Left/right arrow navigation for multiple product images
    const images = document.querySelectorAll(".image-wrapper img");
    const leftArrow = document.querySelector(".switch-arrow.left");
    const rightArrow = document.querySelector(".switch-arrow.right");
    
    // Only initialize if carousel elements exist on page
    if (images.length > 0 && leftArrow && rightArrow) {
      let currentIndex = 0;

      // ========== UPDATE VISIBLE IMAGE ========== 
      // Show image at index, hide all others
      function updateImages(index) {
        images.forEach((img, i) => {
          img.classList.toggle("visible", i === index);
          img.classList.toggle("hidden", i !== index);
        });
      }

      // ========== LEFT ARROW CLICK HANDLER ========== 
      // Previous image (wraps to last image if at beginning)
      leftArrow.addEventListener("click", () => {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        updateImages(currentIndex);
      });

      // ========== RIGHT ARROW CLICK HANDLER ========== 
      // Next image (wraps to first image if at end)
      rightArrow.addEventListener("click", () => {
        currentIndex = (currentIndex + 1) % images.length;
        updateImages(currentIndex);
      });

      // Initialize carousel with first image visible
      updateImages(currentIndex);
    }
});  