<?php
session_start();
include "includes/db.php";

$otpError = $passwordError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_otp'])) {
        $enteredOtp = $_POST['otp'];
        if ($enteredOtp == $_SESSION['otp']) {
            $_SESSION['otp_verified'] = true;
            echo "OTP Verified! You can now reset your password.";
        } else {
            $otpError = "Invalid OTP. Please try again.";
        }
    }

    if (isset($_POST['reset_password'])) {
        if ($_SESSION['otp_verified'] === true) {
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            if ($newPassword === $confirmPassword) {
                $crn = $_SESSION['otp_crn'];
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                $sql = "UPDATE students SET password='$hashedPassword' WHERE crn='$crn'";
                if ($conn->query($sql) === TRUE) {
                    echo"<script> alert('Password reset successfully'); </script>";
                    session_unset();
                    session_destroy();
                    echo "<script>window.location.href='login.php';</script>";
                    exit();
                } else {
                    $passwordError = "Failed to reset password. Please try again.";
                }
            } else {
                $passwordError = "Passwords do not match.";
            }
        } else {
            $otpError = "OTP verification required.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP and Reset Password</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        .container {
        max-width: 400px;
        margin: 50px auto;
        padding: 20px;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Verify OTP and Reset Password</h2>
        
        <form method="POST" action="verifyotp.php">
            <label for="otp">Enter OTP:</label>
            <input type="text" name="otp" required>
            <span class="error"><?php echo $otpError; ?></span>
            <button type="submit" name="verify_otp">Verify OTP</button>
        </form>
        
        <?php if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true): ?>
        <form method="POST" action="verifyotp.php">
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" required> <br>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" required>
            
            <span class="error"><?php echo $passwordError; ?></span>
            <button type="submit" name="reset_password">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$conn->close();
?>
