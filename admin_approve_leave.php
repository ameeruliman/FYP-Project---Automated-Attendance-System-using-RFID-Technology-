<?php
session_start();

// Check if the user is logged in and is an admin or headmaster
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'headmaster'])) {
    header("Location: loginPage.php");
    exit();
}

require_once 'db_connection.php'; // Include database connection

// Fetch leave applications to approve/reject
$stmt = $conn->prepare("
    SELECT la.id AS leave_id, la.user_id, la.description, la.from_date, la.until_date, la.status, u.username
    FROM leave_applications AS la
    JOIN users AS u ON la.user_id = u.id
    ORDER BY la.date_applied DESC
");
$stmt->execute();
$result = $stmt->get_result();
$leave_applications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle approval/rejection or deletion of leave application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['leave_id'])) {
    $leave_id = $_POST['leave_id'];
    $action = $_POST['action']; // 'approve', 'reject', or 'delete'

    if ($action === 'approve' || $action === 'reject') {
        // Update the leave application status
        $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';

        $stmt = $conn->prepare("UPDATE leave_applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $leave_id);

        if ($stmt->execute()) {
            $success = "Leave application $new_status successfully!";
        } else {
            $error = "Failed to update leave application status. Please try again.";
        }

        $stmt->close();
    } elseif ($action === 'delete') {
        // Delete the leave application
        $stmt = $conn->prepare("DELETE FROM leave_applications WHERE id = ?");
        $stmt->bind_param("i", $leave_id);

        if ($stmt->execute()) {
            $success = "Leave application cleared successfully!";
        } else {
            $error = "Failed to clear leave application. Please try again.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Approve Leave</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-light: #818cf8;
            --primary-dark: #4338ca;
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
            background: #ffffff;
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

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert .material-icons {
            margin-right: 0.75rem;
            font-size: 1.5rem;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border-left: 4px solid var(--success-color);
            color: #065f46;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--danger-color);
            color: #b91c1c;
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
            background: rgba(79, 70, 229, 0.1);
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
            background-color: rgba(79, 70, 229, 0.05);
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
            background-color: rgba(79, 70, 229, 0.03);
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

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
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

        .btn-approve {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.25);
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-reject {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 6px rgba(245, 158, 11, 0.25);
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(245, 158, 11, 0.3);
        }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 6px rgba(239, 68, 68, 0.25);
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(239, 68, 68, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.25);
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(79, 70, 229, 0.3);
        }

        .footer-actions {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
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

        .description-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .description-cell:hover {
            white-space: normal;
            overflow: visible;
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
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
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
            <h1>Leave Application Management</h1>
            <p>Review and manage staff leave requests</p>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <span class="material-icons">check_circle</span>
            <div><?php echo $success; ?></div>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <span class="material-icons">error</span>
            <div><?php echo $error; ?></div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <span class="material-icons">event_busy</span>
                <h2>Leave Requests</h2>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Staff</th>
                            <th>Description</th>
                            <th>From</th>
                            <th>Until</th>
                            <th>Status</th>
                            <?php if ($_SESSION['role'] === 'headmaster'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($leave_applications)): ?>
                            <?php foreach ($leave_applications as $application): ?>
                                <tr>
                                    <td>#<?php echo $application['leave_id']; ?></td>
                                    <td><?php echo htmlspecialchars($application['username']); ?></td>
                                    <td class="description-cell"><?php echo htmlspecialchars($application['description']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($application['from_date'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($application['until_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        switch($application['status']) {
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
                                            <?php echo ucfirst($application['status']); ?>
                                        </span>
                                    </td>
                                    <?php if ($_SESSION['role'] === 'headmaster'): ?>
                                        <td>
                                            <?php if ($application['status'] === 'Pending'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="leave_id" value="<?php echo $application['leave_id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-approve">
                                                        <span class="material-icons">check</span> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="leave_id" value="<?php echo $application['leave_id']; ?>">
                                                    <button type="submit" name="action" value="reject" class="btn btn-reject">
                                                        <span class="material-icons">close</span> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="leave_id" value="<?php echo $application['leave_id']; ?>">
                                                    <button type="submit" name="action" value="delete" class="btn btn-delete">
                                                        <span class="material-icons">delete</span> Clear
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo ($_SESSION['role'] === 'headmaster') ? '7' : '6'; ?>">
                                    <div class="empty-state">
                                        <span class="material-icons">inbox</span>
                                        <p>No leave applications to approve.</p>
                                    </div>
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
