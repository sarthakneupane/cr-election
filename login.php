<?php

session_start();

include "includes/db.php";

    //STUDENT FORM VALIDATION
    if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['student_login'])){
        $crn = $_POST['crn'];
        $stdpassword = $_POST['student_password'];
        $student_errors = [];

        //ABSTRACTING STUDENT DATA FROM DATABASE
        $sql = "SELECT * FROM students";
        $result = mysqli_query($conn, $sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if($crn == $row['crn'] && password_verify($stdpassword, $row['password'])){
                    $_SESSION['crn'] = $row['crn'];
                    $_SESSION['stdname'] = $row['name'];
                    $_SESSION['voted'] = $row['voted'];

                    // echo $_SESSION['name'];
                    header("Location: student_dashboard.php");
                }
                else{
                    $student_errors['login_error'] = "Invalid username or password!";
                }
            }
        }
    }

    //ADMIN FORM VALIDATION
    else if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['admin_login'])){
        $admin_identifier = $_POST['admin_identifier'];
        $admpassword = $_POST['admin_password'];
        $admin_errors = [];

        //ABSTRACTING ADMIN DATA FROM DATABASE
        $sql = "SELECT * FROM admins";
        $result = mysqli_query($conn, $sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if($admin_identifier == $row['email'] ){
                    if(password_verify($admpassword, $row['password'])){
                        $_SESSION['admid'] = $row['id'];
                        $_SESSION['admname'] = $row['name'];

                        // echo $_SESSION['name'];
                        header("Location: adminpage.php");
                    }
                }
                else{
                        $admin_errors['login_error'] = "Invalid username or password!";
                    
                }
            }
        }
    }


?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="css/login.css">
    
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="form">
            <div id="studentlogin" class="form-container">
                <h2>Login as student</h2>
                <form method="POST">
                    <input type="number" name="crn" placeholder="CRN No." required> <br>
                    <input type="password" name="student_password" placeholder="Password" required> <br>
                    <button type="submit" class="loginbutton" name="student_login">Login</button><br> <br>
                    <a href = "forgotpw.php"> Forgot password? </a>
                    
                    <?php if(isset($student_errors['login_error'])): ?>
                    <span style="color:red">
                        <?php echo $student_errors['login_error']; ?>
                    </span>
                    <?php endif; ?> 
                    
                    <?php if(isset($admin_errors['login_error'])): ?>
                    <span style="color:red">
                        <?php echo $admin_errors['login_error']; ?>
                    </span>  
                    <?php endif; ?>
                    
                    <hr> <br>

                </form>
                <button onclick="toggleForm('register')" class="switchbutton">Login as admin</button>
            </div>
            <div id="register-form" class="form-container" style="display: none;">
                <h2>Login as admin</h2>
                <form method="POST">
                    <input type="text" name="admin_identifier" placeholder="Email" required> <br>
                    <input type="password" name="admin_password" placeholder="Password" required> <br>
                    <button type="submit" class="loginbutton"  name="admin_login">Login</button> <br><br> 
                    
                    <hr><br>

                </form>
                <button onclick="toggleForm('login')" class="switchbutton">Login as student</button>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="js/login.js" defer></script>
</body>
</html>