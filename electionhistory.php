<?php
session_start();
include "includes/db.php";

// Optional admin check
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Fetch only finished elections
$sql = "SELECT e.id, e.class_id, e.election_date, e.status, e.showresult, 
               CONCAT(c.faculty, ' ', c.batch) AS class_name
        FROM elections e
        JOIN classes c ON e.class_id = c.id
        WHERE e.status = 'finished'";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('MySQL prepare error: ' . $conn->error);
}

$stmt->execute();
$electionsResult = $stmt->get_result();

$active = "electionhistory";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election History</title>
    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    background-color: #f9f9f9;
}

.container {
    max-width: 2000px; /* increased from 1000px */
    margin: 30px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow-x: auto; /* adds scroll if table is wider than screen */
}

.election-table {
    width: 100%;
    min-width: 1500px; /* ensures the table is wide */
    border-collapse: collapse;
    margin-top: 10px;
}


.page-header h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #333;
}

.election-table thead {
    background-color: #004080;
    color: white;
}

.election-table th, 
.election-table td {
    padding: 12px;
    text-align: center;
    border: 1px solid #ddd;
}

.election-table tbody tr:nth-child(even) {
    background-color: #f2f2f2;
}

.election-table tbody tr:hover {
    background-color: #e9f5ff;
}

.candidate-btn {
    background-color: #28a745;
    color: white;
    padding: 6px 10px;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
}

.candidate-btn:hover {
    background-color: #218838;
}

a {
    color: #007BFF;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

    </style>
</head>
<body>
    <?php include 'includes/adminheader.php'; ?>

    <div class="container">
        <div class="page-header">
            <h2>Election History</h2>
        </div>

        <table class="election-table">
            <thead>
                <tr>
                    <th>S No.</th>
                    <th>Class</th>
                    <th>Election Date</th>
                    <th>Status</th>
                    <th>Results</th>
                    <th>Candidates</th>
                </tr>
            </thead>
            <tbody>
                <?php $serial = 1; ?>
                <?php while ($election = $electionsResult->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $serial++; ?></td>
                    <td><?php echo htmlspecialchars($election['class_name']); ?></td>
                    <td><?php echo htmlspecialchars($election['election_date']); ?></td>
                    <td><?php echo ucfirst($election['status']); ?></td>
                    <td>
                        <a href="result.php?class_id=<?php echo $election['class_id']; ?>">Result</a>
                    </td>
                    <td>
                        <a href="candidates.php?class_id=<?php echo $election['class_id']; ?>" class="candidate-btn">Candidates</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- <?php include 'includes/footer.php'; ?> -->
</body>
</html>

<?php $conn->close(); ?>
