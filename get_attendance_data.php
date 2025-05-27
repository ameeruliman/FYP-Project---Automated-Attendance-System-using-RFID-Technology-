<?php
require_once 'db_connection.php';

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

$data = [];

if ($from && $to) {
    // Custom date range provided
    $sql = "SELECT DATE(time_in) as date, COUNT(DISTINCT user_id) as count 
            FROM attendance 
            WHERE DATE(time_in) BETWEEN ? AND ?
            GROUP BY DATE(time_in)
            ORDER BY date";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Default: last 5 days
    $sql = "SELECT DATE(time_in) as date, COUNT(DISTINCT user_id) as count 
            FROM attendance 
            WHERE time_in >= DATE_SUB(CURDATE(), INTERVAL 4 DAY)
            GROUP BY DATE(time_in)
            ORDER BY date";

    $result = $conn->query($sql);
}

while ($row = $result->fetch_assoc()) {
    $data[] = [
        'date' => $row['date'],
        'count' => (int)$row['count']
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
?>
