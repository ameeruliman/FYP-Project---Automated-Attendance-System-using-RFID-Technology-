<?php
// Database connection variables
$servername = "localhost";
$username = "root"; // Default username
$password = ""; // Default password for XAMPP/MAMP
$dbname = "rfid_attendance";

// Create connection using Object-Oriented Approach
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the error to a file and display a generic message
    error_log("Connection failed: " . $conn->connect_error); // Log error to server logs
    die("Connection failed. Please try again later."); // Friendly error message to the user
}
?>
