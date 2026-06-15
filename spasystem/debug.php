<?php
require_once 'config/database.php';

echo "<h2>Luxe Saloon Debug Information</h2>";

// Test database connection
if ($conn->ping()) {
    echo "✅ Database connected successfully<br><br>";
} else {
    echo "❌ Database connection failed: " . $conn->error . "<br>";
}

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "✅ Users table exists<br>";
    
    // Check users in database
    $users = $conn->query("SELECT id, username, full_name, email, role FROM users");
    echo "<h3>Users in Database:</h3>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th></tr>";
    while ($user = $users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['full_name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test admin password verification
    $admin_check = $conn->query("SELECT * FROM users WHERE username = 'admin'");
    if ($admin = $admin_check->fetch_assoc()) {
        echo "<h3>Password Test:</h3>";
        echo "Stored hash: " . $admin['password'] . "<br>";
        
        // Test if 'password123' verifies
        if (password_verify('password123', $admin['password'])) {
            echo "✅ 'password123' is CORRECT for admin<br>";
        } else {
            echo "❌ 'password123' is INCORRECT for admin<br>";
            echo "Password needs to be reset (see instructions below)<br>";
        }
    }
} else {
    echo "❌ Users table does NOT exist!<br>";
    echo "You need to run the SQL setup script in MySQL Workbench.<br>";
}

// Check services
$services = $conn->query("SELECT COUNT(*) as count FROM services");
$svc = $services->fetch_assoc();
echo "<br>✅ Services found: " . $svc['count'] . "<br>";

$conn->close();
?>
