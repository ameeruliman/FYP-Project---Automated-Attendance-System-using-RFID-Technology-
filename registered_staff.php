<?php
session_start();

// Check if the user is logged in and is an admin or headmaster
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'headmaster'])) {
    header("Location: loginPage.php");
    exit();
}

require_once 'db_connection.php'; // Include database connection

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
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #1e293b;
        }

        header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #fff;
            width: 100%;
            padding: 25px 0;
            text-align: center;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        header h1 {
            font-size: 2.5em;
            font-weight: 700;
            margin: 0;
            position: relative;
            display: inline-block;
            background: linear-gradient(90deg, #fff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        main {
            width: 95%;
            max-width: 1400px;
            padding: 30px;
            margin: 0 auto;
        }

        .staff-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
        }

        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .staff-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .staff-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .staff-header {
            background: linear-gradient(135deg, #9333ea 0%, #7e22ce 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            margin: 0 auto 15px;
            display: block;
            background-color: #f1f5f9;
        }

        .staff-name {
            font-size: 1.3em;
            font-weight: 600;
            margin: 0;
        }

        .staff-role {
            font-size: 0.9em;
            opacity: 0.9;
            margin-top: 5px;
        }

        .staff-details {
            padding: 20px;
        }

        .detail-item {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .detail-item .material-icons {
            margin-right: 10px;
            color: #9333ea;
            font-size: 20px;
        }

        .detail-label {
            font-weight: 500;
            color: #64748b;
            font-size: 0.9em;
            margin-bottom: 3px;
        }

        .detail-value {
            color: #1e293b;
            font-size: 1em;
        }

        .btn {
            text-align: center;
            margin-top: 30px;
        }

        .button1 {
            background: linear-gradient(135deg, #1e2a38 0%, #2d3a4e 100%);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .button1:hover {
            background: linear-gradient(135deg, #2d3a4e 0%, #1e2a38 100%);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #64748b;
        }

        .empty-state .material-icons {
            font-size: 48px;
            margin-bottom: 15px;
            color: #9333ea;
        }

        .empty-state p {
            font-size: 1.1em;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .staff-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 15px;
            }
            
            .staff-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Registered Staff</h1>
    </header>

    <main>
        <div class="staff-container">
            <h2>All Staff Members</h2>
            
            <?php if (count($staff_members) > 0): ?>
                <div class="staff-grid">
                    <?php foreach ($staff_members as $staff): ?>
                        <div class="staff-card">
                            <div class="staff-header">
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
                                <h3 class="staff-name"><?php echo htmlspecialchars($staff['username']); ?></h3>
                                <p class="staff-role">Staff Member</p>
                            </div>
                            <div class="staff-details">
                                <div class="detail-item">
                                    <span class="material-icons">person</span>
                                    <div>
                                        <div class="detail-label">Username</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($staff['username']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="material-icons">badge</span>
                                    <div>
                                        <div class="detail-label">Staff ID</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($staff['staff_id'] ?? 'Not assigned'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="material-icons">email</span>
                                    <div>
                                        <div class="detail-label">Email</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($staff['email'] ?? 'Not provided'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="material-icons">phone</span>
                                    <div>
                                        <div class="detail-label">Phone</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($staff['phone'] ?? 'Not provided'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="material-icons">calendar_today</span>
                                    <div>
                                        <div class="detail-label">Joined</div>
                                        <div class="detail-value"><?php echo date('F j, Y', strtotime($staff['created_at'])); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <span class="material-icons">people</span>
                    <p>No staff members registered yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="btn">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin_page.php" class="button1">
                    <span class="material-icons">arrow_back</span>
                    Back to Dashboard
                </a>
            <?php else: ?>
                <a href="hm_page.php" class="button1">
                    <span class="material-icons">arrow_back</span>
                    Back to Dashboard
                </a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>