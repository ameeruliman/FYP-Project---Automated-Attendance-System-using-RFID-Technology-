<?php
session_start();

// Check if the user is logged in and is a staff
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: loginPage.php");
    exit();
}

// Database connection
// $servername = "localhost";
// $username = "root";  // Default username for XAMPP
// $password = "";      // Default password for XAMPP
// $dbname = "rfid_attendance";

include 'db_connection.php';

// Create connection
// $conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['remove_picture'])) {
        // Handle profile picture removal
        $profile_image = 'uploads/' . $_SESSION['username'] . '.jpg';
        $profile_image_png = 'uploads/' . $_SESSION['username'] . '.png';
        
        // Check and remove both JPG and PNG versions if they exist
        if (file_exists($profile_image)) {
            unlink($profile_image);
        }
        if (file_exists($profile_image_png)) {
            unlink($profile_image_png);
        }
        
        // Update database to remove profile picture reference
        $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE username = ?");
        $stmt->bind_param("s", $_SESSION['username']);
        
        if ($stmt->execute()) {
            $message = "Profile picture removed successfully!";
            $messageType = "success";
        } else {
            $message = "Error removing profile picture.";
            $messageType = "error";
        }
    } else {
        $staff_id = $_POST['staff_id'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        
        // Handle image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $new_filename = $_SESSION['username'] . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file);
        }
        
        // Update user data without telegram_chat_id
        $sql = "UPDATE users SET staff_id = ?, phone = ?, email = ? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $staff_id, $phone, $email, $_SESSION['username']);
        
        if ($stmt->execute()) {
            $message = "Profile updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating profile: " . $conn->error;
            $messageType = "error";
        }
    }
}

// Fetch current user data without telegram_chat_id
$sql = "SELECT staff_id, phone, email, profile_picture FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Staff Dashboard</title>
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
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }

        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #032539;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #1C768F;
            outline: none;
            box-shadow: 0 0 0 3px rgba(28, 118, 143, 0.1);
        }

        .profile-preview {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            border: 4px solid #032539;
            padding: 4px;
        }

        .file-input-wrapper {
            text-align: center;
            margin-bottom: 30px;
        }

        /* Add these new styles */
        .custom-file-upload {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #032539 0%, #1C768F 100%);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .custom-file-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(3, 37, 57, 0.2);
        }

        input[type="file"] {
            display: none;
        }

        .remove-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .remove-btn:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        .submit-btn {
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

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(3, 37, 57, 0.2);
        }

        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #f43f5e;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .back-btn:hover {
            background: #e11d48;
            transform: translateY(-2px);
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .message.success {
            background-color: #dcfce7;
            color: #166534;
        }

        .message.error {
            background-color: #fee2e2;
            color: #dc2626;
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            .edit-form {
                padding: 20px;
            }

            .profile-preview {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Edit Profile</h1>
    </header>

    <div class="container">
        <form class="edit-form" method="POST" enctype="multipart/form-data">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            
            <!-- In the HTML section, update the file input wrapper -->
            <div class="file-input-wrapper">
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
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" 
                     alt="" class="profile-preview" id="profile-preview">
                <div style="text-align: center; margin: 10px 0;">
                    <?php if ($profile_pic === 'default-profile.jpg'): ?>
                        <label for="profile-image" class="custom-file-upload">
                            Upload Photo
                        </label>
                    <?php endif; ?>
                    <input type="file" name="profile_image" id="profile-image" accept="image/jpeg,image/png"
                           onchange="previewImage(this);">
                    <?php if ($profile_pic !== 'default-profile.jpg'): ?>
                        <button type="submit" name="remove_picture" class="remove-btn" 
                                onclick="return confirm('Are you sure you want to remove your profile picture?')">
                            Remove Picture
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="staff_id">Staff ID</label>
                <input type="text" id="staff_id" name="staff_id" 
                       value="<?php echo htmlspecialchars($user_data['staff_id'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
            </div>

            <button type="submit" class="submit-btn">Save Changes</button>
            <a href="staff_page.php" class="back-btn">Back to Dashboard</a>
        </form>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>