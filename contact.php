<?php
require 'vendor/autoload.php';
include 'includes/db.php';
include 'includes/header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
?>

<main class="container">
  <div class="contact-header">
    <h1>Contact Us</h1>
    <p>If you have any questions about our freeze-dried products, feel free to reach out!</p>
  </div>

  <?php
  $success = false;
  $error   = false;

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Raw values for sending; escape only when echoing back to HTML
    $name    = trim($_POST['name']   ?? '');
    $email   = trim($_POST['email']  ?? '');
    $message = trim($_POST['message']?? '');

    if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $message !== '') {
      $mail = new PHPMailer(true);
      try {
        // === Local SMTP catcher (Mailpit/MailHog) ===
        $mail->isSMTP();
        $mail->Host       = 'localhost';
        $mail->Port       = 1025;
        $mail->SMTPAuth   = false;
        $mail->SMTPSecure = false;

        // IMPORTANT: fixed sender to avoid spoofing; user goes in Reply-To
        $mail->setFrom('no-reply@astrobite.local', 'AstroBite');
        $mail->addReplyTo($email, $name);

        // Your local "inbox" in the catcher UI
        $mail->addAddress('contact@demo.local', 'AstroBite Contact');

        $mail->Subject = "New Contact Message from {$name}";
        $mail->Body    = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";

        // Debug while testing (uncomment if needed)
        // $mail->SMTPDebug  = 2;          // 0=off, 2=client+server messages
        // $mail->Debugoutput = 'error_log';

        $mail->send();
        $success = true;
      } catch (Exception $e) {
        // error_log('Mailer Error: ' . $mail->ErrorInfo);
        $error = true;
      }
    } else {
      $error = true;
    }
  }
  ?>

  <?php if ($success): ?>
    <p class="success-message">✅ Thank you for your message! We'll get back to you soon.</p>
  <?php elseif ($error): ?>
    <p class="error-message">❌ There was a problem sending your message. Please try again later.</p>
  <?php endif; ?>

  <form action="contact.php" method="POST" class="contact-form" novalidate>
    <label for="name">Name *</label>
    <input type="text" id="name" name="name" required>

    <label for="email">Email *</label>
    <input type="email" id="email" name="email" required>

    <label for="message">Message *</label>
    <textarea id="message" name="message" rows="6" required></textarea>

    <button type="submit" class="primary">Send Message</button>
  </form>
</main>

<?php include 'includes/footer.php'; ?>
