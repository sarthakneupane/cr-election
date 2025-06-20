<?php

    session_start();

    include "includes/db.php";
    if (!isset($_SESSION['admname'])) {
        header("Location: login.php");
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] == "POST"){
        // $adm_id = $_POST['id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $cpassword = $_POST['cpassword'];

        $errors = 0;

        if($password != $cpassword){
            $errors++;
        }
        else{
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        }

        if($errors == 0){
            $sql = "INSERT INTO admins(name, email, password) VALUES('$name','$email','$hashed_password')";
            $result = mysqli_query($conn, $sql);

            if($result){
                echo "<script>alert('Successfully inserted data');</script>";
                echo "<script>window.location.href='adminpage.php';</script>";
                exit();  // Ensure no further code is executed after redirect
            }
            else{
                echo "failed";
            }
        
        }
        
    }

    $active="adminentry";


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Entry</title>
    <link rel="stylesheet" href="css/adminentry.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include 'includes/adminheader.php'; ?>

    <div class="content-wrapper">
        <!-- Sidebar is included in adminheader.php -->
        
        <main class="main-content">
            <div class="admin-container">
                <form method="POST" class="admin-form-wrapper">
                    <h2>Admin Entry Form</h2>
                    
                    <?php if(isset($errors) && $errors > 0): ?>
                        <div class="error-message">
                            Passwords do not match!
                        </div>
                    <?php endif; ?>
                    
                    <input type="text" name="name" placeholder="Full Name" required> <br>
                    <input type="email" name="email" placeholder="Email Address" required> <br>
                    <input type="password" name="password" placeholder="Password" required> <br>
                    <input type="password" name="cpassword" placeholder="Confirm Password" required>
                    
                    <div id="passwordMatch" class="password-match"></div>
                    
                    <button type="submit" class="form-submit-btn">
                        <i class="fas fa-user-plus"></i> Register Admin
                    </button>
                </form>
            </div>
        </main>
    </div>
    
    <!-- <?php include 'includes/footer.php'; ?> -->

    <script>
        // Add password match validation
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.querySelector('input[name="password"]');
            const confirmPassword = document.querySelector('input[name="cpassword"]');
            const matchMessage = document.getElementById('passwordMatch');
            
            function checkPasswordMatch() {
                if (password.value && confirmPassword.value) {
                    if (password.value === confirmPassword.value) {
                        matchMessage.textContent = 'Passwords match!';
                        matchMessage.className = 'password-match valid';
                    } else {
                        matchMessage.textContent = 'Passwords do not match!';
                        matchMessage.className = 'password-match invalid';
                    }
                } else {
                    matchMessage.textContent = '';
                }
            }
            
            password.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
        });
    </script>
</body>
</html>