<?php
require_once 'config/database.php';

echo "<h2>Password Reset Tool</h2>";

// Generate fresh hash for 'password123'
$new_hash = password_hash('password123', PASSWORD_DEFAULT);
echo "New hash for 'password123': <code>" . $new_hash . "</code><br><br>";

// Update admin password
$sql = "UPDATE users SET password = '$new_hash' WHERE username = 'admin'";
if ($conn->query($sql)) {
    echo "✅ Admin password updated successfully!<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// Update customer passwords
$sql2 = "UPDATE users SET password = '$new_hash' WHERE role = 'customer'";
if ($conn->query($sql2)) {
    echo "✅ Customer passwords updated successfully!<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// Verify
$verify = $conn->query("SELECT username, password FROM users");
echo "<h3>Updated Users:</h3>";
while ($row = $verify->fetch_assoc()) {
    echo $row['username'] . " - Hash updated<br>";
}

echo "<br><strong>Now try logging in with: admin / password123</strong>";
$conn->close();
?>
