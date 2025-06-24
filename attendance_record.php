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


$logged_in_user = $_SESSION['username'];


$is_admin = ($logged_in_user === 'admin' || $_SESSION['role'] === 'headmaster');


$staff_list = [];
if ($is_admin) {
    $staff_sql = "SELECT id, username FROM users WHERE username != 'admin' AND username != 'hm' ORDER BY username";
    $staff_stmt = $conn->prepare($staff_sql);
    $staff_stmt->execute();
    $staff_result = $staff_stmt->get_result();
    while ($staff = $staff_result->fetch_assoc()) {
        $staff_list[] = $staff;
    }
}


$selected_staff = isset($_GET['staff']) ? $_GET['staff'] : '';


if ($is_admin) {
    if (!empty($selected_staff)) {

        $sql = "SELECT a.id, u.username, a.time_in, a.time_out 
                FROM attendance a
                JOIN users u ON a.user_id = u.id
                WHERE u.username = ?
                ORDER BY a.time_in DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $selected_staff);
    } else {

        $sql = "SELECT a.id, u.username, a.time_in, a.time_out 
                FROM attendance a
                JOIN users u ON a.user_id = u.id
                ORDER BY a.time_in DESC";
        $stmt = $conn->prepare($sql);
    }
} else {
    // Staff sees only their attendance records
    $sql = "SELECT a.id, u.username, a.time_in, a.time_out 
            FROM attendance a
            JOIN users u ON a.user_id = u.id
            WHERE u.username = ?
            ORDER BY a.time_in DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $logged_in_user);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            color: #333;
            min-height: 100vh;
        }

        h2 {
            text-align: center;
            margin: 30px 0;
            color: #032539;
            font-size: 2em;
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .filter-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            width: 100%;
        }

        .filter-label {
            color: #032539;
            font-weight: 500;
            font-size: 0.95em;
            margin-right: 5px;
        }

        .staff-select {
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95em;
            color: #444;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 250px;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="6"><path d="M0 0l6 6 6-6z" fill="%23666"/></svg>');
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 12px;
        }

        .staff-select:hover, .staff-select:focus {
            border-color: #1C768F;
            outline: none;
            box-shadow: 0 0 0 3px rgba(28, 118, 143, 0.1);
        }

        .filter-button {
            background: linear-gradient(135deg, #032539 0%, #1C768F 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95em;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(3, 37, 57, 0.2);
        }

        .clear-filter {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9em;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
        }

        .clear-filter:hover {
            color: #1C768F;
        }

        .clear-filter::before {
            content: "×";
            margin-right: 5px;
            font-size: 1.2em;
        }

        .table-wrapper {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }

        th {
            background: linear-gradient(135deg, #032539 0%, #1C768F 100%);
            color: white;
            font-weight: 500;
            padding: 15px;
            text-align: left;
            font-size: 0.95em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eef0f5;
            color: #444;
            font-size: 0.95em;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

.status-in {
    color: #2563eb;
    background: #dbeafe;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 500;
    display: inline-block;
}
.status-out {
    color: #10b981;
    background: #dcfce7;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 500;
    display: inline-block;
}

        .time {
            font-family: 'Roboto Mono', monospace;
            color: #1C768F;
            font-weight: 500;
        }

        .no-records {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
            font-size: 1.1em;
        }

        .btn {
            text-align: center;
            margin-top: 30px;
        }

        .button1 {
            background: linear-gradient(135deg, #032539 0%, #1C768F 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .button1:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(3, 37, 57, 0.2);
        }

        .filter-status {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #4b5563;
            display: inline-block;
        }

        .filter-status strong {
            color: #1C768F;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            th, td {
                padding: 12px 15px;
            }

            h2 {
                font-size: 1.5em;
                margin: 20px 0;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .staff-select {
                width: 100%;
                min-width: unset;
            }
            
            .filter-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <h2><?php echo $is_admin ? "Staff Attendance Records" : "My Attendance Records"; ?></h2>
    
    <div class="container">
        <?php if ($is_admin): ?>
        <div class="filter-container">
            <form action="" method="GET" class="filter-form">
                <div>
                    <label for="staff" class="filter-label">Select Staff:</label>
                    <select name="staff" id="staff" class="staff-select">
                        <option value="">All Staff Members</option>
                        <?php foreach ($staff_list as $staff): ?>
                            <option value="<?php echo htmlspecialchars($staff['username']); ?>" 
                                    <?php echo $selected_staff === $staff['username'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($staff['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="filter-button">Apply Filter</button>
                <?php if (!empty($selected_staff)): ?>
                    <a href="attendance_record.php" class="clear-filter">Clear Filter</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (!empty($selected_staff)): ?>
            <div class="filter-status">
                Currently viewing attendance for: <strong><?php echo htmlspecialchars($selected_staff); ?></strong>
            </div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Staff</th>
                        <th>Date</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $time_in = new DateTime($row['time_in']);
                            $time_out = $row['time_out'] ? new DateTime($row['time_out']) : null;
                            
                            $date_in = $time_in->format('Y-m-d');
                            $clock_in = $time_in->format('H:i:s');
                            // Set status: "Clock-in" if no time_out, "Clocked-out" if time_out exists
                            $status = $row['time_out'] ? '<span class="status-out">Clocked-out</span>' : '<span class="status-in">Clocked-in</span>';
                            
                            echo "<tr>
                                <td>#{$row['id']}</td>
                                <td>{$row['username']}</td>
                                <td>{$date_in}</td>
                                <td><span class='time'>{$clock_in}</span></td>
                                <td>" . ($row['time_out'] ? "<span class='time'>" . $time_out->format('H:i:s') . "</span>" : '-') . "</td>
                                <td>{$status}</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='no-records'>No attendance records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="btn">
            <?php if ($is_admin): ?>
                <a href="admin_page.php" class="button1">Back to Dashboard</a>
            <?php else: ?>
                <a href="staff_page.php" class="button1">Back to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php 
// Close all statements and connection
if (isset($staff_stmt)) {
    $staff_stmt->close();
}
$stmt->close();
$conn->close(); 
?>
