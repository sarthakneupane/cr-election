<?php
session_start();
include "includes/db.php";

// Check if the user is logged in as admin
if (!isset($_SESSION['admname'])) {
    header("Location: login.php");
    exit();
}

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

$active = "manageelection";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Elections</title>
    <link rel="stylesheet" href="css/manageelection.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Student List Page Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
        }

        .container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 25px 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h2 {
            color: #004080;
            font-size: 24px;
            margin: 0;
        }

        /* Election List Container */
        .election-list-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 30px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
        }

        .add-election-btn, .history-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: #004080;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .add-election-btn:hover, .history-btn:hover {
            background-color: #0066cc;
            transform: translateY(-2px);
        }

        /* Info Box */
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0d47a1;
        }

        /* Election Table */
        .election-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .election-table th,
        .election-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .election-table th {
            background-color: #f8f9fa;
            color: #004080;
            font-weight: 600;
        }

        .election-table tr:hover {
            background-color: #f8fafd;
        }

        /* Status Badges */
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

        /* Action Buttons */
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .view-btn {
            background-color: #004080;
            color: white;
        }

        .view-btn:hover {
            background-color: #0066cc;
        }

        .manage-btn {
            background-color: #28a745;
            color: white;
        }

        .manage-btn:hover {
            background-color: #218838;
        }

        /* Countdown Timer */
        .countdown-timer {
            font-size: 0.8rem;
            color: #666;
            font-family: 'Courier New', monospace;
            margin-top: 5px;
        }

        .countdown-timer.urgent {
            color: #dc3545;
            font-weight: 600;
        }

        /* No Elections Message */
        .no-elections {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 16px;
        }

        .no-elections i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .add-election-btn, .history-btn {
                width: 100%;
                justify-content: center;
            }
            
            .election-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 480px) {
            .election-table th,
            .election-table td {
                padding: 8px 10px;
                font-size: 14px;
            }
            
            .action-btn {
                padding: 4px 8px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/adminheader.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-vote-yea"></i> Manage Elections</h2>
                <div class="action-buttons">
                    <a href="addelection.php" class="add-election-btn">
                        <i class="fas fa-plus"></i> New Election
                    </a>
                    <a href="electionhistory.php" class="history-btn">
                        <i class="fas fa-history"></i> Election History
                    </a>
                </div>
            </div>

            <div class="election-list-container">
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Automated Status Management:</strong> Election statuses are automatically determined based on start and end times.</span>
                </div>

                <?php if ($electionsResult->num_rows > 0): ?>
                    <table class="election-table">
                        <thead>
                            <tr>
                                <th>S No.</th>
                                <th>Class</th>
                                <th>Schedule</th>
                                <th>Status</th>
                                <th>Results</th>
                                <th>Candidates</th>
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
                                    <div>
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
                                    <a href="result.php?class_id=<?php echo $election['class_id']; ?>" class="action-btn view-btn">
                                        <i class="fas fa-chart-bar"></i> View Results
                                    </a>
                                </td>
                                <td>
                                    <a href="candidates.php?class_id=<?php echo $election['class_id']; ?>" class="action-btn manage-btn">
                                        <i class="fas fa-users"></i> Manage
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-elections">
                        <i class="fas fa-vote-yea"></i>
                        <p>No elections found in the system.</p>
                        <a href="addelection.php" class="add-election-btn" style="margin-top: 15px; display: inline-flex;">
                            <i class="fas fa-plus"></i> Create First Election
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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