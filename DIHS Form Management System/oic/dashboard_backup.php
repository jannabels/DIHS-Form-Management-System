<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has OIC role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'OIC') {
    header('Location: /systemdihs/login/index.php');
    exit();
}

// Get current school year
$current_year = date('Y');
$next_year = $current_year + 1;
$current_sy = "$current_year-$next_year";

// Fetch dashboard data
$stats = [
    'total_students' => 0,
    'total_teachers' => 0,
    'total_classes' => 0,
    'attendance_rate' => 0,
    'male_students' => 0,
    'female_students' => 0
];

try {
    // Get total students
    $result = $conn->query("SELECT COUNT(DISTINCT LRN) as total FROM sf1 WHERE sy = '$current_sy'");
    $stats['total_students'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Get total teachers
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'Teacher' AND status = 'active'");
    $stats['total_teachers'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Get total classes
    $result = $conn->query("SELECT COUNT(DISTINCT section) as total FROM class_sections WHERE sy = '$current_sy'");
    $stats['total_classes'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Get gender distribution
    $result = $conn->query("
        SELECT 
            SUM(CASE WHEN Sex = 'M' THEN 1 ELSE 0 END) as male,
            SUM(CASE WHEN Sex = 'F' THEN 1 ELSE 0 END) as female 
        FROM sf1 
        WHERE sy = '$current_sy'
    ");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['male_students'] = $row['male'] ?? 0;
        $stats['female_students'] = $row['female'] ?? 0;
    }

    // Get attendance rate
    $result = $conn->query("
        SELECT ROUND(AVG(CASE WHEN status = 'present' THEN 100 ELSE 0 END), 1) as rate
        FROM daily_attendance 
        WHERE date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
    ");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['attendance_rate'] = $row['rate'] ?? 0;
    }

} catch (Exception $e) {
    // Handle any database errors
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OIC Dashboard - School Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .sidebar {
            transition: all 0.3s ease;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .main-content {
            transition: margin-left 0.3s ease;
        }
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="h-screen overflow-hidden">

        <!-- Main Content -->
        <div class="flex flex-col h-full">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm z-10">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-800">Dashboard Overview</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <button class="p-2 text-gray-500 hover:bg-gray-100 rounded-full">
                            <i class="far fa-bell"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <!-- School Year Selector -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['username'])[0]); ?>!</h2>
                        <p class="text-gray-600">Here's what's happening with your school today.</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <select id="school-year" class="appearance-none bg-white border border-gray-300 rounded-lg pl-4 pr-10 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option>SY 2024-2025</option>
                                <option>SY 2023-2024</option>
                                <option>SY 2022-2023</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-500 text-xs"></i>
                            </div>
                        </div>
                        <button class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-download mr-2"></i> Export
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Students -->
                    <div class="stat-card bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Students</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_students']); ?></p>
                                <div class="flex items-center mt-2 text-sm text-gray-500">
                                    <span class="text-green-500 mr-1"><i class="fas fa-arrow-up"></i> 12%</span>
                                    <span>from last month</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-lg bg-blue-50 text-blue-600">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Teachers -->
                    <div class="stat-card bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Teachers</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_teachers']); ?></p>
                                <div class="flex items-center mt-2 text-sm text-gray-500">
                                    <span class="text-green-500 mr-1"><i class="fas fa-arrow-up"></i> 5%</span>
                                    <span>from last month</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-lg bg-green-50 text-green-600">
                                <i class="fas fa-chalkboard-teacher text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Classes -->
                    <div class="stat-card bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Classes</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_classes']); ?></p>
                                <div class="flex items-center mt-2 text-sm text-gray-500">
                                    <span class="text-green-500 mr-1"><i class="fas fa-arrow-up"></i> 3%</span>
                                    <span>from last month</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-lg bg-yellow-50 text-yellow-600">
                                <i class="fas fa-school text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Rate -->
                    <div class="stat-card bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Attendance Rate</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['attendance_rate']; ?>%</p>
                                <div class="flex items-center mt-2 text-sm text-gray-500">
                                    <span class="text-green-500 mr-1"><i class="fas fa-arrow-up"></i> 2.5%</span>
                                    <span>from last week</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-lg bg-purple-50 text-purple-600">
                                <i class="fas fa-clipboard-check text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Enrollment by Grade Level -->
                    <div class="bg-white p-6 rounded-xl shadow-sm col-span-2">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-800">Enrollment by Grade Level</h3>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 text-xs bg-indigo-100 text-indigo-700 rounded-md">This Year</button>
                                <button class="px-3 py-1 text-xs bg-gray-100 text-gray-600 rounded-md hover:bg-gray-200">Last Year</button>
                            </div>
                        </div>
                        <div class="h-80">
                            <canvas id="enrollmentChart"></canvas>
                        </div>
                    </div>

                    <!-- Gender Distribution -->
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">Gender Distribution</h3>
                        <div class="h-64 flex items-center justify-center">
                            <canvas id="genderChart" width="200" height="200"></canvas>
                        </div>
                        <div class="mt-6 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-indigo-500 mr-2"></div>
                                    <span class="text-sm text-gray-600">Male</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900"><?php echo $stats['total_students'] > 0 ? round(($stats['male_students'] / $stats['total_students']) * 100, 1) : 0; ?>%</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-pink-500 mr-2"></div>
                                    <span class="text-sm text-gray-600">Female</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900"><?php echo $stats['total_students'] > 0 ? round(($stats['female_students'] / $stats['total_students']) * 100, 1) : 0; ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activities & Quick Stats -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Activities Overview -->
                    <div class="bg-white p-6 rounded-xl shadow-sm lg:col-span-2">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-800">Activities Overview</h3>
                            <div class="flex space-x-2">
                                <button id="btn-weekly" class="px-3 py-1 text-xs bg-indigo-100 text-indigo-700 rounded-md">Weekly</button>
                                <button id="btn-monthly" class="px-3 py-1 text-xs bg-gray-100 text-gray-600 rounded-md hover:bg-gray-200">Monthly</button>
                            </div>
                        </div>
                        <div class="h-80">
                            <canvas id="activitiesChart"></canvas>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="space-y-6">
                        <!-- Upcoming Events -->
                        <div class="bg-white p-6 rounded-xl shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Upcoming Events</h3>
                            <div class="space-y-4">
                                <?php
                                $events = [
                                    ['title' => 'Parent-Teacher Meeting', 'date' => 'Nov 25, 2023', 'color' => 'bg-indigo-500'],
                                    ['title' => 'Final Exams', 'date' => 'Dec 5-10, 2023', 'color' => 'bg-green-500'],
                                    ['title' => 'Christmas Break', 'date' => 'Dec 20, 2023 - Jan 3, 2024', 'color' => 'bg-red-500'],
                                ];
                                
                                foreach ($events as $event) {
                                    echo '<div class="flex items-start">';
                                    echo '    <div class="mt-1 w-2 h-2 rounded-full ' . $event['color'] . ' mr-3"></div>';
                                    echo '    <div>';
                                    echo '        <p class="text-sm font-medium text-gray-800">' . $event['title'] . '</p>';
                                    echo '        <p class="text-xs text-gray-500">' . $event['date'] . '</p>';
                                    echo '    </div>';
                                    echo '</div>';
                            </div>
                      
                        <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-500 mb-1">
                            <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                        </div>
                        <div class="grid grid-cols-7 gap-1">
                            <?php
                            $month = 11; $year = 2025;
                            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                            $firstDay = date('w', strtotime("$year-$month-01"));
                            
                            for ($i = 0; $i < $firstDay; $i++) {
                                echo '<div class="h-6"></div>';
                            }
                            
                            $today = date('j');
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $isToday = ($day == $today) ? 'bg-indigo-100 text-indigo-700 font-semibold' : 'hover:bg-gray-100';
                                echo "<div class='h-6 text-sm flex items-center justify-center rounded-full $isToday'>$day</div>";
                            }
                            ?>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <div class="text-center">
                                <span class="text-sm font-medium text-gray-800">Today: November 19</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Upcoming Events</h3>
                    <div class="space-y-4">
                        <?php
                        $events = [
                            ['title' => 'Parent-Teacher Meeting', 'date' => 'Nov 25, 2023', 'color' => 'bg-indigo-500'],
                            ['title' => 'Final Exams', 'date' => 'Dec 5-10, 2023', 'color' => 'bg-green-500'],
                            ['title' => 'Christmas Break', 'date' => 'Dec 20, 2023 - Jan 3, 2024', 'color' => 'bg-red-500'],
                        ];
                        
                        foreach ($events as $event) {
                            echo '<div class="flex items-start">';
                            echo '    <div class="mt-1 w-2 h-2 rounded-full ' . $event['color'] . ' mr-3"></div>';
                            echo '    <div>';
                            echo '        <p class="text-sm font-medium text-gray-800">' . $event['title'] . '</p>';
                            echo '        <p class="text-xs text-gray-500">' . $event['date'] . '</p>';
                            echo '    </div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </main>
        </div>

    <script>
        // School year selector
        document.getElementById('school-year').addEventListener('change', function() {
            console.log('Selected school year:', this.value);
            // Add AJAX call to update data based on selected year
        });

        // Enrollment Chart
        const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
        new Chart(enrollmentCtx, {
            type: 'bar',
            data: {
                labels: ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'],
                datasets: [{
                    label: 'Number of Students',
                    data: [120, 110, 105, 100, 95, 90],
                    backgroundColor: '#4f46e5',
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Gender Distribution Chart
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [<?php echo $stats['male_students']; ?>, <?php echo $stats['female_students']; ?>],
                    backgroundColor: ['#4f46e5', '#ec4899'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Activities Chart
        const activitiesCtx = document.getElementById('activitiesChart').getContext('2d');
        const activitiesChart = new Chart(activitiesCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [
                    {
                        label: 'Logins',
                        data: [65, 59, 80, 81, 56, 55, 40],
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.05)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'New Students',
                        data: [28, 48, 40, 19, 86, 27, 90],
                        borderColor: '#10b981',
                        borderWidth: 2,
                        tension: 0.3,
                        borderDash: [5, 5]
                    },
                    {
                        label: 'Attendance',
                        data: [45, 25, 60, 30, 70, 35, 75],
                        borderColor: '#f59e0b',
                        borderWidth: 2,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'white',
                        titleColor: '#111827',
                        bodyColor: '#4b5563',
                        borderColor: '#e5e7eb',
                        borderWidth: 1,
                        padding: 12,
                        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: '#e5e7eb'
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Toggle between weekly and monthly views
        document.getElementById('btn-weekly').addEventListener('click', function() {
            activitiesChart.data.labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            activitiesChart.update();
            this.classList.remove('bg-gray-100', 'text-gray-600');
            this.classList.add('bg-indigo-100', 'text-indigo-700');
            document.getElementById('btn-monthly').classList.remove('bg-indigo-100', 'text-indigo-700');
            document.getElementById('btn-monthly').classList.add('bg-gray-100', 'text-gray-600');
        });

        document.getElementById('btn-monthly').addEventListener('click', function() {
            activitiesChart.data.labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            activitiesChart.update();
            this.classList.remove('bg-gray-100', 'text-gray-600');
            this.classList.add('bg-indigo-100', 'text-indigo-700');
            document.getElementById('btn-weekly').classList.remove('bg-indigo-100', 'text-indigo-700');
            document.getElementById('btn-weekly').classList.add('bg-gray-100', 'text-gray-600');
        });
    </script>
</body>
</html>