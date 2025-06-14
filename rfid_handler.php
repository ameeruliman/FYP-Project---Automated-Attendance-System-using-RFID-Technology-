<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uid'])) {
    $uid = preg_replace('/[^A-Fa-f0-9]/', '', $_POST['uid']);

    // Check if UID exists in users table
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE rfid = ? OR uid = ?");
    $stmt->bind_param("ss", $uid, $uid);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $username);
        $stmt->fetch();

        // Check for an active clock-in entry (no time_out yet)
        $stmt2 = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND time_out IS NULL");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        if ($result2->num_rows > 0) {
            // Active clock-in found, update time_out
            $row = $result2->fetch_assoc();
            $attendance_id = $row['id'];
            $stmt3 = $conn->prepare("UPDATE attendance SET time_out = NOW() WHERE id = ?");
            $stmt3->bind_param("i", $attendance_id);
            if ($stmt3->execute()) {
                echo "Goodbye, $username!   Clock-out ";
            } else {
                echo "Error updating clock-out: " . $conn->error;
            }
            $stmt3->close();
        } else {
            // No active clock-in, insert a new time_in
            $stmt3 = $conn->prepare("INSERT INTO attendance (user_id, uid, time_in) VALUES (?, ?, NOW())");
            $stmt3->bind_param("is", $user_id, $uid);
            if ($stmt3->execute()) {
                echo "Welcome, $username!   Clock-in ";
            } else {
                echo "Error inserting clock-in: " . $conn->error;
            }
            $stmt3->close();
        }
        $stmt2->close();
    } else {
        // UID not found: store for registration
        file_put_contents('latest_uid.txt', $uid);
        echo "Done Register";
    }
    $stmt->close();
} else {
    echo "No UID received";
}
$conn->close();
?>