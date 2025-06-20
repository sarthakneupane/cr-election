<?php
session_start();

if (!isset($_SESSION['admname'])) {
    header("Location: login.php");
    exit();
}

include "includes/db.php";

$errors = array();

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $crn = $_POST['crn'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $class = $_POST['class'];
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    $profile_image = $_FILES['profile_image']['tmp_name'];

    $sql2 = "SELECT * FROM students";
    $result2 = mysqli_query($conn, $sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $dbcrn = $row2['crn'];
            }
        }

    // Validate CRN
    if (empty($crn)) {
        $errors['crn_error'] = "CRN cannot be empty";
    }
    

    // Validate CRN
if (empty($crn)) {
    $errors['crn_error'] = "CRN cannot be empty";
} else {
    $crn = trim($crn);
    $check = mysqli_query($conn, "SELECT * FROM students WHERE crn = '$crn'");
    if (mysqli_num_rows($check) > 0) {
        $errors['crn_error'] = "Student CRN already exists";
    }
}


    // Validate Email
    if (empty($email)) {
        $errors['email_error'] = "Email cannot be empty";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email_error'] = "Invalid email format";
    }

    // Validate Password and Confirm Password
    if (empty($password)) {
        $errors['password_error'] = "Password cannot be empty";
    } elseif ($password != $cpassword) {
        $errors['password_error'] = "Passwords do not match";
    }

    //Validate Profile Image
    if (empty($profile_image)) {
        $errors['image_error'] = "Profile image cannot be empty";
    }

    if (empty($class)) {
        $errors['class_error'] = "Class cannot be empty";
    }

    // If there are no errors, proceed with database insertion
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $image_name = $_FILES['profile_image']['name'];
        $image_tmp = $_FILES['profile_image']['tmp_name'];
        $image_path = 'uploads/' . time() . '_' . basename($image_name);

        move_uploaded_file($image_tmp, $image_path);

        $admin_id = $_SESSION['admid']; 

        $sql = "INSERT INTO students (crn, name, class_id, email, password, image, admin_id) 
        VALUES ('$crn', '$name', '$class', '$email', '$hashed_password', '$image_path', '$admin_id')";


        
        $result = mysqli_query($conn, $sql);

        

        if ($result) {
            // Store email details in session for mail.php
            $_SESSION['mail_email'] = $email;
            $_SESSION['mail_name'] = $name;
            $_SESSION['mail_crn'] = $crn;
            $_SESSION['mail_password'] = $password;
            $_SESSION['student_registered'] = true;

        
            header("Location: mail.php");
            exit();
        }
        

    } else {
        foreach ($errors as $error) {
            echo "<script>alert('$error');</script>";
        }
    }
}

if (isset($_POST['logoutbutton'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch active classes for dropdown
$classes = [];
$class_query = $conn->query("SELECT id, faculty, batch FROM classes WHERE status = 'active' ORDER BY faculty, batch");
while ($row = $class_query->fetch_assoc()) {
    $classes[] = $row;
}

$active = "stdlist";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Entry</title>
    
    <link rel="stylesheet" href="css/entryform.css">
</head>
<body>
    <?php include 'includes/adminheader.php'; ?>

    <div class="container">
    <form method="POST" class="form-wrapper" enctype="multipart/form-data">
        <h2>Student Entry Form</h2>
        
        <!-- CRN Field -->
        <label for="crn">CRN No.</label>
        <input type="number" name="crn" id="crn" placeholder="Enter CRN" value="<?= isset($_POST['crn']) ? htmlspecialchars($_POST['crn']) : '' ?>" required>
        <?php if(isset($errors['crn_error'])): ?>
            <span class="error-message"><?= $errors['crn_error'] ?></span>
        <?php endif; ?>
        
        <!-- Name Field -->
        <label for="name">Full Name</label>
        <input type="text" name="name" id="name" placeholder="Enter full name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
        
        <!-- Class Field -->
        <label for="class">Class</label>
        <select name="class" id="class" required>
            <option value="">Select Class</option>
            <?php foreach ($classes as $class): ?>
                <option value="<?= $class['id'] ?>" 
                    <?= (isset($_POST['class']) && $_POST['class'] == $class['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($class['faculty']) ?> - <?= htmlspecialchars($class['batch']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if(isset($errors['class_error'])): ?>
            <span class="error-message"><?= $errors['class_error'] ?></span>
        <?php endif; ?>
        
        <!-- Email Field -->
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="Enter email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
        <?php if(isset($errors['email_error'])): ?>
            <span class="error-message"><?= $errors['email_error'] ?></span>
        <?php endif; ?>
        
        <!-- Password Field -->
        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter password" required>
        
        <!-- Confirm Password Field -->
        <label for="cpassword">Confirm Password</label>
        <input type="password" name="cpassword" id="cpassword" placeholder="Confirm password" required>
        <?php if(isset($errors['password_error'])): ?>
            <span class="error-message"><?= $errors['password_error'] ?></span>
        <?php endif; ?>
        
        <!-- Profile Image Field -->
        <label for="profile_image">Profile Image</label>
        <input type="file" name="profile_image" id="profile_image" accept="image/*" required>
        <?php if(isset($errors['image_error'])): ?>
            <span class="error-message"><?= $errors['image_error'] ?></span>
        <?php endif; ?>
        
        <button type="submit" class="submit">Add Student</button>
    </form>

    <div style="margin-bottom: 15px;">
        <a href="studentslist.php" class="back-button">← Back to Student List</a>
    </div>
</div>
    <!-- <?php include 'includes/footer.php'; ?> -->
</body>
</html>

<?php
$conn->close();
?>
