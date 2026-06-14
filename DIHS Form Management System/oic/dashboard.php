<?php
include '../db_connect.php';

// Get dashboard statistics
$stats = [];

// Get user counts by role
$role_counts = [];
$roles = ['Teacher', 'Adviser', 'Guidance', 'Principal', 'Registrar'];
foreach($roles as $role) {
    $sql = "SELECT COUNT(*) as count FROM accounts WHERE Role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $role_counts[$role] = $result->fetch_assoc()['count'];
}

// Get total active/inactive users
$sql = "SELECT Status, COUNT(*) as count FROM accounts GROUP BY Status";
$result = $conn->query($sql);
$status_counts = ['active' => 0, 'inactive' => 0];
while($row = $result->fetch_assoc()) {
    $status_counts[strtolower($row['Status'])] = $row['count'];
}

// Get total users
$sql = "SELECT COUNT(*) as total FROM accounts";
$result = $conn->query($sql);
$total_users = $result->fetch_assoc()['total'];

// Get recent users (last 5)
$sql = "SELECT Username, `First Name`, `Last Name`, Role, Status FROM accounts ORDER BY Username DESC LIMIT 5";
$result = $conn->query($sql);
$recent_users = [];
while($row = $result->fetch_assoc()) {
    $recent_users[] = $row;
}

// ========== STUDENT STATISTICS ==========
// Get total students from SF1
$sql = "SELECT COUNT(*) as total FROM sf1";
$result = $conn->query($sql);
$total_students = $result->fetch_assoc()['total'];

// Get students by grade level
$grade_stats = ['Grade 11' => 0, 'Grade 12' => 0, 'Unassigned' => 0];
$sql = "SELECT 
    CASE 
        WHEN s.grade_level IS NOT NULL AND s.grade_level != '' THEN s.grade_level
        ELSE 'Unassigned'
    END as grade_level,
    COUNT(*) as count
    FROM sf1 sf
    LEFT JOIN sf9 sf9 ON sf.LRN = sf9.LRN
    LEFT JOIN section s ON sf9.section = s.class_name
    GROUP BY grade_level";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $grade_stats[$row['grade_level']] = $row['count'];
}

// Get students by track (excluding unassigned)
$track_stats = [];
$sql = "SELECT 
    s.track as track,
    COUNT(*) as count
    FROM sf1 sf
    INNER JOIN sf9 sf9 ON sf.LRN = sf9.LRN
    INNER JOIN section s ON sf9.section = s.class_name
    WHERE s.track IS NOT NULL AND s.track != ''
    GROUP BY s.track";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $track_stats[$row['track']] = $row['count'];
}

// Get school form connections
$school_forms = [
    'SF1 (School Form)' => 0,
    'SF9 (Academic Record)' => 0,
    'Assigned to Section' => 0,
    'Has Grades' => 0,
    'Has Attendance' => 0
];

// SF1 count
$sql = "SELECT COUNT(*) as count FROM sf1";
$result = $conn->query($sql);
$school_forms['SF1 (School Form)'] = $result->fetch_assoc()['count'];

// SF9 count
$sql = "SELECT COUNT(*) as count FROM sf9";
$result = $conn->query($sql);
$school_forms['SF9 (Academic Record)'] = $result->fetch_assoc()['count'];

// Students assigned to sections
$sql = "SELECT COUNT(DISTINCT sf.LRN) as count FROM sf1 sf INNER JOIN sf9 sf9 ON sf.LRN = sf9.LRN WHERE sf9.section != 'Unassigned'";
$result = $conn->query($sql);
$school_forms['Assigned to Section'] = $result->fetch_assoc()['count'];

// Students with grades
$sql = "SELECT COUNT(DISTINCT LRN) as count FROM student_grades";
$result = $conn->query($sql);
$school_forms['Has Grades'] = $result ? $result->fetch_assoc()['count'] : 0;

// Students with attendance
$sql = "SELECT COUNT(DISTINCT LRN) as count FROM monthly_attendance";
$result = $conn->query($sql);
$school_forms['Has Attendance'] = $result->fetch_assoc()['count'];

// ===== Gender Distribution by Grade Level =====
$gender_distribution = ['Grade 11' => ['Male' => 0, 'Female' => 0], 'Grade 12' => ['Male' => 0, 'Female' => 0]];

// Get all students with their gender and grade level
$sql = "SELECT 
    COALESCE(s.grade_level, 'Grade 11') as grade_level,
    sf.Sex,
    COUNT(*) as count
    FROM sf1 sf
    INNER JOIN sf9 sf9 ON sf.LRN = sf9.LRN
    INNER JOIN section s ON sf9.section = s.class_name
    WHERE s.grade_level IN ('Grade 11', 'Grade 12')
    AND sf.Sex IN ('M', 'F')
    GROUP BY s.grade_level, sf.Sex
    ORDER BY s.grade_level, sf.Sex";

error_log("Gender Distribution Query: " . $sql); // Debug log

$result = $conn->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $grade_level = $row['grade_level'];
        $gender = ($row['Sex'] == 'M') ? 'Male' : 'Female';
        
        // Debug log for each row
        error_log(sprintf(
            "Processing row - Grade: %s, Sex: %s, Count: %d", 
            $grade_level, 
            $row['Sex'], 
            $row['count']
        ));
        
        if (isset($gender_distribution[$grade_level][$gender])) {
            $gender_distribution[$grade_level][$gender] = (int)$row['count'];
        }
    }
    // Debug output
    error_log("Final Gender Distribution Data: " . print_r($gender_distribution, true));
} else {
    error_log("Error in gender distribution query: " . $conn->error);
}

// Debug: Check the final data being sent to JavaScript
error_log("JSON Data for Chart: " . json_encode($gender_distribution));

// Get recent student enrollments (last 5)
$sql = "SELECT sf.LRN, sf.Name, sf.Sex, s.grade_level, s.track 
        FROM sf1 sf 
        LEFT JOIN sf9 sf9 ON sf.LRN = sf9.LRN 
        LEFT JOIN section s ON sf9.section = s.class_name 
        ORDER BY sf.LRN DESC LIMIT 5";
$result = $conn->query($sql);
$recent_students = [];
while($row = $result->fetch_assoc()) {
    $recent_students[] = $row;
}

// Get system info
$server_info = [
    'php_version' => phpversion(),
    'server_time' => date('Y-m-d H:i:s'),
    'database_status' => $conn->ping() ? 'Connected' : 'Disconnected'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../guidance/gstyle.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Ensure the main content doesn't get hidden behind the sidebar */
        #mainContent {
            margin-left: 0; /* Match this with the sidebar width */
            transition: margin-left 0.3s ease;
            width: 100%;
        }
        /* Make sure the sidebar is on top of other content */
        .sidebar {
            z-index: 40;
        }
    </style>
</head>
<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set the user role in session if not set
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'OIC'; // Default to OIC role for this dashboard
    $_SESSION['username'] = 'OIC User';
}

// Debug output
$debug_info = [
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'sidebar_path' => realpath(__DIR__ . '/../includes/unified_sidebar.php'),
    'file_exists' => file_exists(__DIR__ . '/../includes/unified_sidebar.php'),
    'current_dir' => __DIR__
];

error_log('Dashboard Debug: ' . print_r($debug_info, true));

// Set current page for sidebar highlighting
$currentPage = 'dashboard';
?>

<body class="bg-gray-100 flex h-screen">
    <!-- Sidebar Container -->
    <div class="fixed left-0 top-0 bottom-0 w-64 bg-gray-800 text-white z-50 transition-all duration-300 ease-in-out">
        <!-- Sidebar Content -->
        <div class="h-full flex flex-col">
            <!-- Sidebar Header -->
            <div class="p-4 border-b border-gray-700 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <img src="../images/dihslogo.png" alt="Logo" class="h-8 w-auto">
                    <span class="text-lg font-semibold">DIHS System</span>
                </div>
                <button id="toggleSidebar" class="text-gray-400 hover:text-white focus:outline-none">
                    <i class="fas fa-chevron-left menu-toggle-icon"></i>
                </button>
            </div>
            
            <!-- Include the unified sidebar -->
            <?php include '../includes/unified_sidebar.php'; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden ml-64 transition-all duration-300 ease-in-out" id="mainContent">
        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-white relative z-10 p-6">
            <!-- Statistics Cards Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">IT Admin Dashboard Overview</h2>
                <select id="school-year" class="block w-40 pl-3 pr-10 py-2 text-sm border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 rounded-md bg-white shadow-sm">
                    <option>SY 2024-2025</option>
                    <option>SY 2023-2024</option>
                    <option>SY 2022-2023</option>
                </select>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Students -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500" style="background: rgba(255, 255, 255, 0.9);">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-graduation-cap text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Students</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_students; ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-green-600 text-sm font-medium">
                            <i class="fas fa-arrow-up"></i> Enrolled
                        </span>
                        <span class="text-gray-500 text-sm">this school year</span>
                    </div>
                </div>

                <!-- Grade 11 Students -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-user-graduate text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Grade 11</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $grade_stats['Grade 11']; ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-green-600 text-sm font-medium">
                            <i class="fas fa-arrow-up"></i> Senior High
                        </span>
                        <span class="text-gray-500 text-sm">students</span>
                    </div>
                </div>

                <!-- Grade 12 Students -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-medal text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Grade 12</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $grade_stats['Grade 12']; ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-green-600 text-sm font-medium">
                            <i class="fas fa-arrow-up"></i> Graduating
                        </span>
                        <span class="text-gray-500 text-sm">this year</span>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">System Users</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_users; ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-green-600 text-sm font-medium">
                            <i class="fas fa-arrow-up"></i> <?php echo $status_counts['active']; ?> active
                        </span>
                        <span class="text-gray-500 text-sm">users</span>
                    </div>
                </div>
            </div>

            <!-- Student Statistics -->
            <div class="mt-8">
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Student Statistics</h3>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Gender Distribution -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-4">Enrollment by Gender</h4>
                            <div class="bg-gray-50 rounded p-4 h-64">
                                <canvas id="genderDistributionChart"></canvas>
                            </div>
                        </div>
                        <!-- Track Distribution -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-4">Students by Track/Strand</h4>
                            <div class="bg-gray-50 rounded p-4 h-64">
                                <canvas id="trackDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar & Recent Activity -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Calendar & Recent Activity</h3>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Calendar -->
                        <div class="lg:col-span-2">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-md font-medium text-gray-700"><?php echo date('F Y'); ?></h4>
                                    <div class="flex space-x-2">
                                        <button class="p-1 hover:bg-gray-200 rounded">
                                            <i class="fas fa-chevron-left text-gray-600"></i>
                                        </button>
                                        <button class="p-1 hover:bg-gray-200 rounded">
                                            <i class="fas fa-chevron-right text-gray-600"></i>
                                        </button>
                                    </div>
                                </div>
                                <!-- Calendar Grid -->
                                <div class="grid grid-cols-7 gap-1 text-sm">
                                    <!-- Days of week -->
                                    <div class="p-2 text-center font-medium text-gray-600">Sun</div>
                                    <div class="p-2 text-center font-medium text-gray-600">Mon</div>
                                    <div class="p-2 text-center font-medium text-gray-600">Tue</div>
                                    <div class="p-2 text-center font-medium text-gray-600">Wed</div>
                                    <div class="p-2 text-center font-medium text-gray-600">Thu</div>
                                    <div class="p-2 text-center font-medium text-gray-600">Fri</div>
                                    <div class="p-2 text-center font-medium text-gray-600">Sat</div>
                                    
                                    <!-- Calendar days -->
                                    <?php
                                    $today = date('j');
                                    $daysInMonth = date('t');
                                    $firstDay = date('w', mktime(0, 0, 0, date('n'), 1, date('Y')));
                                    
                                    // Add empty cells for days before the first day of the month
                                    for ($i = 0; $i < $firstDay; $i++) {
                                        echo '<div class="p-2 text-center text-gray-400"></div>';
                                    }
                                    
                                    // Add days of the month
                                    for ($day = 1; $day <= $daysInMonth; $day++) {
                                        $isToday = $day == $today;
                                        $class = $isToday ? 'p-2 text-center bg-blue-500 text-white font-medium rounded' : 'p-2 text-center hover:bg-gray-100 rounded cursor-pointer';
                                        echo "<div class=\"$class\">$day</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- To-Do List -->
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="text-md font-medium text-gray-700">My To-Do List</h4>
                                <button id="addTodoBtn" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-plus-circle"></i> Add
                                </button>
                            </div>
                            <div id="todoList" class="space-y-2 max-h-80 overflow-y-auto">
                                <!-- To-do items will be added here dynamically -->
                                <div class="p-3 border border-gray-200 rounded-lg bg-white shadow-sm">
                                    <div class="flex items-start">
                                        <input type="checkbox" class="mt-1 mr-2 todo-checkbox" data-id="1">
                                        <div class="flex-1">
                                            <div class="text-sm text-gray-800">Review student submissions for grading</div>
                                            <div class="text-xs text-gray-500 mt-1">Due: Tomorrow</div>
                                        </div>
                                        <button class="text-gray-400 hover:text-red-500 delete-todo" data-id="1">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-3 border border-gray-200 rounded-lg bg-white shadow-sm">
                                    <div class="flex items-start">
                                        <input type="checkbox" class="mt-1 mr-2 todo-checkbox" data-id="2">
                                        <div class="flex-1">
                                            <div class="text-sm text-gray-800">Update class schedule for next week</div>
                                            <div class="text-xs text-gray-500 mt-1">Due: Friday</div>
                                        </div>
                                        <button class="text-gray-400 hover:text-red-500 delete-todo" data-id="2">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Add To-Do Form (initially hidden) -->
                            <div id="addTodoForm" class="mt-3 hidden">
                                <input type="text" id="newTodoInput" placeholder="Enter a new task" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                                <div class="flex justify-between mt-2">
                                    <input type="date" id="todoDueDate" class="text-sm border border-gray-300 rounded px-2 py-1">
                                    <div>
                                        <button id="cancelTodoBtn" class="text-gray-500 text-sm mr-2">Cancel</button>
                                        <button id="saveTodoBtn" class="bg-green-600 text-white text-sm px-3 py-1 rounded hover:bg-green-700">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.getElementById('mainContent');

            // Function to update main content margin based on sidebar state
            function updateMainContentMargin() {
                if (sidebar && mainContent) {
                    if (sidebar.classList.contains('collapsed')) {
                        mainContent.style.marginLeft = '0'; // 85px sidebar + 32px margin
                    } else {
                        mainContent.style.marginLeft = '16rem'; // 270px sidebar + 32px margin
                    }
                }
            }

            // Update margin on window resize
            window.addEventListener('resize', updateMainContentMargin);
            
            // Listen for sidebar toggle events
            window.addEventListener('sidebarToggle', updateMainContentMargin);
            
            // Initial margin update
            updateMainContentMargin();

            // ===== Charts Initialization =====
            // Gender Distribution Chart
            const genderCtx = document.getElementById('genderDistributionChart');
            if (genderCtx) {
                const genderData = <?php echo json_encode($gender_distribution); ?>;
                console.log("Gender Data:", genderData); // Debug log
                
                new Chart(genderCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Grade 11', 'Grade 12'],
                        datasets: [
                            {
                                label: 'Male',
                                data: [
                                    genderData['Grade 11']?.Male || 0,
                                    genderData['Grade 12']?.Male || 0
                                ],
                                backgroundColor: '#3B82F6',
                                borderRadius: 4
                            },
                            {
                                label: 'Female',
                                data: [
                                    genderData['Grade 11']?.Female || 0,
                                    genderData['Grade 12']?.Female || 0
                                ],
                                backgroundColor: '#EC4899',
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                grid: { display: false }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { 
                                    stepSize: 1,
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.dataset.label}: ${context.raw} students`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Track Distribution Chart
            const trackCtx = document.getElementById('trackDistributionChart');
            if (trackCtx) {
                const trackData = <?php echo json_encode($track_stats); ?>;
                new Chart(trackCtx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(trackData),
                        datasets: [{
                            data: Object.values(trackData),
                            backgroundColor: [
                                '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'
                            ],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    boxWidth: 8
                                }
                            }
                        }
                    }
                });
            }
        });

        // Auto-refresh dashboard every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>

    <!-- Sidebar Styles -->
    <style>
        /* Importing Google Fonts - Poppins */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: #fff;
        }
        
        .sidebar {
            position: fixed;
            width: 0px;
            margin: 0px;
            border-radius: 0px;
            background: #16a34a;
            height: calc(100vh - 0px);
            transition: all 0.4s ease;
            z-index: 1000;
        }
        
        .sidebar.collapsed {
            width: 0px;
        }
        
        .sidebar .sidebar-header {
            display: none;
        }
        
        .sidebar-nav .nav-list {
            list-style: none;
            display: none;
            gap: 4px;
            padding: 0 15px;
            flex-direction: column;
            transform: translateY(15px);
            transition: 0.4s ease;
        }
        
        .sidebar.collapsed .sidebar-nav .primary-nav {
            transform: translateY(65px);
        }
        
        .sidebar-nav .nav-link {
            color: #fff;
            display: none;
            gap: 12px;
            white-space: nowrap;
            border-radius: 8px;
            padding: 12px 15px;
            align-items: center;
            text-decoration: none;
            transition: 0.4s ease;
        }
        
        .sidebar.collapsed .sidebar-nav .nav-link {
            border-radius: 12px;
        }
        
        .sidebar .sidebar-nav .nav-link .nav-label {
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .sidebar-nav .nav-link .nav-label {
            opacity: 0;
            pointer-events: none;
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            color: #16a34a;
            background: #fff;
        }
        
        .sidebar-nav .nav-item {
            position: relative;
        }
        
        .sidebar-nav .nav-tooltip {
            position: absolute;
            top: -10px;
            opacity: 0;
            color: #16a34a;
            display: none;
            pointer-events: none;
            padding: 6px 12px;
            border-radius: 8px;
            white-space: nowrap;
            background: #fff;
            left: calc(100% + 25px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            transition: 0s;
        }
        
        .sidebar.collapsed .sidebar-nav .nav-tooltip {
            display: none;
        }
        
        .sidebar-nav .nav-item:hover .nav-tooltip {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(50%);
            transition: all 0.4s ease;
        }
        
        .sidebar-nav .secondary-nav {
            position: absolute;
            bottom: 30px;
            width: 100%;
        }
        
        /* Responsive media query code for small screens */
        @media (max-width: 1024px) {
            .sidebar {
                height: 0px;
                margin: 0px;
                overflow-y: hidden;
                scrollbar-width: none;
                width: calc(100% - 0px);
                max-height: calc(100vh - 0px);
            }
            
            .sidebar.menu-active {
                overflow-y: auto;
            }
            
            .sidebar .sidebar-header {
                position: sticky;
                top: 0;
                z-index: 20;
                border-radius: 0px;
                background: #16a34a;
                padding: 0px 0px;
            }
            
            .sidebar-header .header-logo {
                width: 0px;
                height: 0px;
                padding: 0px;
            }
            
            .sidebar-header .header-logo img {
                width: 0px;
                height: 0px;
            }
            
            .sidebar-header .sidebar-toggler,
            .sidebar-nav .nav-item:hover .nav-tooltip {
                display: none;
            }
            
            .sidebar-header .menu-toggler {
                display: none;
            }
            
            .sidebar .sidebar-nav .nav-list {
                padding: 0 0px;
            }
            
            .sidebar-nav .nav-link {
                gap: 0px;
                padding: 0px;
                font-size: 0px;
            }
            
            .sidebar-nav .nav-link .nav-icon {
                font-size: 0px;
            }
            
            .sidebar-nav .secondary-nav {
                position: relative;
                bottom: 0;
                margin: 0px 0 0px;
            }
        }
    </style>

    <!-- Sidebar Script -->
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector(".fixed.left-0");
            const mainContent = document.getElementById("mainContent");
            const toggleBtn = document.getElementById("toggleSidebar");
            
            // Initialize sidebar state
            function initSidebar() {
                // Set initial state based on screen size
                if (window.innerWidth >= 1024) {
                    // Desktop view - show sidebar by default
                    mainContent.classList.remove('sidebar-collapsed');
                    if (toggleBtn) {
                        toggleBtn.querySelector('.fa-chevron-left').classList.remove('hidden');
                        toggleBtn.querySelector('.fa-chevron-right').classList.add('hidden');
                    }
                } else {
                    // Mobile view - hide sidebar by default
                    mainContent.classList.add('sidebar-collapsed');
                    if (toggleBtn) {
                        toggleBtn.querySelector('.fa-chevron-left').classList.add('hidden');
                        toggleBtn.querySelector('.fa-chevron-right').classList.remove('hidden');
                    }
                }
            }
            
            // Toggle sidebar
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    mainContent.classList.toggle('sidebar-collapsed');
                    
                    // Toggle chevron icons
                    const chevronLeft = toggleBtn.querySelector('.fa-chevron-left');
                    const chevronRight = toggleBtn.querySelector('.fa-chevron-right');
                    
                    if (mainContent.classList.contains('sidebar-collapsed')) {
                        chevronLeft.classList.add('hidden');
                        chevronRight.classList.remove('hidden');
                    } else {
                        chevronLeft.classList.remove('hidden');
                        chevronRight.classList.add('hidden');
                    }
                });
            }
            
            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    initSidebar();
                }, 250);
            });
            
            // Initialize sidebar on page load
            initSidebar();
        });
    </script>
</body>
</html>