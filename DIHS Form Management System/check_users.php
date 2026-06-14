<?php
require_once 'db_connect.php';

echo "<h2>Database Users and Roles</h2>";

// 1. List all users and their roles
$query = "SELECT id, username, role, created_at, last_login FROM accounts ORDER BY role, username";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<h3>User Accounts</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Created At</th><th>Last Login</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "<td>" . htmlspecialchars($row['last_login'] ?? 'Never') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No users found in the database.<br>";
}

// 2. Show table structure
echo "<h3>Accounts Table Structure</h3>";
$result = $conn->query("DESCRIBE accounts");

if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. List all distinct roles
echo "<h3>Available Roles</h3>";
$result = $conn->query("SELECT DISTINCT role, COUNT(*) as count FROM accounts GROUP BY role");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Role</th><th>User Count</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['count']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
