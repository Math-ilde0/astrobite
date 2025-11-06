<footer>
  <div class="container">
    <!-- About Section -->
    <div class="footer-about">
      <h2>About AstroBite</h2>
      <p>AstroBite is your go-to destination for premium space-themed products. From apparel to home decor, we bring the universe closer to your everyday life.</p>
      <p>Explore our curated collections and experience the magic of the cosmos.</p>
    </div>

    <!-- Google Maps Locations -->
    <div class="footer-maps">
      <h2>Our Stores</h2>
      <div class="map">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.498198196343!2d106.69847507552467!3d10.77310308937549!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f4743648f3d%3A0x16ce95918cb14834!2sSaigon%20Centre!5e0!3m2!1sen!2s!4v1761796806667!5m2!1sen!2s" width="400" height="200" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        <p>Store 1: 2nd Floor, Saigon Center, District 1, Ho Chi Minh City</p>
      </div>
      <div class="map">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d13148.383036046955!2d106.67389976294564!3d10.781611263277997!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752fc3228914e9%3A0x8a6a3f7b6dcae1a8!2zR2EgU8OgaSBHw7Ju!5e0!3m2!1sen!2s!4v1761797183425!5m2!1sen!2s" width="400" height="200" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        <p>Store 2: Basement 1, Saigon Rail Station, District 3, Ho Chi Minh City</p>
      </div>
    </div>

    <!-- Contact Information -->
<div class="footer-contact">
  <h2>Contact Us</h2>
  <p>
    Feel free to reach out to us for any inquiries or support! Click the button below:
  </p>
  <a href="/contact.php" class="contact-button" aria-label="Go to the contact page">Contact Us</a>
</div>

    <!-- Vietnamese Time -->
    <div class="footer-clock" id="vnClock" aria-label="Vietnamese Time"></div>
  </div>
</footer>

<script>
  (function() {
    function pad(n){return n<10?('0'+n):n}
    function tick(){
      try {
        // Vietnamese Time (UTC+7)
        var d = new Date(new Date().getTime() + (7 * 60 * 60 * 1000)); // Add 7 hours to UTC
        var h = pad(d.getHours());
        var m = pad(d.getMinutes());
        var s = pad(d.getSeconds());
        var el = document.getElementById('vnClock');
        if(el) {
          el.textContent = 'Giờ Việt Nam: ' + h + ':' + m + ':' + s; // Display in Vietnamese
        }
      } catch(e) {}
    }
    tick();
    setInterval(tick, 1000);
  })();
</script>