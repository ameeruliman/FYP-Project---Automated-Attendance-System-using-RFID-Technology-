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

// Query to fetch attendance data
$sql = "SELECT a.id, u.username, u.uid, a.time_in, a.time_out 
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        ORDER BY a.time_in DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #343a40;
        }
        h2 {
            text-align: center;
            margin-top: 20px;
            color: #007bff;
        }
        .container {
            width: 90%;
            margin: auto;
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #dee2e6;
        }
        th {
            background-color: #007bff;
            color: #fff;
            font-weight: bold;
        }
        th, td {
            padding: 12px;
            text-align: center;
        }
        tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tbody tr:hover {
            background-color: #e9ecef;
        }
        .no-records {
            text-align: center;
            font-size: 1.2em;
            margin: 20px 0;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <h2>Attendance Records</h2>
    <div class="container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>RFID UID</th>
                    <th>Clock-In Time</th>
                    <th>Clock-Out Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['username']}</td>
                            <td>{$row['uid']}</td>
                            <td>{$row['time_in']}</td>
                            <td>" . ($row['time_out'] ? $row['time_out'] : 'Still Clocked In') . "</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='no-records'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php $conn->close(); ?>
