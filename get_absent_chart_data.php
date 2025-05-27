<?php
require_once 'db_connection.php';

$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d', strtotime('-7 days'));
$to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

// Get total staff count
$staff_count_sql = "SELECT COUNT(*) as total FROM users WHERE role = 'staff'";
$staff_result = $conn->query($staff_count_sql);
$total_staff = $staff_result->fetch_assoc()['total'];

// Get attendance data for the date range
$sql = "SELECT 
    d.date,
    COALESCE(a.present_count, 0) as present_count
FROM (
    SELECT DATE(date_range.date) as date
    FROM (
        SELECT DATE_ADD(?, INTERVAL n DAY) as date
        FROM (
            SELECT @row := @row + 1 as n
            FROM (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6) t,
            (SELECT @row := -1) r
            WHERE DATE_ADD(?, INTERVAL @row + 1 DAY) <= ?
        ) numbers
    ) date_range
) d
LEFT JOIN (
    SELECT 
        DATE(time_in) as date,
        COUNT(DISTINCT user_id) as present_count
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE u.role = 'staff' 
    GROUP BY DATE(time_in)
) a ON d.date = a.date
ORDER BY d.date";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("sss", $from, $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'date' => $row['date'],
            'count' => $total_staff - (int)$row['present_count'] // Calculate absent count
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    echo json_encode([]);
}
?>
