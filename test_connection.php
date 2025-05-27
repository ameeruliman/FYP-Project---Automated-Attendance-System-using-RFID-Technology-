<?php
require 'db_connection.php'; // Include the database connection

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
} else {
    echo "Connected successfully!";
}

$conn->close(); // Close the connection
?>
