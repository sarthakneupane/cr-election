<?php
include "includes/db.php";

// Get class ID
$class_id = $_GET['class_id'] ?? 0;

// Handle approve/reject form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = intval($_POST['request_id']);
    $status = intval($_POST['status']); // force to int

    $updateSql = "UPDATE candidates SET status = ? WHERE id = ?";
    $stmtUpdate = $conn->prepare($updateSql);
    $stmtUpdate->bind_param("ii", $status, $request_id);

    if ($stmtUpdate->execute()) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        $message = "Error updating candidate: " . $stmtUpdate->error;
        echo $message;
    }
}

// Fetch class info
$class_sql = "SELECT faculty, batch FROM classes WHERE id = ?";
$class_stmt = $conn->prepare($class_sql);
$class_stmt->bind_param("i", $class_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$class_info = $class_result->fetch_assoc();

// Fetch candidates with supporter names
$sql = "SELECT 
            cr.id AS request_id,
            cr.status,
            cr.manifesto AS message,
            s.name,
            s.crn,
            sup1.name AS supporter1_name,
            sup2.name AS supporter2_name
        FROM candidates cr
        JOIN students s ON cr.student_id = s.id
        LEFT JOIN students sup1 ON cr.supporter_1_id = sup1.id
        LEFT JOIN students sup2 ON cr.supporter_2_id = sup2.id
        WHERE s.class_id = ?
        AND cr.supporter_1_status = 1
        AND cr.supporter_2_status = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

$active = "manageelection";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Candidate Requests</title>
    <link rel="stylesheet" href="css/main2.css">
    <style>
      /* General Styles */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f6f9;
    color: #333;
    margin: 0;
    padding: 0;
}

.main-content h2 {
    color: #333;
    font-size: 2rem;
    font-weight: 600;
    text-align: center;
    margin-top: 30px;
    margin-bottom: 20px;
}

/* Card Styles */
.main-content .card {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    margin: 20px auto;
    width: 100%;
    text-align: center;
}

.main-content .card h3 {
    font-size: 1.5rem;
    color: #004080;
    margin-bottom: 10px;
}

.main-content .card p {
    font-size: 1rem;
    color: #555;
}

/* Table Styles */
.main-content .candidate-table {
    width: 100%;
    margin: 30px auto;
    border-collapse: collapse;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
}

.main-content th,
.main-content td {
    padding: 15px;
    border: 1px solid #ddd;
    text-align: center;
}

.main-content th {
    background-color: #004080;
    color: white;
    font-size: 1.1rem;
}

.main-content td {
    font-size: 1rem;
}

/* Row Colors */
.main-content .pending {
    color: orange;
}

.main-content .selected {
    color: green;
}

.main-content .rejected {
    color: red;
}

/* Button Styles */
.main-content .btn {
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.main-content .approve {
    background-color: #28a745;
    color: white;
}

.main-content .reject {
    background-color: #dc3545;
    color: white;
}

.main-content .approve:hover {
    background-color: #218838;
}

.main-content .reject:hover {
    background-color: #c82333;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .main-content table {
        width: 100%;
    }

    .main-content th,
    .main-content td {
        padding: 8px;
        font-size: 0.9rem;
    }

    .main-content h2 {
        font-size: 1.5rem;
    }

    .main-content .btn {
        padding: 6px 12px;
    }
}

/* Desktop Styles */
@media (min-width: 769px) {
    .main-content table {
        width: 80%;
    }

    .main-content h2 {
        font-size: 2rem;
    }

    .main-content th,
    .main-content td {
        padding: 15px;
        font-size: 1rem;
    }
}

    </style>
</head>
<body>

<?php include "includes/adminheader.php"; ?>


<div class="main-content">



<!-- Card Section -->
<div class="card">
    <h3>Class: <?php echo htmlspecialchars($class_info['faculty']) . ' ' . htmlspecialchars($class_info['batch']); ?></h3>
    <p>Please review and manage candidate requests for this class below.</p>
</div>

<!-- Candidate Table -->
<?php if (isset($message)) echo "<p class='message'>$message</p>"; ?>

<table class="candidate-table">
    <thead>
        <tr>
            <th>CRN</th>
            <th>Name</th>
            <th>Manifesto</th>
            <th>Supporter 1</th>
            <th>Supporter 2</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['crn']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
            <td><?= htmlspecialchars($row['supporter1_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['supporter2_name'] ?? 'N/A') ?></td>
            <td>
                <?php
                    if ($row['status'] == 0) echo "<span class='pending'>Pending</span>";
                    elseif ($row['status'] == 1) echo "<span class='selected'>Selected</span>";
                    elseif ($row['status'] == 2) echo "<span class='rejected'>Rejected</span>";
                ?>
            </td>
            <td>
                <?php if ($row['status'] == 0): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                        <input type="hidden" name="status" value="1">
                        <button type="submit" name="update_status" class="btn approve">Approve</button>
                    </form>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                        <input type="hidden" name="status" value="2">
                        <button type="submit" name="update_status" class="btn reject">Reject</button>
                    </form>
                <?php else: ?>
                    No Action Available
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>
</body>
</html>
