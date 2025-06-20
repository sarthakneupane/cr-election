<?php
session_start();
include "includes/db.php";
if (!isset($_SESSION['admname'])) {
        header("Location: login.php");
        exit();
    }

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_crn'])) {
    $delete_crn = $_POST['delete_crn'];

    $stmt = $conn->prepare("DELETE FROM students WHERE crn = ?");
    $stmt->bind_param("s", $delete_crn);

    if ($stmt->execute()) {
        $message = "Student deleted successfully.";
    } else {
        $message = "Error deleting student: " . $conn->error;
    }

    $stmt->close();
}

// Fetch classes for dropdown
$class_query = "SELECT id, faculty, batch FROM classes WHERE status = 'active' ORDER BY faculty, batch ";
$class_result = mysqli_query($conn, $class_query);

// Handle class filter
$selected_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';

$sql = "SELECT s.crn, s.name, s.email, s.image, c.faculty, c.batch 
        FROM students s
        JOIN classes c ON s.class_id = c.id
        WHERE c.status = 'active'";

if (!empty($selected_class)) {
    $sql .= " AND s.class_id = " . intval($selected_class);
}

$sql .= " ORDER BY c.faculty, c.batch, s.name";



$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

$active = "stdlist";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List</title>
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

        /* Student List Container */
        .student-list-container {
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

        .add-student-btn {
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

        .add-student-btn:hover {
            background-color: #0066cc;
            transform: translateY(-2px);
        }

        /* Filter Form */
        .filter-form {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-form label {
            font-weight: 500;
            color: #555;
        }

        .filter-form select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 250px;
            background-color: #f9f9f9;
        }

        .filter-form button {
            padding: 8px 20px;
            background-color: #004080;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .filter-form button:hover {
            background-color: #0066cc;
        }

        /* Student Table */
        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .student-table th,
        .student-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .student-table th {
            background-color: #f8f9fa;
            color: #004080;
            font-weight: 600;
        }

        .student-table tr:hover {
            background-color: #f8fafd;
        }

        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e0e0e0;
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
            border: none;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
        }

        /* Message Box */
        .message-box {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-box.error {
            background-color: #ffebee;
            color: #c62828;
            border-color: #ef9a9a;
        }

        /* No Students Message */
        .no-students {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 16px;
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
            
            .filter-form {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .filter-form select {
                width: 100%;
            }
            
            .student-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                width: 100%;
            }
            
            .add-student-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .student-table th,
            .student-table td {
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
                <h2><i class="fas fa-users"></i> Student List</h2>
                <div class="action-buttons">
                    <a href="studententry.php" class="add-student-btn">
                        <i class="fas fa-plus"></i> Add Student
                    </a>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="message-box <?= strpos($message, 'Error') !== false ? 'error' : '' ?>">
                    <i class="fas <?= strpos($message, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="student-list-container">
                <form method="GET" class="filter-form">
                    <label for="class_id"><i class="fas fa-filter"></i> Filter by Class:</label>
                    <select name="class_id" id="class_id">
                        <option value="">-- All Classes --</option>
                        <?php while ($class = mysqli_fetch_assoc($class_result)): ?>
                            <option value="<?= $class['id'] ?>" <?= $selected_class == $class['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($class['faculty'] . ' - ' . $class['batch']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit"><i class="fas fa-search"></i> Apply Filter</button>
                </form>

                <table class="student-table">
                    <thead>
                        <tr>
                            <th>S No.</th>
                            <th>Profile</th>
                            <th>CRN</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php $serial = 1; ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= $serial++ ?></td>
                                    <td>
                                        <?php if (!empty($row['image']) && file_exists($row['image'])): ?>
                                            <img src="<?= htmlspecialchars($row['image']) ?>" alt="Profile" class="profile-img">
                                        <?php else: ?>

                                            <div class="profile-img" style="background-color: #eee; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user" style="color: #999;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['crn']) ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['faculty'] . ' - ' . $row['batch']) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <a href="editstudent.php?crn=<?= urlencode($row['crn']) ?>" class="action-btn edit-btn">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this student?');" style="display:inline;">
                                                <input type="hidden" name="delete_crn" value="<?= htmlspecialchars($row['crn']) ?>">
                                                <button type="submit" class="action-btn delete-btn">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-students">
                                    <i class="fas fa-user-graduate" style="font-size: 24px; margin-bottom: 10px;"></i>
                                    <p>No students found in this class.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
