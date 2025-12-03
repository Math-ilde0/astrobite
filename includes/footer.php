<!-- ========== FOOTER SECTION ========== -->
<!-- Global footer: about, store locations with embedded maps, contact, quick links, Vietnamese time clock, and LocalBusiness schema -->
<footer>
  <div class="container footer-grid">
    <!-- ========== ABOUT SECTION ========== -->
    <!-- Brand description and mission -->
    <section class="footer-about" aria-labelledby="footer-about-title">
      <h2 id="footer-about-title">About AstroBite</h2>
      <p>AstroBite is your go-to destination for premium space-themed products. From apparel to home décor, we bring the universe closer to your everyday life.</p>
      <p>Explore our curated collections and experience the magic of the cosmos.</p>
    </section>

    <!-- ========== STORE LOCATIONS WITH MAPS ========== -->
    <!-- Embedded Google Maps for both collection points (SEO local business schema) -->
    <section class="footer-maps" aria-labelledby="footer-stores-title">
      <h2 id="footer-stores-title">Our Stores</h2>

      <!-- Store 1: Saigon Centre, District 1 -->
      <div class="map">
        <iframe 
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.498198196343!2d106.69847507552467!3d10.77310308937549!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f4743648f3d%3A0x16ce95918cb14834!2sSaigon%20Centre!5e0!3m2!1sen!2s!4v1761796806667!5m2!1sen!2s"
          width="400" height="200" style="border:0;" allowfullscreen="" loading="lazy" 
          referrerpolicy="no-referrer-when-downgrade" title="AstroBite Store - Saigon Centre">
        </iframe>
        <address>
          <strong>Store 1:</strong> 2nd Floor, Saigon Centre, District 1, Ho Chi Minh City
        </address>
      </div>

      <!-- Store 2: Ga Sài Gòn Rail Station, District 3 -->
      <div class="map">
        <iframe 
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d13148.383036046955!2d106.67389976294564!3d10.781611263277997!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752fc3228914e9%3A0x8a6a3f7b6dcae1a8!2zR2EgU8OgaSBHw7Ju!5e0!3m2!1sen!2s!4v1761797183425!5m2!1sen!2s"
          width="400" height="200" style="border:0;" allowfullscreen="" loading="lazy" 
          referrerpolicy="no-referrer-when-downgrade" title="AstroBite Store - Ga Sài Gòn">
        </iframe>
        <address>
          <strong>Store 2:</strong> Basement 1, Saigon Rail Station, District 3, Ho Chi Minh City
        </address>
      </div>
    </section>

    <!-- ========== CONTACT CALL-TO-ACTION ========== -->
    <!-- Direct link to contact form -->
    <section class="footer-contact" aria-labelledby="footer-contact-title">
      <h2 id="footer-contact-title">Contact Us</h2>
      <p>Feel free to reach out to us for any inquiries or support! Click below to get in touch.</p>
      <a href="/mywebsite/astrobite/contact.php" class="contact-button" aria-label="Go to contact page">Contact Us</a>
    </section>

    <!-- ========== QUICK NAVIGATION LINKS ========== -->
    <!-- Footer navigation to main pages -->
    <nav class="footer-links" aria-label="Footer navigation">
      <h2 class="visually-hidden">Quick Links</h2>
      <ul>
        <li><a href="/mywebsite/astrobite/index.php">Home</a></li>
        <li><a href="/mywebsite/astrobite/products.php">Products</a></li>
        <li><a href="/mywebsite/astrobite/contact.php">Contact</a></li>
      </ul>
    </nav>

    <!-- ========== VIETNAMESE TIME DISPLAY ========== -->
    <!-- Live clock updated every second by JavaScript below -->
    <div class="footer-clock" id="vnClock" aria-label="Vietnamese Time"></div>
  </div>

</footer>

<!-- ========== STRUCTURED DATA (LocalBusiness Schema) ========== -->
<!-- JSON-LD schema for search engines to understand business locations and details -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "AstroBite",
  "url": "https://example.com",
  "logo": "https://example.com/assets/images/logo.png",
  "description": "AstroBite is your destination for premium freeze-dried snacks and space-inspired gifts.",
  "address": [{
    "@type": "PostalAddress",
    "streetAddress": "Saigon Centre, District 1",
    "addressLocality": "Ho Chi Minh City",
    "addressCountry": "Vietnam"
  },
  {
    "@type": "PostalAddress",
    "streetAddress": "Ga Sài Gòn, District 3",
    "addressLocality": "Ho Chi Minh City",
    "addressCountry": "Vietnam"
  }]
}
</script>

<!-- ========== VIETNAMESE TIME CLOCK SCRIPT ========== -->
<!-- Displays real-time Vietnamese time (UTC+7) updated every second -->
<script>
(function() {
  // Helper: pad single digits with leading zero
  function pad(n){return n<10?('0'+n):n}
  
  // Update time display
  function tick(){
    try {
      const now = new Date();
      const vnOffset = 7 * 60 * 60 * 1000;  // Vietnam is UTC+7
      const vnTime = new Date(now.getTime() + vnOffset);
      const h = pad(vnTime.getUTCHours());
      const m = pad(vnTime.getUTCMinutes());
      const s = pad(vnTime.getUTCSeconds());
      const el = document.getElementById('vnClock');
      if (el) el.textContent = 'Giờ Việt Nam: ' + h + ':' + m + ':' + s;  // "Vietnamese Time: HH:MM:SS"
    } catch(e) {}
  }
  
  // Initial display and update every second
  tick();
  setInterval(tick, 1000);
})();
</script>

<!-- ========== MAIN JAVASCRIPT ========== -->
<!-- Global scripts for site functionality -->
<script src="/mywebsite/astrobite/assets/js/script.js"></script>

