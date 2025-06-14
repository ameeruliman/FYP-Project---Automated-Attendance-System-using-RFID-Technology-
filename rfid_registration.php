<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uid'])) {
    // Sanitize UID (allow only hex characters)
    $uid = preg_replace('/[^A-Fa-f0-9]/', '', $_POST['uid']);
    // Store UID in session for the current registration
    $_SESSION['pending_rfid_uid'] = $uid;
    file_put_contents('latest_uid.txt', $uid);
    echo "UID received";
} else {
    echo "No UID received";
}
?>