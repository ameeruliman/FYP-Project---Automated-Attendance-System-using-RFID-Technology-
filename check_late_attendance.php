<?php
require_once 'db_connection.php';
date_default_timezone_set('Asia/Kuala_Lumpur'); // Adjust to your timezone
function logMessage($message) {
    $logFile = __DIR__ . '/email_alert_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}
function sendEmailAlert($to, $name) {
    $from = 'ameeruliman6051@gmail.com'; // Replace with your actual email
    $fromName = 'Attendance System';
    $subject = 'Attendance Reminder: Clock-in Required';
    $date = date('l, F j, Y');
    $textMessage = "Dear $name,\n\nOur records show that you have not clocked in yet today ($date).\nPlease remember that clock-in time is 1:30 PM, and you are now delayed.\n\nIf you are already at work, please clock in immediately.\nIf you are taking leave today, please update your status in the system.\n\nThis is an automated message from the Attendance System.\nPlease do not reply to this email.\n\nRegards,\nHR Department";
    $htmlMessage = "<html><head><style>body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }.container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }.header { background-color: #f8f8f8; padding: 10px; border-bottom: 1px solid #ddd; }.footer { font-size: 12px; color: #777; margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; }h2 { color: #444; }.important { color: #d9534f; font-weight: bold; }</style></head><body><div class='container'><div class='header'><h2>Attendance Reminder</h2></div><p>Dear <strong>$name</strong>,</p><p>Our records show that you have not clocked in yet today (<strong>$date</strong>).</p><p class='important'>Please remember that clock-in time is 1:30 PM, and you are now delayed.</p><p>If you are already at work, please clock in immediately.</p><p>If you are taking leave today, please update your status in the system.</p><div class='footer'><p>This is an automated message from the Attendance System.<br>Please do not reply to this email.</p><p>Regards,<br>HR Department</p></div></div></body></html>";
    $headers = "From: $fromName <$from>\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "Return-Path: $from\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative;\r\n";
    $headers .= " boundary=\"boundary\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "X-Priority: 3\r\n";
    $headers .= "X-MSMail-Priority: Normal\r\n";
    $message = "--boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $textMessage . "\r\n\r\n";
    $message .= "--boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $htmlMessage . "\r\n\r\n";
    $message .= "--boundary--";
    $success = mail($to, $subject, $message, $headers);
    if ($success) {
        logMessage("Email alert sent successfully to $name ($to)");
        return true;
    } else {
        logMessage("Failed to send email alert to $name ($to)");
        return false;
    }
}
$dayOfWeek = date('N');
if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
    $currentHour = (int)date('G');
    $currentMinute = (int)date('i');
    $isTest = isset($_GET['test']) && $_GET['test'] == '1';
    
    // Check if current time is between 1:45 PM and 5:00 PM
    if ($isTest || (($currentHour == 13 && $currentMinute >= 45) || ($currentHour > 13 && $currentHour < 17))) {
        logMessage("Starting late attendance check" . ($isTest ? " (TEST MODE)" : ""));
        $today = date('Y-m-d');
        $sql = "SELECT u.id, u.username, u.email FROM users u WHERE u.id NOT IN (SELECT user_id FROM attendance WHERE DATE(clock_in) = ? AND clock_in IS NOT NULL) AND u.role = 'staff' AND u.status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $alertCount = 0;
        $failCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (empty($row['email'])) {
                    logMessage("Skipping user {$row['username']} (ID: {$row['id']}) - No email address");
                    continue;
                }
                $success = sendEmailAlert($row['email'], $row['username']);
                if ($success) {
                    $alertCount++;
                } else {
                    $failCount++;
                }
            }
        }
        logMessage("Completed late attendance check. Sent: $alertCount, Failed: $failCount");
        if ($isTest) {
            echo "<h2>Late Attendance Check Results</h2>";
            echo "<p>Date: $today</p>";
            echo "<p>Alerts sent: $alertCount</p>";
            echo "<p>Alerts failed: $failCount</p>";
            echo "<p>Check the email_alert_log.txt file for details.</p>";
        }
    } else {
        logMessage("Script executed outside target time window. Current time: " . date('H:i'));
        if (isset($_GET['test']) && $_GET['test'] == '1') {
            echo "<h2>Outside Target Time Window</h2>";
            echo "<p>This script is designed to run between 1:45 PM and 5:00 PM on weekdays.</p>";
            echo "<p>Current time: " . date('H:i') . "</p>";
            echo "<p>Current day: " . date('l') . "</p>";
        }
    }
} else {
    logMessage("Script executed on weekend. Current day: " . date('l'));
    if (isset($_GET['test']) && $_GET['test'] == '1') {
        echo "<h2>Weekend Detected</h2>";
        echo "<p>This script is designed to run only on weekdays (Monday-Friday).</p>";
        echo "<p>Current day: " . date('l') . "</p>";
    }
}
$conn->close();
?>