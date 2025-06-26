<?php
session_start();

// Check if the user is logged in and is an admin or headmaster
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'headmaster'])) {
    header("Location: loginPage.php");
    exit();
}

require_once 'db_connection.php'; // Include database connection

// Add this at the top of the file after session_start()
if (isset($_POST['delete_id']) && $_SESSION['role'] === 'admin') {
    $staff_id = intval($_POST['delete_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete from all related tables in correct order
        $tables = [
            'attendance',
            'leave_applications', // Added this table
            'emergency_info',
            'users'
        ];
        
        foreach ($tables as $table) {
            $field = ($table === 'users') ? 'id' : 'user_id';
            $stmt = $conn->prepare("DELETE FROM $table WHERE $field = ?");
            $stmt->bind_param("i", $staff_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Commit the transaction
        $conn->commit();
        $_SESSION['success_message'] = "Staff member deleted successfully";
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting staff: " . $e->getMessage();
    }
    
    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all staff members with the correct columns from the database
$stmt = $conn->prepare("
    SELECT id, username, role, staff_id, phone, email, created_at
    FROM users
    WHERE role = 'staff'
    ORDER BY created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
$staff_members = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-light: #818cf8;
            --primary-dark: #4338ca;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 16px;
            --transition: all 0.3s ease;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff;
            color: var(--dark-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
        }
        .main-container {
            width: 100%;
            max-width: 1200px;
            background: #fff;
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
            top: 0; left: 0; right: 0;
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
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
            background: #fff;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }
        th, td {
            padding: 1.2rem;
            text-align: left;
            vertical-align: middle;
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
        th:first-child { border-top-left-radius: 10px; }
        th:last-child { border-top-right-radius: 10px; }
        tr:last-child td:first-child { border-bottom-left-radius: 10px; }
        tr:last-child td:last-child { border-bottom-right-radius: 10px; }
        td {
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
        }
        tr:last-child td { border-bottom: none; }
        tr { transition: var(--transition); }
        tr:hover { background-color: rgba(79, 70, 229, 0.03); }
        .profile-image {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ede9fe;
            background-color: #ede9fe;
            margin-right: 18px;
            display: block;
        }
        .delete-form {
            display: inline;
        }
        .delete-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 0.7rem 1.6rem;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.08);
        }
        .delete-btn .material-icons {
            font-size: 22px;
            margin-right: 8px;
            vertical-align: middle;
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
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header">
            <h1>Registered Staff</h1>
            <p>Manage all staff members in the system</p>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <span class="material-icons">check_circle</span>
                <div><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <span class="material-icons">error</span>
                <div><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <span class="material-icons">groups</span>
                <h2>All Staff Members</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Username</th>
                            <th>Staff ID</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined</th>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($staff_members)): ?>
                            <?php foreach ($staff_members as $staff): ?>
                                <tr>
                                    <td>
                                        <?php
                                            $profile_image = 'uploads/' . $staff['username'] . '.jpg';
                                            $profile_image_png = 'uploads/' . $staff['username'] . '.png';
                                            if (file_exists($profile_image)) {
                                                $profile_pic = $profile_image;
                                            } elseif (file_exists($profile_image_png)) {
                                                $profile_pic = $profile_image_png;
                                            } else {
                                                $profile_pic = 'default_profile.jpg';
                                            }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="" class="profile-image">
                                    </td>
                                    <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['staff_id'] ?? 'Not assigned'); ?></td>
                                    <td><?php echo htmlspecialchars($staff['email'] ?? 'Not provided'); ?></td>
                                    <td><?php echo htmlspecialchars($staff['phone'] ?? 'Not provided'); ?></td>
                                    <td><?php echo date('F j, Y', strtotime($staff['created_at'])); ?></td>
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this staff member?');" class="delete-form">
                                                <input type="hidden" name="delete_id" value="<?php echo $staff['id']; ?>">
                                                <button type="submit" class="delete-btn">
                                                    <span class="material-icons">delete</span>
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo ($_SESSION['role'] === 'admin') ? '7' : '6'; ?>">
                                    <div class="empty-state">
                                        <span class="material-icons">inbox</span>
                                        <p>No staff members registered yet.</p>
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