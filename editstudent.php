<?php
include "includes/db.php";

if (!isset($_GET['crn'])) {
    die("Student not specified.");
}

$old_crn = $_GET['crn'];
$stmt = $conn->prepare("SELECT crn, name, email FROM students WHERE crn = ?");
$stmt->bind_param("s", $old_crn);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_crn  = $_POST['crn'];
    $new_name = $_POST['name'];
    $new_email = $_POST['email'];

    $update = $conn->prepare("UPDATE students SET crn = ?, name = ?, email = ? WHERE crn = ?");
    $update->bind_param("ssss", $new_crn, $new_name, $new_email, $old_crn);

    if ($update->execute()) {
        header("Location: studentlist.php?msg=updated");
        exit();
    } else {
        echo "Error updating student: " . $conn->error;
    }
}
$active = "stdlist";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student</title>
    <link rel="stylesheet" href="css/adminheader.css">
    <style>
        .edit-form-container {
            background-color: #fff;
            max-width: 400px;
            margin: 80px auto;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .edit-form-container label {
            display: block;
            margin-bottom: 15px;
            font-size: 16px;
            color: #333;
        }
        .edit-form-container input[type="text"],
        .edit-form-container input[type="email"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 15px;
        }
        .edit-form-container button[type="submit"] {
            background-color: #007BFF;
            color: #fff;
            padding: 10px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        .edit-form-container button[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<?php include 'includes/adminheader.php'; ?>

<div class="edit-form-container">
    <h2>Edit Student</h2>
    <form method="POST">
        <label>
            CRN:
            <input type="text" name="crn" value="<?= htmlspecialchars($student['crn']) ?>" required>
        </label>
        <label>
            Name:
            <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
        </label>
        <label>
            Email:
            <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
        </label>
        <button type="submit">Update</button>
    </form>
</div>
</body>
</html>
