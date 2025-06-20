<?php

    session_start();

    include "includes/db.php";

    // COLLECTING VOTER'S DATA FROM SESSION
    $votername = $_SESSION['stdname'];
    $votercrn = $_SESSION['crn'];
    $voted = $_SESSION['voted'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (isset($_POST['logoutbutton'])) {
            session_unset(); 
            session_destroy();
            header("Location: login.php");
            exit();
        }
        
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
    
        // Validate passwords
        if ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } else {
            // Fetch the current hashed password from the database
            $sql = "SELECT password FROM students WHERE crn = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $votercrn);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($hashed_password);
            $stmt->fetch();
            if (password_verify($current_password, $hashed_password)) {
                // Hash the new password
                $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                
                // Update the new password in the database
                $sql_update = "UPDATE students SET password = ? WHERE crn = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ss", $new_hashed_password, $votercrn);
                if ($stmt_update->execute()) {
                    $success = true;
                } else {
                    $error = "Failed to update password. Please try again.";
                }
            } else {
                $error = "Current password is incorrect.";
            }
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <!-- LINKING CSS -->
    <link rel="stylesheet" href="css/main.css">
    <style>
        /* General Styles */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #f5f5f5;
    margin: 0;
    padding: 0;
}

/* Navbar container */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 12px 20px;
    color: black;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Welcome message */
.welcome span {
    font-size: 18px;
    font-weight: 500;
}

/* Button group container */
.navbar-links {
    display: flex;
    gap: 10px;
}

/* Buttons inside the navbar */
.navbar-button {
    padding: 8px 16px;
    font-size: 15px;
    color: #1a73e8;
    background-color: #fff;
    border: none;
    border-radius: 5px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.navbar-button:hover {
    background-color: #e3f0ff;
    color: #0b57d0;
}


/* Container Styles */
.password-change-container {
    max-width: 450px;
    margin: 40px auto;
    padding: 30px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.password-change-title {
    text-align: center;
    color: #2c3e50;
    font-size: 24px;
    margin-bottom: 25px;
    font-weight: 600;
}

/* Form Styles */
.password-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 15px;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.form-input:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

/* Button Styles */
.password-submit-btn {
    width: 100%;
    padding: 12px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s;
}

.password-submit-btn:hover {
    background-color: #2980b9;
}

/* Link Styles */
.forgot-password-link {
    display: block;
    text-align: center;
    margin-top: 15px;
    color: #3498db;
    text-decoration: none;
    font-size: 14px;
}

.forgot-password-link:hover {
    text-decoration: underline;
}

/* Message Styles */
.alert-message {
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 15px;
}

.alert-error {
    background-color: #fdecea;
    color: #d32f2f;
    border: 1px solid #f5c2c7;
}

.alert-success {
    background-color: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

/* Responsive Design */
@media (max-width: 576px) {
    .password-change-container {
        margin: 20px 15px;
        padding: 20px;
    }
    
    .password-change-title {
        font-size: 22px;
    }
}
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="navbar">
        <div class="welcome">
            <span>Welcome, <?php echo htmlspecialchars($votername); ?></span>
        </div>
        <div class="navbar-links">
            <form method="get" action="votingpage.php" style="display: inline;">
                <button type="submit" class="navbar-button">Vote</button>
            </form>
            <form method="post" style="display: inline;">
                <button type="submit" class="navbar-button" name="logoutbutton">Logout</button>
            </form>
        </div>
    </div>

   <div class="password-change-container">
    <h2 class="password-change-title">Change Password</h2>

    <?php if (isset($error)): ?>
        <div class="alert-message alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="" class="password-form">
        <div class="form-group">
            <input type="password" class="form-input" id="current_password" name="current_password" placeholder="Current password" required>
        </div>
        <div class="form-group">
            <input type="password" class="form-input" id="new_password" name="new_password" placeholder="New password" required>
        </div>
        <div class="form-group">
            <input type="password" class="form-input" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required>
        </div>
        <button type="submit" class="password-submit-btn">Change Password</button>
        <a href="forgotpw.php" class="forgot-password-link">Forgot Password?</a>
    </form>
</div>

    </div>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

</body>
</html>

<?php
    $conn->close();
?>
t