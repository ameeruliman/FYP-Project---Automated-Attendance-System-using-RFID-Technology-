<?php
require_once 'db_connection.php';

$today = date('Y-m-d');

$sql = "SELECT COUNT(*) AS total_absent 
        FROM leave_applications 
        WHERE status = 'Approved' 
        AND from_date <= ? 
        AND until_date >= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $today, $today);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode(['absent' => $data['total_absent']]);

$conn->close();
?>
