<?php
session_start();

// Check if user is logged in and has the right role
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'guidance') {
    header('Location: ../login/index.php');
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "admindihs");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get gender statistics
function getGenderStats($conn) {
    $stats = ['M' => 0, 'F' => 0];
    $query = "SELECT Sex, COUNT(*) as count FROM admindihs.sf1 GROUP BY Sex";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $gender = strtoupper($row['Sex']);
            if (isset($stats[$gender])) {
                $stats[$gender] = (int)$row['count'];
            }
        }
    }
    return $stats;
}

// Function to get religion statistics
function getReligionStats($conn) {
    $stats = [];
    $query = "SELECT Religious_Affiliation, COUNT(*) as count 
              FROM admindihs.sf1 
              WHERE Religious_Affiliation IS NOT NULL AND Religious_Affiliation != ''
              GROUP BY Religious_Affiliation 
              ORDER BY count DESC 
              LIMIT 10";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $religion = $row['Religious_Affiliation'] ?: 'Not Specified';
            $stats[$religion] = (int)$row['count'];
        }
    }
    return $stats;
}

// Function to get age distribution
function getAgeStats($conn) {
    $stats = [];
    $query = "SELECT 
                CASE 
                    WHEN Age BETWEEN 0 AND 12 THEN '12 and below'
                    WHEN Age BETWEEN 13 AND 15 THEN '13-15'
                    WHEN Age BETWEEN 16 AND 18 THEN '16-18'
                    WHEN Age BETWEEN 19 AND 21 THEN '19-21'
                    ELSE '22 and above'
                END as age_group,
                COUNT(*) as count
              FROM admindihs.sf1 
              WHERE Age IS NOT NULL
              GROUP BY age_group
              ORDER BY age_group";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats[$row['age_group']] = (int)$row['count'];
        }
    }
    return $stats;
}

// Get all statistics
$genderStats = getGenderStats($conn);
$religionStats = getReligionStats($conn);
$ageStats = getAgeStats($conn);

// Generate color arrays for charts
$ageColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1'];
$religionColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1'];

// Generate age chart colors
$ageChartColors = [];
$ageKeys = array_keys($ageStats);
for ($i = 0; $i < count($ageKeys); $i++) {
    $ageChartColors[] = $ageColors[$i % count($ageColors)];
}

// Generate religion chart colors
$religionChartColors = [];
$religionKeys = array_keys($religionStats);
for ($i = 0; $i < count($religionKeys); $i++) {
    $religionChartColors[] = $religionColors[$i % count($religionColors)];
}

// Calculate total students
$totalStudents = array_sum($genderStats);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SF1 Statistics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: 'Arial', sans-serif;
            padding-top: 1rem;
            padding-left: 16rem; /* Account for sidebar width */
            min-height: 100vh;
            background-color: #f3f4f6;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: #4a90e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }
        .nav-item {
            transition: all 0.3s ease;
        }
        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .collapsed .menu-text,
        .collapsed .menu-title {
            display: none;
        }
        .collapsed {
            width: 5rem;
        }
        .collapsed .user-info {
            display: none;
        }
        .collapsed .user-avatar {
            margin: 0 auto;
        }
        .collapsed .menu-icon {
            margin: 0 auto;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Include Unified Sidebar -->
    <?php include_once __DIR__ . '/../includes/unified_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">SF1 Statistics Dashboard</h1>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Students -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500">Total Students</p>
                        <p class="text-3xl font-bold"><?php echo number_format($totalStudents); ?></p>
                    </div>
                </div>
            </div>

            <!-- Gender Distribution -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-venus-mars text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500">Male Students</p>
                        <p class="text-3xl font-bold"><?php echo number_format($genderStats['M']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-pink-100 text-pink-600 mr-4">
                        <i class="fas fa-venus text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500">Female Students</p>
                        <p class="text-3xl font-bold"><?php echo number_format($genderStats['F']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Gender Distribution Pie Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Gender Distribution</h2>
                <div class="h-64">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>

            <!-- Age Distribution Bar Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Age Distribution</h2>
                <div class="h-64">
                    <canvas id="ageChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Religion Distribution -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Top 10 Religious Affiliations</h2>
            <div class="h-96">
                <canvas id="religionChart"></canvas>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-4">Detailed Statistics</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- Gender Rows -->
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">Male Students</td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo number_format($genderStats['M']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $totalStudents > 0 ? round(($genderStats['M'] / $totalStudents) * 100, 1) . '%' : '0%'; ?></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">Female Students</td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo number_format($genderStats['F']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $totalStudents > 0 ? round(($genderStats['F'] / $totalStudents) * 100, 1) . '%' : '0%'; ?></td>
                            </tr>
                            
                            <!-- Age Distribution Rows -->
                            <?php foreach ($ageStats as $ageGroup => $count): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">Age <?php echo $ageGroup; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo number_format($count); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $totalStudents > 0 ? round(($count / $totalStudents) * 100, 1) . '%' : '0%'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-6">
            <a href="guidance_sf1.php" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring focus:ring-gray-200 active:bg-gray-600 disabled:opacity-25 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to SF1
            </a>
        </div>
    </div>

    <!-- Sidebar Toggle Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebar = document.querySelector('.fixed.left-0');
        const chevronLeft = document.querySelector('.fa-chevron-left');
        const chevronRight = document.querySelector('.fa-chevron-right');
        const menuTexts = document.querySelectorAll('.menu-text');
        const menuTitle = document.querySelector('.menu-title');
        const userInfo = document.querySelector('.user-info');

        // Toggle sidebar
        toggleSidebar.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            chevronLeft.classList.toggle('hidden');
            chevronRight.classList.toggle('hidden');
            
            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });

        // Load saved state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
            chevronLeft.classList.add('hidden');
            chevronRight.classList.remove('hidden');
        } else {
            chevronRight.classList.add('hidden');
        }
    });
    </script>

    <script>
        // Gender Distribution Pie Chart
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genderCtx, {
            type: 'pie',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [<?php echo $genderStats['M']; ?>, <?php echo $genderStats['F']; ?>],
                    backgroundColor: ['#3B82F6', '#EC4899'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Age Distribution Bar Chart
        const ageCtx = document.getElementById('ageChart').getContext('2d');
        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($ageStats)); ?>,
                datasets: [{
                    label: 'Number of Students',
                    data: <?php echo json_encode(array_values($ageStats)); ?>,
                    backgroundColor: <?php echo json_encode($ageChartColors); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Religion Distribution Horizontal Bar Chart
        const religionCtx = document.getElementById('religionChart').getContext('2d');
        new Chart(religionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($religionStats)); ?>,
                datasets: [{
                    label: 'Number of Students',
                    data: <?php echo json_encode(array_values($religionStats)); ?>,
                    backgroundColor: <?php echo json_encode($religionChartColors); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>
