<?php
require_once 'db_connection.php';

// Get today's date
$today = date('Y-m-d');

// Count total users (excluding admin)
$sql_total = "SELECT COUNT(*) AS total FROM users WHERE role != 'admin'";
$result_total = $conn->query($sql_total);
$row_total = $result_total->fetch_assoc();
$total_users = $row_total['total'];

// Count how many have marked attendance today
$sql_present = "SELECT COUNT(DISTINCT user_id) AS present FROM attendance WHERE DATE(timestamp) = ?";
$stmt = $conn->prepare($sql_present);
$stmt->bind_param("s", $today);
$stmt->execute();
$result_present = $stmt->get_result();
$row_present = $result_present->fetch_assoc();
$present = $row_present['present'];

// Calculate absent
$absent = $total_users - $present;

// Return JSON
echo json_encode([
    "present" => $present,
    "absent" => $absent
]);

$conn->close();
?>
