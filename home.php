<?php
include "includes/db.php";

// Get current datetime
$currentDateTime = (new DateTime())->modify('+4 hours 45 minutes')->format('Y-m-d H:i:s');

// Fetch elections with updated column names and determine status based on current time
$upcomingElections = [];
$ongoingElections = [];

$sql = "SELECT e.id, e.election_start_datetime, e.election_end_datetime, c.faculty, c.batch
        FROM elections e 
        JOIN classes c ON e.class_id = c.id 
        ORDER BY e.election_start_datetime ASC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $election = [
        'id' => $row['id'],
        'start_datetime' => $row['election_start_datetime'],
        'end_datetime' => $row['election_end_datetime'],
        'faculty' => $row['faculty'],
        'batch' => $row['batch']
    ];
    
    // Determine actual status based on current time
    if ($currentDateTime < $row['election_start_datetime']) {
        $upcomingElections[] = $election;
    } elseif ($currentDateTime >= $row['election_start_datetime'] && $currentDateTime <= $row['election_end_datetime']) {
        $ongoingElections[] = $election;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Election Countdowns - Live Status</title>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .main-container {
            display: flex;
            min-height: calc(100vh - 80px);
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .login-sidebar {
            width: 300px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
            height: fit-content;
        }

        .countdown-content {
            flex: 1;
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .page-title h1 {
            font-size: 2rem;
            color: #004080;
            margin-bottom: 8px;
        }

        .page-title p {
            color: #666;
            font-size: 1rem;
        }

        .login-form {
            background: #004080;
            border-radius: 8px;
            padding: 20px;
            color: white;
            text-align: center;
            margin-bottom: 20px;
        }

        .login-form h2 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .login-form p {
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .login-btn {
            background: white;
            color: #004080;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .login-btn:hover {
            background: #f0f0f0;
        }

        .countdown-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .countdown-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }

        .countdown-card.upcoming {
            border-left: 4px solid #004080;
        }

        .countdown-card.ongoing {
            border-left: 4px solid #28a745;
        }

        .election-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .countdown-display {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .time-unit {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            min-width: 60px;
            text-align: center;
        }

        .time-number {
            font-size: 1.5rem;
            font-weight: bold;
            display: block;
            color: #004080;
            font-family: 'Courier New', monospace;
        }

        .time-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            margin-top: 3px;
        }

        .countdown-status {
            font-size: 0.9rem;
            margin-top: 15px;
            padding: 8px 15px;
            border-radius: 15px;
            display: inline-block;
            font-weight: bold;
        }

        .status-upcoming {
            background: #e3f2fd;
            color: #004080;
            border: 1px solid #004080;
        }

        .status-ongoing {
            background: #e8f5e9;
            color: #28a745;
            border: 1px solid #28a745;
        }

        .no-elections {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .no-elections i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }

        .no-elections h2 {
            color: #004080;
            margin-bottom: 10px;
        }

        .no-elections p {
            color: #666;
        }

        .urgent {
            border-color: #dc3545 !important;
        }

        .urgent .time-number {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                padding: 10px;
            }

            .login-sidebar {
                width: 100%;
                margin-bottom: 20px;
            }

            .page-title h1 {
                font-size: 1.5rem;
            }

            .countdown-grid {
                grid-template-columns: 1fr;
            }

            .countdown-display {
                gap: 8px;
            }

            .time-unit {
                min-width: 50px;
                padding: 8px;
            }

            .time-number {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-container">

        <!-- Countdown Content -->
        <div class="countdown-content">
            <div class="page-title">
                <h1>Election Countdowns</h1>
                <p>Live countdown to upcoming and ongoing elections</p>
                
                <form action="login.php" method="get">
                    <button type="submit" class="login-btn">
                        Login to Vote
                    </button>
                </form>
            
            </div>

            <?php if (count($upcomingElections) > 0 || count($ongoingElections) > 0): ?>
                <div class="countdown-grid">
                    <?php foreach ($upcomingElections as $election): ?>
                        <div class="countdown-card upcoming">
                            <div class="election-title">
                                <?= htmlspecialchars($election['faculty']) ?> <?= htmlspecialchars($election['batch']) ?>
                            </div>
                            
                            <div class="countdown-display" data-target="<?= $election['start_datetime'] ?>" data-type="start">
                                <div class="time-unit">
                                    <span class="time-number days">00</span>
                                    <span class="time-label">Days</span>
                                </div>
                                <div class="time-unit">
                                    <span class="time-number hours">00</span>
                                    <span class="time-label">Hours</span>
                                </div>
                                <div class="time-unit">
                                    <span class="time-number minutes">00</span>
                                    <span class="time-label">Minutes</span>
                                </div>
                                <div class="time-unit">
                                    <span class="time-number seconds">00</span>
                                    <span class="time-label">Seconds</span>
                                </div>
                            </div>
                            
                            <div class="countdown-status status-upcoming">
                                Election Starts Soon
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($ongoingElections as $election): ?>
                        <div class="countdown-card ongoing">
                            <div class="election-title">
                                <?= htmlspecialchars($election['faculty']) ?> <?= htmlspecialchars($election['batch']) ?>
                            </div>
                            
                            <div class="countdown-display" data-target="<?= $election['end_datetime'] ?>" data-type="end">
                                <div class="time-unit">
                                    <span class="time-number days">00</span>
                                    <span class="time-label">Days</span>
                                </div>
                                <div class="time-unit">
                                    <span class="time-number hours">00</span>
                                    <span class="time-label">Hours</span>
                                </div>
                                <div class="time-unit">
                                    <span class="time-number minutes">00</span>
                                    <span class="time-label">Minutes</span>
                                </div>
                                <div class="time-unit">
                                    <span class="time-number seconds">00</span>
                                    <span class="time-label">Seconds</span>
                                </div>
                            </div>
                            
                            <div class="countdown-status status-ongoing">
                                Voting in Progress
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-elections">
                    <i class="fas fa-calendar-times"></i>
                    <h2>No Active Elections</h2>
                    <p>There are currently no upcoming or ongoing elections.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function updateCountdowns() {
            const countdownDisplays = document.querySelectorAll('.countdown-display[data-target]');
            
            countdownDisplays.forEach(display => {
                const targetDate = new Date(display.getAttribute('data-target')).getTime();
                const now = new Date().getTime();
                const distance = targetDate - now;

                if (distance > 0) {
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    display.querySelector('.days').textContent = String(days).padStart(2, '0');
                    display.querySelector('.hours').textContent = String(hours).padStart(2, '0');
                    display.querySelector('.minutes').textContent = String(minutes).padStart(2, '0');
                    display.querySelector('.seconds').textContent = String(seconds).padStart(2, '0');

                    // Add urgent styling if less than 1 hour remaining
                    const card = display.closest('.countdown-card');
                    if (distance < 3600000) { // 1 hour
                        card.classList.add('urgent');
                    } else {
                        card.classList.remove('urgent');
                    }
                } else {
                    // Time's up
                    display.querySelector('.days').textContent = '00';
                    display.querySelector('.hours').textContent = '00';
                    display.querySelector('.minutes').textContent = '00';
                    display.querySelector('.seconds').textContent = '00';
                    
                    const status = display.closest('.countdown-card').querySelector('.countdown-status');
                    if (display.getAttribute('data-type') === 'start') {
                        status.textContent = 'Election Started!';
                        status.style.background = '#e8f5e9';
                        status.style.color = '#28a745';
                    } else {
                        status.textContent = 'Election Ended!';
                        status.style.background = '#f8d7da';
                        status.style.color = '#721c24';
                    }
                }
            });
        }

        // Update countdowns immediately and then every second
        updateCountdowns();
        setInterval(updateCountdowns, 1000);

        // Refresh page every 5 minutes to update election data
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>

<?php $conn->close(); ?>