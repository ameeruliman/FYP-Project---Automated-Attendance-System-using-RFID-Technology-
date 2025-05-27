<?php
require 'db_connection.php'; // Include database connection
session_start(); // Start session to store user data

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the input values
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate that inputs are not empty
    if (empty($username) || empty($password)) {
        header("Location: loginPage.php?error=empty_fields");
        exit();
    }

    // Sanitize input to prevent SQL injection
    $username = $conn->real_escape_string($username);

    // Prepare the SQL statement to fetch user details
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Error in preparing the statement
        // Log the error for debugging purposes (e.g., in error_log)
        error_log("SQL Error: " . $conn->error);
        header("Location: loginPage.php?error=server_error");
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password_hash'])) {
            // Password matches, start a session
            session_regenerate_id(true); // Prevent session fixation

            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $user['id']; // Store user ID in session

            // Check if 'role' exists in the user record
            if (isset($user['role'])) {
                $_SESSION['role'] = $user['role']; // Store user role in session
            } else {
                header("Location: loginPage.php?error=invalid_role");
                exit();
            }

            // Redirect based on user role
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin_page.php");
            } elseif ($_SESSION['role'] === 'staff') {
                header("Location: staff_page.php");
            } elseif ($_SESSION['role'] === 'headmaster') {
                header("Location: hm_page.php"); // Redirect to Headmaster Dashboard
            } else {
                // Handle other roles if necessary
                header("Location: user_dashboard.php");
            }
            exit();
        } else {
            // Invalid password, redirect to login page with error
            header("Location: loginPage.php?error=invalid_password");
            exit();
        }
    } else {
        // User not found, redirect to login page with error
        header("Location: loginPage.php?error=user_not_found");
        exit();
    }

    $stmt->close();
}
$conn->close();
?>
