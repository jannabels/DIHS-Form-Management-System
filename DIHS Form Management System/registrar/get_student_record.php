<?php
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['lrn']) || !isset($_POST['type'])) {
    die('Invalid request');
}

$lrn = $_POST['lrn'];
$type = $_POST['type'];

try {
    switch ($type) {
        case 'sf1':
            $stmt = $conn->prepare("
                SELECT * FROM sf1 
                WHERE LRN = ?
            ");
            $stmt->bind_param('s', $lrn);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $student = $result->fetch_assoc();
                echo '<table class="table table-bordered">';
                foreach ($student as $key => $value) {
                    if (in_array($key, ['id', 'password', 'created_at', 'updated_at'])) continue;
                    echo '<tr>';
                    echo '<th>' . ucwords(str_replace('_', ' ', $key)) . '</th>';
                    echo '<td>' . htmlspecialchars($value ?? 'N/A') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="alert alert-warning">No SF1 record found for this student.</div>';
            }
            break;
            
        case 'sf9':
            // Add your SF9 table query here
            $stmt = $conn->prepare("
                SELECT * FROM sf9 
                WHERE LRN = ?
                ORDER BY school_year, semester
            ");
            $stmt->bind_param('s', $lrn);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo '<table class="table table-bordered">';
                echo '<thead><tr><th>School Year</th><th>Semester</th><th>Grade Level</th><th>Status</th></tr></thead>';
                echo '<tbody>';
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['school_year'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($row['semester'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($row['grade_level'] ?? 'N/A') . '</td>';
                    echo '<td><span class="badge bg-info">' . htmlspecialchars($row['status'] ?? 'N/A') . '</span></td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<div class="alert alert-warning">No SF9 records found for this student.</div>';
            }
            break;
            
        case 'sf10':
            // Add your SF10 table query here
            $stmt = $conn->prepare("
                SELECT * FROM sf10 
                WHERE LRN = ?
                ORDER BY school_year, semester
            ");
            $stmt->bind_param('s', $lrn);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo '<table class="table table-bordered">';
                echo '<thead><tr><th>School Year</th><th>Semester</th><th>Grade Level</th><th>Status</th></tr></thead>';
                echo '<tbody>';
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['school_year'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($row['semester'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($row['grade_level'] ?? 'N/A') . '</td>';
                    echo '<td><span class="badge bg-info">' . htmlspecialchars($row['status'] ?? 'N/A') . '</span></td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<div class="alert alert-warning">No SF10 records found for this student.</div>';
            }
            break;
            
        default:
            echo '<div class="alert alert-danger">Invalid record type requested.</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

$conn->close();
?>