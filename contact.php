<?php
/**
 * contact.php - Contact Form Page
 * 
 * Purpose: Allow users to submit contact messages via form
 * 
 * Features:
 * - Contact form with name, email, and message fields
 * - Email validation using PHP filter_var()
 * - Message sending via PHPMailer (SMTP)
 * - Local SMTP catcher integration (Mailpit/MailHog for testing)
 * - Success/error feedback messages
 * - Field validation (required fields, valid email format)
 * - Reply-To header for proper email threading
 * 
 * Security Measures:
 * - Email validation prevents malformed email addresses
 * - Proper PHPMailer configuration prevents header injection
 * - Fixed sender address ("no-reply@astrobite.local") prevents spoofing
 * - User email stored in Reply-To header instead of From (best practice)
 * - htmlspecialchars() escaping on form display for XSS prevention
 * - Trim on all inputs removes accidental whitespace
 * 
 * POST Parameters:
 * - name: Contact person's full name
 * - email: Contact person's email address (validated)
 * - message: Contact message body
 * 
 * Email Configuration:
 * - SMTP Host: localhost
 * - SMTP Port: 1025 (Mailpit/MailHog development catcher)
 * - From: no-reply@astrobite.local (fixed sender, cannot be spoofed)
 * - Reply-To: User's email address (for replies)
 * - Recipient: contact@demo.local
 * 
 * Dependencies: vendor/autoload.php (PHPMailer), db.php, header.php, footer.php
 * External: PHPMailer\PHPMailer library for SMTP email delivery
 * Dev Tool: Mailpit or MailHog for local SMTP catching during development
 */

require 'vendor/autoload.php';
include 'includes/db.php';
include 'includes/header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
?>

<main class="container">
  <!-- ========== PAGE HEADER ========== -->
  <!-- Contact page title and brief description -->
  <div class="contact-header">
    <h1>Contact Us</h1>
    <p>If you have any questions about our freeze-dried products, feel free to reach out!</p>
  </div>

  <?php
  // -------------------------------------------------------
  // 1) Initialize Success/Error Tracking
  // -------------------------------------------------------
  $success = false;
  $error   = false;

  // -------------------------------------------------------
  // 2) Handle Form Submission
  // -------------------------------------------------------
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and trim user inputs (trim removes leading/trailing whitespace)
    $name    = trim($_POST['name']   ?? '');
    $email   = trim($_POST['email']  ?? '');
    $message = trim($_POST['message']?? '');

    // -------------------------------------------------------
    // 3) Validate Form Fields
    // -------------------------------------------------------
    // Check: name is not empty, email is valid format, message is not empty
    if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $message !== '') {
      
      // -------------------------------------------------------
      // 4) Prepare PHPMailer Instance
      // -------------------------------------------------------
      $mail = new PHPMailer(true);
      try {
        // === Configure SMTP for Local Testing ===
        // Using Mailpit/MailHog on localhost:1025 (development email catcher)
        $mail->isSMTP();
        $mail->Host       = 'localhost';
        $mail->Port       = 1025;
        $mail->SMTPAuth   = false;           // No authentication needed for local dev
        $mail->SMTPSecure = false;           // No encryption for local dev

        // -------------------------------------------------------
        // 5) Set Email Headers & Recipients
        // -------------------------------------------------------
        // Fixed from address prevents header injection and spoofing
        $mail->setFrom('no-reply@astrobite.local', 'AstroBite');
        
        // User's email goes in Reply-To so replies go to them (best practice)
        $mail->addReplyTo($email, $name);

        // Email destination for contact messages
        $mail->addAddress('contact@demo.local', 'AstroBite Contact');

        // -------------------------------------------------------
        // 6) Compose Email Content
        // -------------------------------------------------------
        $mail->Subject = "New Contact Message from {$name}";
        $mail->Body    = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";

        // Debug mode (uncomment to see SMTP conversation):
        // $mail->SMTPDebug  = 2;          // 0=off, 1=client only, 2=client+server
        // $mail->Debugoutput = 'error_log';

        // -------------------------------------------------------
        // 7) Send Email & Track Success
        // -------------------------------------------------------
        $mail->send();
        $success = true;
      } catch (Exception $e) {
        // Email send failed - log error (uncomment to debug)
        // error_log('Mailer Error: ' . $mail->ErrorInfo);
        $error = true;
      }
    } else {
      // Validation failed (missing fields or invalid email)
      $error = true;
    }
  }
  ?>

  <!-- ========== SUCCESS MESSAGE ========== -->
  <!-- Displayed when contact form is successfully submitted -->
  <?php if ($success): ?>
    <p class="success-message">✅ Thank you for your message! We'll get back to you soon.</p>
  <!-- ========== ERROR MESSAGE ========== -->
  <!-- Displayed when validation fails or email send fails -->
  <?php elseif ($error): ?>
    <p class="error-message">❌ There was a problem sending your message. Please try again later.</p>
  <?php endif; ?>

  <!-- ========== CONTACT FORM ========== -->
  <!-- User input form for name, email, and message -->
  <form action="contact.php" method="POST" class="contact-form" novalidate>
    <!-- Name input field -->
    <label for="name">Name *</label>
    <input type="text" id="name" name="name" required>

    <!-- Email input field with HTML5 validation -->
    <label for="email">Email *</label>
    <input type="email" id="email" name="email" required>

    <!-- Message textarea for longer user input -->
    <label for="message">Message *</label>
    <textarea id="message" name="message" rows="6" required></textarea>

    <!-- Submit button to send form -->
    <button type="submit" class="primary">Send Message</button>
  </form>
</main>

<?php include 'includes/footer.php'; ?>
