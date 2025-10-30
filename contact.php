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
  $error = false;

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = htmlspecialchars(trim($_POST['name']));
    $email   = htmlspecialchars(trim($_POST['email']));
    $message = htmlspecialchars(trim($_POST['message']));

    if (!empty($name) && !empty($email) && !empty($message)) {
      $mail = new PHPMailer(true);

      try {
    $mail->isSMTP();
    $mail->Host = 'localhost';      
    $mail->Port = 1025;           
    $mail->SMTPAuth = false;     
    $mail->setFrom($email, $name);
    $mail->addAddress('contact@demo.local'); 

    $mail->Subject = "New Contact Message from $name";
    $mail->Body    = "Name: $name\nEmail: $email\n\nMessage:\n$message";

    $mail->send();
    $success = true;
} catch (Exception $e) {
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

  <form action="contact.php" method="POST" class="contact-form">
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
