<?php
session_start();

// Check if the user is logged in and is a staff
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: loginPage.php");
    exit();
}

// Include database connection
require_once 'db_connection.php';

// Get the logged-in user's data
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #333;
        }

        header {
            background: linear-gradient(135deg, #032539 0%, #1C768F 100%);
            color: #fff;
            width: 100%;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin: 0;
            font-size: 1.8em;
            font-weight: 600;
        }

        .container {
            display: flex;
            gap: 30px;
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .left-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .welcome-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .welcome-section h2 {
            color: #032539;
            margin: 0 0 10px 0;
        }

        .welcome-section p {
            color: #666;
            margin: 0;
            line-height: 1.6;
        }

        .menu-cards {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .card {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 12px;
            text-decoration: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: auto;
            gap: 15px;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .card-content {
            text-align: left;
        }

        .red { background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%); }
        .blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .green { background: linear-gradient(135deg, #34c759 0%, #10b981 100%); }

        .purple { background: linear-gradient(135deg, #9333ea 0%, #7e22ce 100%); }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .tip {
            color: white;
            font-size: 1.1em;
            font-weight: 600;
            margin: 0;
        }

        .second-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9em;
            margin: 5px 0 0 0;
        }

        .right-panel {
            width: 350px;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .staff-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .profile-pic {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #032539;
            padding: 4px;
        }

        .info-item {
            width: 100%;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .info-label {
            font-weight: 600;
            color: #032539;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .info-value {
            color: #666;
            font-size: 1em;
        }

        .edit-btn {
            background: linear-gradient(135deg, #032539 0%, #1C768F 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(3, 37, 57, 0.2);
        }

        .logout-btn {
            background: #f43f5e;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 500;
            text-align: center;
            display: block;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #e11d48;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.2);
        }

        .leave-summary-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 30px;
            max-width: 400px;
            margin-top: 10px;
        }

        .leave-box {
            text-align: center;
            flex: 1;
        }

        .leave-label {
            font-weight: 600;
            font-size: 0.95em;
            color: #032539;
            margin-bottom: 5px;
        }

        .leave-number {
            font-size: 1.4em;
            font-weight: bold;
            color: #10b981;
        }

        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
            }

            .right-panel {
                width: auto;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            .card {
                padding: 15px;
            }

            .profile-pic {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
<header>
    <h1>Staff Dashboard</h1>
</header>

<div class="container">
    <div class="left-panel">
        <div class="welcome-section">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Access your attendance records, submit emergency information, and manage leave applications all in one place.</p>
        </div>

        <div class="menu-cards">
            <a href="attendance_record.php" class="card red">
                <div class="card-icon">
                    <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                        <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                    </svg>
                </div>
                <div class="card-content">
                    <p class="tip">My Attendance</p>
                    <p class="second-text">View your attendance history</p>
                </div>
            </a>

            <a href="emergency_info.php" class="card green">
                <div class="card-icon">
                    <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                </div>
                <div class="card-content">
                    <p class="tip">Emergency Info</p>
                    <p class="second-text">Update your emergency contacts</p>
                </div>
            </a>

            <a href="apply_leave.php" class="card blue">
                <div class="card-icon">
                    <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                        <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1z"/>
                    </svg>
                </div>
                <div class="card-content">
                    <p class="tip">Leave Application</p>
                    <p class="second-text">Submit your leave requests</p>
                </div>
            </a>

            <a href="month_summary.php" class="card purple">
                <div class="card-icon">
                    <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                        <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-2 14l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>
                    </svg>
                </div>
                <div class="card-content">
                    <p class="tip">Month Summary</p>
                    <p class="second-text">View your monthly attendance report</p>
                </div>
            </a>
        </div>

        <!-- Combined Leave Balance Container -->
        <div class="leave-summary-container">
            <?php
                $leave_query = "SELECT COALESCE(SUM(DATEDIFF(until_date, from_date) + 1), 0) as total_days 
                                FROM leave_applications 
                                WHERE user_id = (SELECT id FROM users WHERE username = ?)
                                AND status = 'Approved'
                                AND YEAR(from_date) = YEAR(CURRENT_DATE())";
                $stmt = $conn->prepare($leave_query);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $used = $row['total_days'];
                $remaining = 30 - $used;
            ?>
            <div class="leave-box">
                <div class="leave-label">Used Leave</div>
                <div class="leave-number"><?php echo $used; ?></div>
            </div>
            <div class="leave-box">
                <div class="leave-label">Remaining Leave</div>
                <div class="leave-number"><?php echo $remaining; ?></div>
            </div>
        </div>

    </div>

    <div class="right-panel">
        <div class="staff-info">
            <?php
                $profile_image = 'uploads/' . $_SESSION['username'] . '.jpg';
                $profile_image_png = 'uploads/' . $_SESSION['username'] . '.png';
                if (file_exists($profile_image)) {
                    $profile_pic = $profile_image;
                } elseif (file_exists($profile_image_png)) {
                    $profile_pic = $profile_image_png;
                } else {
                    $profile_pic = 'default-profile.jpg';
                }
            ?>
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="" class="profile-pic">

            <div class="info-item">
                <div class="info-label">Name</div>
                <div class="info-value"><?php echo htmlspecialchars($user_data['username']); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Staff ID</div>
                <div class="info-value"><?php echo htmlspecialchars($user_data['staff_id']); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Phone Number</div>
                <div class="info-value"><?php echo htmlspecialchars($user_data['phone']); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></div>
            </div>

            <button class="edit-btn" onclick="window.location.href='edit_profile.php'">
                Edit Profile
            </button>

            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</div>
</body>
</html>
