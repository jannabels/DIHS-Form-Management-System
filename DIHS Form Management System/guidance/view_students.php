<?php
// Include database connection
include '../db_connect.php';

// Execute query to fetch all records from sf1 table
$query = "SELECT * FROM sf1 ORDER BY LRN ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Student Records</h1>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="py-3 px-4 text-left">LRN</th>
                            <th class="py-3 px-4 text-left">Name</th>
                            <th class="py-3 px-4 text-left">Sex</th>
                            <th class="py-3 px-4 text-left">Birthdate</th>
                            <th class="py-3 px-4 text-left">Age</th>
                            <th class="py-3 px-4 text-left">Contact</th>
                            <th class="py-3 px-4 text-left">Section</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while($student = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($student['LRN']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($student['Name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($student['Sex']); ?></td>
                                <td class="py-3 px-4"><?php echo $student['Birthdate'] ? date('M d, Y', strtotime($student['Birthdate'])) : 'N/A'; ?></td>
                                <td class="py-3 px-4"><?php echo $student['Age'] ?: 'N/A'; ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($student['Contact_Number'] ?: 'N/A'); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($student['section'] ?: 'N/A'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <i class="fas fa-user-graduate text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">No student records found in the database.</p>
                <?php if (!$result): ?>
                    <p class="text-red-500 mt-2">Error: <?php echo $conn->error; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="mt-6">
            <a href="guidance_sf1.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i> Back to SF1
            </a>
        </div>
    </div>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>