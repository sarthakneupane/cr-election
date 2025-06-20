<?php
session_start();
require_once "includes/db.php";

// Validate & fetch class ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: classes.php");
    exit();
}

$class_id = intval($_GET['id']);

// Fetch class details
$stmt = $conn->prepare("SELECT faculty, batch, status FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Class not found.";
    exit();
}

$class = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faculty = trim($_POST['faculty']);
    $batch = trim($_POST['batch']);
    $status = $_POST['status'];

    // Basic validation
    if ($faculty && $batch && in_array($status, ['active', 'inactive'])) {
        $update = $conn->prepare("UPDATE classes SET faculty = ?, batch = ?, status = ? WHERE id = ?");
        $update->bind_param("sssi", $faculty, $batch, $status, $class_id);
        if ($update->execute()) {
            header("Location: classes.php");
            exit();
        } else {
            $error = "Update failed: " . $conn->error;
        }
        $update->close();
    } else {
        $error = "All fields are required.";
    }
}
$active="classes";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Class</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/adminheader.php'; ?>

<div class="container mt-5">
    <h3>Edit Class</h3>
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="faculty" class="form-label">Faculty</label>
            <input type="text" name="faculty" id="faculty" class="form-control" value="<?= htmlspecialchars($class['faculty']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="batch" class="form-label">Batch</label>
            <input type="text" name="batch" id="batch" class="form-control" value="<?= htmlspecialchars($class['batch']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="active" <?= $class['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $class['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Update Class</button>
        <a href="classes.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>

<?php $conn->close(); ?>
