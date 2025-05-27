<?php
// Check for errors in the URL and display them
$error = isset($_GET['error']) ? $_GET['error'] : '';
$errorMessage = '';

switch ($error) {
    case 'invalid_password':
        $errorMessage = 'Invalid password. Please try again.';
        break;
    case 'user_not_found':
        $errorMessage = 'User not found. Please check your username.';
        break;
    case 'invalid_role':
        $errorMessage = 'Unauthorized access. Please contact the administrator.';
        break;
    case 'empty_fields':
        $errorMessage = 'Both username and password are required.';
        break;
    case 'server_error':
        $errorMessage = 'An error occurred on the server. Please try again later.';
        break;
    default:
        $errorMessage = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Attendance System - Login</title>
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

        /* Update form background to be slightly transparent */
        .form {
            width: 100%;
            max-width: 400px;
            margin: 40px auto;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.97);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        /* Make header slightly transparent to blend with background */
        header {
            background: linear-gradient(135deg, rgba(3, 37, 57, 0.97) 0%, rgba(28, 118, 143, 0.97) 100%);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        header {
            background: linear-gradient(135deg, #032539 0%, #1C768F 100%);
            color: #fff;
            text-align: center;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            margin: 0;
            font-size: 2em;
            font-weight: 600;
        }

        header h2 {
            margin: 10px 0 0;
            font-size: 1.2em;
            font-weight: 400;
            opacity: 0.9;
        }

        .form {
            width: 100%;
            max-width: 400px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
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
    <header>
        <h1>Sekolah Agama Parit Jelutong</h1>
        <h2>Attendance Manager</h2>
    </header>

    <form class="form" action="process_login.php" method="POST">
        <p id="heading">Welcome Back</p>

        <!-- School Logo -->
        <img src="SAPJ_Logo.jpg" alt="School Logo" class="logo">

        <!-- Error Message -->
        <?php if ($errorMessage): ?>
            <div class="error-message">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#DC2626" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0-1A6 6 0 1 0 8 2a6 6 0 0 0 0 12zM8 4a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0v-3A.5.5 0 0 1 8 4zm0 6a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1z"/>
                </svg>
                <?php echo $errorMessage; ?>
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

        <!-- Login Button -->
        <div class="btn">
            <button type="submit" class="button1">Login</button>
        </div>

        <a href="signup.php" class="back-link">Don't have an account? Sign Up here</a>
    </form>
</body>
</html>
