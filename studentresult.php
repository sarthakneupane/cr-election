<?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['stdname'])) {
    header("Location: login.php");
    exit();
}

$votername = $_SESSION['stdname'];
$votercrn = $_SESSION['crn'];

// Get current datetime
$currentDateTime = (new DateTime())->modify('+4 hours 45 minutes')->format('Y-m-d H:i:s');

$sql = "SELECT id, class_id FROM students WHERE crn = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $votercrn);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Student not found.");
}

$student = $result->fetch_assoc();
$class_id = $student['class_id'];

// Get election info with datetime columns instead of status
$stmt = $conn->prepare("SELECT e.id as election_id, e.election_start_datetime, e.election_end_datetime, c.faculty, c.batch
                        FROM elections e
                        JOIN classes c ON e.class_id = c.id
                        WHERE c.id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No election found for your class.");
}

$data = $result->fetch_assoc();
$class_name = $data['faculty'] . ' ' . $data['batch'];
$election_start = $data['election_start_datetime'];
$election_end = $data['election_end_datetime'];

// Determine if election is completed based on datetime
$isCompleted = ($currentDateTime > $election_end);

// If election is not completed, redirect to appropriate page
if (!$isCompleted) {
    if ($currentDateTime < $election_start) {
        // Election hasn't started yet
        header("Location: student_dashboard.php");
        exit();
    } elseif ($currentDateTime >= $election_start && $currentDateTime <= $election_end) {
        // Election is ongoing
        header("Location: votingpage.php");
        exit();
    }
}

$candidate_stmt = $conn->prepare("SELECT c.id, s.name, s.crn, s.image, c.votes 
                                  FROM candidates c
                                  JOIN students s ON c.student_id = s.id
                                  WHERE s.class_id = ?
                                  ORDER BY c.votes DESC");
$candidate_stmt->bind_param("i", $class_id);
$candidate_stmt->execute();
$candidates = $candidate_stmt->get_result();

$candidate_stmt->execute();
$chart_data = $candidate_stmt->get_result();
$labels = [];
$votes = [];
$backgroundColors = ['#004080', '#0066cc', '#3399ff', '#66b3ff', '#99ccff', '#cce6ff'];
$i = 0;
while ($row = $chart_data->fetch_assoc()) {
    $labels[] = $row['name'] . " (" . $row['votes'] . " votes)";
    $votes[] = $row['votes'];
    $i++;
    if ($i >= count($backgroundColors)) $i = 0;
}

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logoutbutton'])) {
    session_unset();  // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: login.php"); // Redirect to login page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - <?php echo htmlspecialchars($class_name); ?></title>
    <link rel="stylesheet" href="css/studentresult.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #fafafa;
            color: #333;
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .vp-header {
            background: #004080;
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }

        .vp-header h1 {
            font-size: 2rem;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .vp-header .vp-class-info {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .vp-content {
            background: white;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e5e5e5;
            border-top: none;
            overflow: hidden;
        }

        .vp-back-link {
            display: inline-block;
            margin: 20px 30px 0;
            color: #004080;
            text-decoration: none;
            font-size: 14px;
        }

        .vp-back-link:hover {
            text-decoration: underline;
        }

        .result-wrapper {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            padding: 30px;
            align-items: start;
        }

        .table-section {
            min-width: 0;
        }

        .election-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e5e5e5;
        }

        .election-table thead {
            background: #004080;
            color: white;
        }

        .election-table th,
        .election-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e5e5;
        }

        .election-table th {
            font-weight: 500;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .election-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .election-table tbody tr.winner {
            background-color: #e8f5e8;
            border-left: 4px solid #2e7d32;
        }

        .election-table tbody tr.winner:hover {
            background-color: #e1f5e1;
        }

        .election-table img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e5e5;
        }

        .election-table tbody tr.winner img {
            border-color: #2e7d32;
        }

        .winner-announcement {
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .winner-announcement.won {
            background-color: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .winner-announcement.participated {
            background-color: #e3f2fd;
            color: #004080;
            border: 1px solid #bbdefb;
        }

        .winner-announcement.general {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .chart-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e5e5e5;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .chart-title {
            text-align: center;
            margin-bottom: 20px;
            color: #004080;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .election-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #004080;
        }

        .election-info h4 {
            color: #004080;
            margin-bottom: 8px;
            font-size: 1rem;
            font-weight: 500;
        }

        .election-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 0 10px;
            }

            .vp-header {
                padding: 20px;
            }

            .vp-header h1 {
                font-size: 1.5rem;
            }

            .result-wrapper {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 20px;
            }

            .chart-section {
                order: -1;
            }

            .election-table th,
            .election-table td {
                padding: 10px 8px;
                font-size: 14px;
            }

            .election-table img {
                width: 40px;
                height: 40px;
            }

            .chart-container {
                height: 250px;
            }
        }

        @media (max-width: 480px) {
            .election-table {
                font-size: 12px;
            }

            .election-table th,
            .election-table td {
                padding: 8px 6px;
            }

            .winner-announcement {
                font-size: 1rem;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="vp-header">
        <h1>Election Results</h1>
        <div class="vp-class-info"><?php echo htmlspecialchars($class_name); ?></div>
    </div>

    <div class="vp-content">
        <a href="student_dashboard.php" class="vp-back-link">← Back to Dashboard</a>

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
$candidates->data_seek(0);

while ($row = $candidates->fetch_assoc()):
    $imagePath = (!empty($row['image']) && file_exists($row['image'])) 
        ? htmlspecialchars($row['image']) 
        : null;

    $rowClass = $isFirst ? 'winner' : '';

    if ($isFirst) {
        $winnerName = htmlspecialchars($row['name']);
        $winnerCrn = htmlspecialchars($row['crn']);
        $winnerVotes = $row['votes'];
    }

    $isFirst = false;
?>
    <tr class="<?= $rowClass ?>">
        <td>
            <?php if ($imagePath): ?>
                <img src="<?= $imagePath ?>" alt="Candidate Image" style="width:50px; height:50px; border-radius:50%; object-fit:cover;">
            <?php else: ?>
                <div style="width: 50px; height: 50px; background: #e5e5e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">N/A</div>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['crn']) ?>)</td>
        <td><strong><?= $row['votes'] ?></strong></td>
    </tr>
<?php endwhile; ?>

                    </tbody>
                </table>

                <?php
                // Check if logged-in student is a candidate and determine their result
                $isCandidate = false;
                $hasWon = false;

                $candidates->data_seek(0); // Reset pointer to start
                while ($row = $candidates->fetch_assoc()) {
                    if ($row['crn'] === $votercrn) {
                        $isCandidate = true;
                        if ($row['crn'] === $winnerCrn) {
                            $hasWon = true;
                        }
                        break;
                    }
                }

                if ($isCandidate):
                ?>
                    <div class="winner-announcement <?= $hasWon ? 'won' : 'participated' ?>">
                        <?php if ($hasWon): ?>
                            🎉 <strong>Congratulations <?php echo htmlspecialchars($votername); ?>!</strong> You have won the election with <?php echo $winnerVotes; ?> votes! 🎉
                        <?php else: ?>
                            💙 <strong>Thank you for participating <?php echo htmlspecialchars($votername); ?>.</strong> Your contribution to the democratic process is valued!
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="winner-announcement general">
                        🎉 <strong><?php echo htmlspecialchars($winnerName); ?></strong> has won the election with <?php echo $winnerVotes; ?> votes! 🎉
                    </div>
                <?php endif; ?>

                <div class="election-info">
                    <h4>Election Details</h4>
                    <p><strong>Started:</strong> <?= date('F j, Y g:i A', strtotime($election_start)); ?></p>
                    <p><strong>Ended:</strong> <?= date('F j, Y g:i A', strtotime($election_end)); ?></p>
                    <p><strong>Status:</strong> Completed</p>
                </div>
            </div>

            <div class="chart-section">
                <div class="chart-title">Vote Distribution</div>
                <div class="chart-container">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('pieChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            data: <?= json_encode($votes) ?>,
            backgroundColor: <?= json_encode($backgroundColors) ?>,
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 15,
                    padding: 10,
                    font: {
                        size: 11
                    },
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                        return `${label}: ${percentage}%`;
                    }
                }
            }
        },
        elements: {
            arc: {
                borderWidth: 2
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>

<?php $conn->close(); ?>