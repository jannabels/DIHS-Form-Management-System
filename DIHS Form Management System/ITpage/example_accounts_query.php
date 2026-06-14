<?php
include '../db_connect.php';

// Example: Fetch all accounts
$sql = "SELECT * FROM accounts";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Username: " . $row["Username"] . "<br>";
        // Output other fields as needed, e.g. $row["First Name"]
    }
} else {
    echo "No accounts found.";
}

$conn->close();
?>