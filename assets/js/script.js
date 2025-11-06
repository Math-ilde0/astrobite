document.addEventListener("DOMContentLoaded", () => {
    const images = document.querySelectorAll(".image-wrapper img");
    const leftArrow = document.querySelector(".switch-arrow.left");
    const rightArrow = document.querySelector(".switch-arrow.right");
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
  });