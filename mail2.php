<?php
// session_start();
$email = $_SESSION['forgotpw_email'];
$name = $_SESSION['forgotpw_name'];
$crn = $_SESSION['otp_crn'];
// $password = $_SESSION['mail_password'];
$otp = $_SESSION['otp'];

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = 0; // Enable verbose debug output
    $mail->isSMTP(); // Set mailer to use SMTP
    $mail->Host = 'smtp.gmail.com'; // Specify main and backup SMTP servers
    $mail->SMTPAuth = true; // Enable SMTP authentication
    $mail->Username = 'hello@gmail.com'; // SMTP username
    $mail->Password = 'app-password'; // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587; // TCP port to connect to

    //Recipients
    $mail->setFrom('hello@gmail.com', 'Admin');
    $mail->addAddress($email, $name); // Add a recipient

    // Content
    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = 'HDC Online Voting System';
    $mail->Body = 'Dear ' . $name . ',<br>Please enter the following OTP to reset your password:<br> <b>'.$otp.'</b>';
    $mail->AltBody = 'Dear ' . $name . ',<br>Please enter the following OTP to reset your password:<br> <b>'.$otp.'</b>';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo});</script>";
}

// header("Location: studententry.php");
