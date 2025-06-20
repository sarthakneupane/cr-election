<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['admname'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $get_status = mysqli_query($conn, "SELECT status FROM classes WHERE id = $id");
    if ($row = mysqli_fetch_assoc($get_status)) {
        $new_status = ($row['status'] === 'active') ? 'inactive' : 'active';
        mysqli_query($conn, "UPDATE classes SET status = '$new_status' WHERE id = $id");
        if ($new_status === 'inactive') {
            mysqli_query($conn, "UPDATE elections SET status = 'finished' WHERE class_id = $id");
        }
    }
    header("Location: classes.php");
    exit();
}

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM classes WHERE id = $id");
    header("Location: classes.php");
    exit();
}

$sql = "SELECT id, faculty, batch, status FROM classes ORDER BY faculty, batch";
$result = mysqli_query($conn, $sql);
$active = "classes";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Classes Management Page Styles */
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

        /* Class List Container */
        .class-list-container {
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

        .add-class-btn {
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

        .add-class-btn:hover {
            background-color: #0066cc;
            transform: translateY(-2px);
        }

        /* Class Table */
        .class-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .class-table th,
        .class-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .class-table th {
            background-color: #f8f9fa;
            color: #004080;
            font-weight: 600;
        }

        .class-table tr:hover {
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

        .status-active {
            background: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }

        .status-inactive {
            background: #f5f5f5;
            color: #666;
            border: 1px solid #ddd;
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
            margin-right: 8px;
        }

        .edit-btn {
            background-color: #004080;
            color: white;
        }

        .edit-btn:hover {
            background-color: #0066cc;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
        }

        .toggle-btn {
            background-color: #ff9800;
            color: white;
        }

        .toggle-btn:hover {
            background-color: #f57c00;
        }

        /* No Classes Message */
        .no-classes {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 16px;
        }

        .no-classes i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
            display: block;
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
                width: 100%;
            }
            
            .add-class-btn {
                width: 100%;
                justify-content: center;
            }
            
            .class-table {
                display: block;
                overflow-x: auto;
            }

            .action-btn {
                margin-bottom: 5px;
            }
        }

        @media (max-width: 480px) {
            .class-table th,
            .class-table td {
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
                <h2><i class="fas fa-school"></i> Manage Classes</h2>
                <div class="action-buttons">
                    <a href="addclass.php" class="add-class-btn">
                        <i class="fas fa-plus"></i> Add New Class
                    </a>
                </div>
            </div>

            <div class="class-list-container">
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Class Management:</strong> Deactivating a class will automatically finish any ongoing elections for that class.</span>
                </div>

                <?php if (mysqli_num_rows($result) > 0): ?>
                    <table class="class-table">
                        <thead>
                            <tr>
                                <th>S No.</th>
                                <th>Faculty</th>
                                <th>Batch</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sn = 1; while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= $sn++; ?></td>
                                    <td><strong><?= htmlspecialchars($row['faculty']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['batch']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status'] ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                            <a href="classes.php?toggle_id=<?= $row['id'] ?>" 
                                               class="action-btn toggle-btn"
                                               onclick="return confirm('Are you sure you want to <?= $row['status'] == 'active' ? 'deactivate' : 'activate' ?> this class?');">
                                                <i class="fas fa-<?= $row['status'] == 'active' ? 'pause' : 'play' ?>"></i> 
                                                <?= $row['status'] == 'active' ? 'Deactivate' : 'Activate' ?>
                                            </a>
                                            
                                            <a href="editclass.php?id=<?= $row['id'] ?>" class="action-btn edit-btn">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            
                                            <a href="classes.php?delete_id=<?= $row['id'] ?>"
                                               class="action-btn delete-btn"
                                               onclick="return confirm('Are you sure you want to delete this class? This action cannot be undone.');">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-classes">
                        <i class="fas fa-school"></i>
                        <p>No classes found in the system.</p>
                        <a href="addclass.php" class="add-class-btn" style="margin-top: 15px; display: inline-flex;">
                            <i class="fas fa-plus"></i> Add First Class
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>