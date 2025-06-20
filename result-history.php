<?php
session_start();
include "includes/db.php";

if (!isset($_GET['class_id'])) {
    die("Class ID is missing.");
}
$votername = $_SESSION['stdname'];
$votercrn = $_SESSION['crn'];

$class_id = intval($_GET['class_id']);

// Get current datetime
$currentDateTime = (new DateTime())->modify('+4 hours 45 minutes')->format('Y-m-d H:i:s');

// Get class info and election datetimes
$stmt = $conn->prepare("SELECT e.id as election_id, e.election_start_datetime, e.election_end_datetime, 
                        c.faculty, c.batch
                        FROM elections e
                        JOIN classes c ON e.class_id = c.id
                        WHERE c.id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No election found for this class.");
}

$data = $result->fetch_assoc();
$election_id = $data['election_id'];
$class_name = $data['faculty'] . ' ' . $data['batch'];
$start_datetime = $data['election_start_datetime'];
$end_datetime = $data['election_end_datetime'];

// Determine if results should be shown based on datetime
$showResults = ($currentDateTime > $end_datetime);

// Fetch candidates and their votes
$candidate_stmt = $conn->prepare("SELECT c.id, s.name, s.crn, s.image, c.votes 
                                  FROM candidates c
                                  JOIN students s ON c.student_id = s.id
                                  WHERE s.class_id = ?
                                  ORDER BY c.votes DESC");
$candidate_stmt->bind_param("i", $class_id);
$candidate_stmt->execute();
$candidates = $candidate_stmt->get_result();

// Prepare data for chart
$candidate_stmt->execute();
$chart_data = $candidate_stmt->get_result();
$labels = [];
$votes = [];
while ($row = $chart_data->fetch_assoc()) {
    $labels[] = $row['name'];
    $votes[] = $row['votes'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Result - <?php echo htmlspecialchars($class_name); ?></title>
    <link rel="stylesheet" href="css/main2.css">
    <link rel="stylesheet" href="css/result.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f8f9fa;
        color: #333;
        margin: 0;
        padding: 0;
    }

    .container {
        max-width: 900px;
        margin: 30px auto;
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    h2 {
        text-align: center;
        margin-bottom: 30px;
        color: #004080;
        font-size: 1.8rem;
    }

    .result-wrapper {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    .election-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .election-table th, .election-table td {
        padding: 12px;
        text-align: center;
        border: 1px solid #ddd;
    }

    .election-table th {
        background-color: #004080;
        color: white;
        font-weight: bold;
    }

    .election-table img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
    }

    .election-table .winner {
        background-color: #e8f5e9;
        font-weight: bold;
    }

    .chart-section {
        text-align: center;
    }

    .chart-container {
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
    }

    .election-status {
        text-align: center;
        padding: 40px;
        border-radius: 8px;
        margin: 20px 0;
    }

    .status-ongoing {
        background-color: #e8f5e9;
        border: 1px solid #28a745;
        color: #155724;
    }

    .status-upcoming {
        background-color: #e3f2fd;
        border: 1px solid #004080;
        color: #004080;
    }

    .status-message h3 {
        margin-bottom: 15px;
        font-size: 1.5rem;
    }

    .status-message p {
        font-size: 1.1rem;
        line-height: 1.6;
    }

    .election-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-top: 15px;
        border-left: 4px solid #004080;
    }

    .election-info strong {
        color: #004080;
    }

    @media (max-width: 768px) {
        .container {
            margin: 10px;
            padding: 20px;
        }

        .election-table th, .election-table td {
            padding: 8px;
            font-size: 0.9rem;
        }

        .election-table img {
            width: 50px;
            height: 50px;
        }

        .election-status {
            padding: 30px 20px;
        }
    }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container">
    <h2>Election Result - <?php echo htmlspecialchars($class_name); ?></h2>

    <?php if ($showResults): ?>
        <div class="result-wrapper">
            <div class="table-section">
                <table class="election-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Candidate Name</th>
                            <th>Total Votes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $isFirst = true;
                        while ($row = $candidates->fetch_assoc()):
                            //$image = !empty($row['image']) ? 'data:image/jpeg;base64,' . base64_encode($row['image']) : null;
                            $rowClass = $isFirst ? 'winner' : '';
                            $isFirst = false;
                        ?>
                            <tr class="<?= $rowClass ?>">
                                <td>
                                    <?php if (!empty($row['image']) && file_exists($row['image'])): ?>
                                            <img src="<?= htmlspecialchars($row['image']) ?>" alt="Profile" class="profile-img">
                                        <?php else: ?>

                                            <div class="profile-img" style="background-color: #eee; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user" style="color: #999;"></i>
                                            </div>
                                        <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['crn']) ?>)</td>
                                <td><?= $row['votes'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="chart-section">
                <h3>Vote Distribution</h3>
                <div class="chart-container">
                    <canvas id="pieChart" width="400" height="400"></canvas>
                </div>
            </div>
        </div>
    <?php elseif ($currentDateTime >= $start_datetime && $currentDateTime <= $end_datetime): ?>
        <div class="election-status status-ongoing">
            <div class="status-message">
                <h3>Election in Progress</h3>
                <p>This election is currently ongoing. Results will be available after the election ends.</p>
                
                <div class="election-info">
                    <p><strong>Election Started:</strong> <?= date('M j, Y g:i A', strtotime($start_datetime)) ?></p>
                    <p><strong>Election Ends:</strong> <?= date('M j, Y g:i A', strtotime($end_datetime)) ?></p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="election-status status-upcoming">
            <div class="status-message">
                <h3>Election Not Started</h3>
                <p>This election has not started yet. Results will be available after the election ends.</p>
                
                <div class="election-info">
                    <p><strong>Election Starts:</strong> <?= date('M j, Y g:i A', strtotime($start_datetime)) ?></p>
                    <p><strong>Election Ends:</strong> <?= date('M j, Y g:i A', strtotime($end_datetime)) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
<?php if ($showResults): ?>
const ctx = document.getElementById('pieChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            data: <?= json_encode($votes) ?>,
            backgroundColor: ['#004080', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 20,
                    padding: 15
                }
            }
        }
    }
});
<?php endif; ?>
</script>
</body>
</html>

<?php $conn->close(); ?>