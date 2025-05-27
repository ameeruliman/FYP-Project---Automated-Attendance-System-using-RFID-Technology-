<?php
// Database connection
$servername = "localhost";
$username = "root";  // Default username for XAMPP
$password = "";      // Default password for XAMPP
$dbname = "rfid_attendance";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username']; // User's name (or username)
    $rfid = $_POST['rfid'];         // RFID UID

    // Insert new user with RFID UID into the database
    $sql = "INSERT INTO users (username, rfid, created_at, role) 
            VALUES ('$username', '$rfid', NOW(), 'user')";
    if ($conn->query($sql) === TRUE) {
        echo "User registered successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
