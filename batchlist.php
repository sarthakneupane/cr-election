<?php
    session_start();

    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "crelection";

    $conn = new mysqli($servername, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch classes where class_status is 1
    $sql = "SELECT * FROM election WHERE class_status = 1";
    $result = $conn->query($sql);

    if (!isset($_SESSION['admname'])) {
        header("Location: login.php");
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] == "POST"){
        if(isset($_POST['class'])){
            $_SESSION['class'] = $_POST['class'];
            header("Location: studentlist.php");
            exit();
        }

        if(isset($_POST['logoutbutton'])){
            session_unset(); 
            session_destroy();
            header("Location: login.php");
            exit();
        }
    }

    $active = "stdlist";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Entry Page</title>
    <link rel="stylesheet" href="css/main2.css">
    <link rel="stylesheet" href="css/batchlist.css">
</head>
<body>
    <?php include 'includes/adminheader.php'; ?>
    <div class="container">
        <table>
            <tr>
                <td colspan="5" style="background-color: #afd9de; border-radius: 10px 10px 0px 0px; font-size:25px;"> Select Faculty and Batch </td>
            </tr>
            <tr>
                <td> BIM </td>
                <td> BCA </td>
                <td> BHM </td>
                <td> BSC CSIT </td>
                <td> BBS </td>
            </tr>

            <tr>
                <?php 
                $faculties = ['BIM', 'BCA', 'BHM', 'CSIT', 'BBS'];
                foreach ($faculties as $faculty) {
                    echo "<td>";
                    if ($result->num_rows > 0) {
                        $result->data_seek(0); // Reset result pointer to the beginning
                        while ($row = $result->fetch_assoc()) {
                            // Check if the class belongs to the current faculty
                            if (strpos($row['class'], $faculty) !== false) {
                                echo '<form method="post">
                                        <button type="submit" name="class" value="'.$row['class'].'" class="cell"><p class="link">'.$row['class'].'</p></button></br>
                                      </form>';
                            }
                        }
                    }
                    echo "</td>";
                }
                ?>
            </tr>
        </table>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
