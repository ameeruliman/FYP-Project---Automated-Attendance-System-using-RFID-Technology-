<?php
// Database connection
// $servername = "localhost";
// $username = "root";  // Default username for XAMPP
// $password = "";      // Default password for XAMPP
// $dbname = "rfid_attendance";

include 'db_connection.php';

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_POST['uid'];

    // Query to find the user based on RFID
    $sql = "SELECT id, username FROM users WHERE rfid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists
        $row = $result->fetch_assoc();
        $user_id = $row['id'];
        $username = $row['username'];

        // Check for an active clock-in entry (no time_out yet)
        $sql = "SELECT id FROM attendance WHERE user_id = ? AND time_out IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Active clock-in found, update time_out
            $row = $result->fetch_assoc();
            $attendance_id = $row['id'];
            $sql = "UPDATE attendance SET time_out = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $attendance_id);

            if ($stmt->execute()) {
                echo "Goodbye, $username!   Clock-out ";
            } else {
                echo "Error updating clock-out: " . $conn->error;
            }
        } else {
            // No active clock-in, insert a new time_in
            $sql = "INSERT INTO attendance (user_id, uid, time_in) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $user_id, $uid);

            if ($stmt->execute()) {
                echo "Welcome, $username!   Clock-in ";
            } else {
                echo "Error inserting clock-in: " . $conn->error;
            }
        }
    } else {
        // Card not assigned
        echo "UID: $uid";
    }

    $stmt->close();
}

$conn->close();
?>
