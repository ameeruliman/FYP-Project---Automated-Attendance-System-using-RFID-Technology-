<?php
require_once 'db_connection.php';

// Get the incoming update from Telegram
$update = json_decode(file_get_contents('php://input'), true);

// Log the update for debugging
file_put_contents('telegram_log.txt', date('Y-m-d H:i:s') . ': ' . print_r($update, true) . "\n", FILE_APPEND);

// Check if this is a message
if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';
    $username = $update['message']['from']['username'] ?? '';
    
    // If the message is a /start command or contains a phone number
    if ($text === '/start' || preg_match('/^\d{10,15}$/', $text)) {
        $phone = $text === '/start' ? '' : $text;
        
        // If user sent their phone number, try to match it with a user
        if (!empty($phone)) {
            // Update the user's telegram_chat_id in the database
            $stmt = $conn->prepare("UPDATE users SET telegram_chat_id = ? WHERE phone = ?");
            $stmt->bind_param("ss", $chat_id, $phone);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                // Send confirmation message
                sendTelegramMessage($chat_id, "✅ Your Telegram account has been linked to the attendance system. You will now receive alerts if you haven't clocked in by 8:05 AM.");
            } else {
                // Phone number not found
                sendTelegramMessage($chat_id, "❌ Phone number not found in our system. Please check and try again, or contact your administrator.");
            }
        } else {
            // Send welcome message asking for phone number
            sendTelegramMessage($chat_id, "👋 Welcome to the Attendance Alert System!\n\nPlease send your phone number (as registered in the system) to link your Telegram account.");
        }
    }
}

function sendTelegramMessage($chat_id, $message) {
    $bot_token = '8184908530:AAGSNkxdm6S2QNE_qgVUYBX-ELEuhzuvn2o';
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}

$conn->close();
?>