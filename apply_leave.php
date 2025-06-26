<?php
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to login page if not logged in
    header("Location: loginPage.php");
    exit();
}

// Get the logged-in user's username
$logged_in_user = $_SESSION['username'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check remaining leave days
    $leave_check_sql = "SELECT COALESCE(SUM(DATEDIFF(until_date, from_date) + 1), 0) as used_days 
                        FROM leave_applications 
                        WHERE user_id = (SELECT id FROM users WHERE username = ?)
                        AND status = 'Approved'
                        AND YEAR(from_date) = YEAR(CURRENT_DATE())";
    $check_stmt = $conn->prepare($leave_check_sql);
    $check_stmt->bind_param("s", $logged_in_user);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $used_days = $check_result->fetch_assoc()['used_days'];
    
    // Calculate days requested
    $from_date = new DateTime($_POST['from_date']);
    $until_date = new DateTime($_POST['until_date']);
    $days_requested = $until_date->diff($from_date)->days + 1;
    
    if (($used_days + $days_requested) > 30) {
        $error_message = "Error: You cannot apply for more than 30 days of leave per year. You have already used {$used_days} days.";
    } else {
        $description = $_POST['description'];
        $from_date = $_POST['from_date'];
        $until_date = $_POST['until_date'];

        // Insert leave application into the database
        $sql = "INSERT INTO leave_applications (user_id, description, from_date, until_date, status, date_applied) 
                SELECT id, ?, ?, ?, 'Pending', NOW() FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $description, $from_date, $until_date, $logged_in_user);

        if ($stmt->execute()) {
            // Redirect to avoid duplicate submission on refresh
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit();
        } else {
            $error_message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
    
    $check_stmt->close();
}

// Fetch leave applications for the logged-in user
$sql = "SELECT id, description, from_date, until_date, status, date_applied FROM leave_applications 
        WHERE user_id = (SELECT id FROM users WHERE username = ?) 
        ORDER BY date_applied DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $logged_in_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Leave</title>
    <!-- Add Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Add Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --text-color: #334155;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .main-container {
            width: 100%;
            max-width: 1200px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(249, 250, 251, 0.9) 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 40px;
            position: relative;
            margin: 20px auto;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .main-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark), var(--success-color));
            border-radius: 6px 6px 0 0;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
        }

        .page-header h1::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            border-radius: 3px;
        }

        .page-header p {
            color: #64748b;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header .material-icons {
            font-size: 24px;
            margin-right: 10px;
            color: var(--primary-color);
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            color: var(--text-color);
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .alert .material-icons {
            margin-right: 10px;
            font-size: 24px;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border-left: 4px solid var(--success-color);
            color: #065f46;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--error-color);
            color: #b91c1c;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: #334155;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: #f8fafc;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: #b45309;
        }

        .status-approved {
            background-color: rgba(16, 185, 129, 0.1);
            color: #065f46;
        }

        .status-rejected {
            background-color: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }

        .empty-state .material-icons {
            font-size: 48px;
            margin-bottom: 15px;
            color: #cbd5e1;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 20px;
                margin: 10px;
            }
            
            .card {
                padding: 20px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .page-header p {
                font-size: 1rem;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container">
            <div class="page-header">
                <h1>Apply for Leave</h1>
                <p>Submit your leave application for approval</p>
            </div>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <span class="material-icons">check_circle</span>
                <div>Your leave application has been submitted successfully!</div>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <span class="material-icons">error</span>
                <div><?php echo $error_message; ?></div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <span class="material-icons">event_available</span>
                    <h2>Submit Leave Application</h2>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="description">Reason for Leave</label>
                        <textarea name="description" id="description" rows="4" placeholder="Please provide detailed reason for your leave request..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="from_date">From Date</label>
                        <input type="date" name="from_date" id="from_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="until_date">Until Date</label>
                        <input type="date" name="until_date" id="until_date" required value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>

                    <button type="submit" class="btn btn-block">
                        <span class="material-icons" style="vertical-align: middle; margin-right: 8px;">send</span>
                        Submit Leave Application
                    </button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="material-icons">history</span>
                    <h2>Your Leave Application History</h2>
                </div>
                
                <div class="table-responsive">
                    <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Description</th>
                                <th>From</th>
                                <th>Until</th>
                                <th>Status</th>
                                <th>Applied Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['from_date'])); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['until_date'])); ?></td>
                                <td>
                                    <?php 
                                    $statusClass = '';
                                    switch($row['status']) {
                                        case 'Pending':
                                            $statusClass = 'status-pending';
                                            break;
                                        case 'Approved':
                                            $statusClass = 'status-approved';
                                            break;
                                        case 'Rejected':
                                            $statusClass = 'status-rejected';
                                            break;
                                    }
                                    ?>
                                    <span class="status <?php echo $statusClass; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['date_applied'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <span class="material-icons">inbox</span>
                        <p>You haven't submitted any leave applications yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-resize textarea as user types
        const textarea = document.querySelector('textarea');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
