<?php
// Include the database connection
include('db_connection.php');

// Ensure the 'from' and 'to' parameters are sanitized
$from = isset($_GET['from']) ? mysqli_real_escape_string($conn, $_GET['from']) : null;
$to = isset($_GET['to']) ? mysqli_real_escape_string($conn, $_GET['to']) : null;

// Prepare the base query
$query = "SELECT date, COUNT(*) AS absent_count FROM attendance WHERE status = 0"; // assuming 0 means absent

// Add the date range condition if 'from' and 'to' parameters are provided
if ($from && $to) {
    $query .= " AND date BETWEEN '$from' AND '$to'";
}

$query .= " GROUP BY date ORDER BY date ASC";

// Execute the query
$result = mysqli_query($conn, $query);

// Check for errors
if (!$result) {
    die(json_encode(['error' => 'Query failed: ' . mysqli_error($conn)]));
}

// Initialize an empty array to hold the data
$data = [];

// Fetch data from the result set
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'date' => $row['date'],
        'count' => (int) $row['absent_count']  // Ensure count is an integer
    ];
}

// Set the response header to JSON
header('Content-Type: application/json');

// Output the JSON-encoded data
echo json_encode($data);

// Close the database connection
mysqli_close($conn);
?>
