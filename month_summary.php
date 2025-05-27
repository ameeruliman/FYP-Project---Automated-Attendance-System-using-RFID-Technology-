<?php
session_start();

// Check if the user is logged in and is a staff
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: loginPage.php");
    exit();
}

// Include database connection
require_once 'db_connection.php';

// Get the logged-in user's ID
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];
$stmt->close();

// Get current month and year if not specified
$current_month = date('m');
$current_year = date('Y');

// Handle month/year selection
$selected_month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;

// Format the selected month and year for display
$month_name = date('F', mktime(0, 0, 0, $selected_month, 1, $selected_year));
$display_date = $month_name . ' ' . $selected_year;

// Get attendance records for the selected month
$attendance_query = "SELECT a.time_in, a.time_out, 
                    DATE(a.time_in) as date,
                    TIME(a.time_in) as in_time,
                    TIME(a.time_out) as out_time,
                    TIMEDIFF(a.time_out, a.time_in) as hours_worked
                    FROM attendance a
                    WHERE a.user_id = ? 
                    AND MONTH(a.time_in) = ? 
                    AND YEAR(a.time_in) = ?
                    ORDER BY a.time_in DESC";

$stmt = $conn->prepare($attendance_query);
$stmt->bind_param("iii", $user_id, $selected_month, $selected_year);
$stmt->execute();
$attendance_result = $stmt->get_result();
$attendance_records = $attendance_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get emergency information for the selected month
$emergency_query = "SELECT e.id, e.description, e.date as date_submitted, e.status
                    FROM emergency_info e
                    WHERE e.user_id = ? 
                    AND MONTH(e.date) = ? 
                    AND YEAR(e.date) = ?
                    ORDER BY e.date DESC";

$stmt = $conn->prepare($emergency_query);
$stmt->bind_param("iii", $user_id, $selected_month, $selected_year);
$stmt->execute();
$emergency_result = $stmt->get_result();
$emergency_records = $emergency_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get leave applications for the selected month
$leave_query = "SELECT l.id, l.description, l.from_date, l.until_date, l.status, l.date_applied
                FROM leave_applications l
                WHERE l.user_id = ? 
                AND (
                    (MONTH(l.from_date) = ? AND YEAR(l.from_date) = ?) OR
                    (MONTH(l.until_date) = ? AND YEAR(l.until_date) = ?) OR
                    (l.from_date <= LAST_DAY(?) AND l.until_date >= ?)
                )
                ORDER BY l.date_applied DESC";

$first_day = date('Y-m-d', mktime(0, 0, 0, $selected_month, 1, $selected_year));
$last_day = date('Y-m-t', mktime(0, 0, 0, $selected_month, 1, $selected_year));

$stmt = $conn->prepare($leave_query);
$stmt->bind_param("iiiiiss", $user_id, $selected_month, $selected_year, $selected_month, $selected_year, $last_day, $first_day);
$stmt->execute();
$leave_result = $stmt->get_result();
$leave_records = $leave_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate summary statistics
$total_days_worked = count($attendance_records);
$total_hours_worked = 0;
foreach ($attendance_records as $record) {
    if (!empty($record['hours_worked'])) {
        list($hours, $minutes, $seconds) = explode(':', $record['hours_worked']);
        $total_hours_worked += $hours + ($minutes / 60) + ($seconds / 3600);
    }
}
$total_hours_worked = round($total_hours_worked, 2);

// Count emergency submissions
$total_emergency = count($emergency_records);

// Count leave days in the selected month
$total_leave_days = 0;
foreach ($leave_records as $leave) {
    $from = max($first_day, $leave['from_date']);
    $until = min($last_day, $leave['until_date']);
    
    $from_date = new DateTime($from);
    $until_date = new DateTime($until);
    $interval = $from_date->diff($until_date);
    $total_leave_days += $interval->days + 1;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Summary - <?php echo $display_date; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary-color: #032539;
            --primary-light: #1C768F;
            --primary-dark: #032539;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 16px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            color: var(--dark-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }

        .main-container {
            width: 100%;
            max-width: 1200px;
            background: #ffffff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            padding: 2rem;
            position: relative;
            border: 1px solid rgba(226, 232, 240, 0.8);
            margin-bottom: 2rem;
        }

        .main-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light), var(--success-color));
            border-radius: 6px 6px 0 0;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
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
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 3px;
        }

        .page-header p {
            color: #64748b;
            font-size: 1.1rem;
        }

        .month-selector {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .month-selector select, .month-selector button {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            background-color: white;
            transition: var(--transition);
        }

        .month-selector select {
            min-width: 150px;
            cursor: pointer;
        }

        .month-selector select:focus, .month-selector button:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(28, 118, 143, 0.1);
        }

        .month-selector button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .month-selector button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(3, 37, 57, 0.2);
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: var(--transition);
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .summary-card .material-icons {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            background: rgba(3, 37, 57, 0.1);
            padding: 0.75rem;
            border-radius: 50%;
        }

        .summary-card h3 {
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .summary-card p {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }

        .card-header .material-icons {
            font-size: 1.75rem;
            margin-right: 0.75rem;
            color: var(--primary-color);
            background: rgba(3, 37, 57, 0.1);
            padding: 0.5rem;
            border-radius: 50%;
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }

        th, td {
            padding: 1rem;
            text-align: left;
        }

        th {
            background-color: rgba(3, 37, 57, 0.05);
            font-weight: 600;
            color: var(--primary-dark);
            position: relative;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        th:first-child {
            border-top-left-radius: 10px;
        }

        th:last-child {
            border-top-right-radius: 10px;
        }

        tr:last-child td:first-child {
            border-bottom-left-radius: 10px;
        }

        tr:last-child td:last-child {
            border-bottom-right-radius: 10px;
        }

        td {
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr {
            transition: var(--transition);
        }

        tr:hover {
            background-color: rgba(3, 37, 57, 0.03);
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
            min-width: 100px;
        }

        .status-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: #b45309;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .status-approved {
            background-color: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-rejected {
            background-color: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #94a3b8;
        }

        .empty-state .material-icons {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .footer-actions {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            outline: none;
            text-decoration: none;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .btn .material-icons {
            font-size: 1.1rem;
            margin-right: 0.4rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 6px rgba(3, 37, 57, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(3, 37, 57, 0.3);
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1.5rem;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            th, td {
                padding: 0.75rem;
            }
            
            .btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }
            
            .card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header">
            <h1>Monthly Summary</h1>
            <p>View your attendance, emergency information, and leave applications for <?php echo $display_date; ?></p>
        </div>

        <form class="month-selector" method="GET">
            <select name="month">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo ($m == $selected_month) ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
            
            <select name="year">
                <?php 
                $current_year = date('Y');
                for ($y = $current_year; $y >= $current_year - 5; $y--): 
                ?>
                    <option value="<?php echo $y; ?>" <?php echo ($y == $selected_year) ? 'selected' : ''; ?>>
                        <?php echo $y; ?>
                    </option>
                <?php endfor; ?>
            </select>
            
            <button type="submit">View Summary</button>
        </form>

        <div class="summary-cards">
            <!-- Removed Days Worked and Hours Worked cards as requested -->
            <div class="summary-card">
                <span class="material-icons">warning</span>
                <h3>Emergency Reports</h3>
                <p><?php echo $total_emergency; ?></p>
            </div>
            
            <div class="summary-card">
                <span class="material-icons">event_busy</span>
                <h3>Leave Days</h3>
                <p><?php echo $total_leave_days; ?></p>
            </div>
        </div>

        <!-- Attendance Records -->
        <div class="card">
            <div class="card-header">
                <span class="material-icons">event_available</span>
                <h2>Attendance Records</h2>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <!-- Removed Hours Worked column as requested -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendance_records)): ?>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($record['in_time'])); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($record['out_time']) && $record['out_time'] != '00:00:00') {
                                            echo date('h:i A', strtotime($record['out_time']));
                                        } else {
                                            echo 'Not clocked out';
                                        }
                                        ?>
                                    </td>
                                    <!-- Removed Hours Worked cell as requested -->
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"> <!-- Changed colspan from 4 to 3 since we removed a column -->
                                    <div class="empty-state">
                                        <span class="material-icons">event_busy</span>
                                        <p>No attendance records found for <?php echo $display_date; ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Emergency Information -->
        <div class="card">
            <div class="card-header">
                <span class="material-icons">warning</span>
                <h2>Emergency Information</h2>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date Submitted</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($emergency_records)): ?>
                            <?php foreach ($emergency_records as $record): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($record['date_submitted'])); ?></td>
                                    <td><?php echo htmlspecialchars($record['description']); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        switch($record['status']) {
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
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state">
                                        <span class="material-icons">info</span>
                                        <p>No emergency information found for <?php echo $display_date; ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leave Applications -->
        <div class="card">
            <div class="card-header">
                <span class="material-icons">event_busy</span>
                <h2>Leave Applications</h2>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date Applied</th>
                            <th>From</th>
                            <th>Until</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($leave_records)): ?>
                            <?php foreach ($leave_records as $record): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($record['date_applied'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($record['from_date'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($record['until_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($record['description']); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        switch($record['status']) {
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
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <span class="material-icons">event_busy</span>
                                        <p>No leave applications found for <?php echo $display_date; ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer-actions">
            <a href="staff_page.php" class="btn btn-primary">
                <span class="material-icons">dashboard</span>
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>