<?php
session_start();
include "includes/db.php";

$votername = $_SESSION['stdname'];
$votercrn = $_SESSION['crn'];


// Get current datetime
$currentDateTime = (new DateTime())->modify('+4 hours 45 minutes')->format('Y-m-d H:i:s');

// Fetch elections with datetime-based status determination
$sql = "SELECT e.id, e.class_id, e.election_start_datetime, e.election_end_datetime,
               CONCAT(c.faculty, ' ', c.batch) AS class_name,
               CASE 
                   WHEN ? < e.election_start_datetime THEN 'upcoming'
                   WHEN ? >= e.election_start_datetime AND ? <= e.election_end_datetime THEN 'ongoing'
                   ELSE 'completed'
               END AS current_status
        FROM elections e
        JOIN classes c ON e.class_id = c.id
        ORDER BY e.election_start_datetime DESC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('MySQL prepare error: ' . $conn->error);
}

$stmt->bind_param("sss", $currentDateTime, $currentDateTime, $currentDateTime);
$stmt->execute();
$electionsResult = $stmt->get_result();

if ($electionsResult === false) {
    die('Error fetching results: ' . $conn->error);
}


?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Elections</title>
    <link rel="stylesheet" href="css/manageelection.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.5;
        }

        .manage-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .page-header h2 {
            color: #004080;
            font-size: 1.8rem;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
        }

        .new-election-btn,
        .history-btn {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            background: #004080;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .new-election-btn:hover,
        .history-btn:hover {
            background: #003366;
            transform: translateY(-1px);
        }

        .new-election-btn i,
        .history-btn i {
            margin-right: 8px;
        }

        .election-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .election-table thead {
            background: #004080;
            color: white;
        }

        .election-table th,
        .election-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .election-table th {
            font-weight: 500;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .election-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .election-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-upcoming {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        .status-ongoing {
            background: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }

        .status-completed {
            background: #f3e5f5;
            color: #7b1fa2;
            border: 1px solid #e1bee7;
        }

        .datetime-info {
            font-size: 0.9rem;
            color: #666;
            margin-top: 4px;
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            background: #f8f9fa;
            color: #004080;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid #e9ecef;
        }

        .action-link:hover {
            background: #004080;
            color: white;
            transform: translateY(-1px);
        }

        .action-link i {
            margin-right: 6px;
        }

        .candidate-btn {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }

        .candidate-btn:hover {
            background: #218838;
            color: white;
        }

        .countdown-timer {
            font-size: 0.8rem;
            color: #666;
            font-family: 'Courier New', monospace;
            margin-top: 2px;
        }

        .countdown-timer.urgent {
            color: #dc3545;
            font-weight: 600;
        }

        .auto-status-info {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            color: #0c5460;
        }

        .auto-status-info i {
            margin-right: 8px;
            color: #17a2b8;
        }

        .no-elections {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-elections i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .manage-page {
                padding: 10px;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .election-table {
                font-size: 0.9rem;
            }

            .election-table th,
            .election-table td {
                padding: 10px 8px;
            }

            .election-table th:nth-child(3),
            .election-table td:nth-child(3) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="manage-page">

        <?php if ($electionsResult->num_rows > 0): ?>
            <table class="election-table">
                <thead>
                    <tr>
                        <th>S No.</th>
                        <th>Class</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Results</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $serial = 1; ?>
                    <?php while ($election = $electionsResult->fetch_assoc()): ?>
                    <tr data-election-id="<?php echo $election['id']; ?>">
                        <td><?php echo $serial++; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($election['class_name']); ?></strong>
                        </td>
                        <td>
                            <div>
                                <strong>Start:</strong> <?php echo date('M j, Y g:i A', strtotime($election['election_start_datetime'])); ?>
                            </div>
                            <div class="datetime-info">
                                <strong>End:</strong> <?php echo date('M j, Y g:i A', strtotime($election['election_end_datetime'])); ?>
                            </div>
                            <?php if ($election['current_status'] === 'upcoming'): ?>
                                <div class="countdown-timer" data-target="<?php echo $election['election_start_datetime']; ?>" data-type="start">
                                    Calculating...
                                </div>
                            <?php elseif ($election['current_status'] === 'ongoing'): ?>
                                <div class="countdown-timer urgent" data-target="<?php echo $election['election_end_datetime']; ?>" data-type="end">
                                    Calculating...
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $election['current_status']; ?>">
                                <?php echo ucfirst($election['current_status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="result-history.php?class_id=<?php echo $election['class_id']; ?>" class="action-link">
                                <i class="fas fa-chart-bar"></i> View Results
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-elections">
                <i class="fas fa-vote-yea"></i>
                <h3>No Elections Found</h3>
                <p>There are currently no elections in the system.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update countdown timers
            function updateCountdowns() {
                const countdownElements = document.querySelectorAll('.countdown-timer[data-target]');
                
                countdownElements.forEach(element => {
                    const targetDate = new Date(element.getAttribute('data-target')).getTime();
                    const now = new Date().getTime();
                    const distance = targetDate - now;
                    const type = element.getAttribute('data-type');

                    if (distance > 0) {
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        let countdownText = '';
                        if (days > 0) {
                            countdownText = `${days}d ${hours}h ${minutes}m`;
                        } else if (hours > 0) {
                            countdownText = `${hours}h ${minutes}m ${seconds}s`;
                        } else if (minutes > 0) {
                            countdownText = `${minutes}m ${seconds}s`;
                        } else {
                            countdownText = `${seconds}s`;
                        }

                        if (type === 'start') {
                            element.textContent = `Starts in: ${countdownText}`;
                        } else {
                            element.textContent = `Ends in: ${countdownText}`;
                        }

                        // Add urgent class if less than 1 hour remaining
                        if (distance < 3600000) { // 1 hour in milliseconds
                            element.classList.add('urgent');
                        }
                    } else {
                        if (type === 'start') {
                            element.textContent = 'Election has started!';
                        } else {
                            element.textContent = 'Election has ended!';
                        }
                        element.style.color = '#dc3545';
                        element.classList.add('urgent');
                    }
                });
            }

            // Update countdowns immediately and then every second
            updateCountdowns();
            setInterval(updateCountdowns, 1000);

            // Refresh page every 5 minutes to update election statuses
            setTimeout(() => {
                location.reload();
            }, 300000);
        });
    </script>
</body>
</html>

<?php 
$stmt->close();
$conn->close(); 
?>