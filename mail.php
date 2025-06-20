<?php
session_start();
if (isset($_SESSION['student_registered']) && $_SESSION['student_registered'] === true) {
    echo "<script>alert('Student has been registered successfully!');</script>";
    unset($_SESSION['student_registered']);
}
$email = $_SESSION['mail_email'];
$name = $_SESSION['mail_name'];
$crn = $_SESSION['mail_crn'];
$password = $_SESSION['mail_password'];

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

    $mail->Body = 'Dear ' . $name . '<br>You have been registered in HDC Online Voting System.<br>Please login using the following credentials: <br> <br> CRN No.: ' . $crn . '<br>Password: ' . $password . '<br><br>Please change your password as soon as you login.';
    $mail->AltBody = 'Hi ' . $name . '. You have been registered in HDC Online Voting System. Please login using the following credentials: CRN No.: ' . $crn . ', Password: ' . $password . '. <b>Please change your password as soon as you login.</b>';

    $mail->send();
echo "<script>alert('Registration successful. Email sent.');</script>";
echo "<script>window.location.href='studententry.php';</script>";
exit();

} catch (Exception $e) {
    echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo});</script>";
}

// header("Location: studententry.php");
