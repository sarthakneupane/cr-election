<?php
session_start();

include "includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if email exists in the database
    $sql = "SELECT * FROM students WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $crn = $row['crn'];
        
        // Generate a random OTP
        $otp = rand(100000, 999999);
        
        // Store OTP in session
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_crn'] = $crn;

        $_SESSION['forgotpw_email'] = $row['email'];
        $_SESSION['forgotpw_name'] = $row['name'];
        
        // Send OTP via email using mail2.php
        include 'mail2.php';
        
        echo "OTP has been sent to your email.";
        // Redirect to OTP verification page
        header("Location: verifyotp.php");
        exit();
    } else {
        echo "Email not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
        <h2>Forgot Password</h2>
        <form method="POST" action="forgotpw.php">
            <label for="email">Enter your email:</label>
            <input type="email" name="email" required>
            <button type="submit">Submit</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$conn->close();
?>
