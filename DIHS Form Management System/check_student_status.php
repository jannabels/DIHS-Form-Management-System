<?php
// Connect to database
include 'db_connect.php';

// Student LRN to check
$lrn = '107920130582';

// Query to get student details
$query = "SELECT LRN, Name, status, Remarks, archived_at FROM sf1 WHERE LRN = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $lrn);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    echo "<h2>Student Information</h2>";
    echo "<pre>";
    echo "LRN: " . htmlspecialchars($student['LRN']) . "\n";
    echo "Name: " . htmlspecialchars($student['Name']) . "\n";
    echo "Status: " . htmlspecialchars($student['status'] ?? 'Not set') . "\n";
    echo "Remarks: " . htmlspecialchars($student['Remarks'] ?? 'No remarks') . "\n";
    echo "Archived At: " . ($student['archived_at'] ? htmlspecialchars($student['archived_at']) : 'Not archived') . "\n";
    echo "</pre>";
} else {
    echo "No student found with LRN: " . htmlspecialchars($lrn);
}

// Check if student appears in archived students query
$query = "SELECT COUNT(*) as count FROM sf1 
          WHERE LRN = ? 
          AND (status = 'dropped' 
               OR status = 'transferred' 
               OR status = 'graduated' 
               OR LOWER(Remarks) LIKE '%kicked out%'
               OR LOWER(Remarks) LIKE '%dropped out%'
               OR LOWER(Remarks) LIKE '%transferred out%')";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $lrn);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];

echo "<h2>Archived Students Check</h2>";
if ($count > 0) {
    echo "<p>✅ Student appears in archived students query.</p>";
} else {
    echo "<p>❌ Student does NOT appear in archived students query.</p>";
}

// Check status and remarks
$query = "SELECT status, Remarks FROM sf1 WHERE LRN = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $lrn);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo "<h2>Status and Remarks Analysis</h2>";
echo "<pre>";
if (empty($data['status']) || $data['status'] === 'active') {
    echo "⚠️ Status is either not set or set to 'active'. Should be 'dropped' or 'transferred' for archiving.\n";
}

$remarks = strtolower($data['Remarks'] ?? '');
$has_archiving_remark = (strpos($remarks, 'dropped') !== false || 
                        strpos($remarks, 'transferred') !== false ||
                        strpos($remarks, 'kicked out') !== false);

echo "Has archiving remark: " . ($has_archiving_remark ? '✅ Yes' : '❌ No') . "\n";

if ($has_archiving_remark) {
    echo "Archiving remark found in: " . htmlspecialchars($data['Remarks']) . "\n";
}
echo "</pre>";

// Check archived_at field
$query = "SELECT archived_at FROM sf1 WHERE LRN = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $lrn);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo "<h2>Archival Status</h2>";
if (empty($data['archived_at'])) {
    echo "<p>❌ Student record is not marked as archived (archived_at is NULL).</p>";
    
    // Show how to fix it
    echo "<h3>To fix this issue, run this SQL query:</h3>";
    echo "<pre>
    UPDATE sf1 
    SET status = 'dropped', 
        archived_at = NOW() 
    WHERE LRN = '$lrn';
    </pre>";
} else {
    echo "<p>✅ Student record is marked as archived on: " . htmlspecialchars($data['archived_at']) . "</p>";
}

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    h2 { color: #2c3e50; margin-top: 30px; }
    h3 { color: #34495e; margin-top: 20px; }
</style>
