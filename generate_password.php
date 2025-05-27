<?php
// Display hashed password for reference
echo "<p>Hashed password for 'azim':<br>" . password_hash('azim', PASSWORD_BCRYPT) . "</p>";

// Database connection setup
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rfid_attendance";

try {
    // Create a new PDO connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the 'password' column exists in the 'users' table
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $columnNames = array_map(function($col) { return $col['Field']; }, $columns);
    
    // If 'password' column doesn't exist, add it
    if (!in_array('password', $columnNames)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL");
        echo "<p style='color:blue;'>🔧 'password' column added successfully.</p>";
    }

    // Hash the password
    $hashedPassword = password_hash("12345", PASSWORD_DEFAULT);

    // Check if headmaster user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['hm']);
    $user = $stmt->fetch();

    if ($user) {
        // Update existing headmaster's password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, 'hm']);
        echo "<p style='color:green;'>✅ Headmaster user 'hm' password updated successfully.</p>";
    } else {
        // Insert new headmaster user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['hm', $hashedPassword, 'headmaster']);
        echo "<p style='color:green;'>✅ Headmaster user 'hm' created successfully.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
