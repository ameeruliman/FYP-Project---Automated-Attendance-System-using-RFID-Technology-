<?php
// signup.php
session_start();
// Database connection
// $servername = "localhost";
// $username = "root";  // Default username for XAMPP
// $password = "";      // Default password for XAMPP
// $dbname = "rfid_attendance";

include 'db_connection.php';

// Create connection
// $conn = new mysqli($servername, $username, $password, $dbname);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['awaiting_rfid'])) {
        // Step 1: Validate user input and process form
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = $_POST['role'];

        if ($password !== $confirm_password) {
            header("Location: signup.php?error=password_mismatch");
            exit();
        }

        // Check if user already exists
        $check_user_query = "SELECT * FROM users WHERE username = '$username'";
        $result = $conn->query($check_user_query);
        if ($result->num_rows > 0) {
            header("Location: signup.php?error=user_exists");
            exit();
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Save data to session and prompt for RFID tap
        $_SESSION['username'] = $username;
        $_SESSION['password_hash'] = $password_hash;
        $_SESSION['role'] = $role;
        $_SESSION['awaiting_rfid'] = true;

        // Redirect to the same page with RFID prompt
        header("Location: signup.php?step=rfid");
        exit();
    } else {
        // Step 2: Capture RFID UID
        if (isset($_POST['uid']) && !empty($_POST['uid'])) {
            $uid = $_POST['uid'];
            $username = $_SESSION['username'];
            $password_hash = $_SESSION['password_hash'];
            $role = $_SESSION['role'];

            // Insert user into database
            $sql = "INSERT INTO users (username, password_hash, role, rfid, uid, created_at) 
                    VALUES ('$username', '$password_hash', '$role', '$uid', '$uid', NOW())";

            if ($conn->query($sql) === TRUE) {
                echo "Registration successful!";
                session_destroy(); // Clear session after success
                header("Location: loginPage.php");
                exit();
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            echo "RFID card not detected. Please tap your card again.";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Attendance System - Sign Up</title>
    <!-- Add Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(rgba(251, 243, 242, 0.75), rgba(251, 243, 242, 0.75)), 
                        url('school.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .header {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #032539 0%, #1C768F 100%);
            color: #fff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            margin: 0;
            font-size: 2em;
            font-weight: 600;
            color: #fff;
        }

        .header h2 {
            margin: 10px 0 0;
            font-size: 1.2em;
            font-weight: 400;
            opacity: 0.9;
            color: #fff;
        }

        /* Update form background to be slightly transparent */
        .form {
            width: 100%;
            max-width: 400px;
            margin: 40px auto;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.97);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .form:hover {
            transform: translateY(-5px);
        }

        .field {
            margin-bottom: 25px;
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: #1C768F;
            width: 20px;
            height: 20px;
        }

        .input-field {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .input-field:focus {
            border-color: #1C768F;
            outline: none;
            box-shadow: 0 0 0 3px rgba(28, 118, 143, 0.1);
        }

        .btn button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #1C768F 0%, #032539 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(28, 118, 143, 0.2);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #1C768F;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #032539;
        }

        .error-message {
            background-color: #FEE2E2;
            color: #DC2626;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #heading {
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            color: #032539;
            margin: 0 0 30px;
        }

        .logo {
            display: block;
            margin: 0 auto 30px;
            width: 120px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 480px) {
            .form {
                margin: 20px;
                padding: 20px;
            }

            header h1 {
                font-size: 1.5em;
            }

            header h2 {
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sekolah Agama Parit Jelutong</h1>
        <h2>Attendance Manager</h2>
    </div>

    <?php if (!isset($_GET['step'])): ?>
        <!-- Registration Form -->
        <form class="form" action="signup.php" method="POST">
            <p id="heading">Sign Up</p>

            <!-- School Logo -->
            <img src="SAPJ_Logo.jpg" alt="School Logo" class="logo">

            <!-- Error Message -->
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#DC2626" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0-1A6 6 0 1 0 8 2a6 6 0 0 0 0 12zM8 4a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0v-3A.5.5 0 0 1 8 4zm0 6a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1z"/>
                    </svg>
                    <?php
                        if ($_GET['error'] == 'user_exists') {
                            echo "User already exists. Please choose another username.";
                        } elseif ($_GET['error'] == 'password_mismatch') {
                            echo "Passwords do not match. Please try again.";
                        }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Username Field -->
            <div class="field">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                <input name="username" autocomplete="off" placeholder="Username" class="input-field" type="text" required>
            </div>

            <!-- Password Field -->
            <div class="field">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6z"/>
                </svg>
                <input name="password" placeholder="Password" class="input-field" type="password" required>
            </div>

            <!-- Confirm Password Field -->
            <div class="field">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6z"/>
                </svg>
                <input name="confirm_password" placeholder="Confirm Password" class="input-field" type="password" required>
            </div>

            <!-- Role Selection -->
            <div class="field">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
                <select name="role" class="input-field" required>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="btn">
                <button type="submit" class="button1">Sign Up</button>
            </div>

            <a href="loginPage.php" class="back-link">Already have an account? Login here</a>
        </form>
    <?php else: ?>
        <!-- RFID Tap Prompt -->
        <form class="form" action="signup.php" method="POST">
            <p id="heading">Enter Your RFID Card UID</p>
            <img src="SAPJ_Logo.jpg" alt="School Logo" class="logo">
            <div class="field">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12zM6 10h2v2H6zm0 4h8v2H6z"/>
                </svg>
                <input type="text" name="uid" class="input-field" placeholder="Please enter UID card..." required>
            </div>
            <div class="btn">
                <button type="submit" class="button1">Register</button>
            </div>
        </form>
    <?php endif; ?>
</body>
</html>
