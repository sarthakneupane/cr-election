<?php

    session_start();

    if (!isset($_SESSION['admname'])) {
        header("Location: login.php");
        exit();
    }

    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "crelection";

    $conn = mysqli_connect($servername, $username, $password, $database);

    if($_SERVER['REQUEST_METHOD'] == "POST"){
        if(isset($_POST['updatebutton'])){

        $crn = $_POST['crn'];
        $name = $_POST['name'];
        $faculty = $_POST['faculty'];
        $batch = $_POST['batch'];

        $error = array();
        $errors = 0;

        if(empty($crn)){
            $error['crn_error'] = "CRN cannot be empty";
            $errors++;
        }
        

        if($errors == 0){
            $sql = "UPDATE students SET crn = '$crn', name = '$name', faculty = '$faculty', batch = '$batch' WHERE crn = '{$_SESSION['update_crn']}'";

            $result = mysqli_query($conn, $sql);

            if($result){
                echo "<script>alert('Successfully updated data');</script>";
                echo "<script>window.location.href='studentlist.php';</script>";
                exit();  // Ensure no further code is executed after redirect
            }
        
        }
        

        }
        
        if(isset($_POST['logoutbutton'])){
            session_unset(); 
            session_destroy();
            header("Location: login.php");
            exit();
        }
        
    }

    $active="stdlist";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/main2.css">
    <link rel="stylesheet" href="css/entryform.css">
</head>
<body>
    <?php include 'includes/adminheader.php'; ?>

    <div class="container">
        
        <form method="POST" class="form-container">
        <h2>Student update form</h2>
            <input type="number" name="crn" value  = "<?php echo $_SESSION['update_crn'] ?>"> <br>
            <input type="text" name="name" value  = "<?php echo $_SESSION['update_name'] ?>"> <br>
            Faculty:<select name="faculty" style="width: 200px; height: 30px;">
                <option>BIM</option>
                <option>BCA</option>
                <option>BHM</option>
                <option>BSC. CSIT</option>
                <option>BBS</option>
            </select>
            <input type="number" name="batch" value  = "<?php echo $_SESSION['update_batch'] ?>"> <br>
            <button type="submit" class="submit" name ="updatebutton">Update</button>
        </form>
    </div> 
    <?php include 'includes/footer.php'; ?>
</body>
</html>