<?php
session_start();
require_once "includes/db.php";



// Handle form submission
$message = '';
if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add-class'])){
    $faculty = $_POST['faculty'];
    $batch = $_POST['batch'];
    $status = $_POST['status'];

    $errors = 0;
    if($errors == 0){
        $sql = "INSERT INTO classes(faculty, batch, status) VALUES('$faculty','$batch','$status')";
        $result = mysqli_query($conn, $sql);

        if($result){
            echo "<script>alert('Successfully inserted data');</script>";
            header("Location: classes.php"); 
            exit();  // Ensure no further code is executed after redirect
        }
        else{
            echo "failed";
        }
    
    }
    
}

$active = "classes";


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Class</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<?php include 'includes/adminheader.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center mb-0">Add New Class</h3>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="faculty" class="form-label">Faculty Name*</label>
                                <input type="text" class="form-control" id="faculty" name="faculty" 
                                    value="<?php echo isset($_POST['faculty']) ? htmlspecialchars($_POST['faculty']) : ''; ?>" 
                                    required placeholder="e.g. BIM">
                            </div>
                            <div class="mb-3">
                                <label for="batch" class="form-label">Batch*</label>
                                <input type="text" class="form-control" id="batch" name="batch" 
                                    value="<?php echo isset($_POST['batch']) ? htmlspecialchars($_POST['batch']) : ''; ?>" 
                                    required placeholder="e.g. 2082">
                            </div>
                            <div class="mb-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?= (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : 'selected' ?>>Active</option>
                                    <option value="inactive" <?= (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="classes.php" class="btn btn-secondary me-md-2">Back to Classes</a>
                                <button type="submit" class="btn btn-primary" name="add-class">Add Class</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- <?php include 'includes/footer.php'; ?> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

