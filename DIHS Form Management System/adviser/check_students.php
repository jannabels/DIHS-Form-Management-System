<?php
include '../db_connect.php';

// Check if the section exists in the section table
$section = 'STEM 11-A';
$section_check = $conn->prepare("SELECT * FROM section WHERE class_name = ?");
$section_check->bind_param("s", $section);
$section_check->execute();
$section_result = $section_check->get_result();

if ($section_result->num_rows === 0) {
    die("Section '$section' not found in the section table.");
}

// Get students in the section
$query = "
    SELECT 
        s.LRN, 
        sf1.name, 
        s.section,
        s.status, 
        s.grade_level,
        section.track
    FROM sf9 s
    INNER JOIN sf1 ON s.LRN = sf1.LRN
    INNER JOIN section ON s.section = section.class_name
    WHERE s.section = ?
    ORDER BY sf1.name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $section);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Students in $section</h2>";
echo "<p>Query: " . htmlspecialchars($query) . "</p>";

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>LRN</th><th>Name</th><th>Grade Level</th><th>Track</th><th>Status</th></tr>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['LRN']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['grade_level']) . "</td>";
        echo "<td>" . htmlspecialchars($row['track']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No students found in this section.</td></tr>";
}

echo "</table>";

// Debug: Check if section name has hidden characters
$hex = unpack('H*', $section);
echo "<p>Section name hex: " . $hex[1] . "</p>";

// Check for any students in the sf9 table
$check_sf9 = $conn->query("SELECT COUNT(*) as count FROM sf9 WHERE section = '" . $conn->real_escape_string($section) . "'");
$sf9_count = $check_sf9->fetch_assoc()['count'];
echo "<p>Students in sf9 table with this section: " . $sf9_count . "</p>";

// Check for any students in the sf1 table
$check_sf1 = $conn->query("SELECT COUNT(*) as count FROM sf1");
$sf1_count = $check_sf1->fetch_assoc()['count'];
echo "<p>Total students in sf1 table: " . $sf1_count . "</p>";
?>
