<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

// Redirect if not logged in as Registrar
if (!$user_id || $role !== 'Registrar') {
    header("Location: /systemdihs/login/index.php");
    exit();
}

// Set page title
$pageTitle = 'Archived Students - Registrar';

// Get the current school year
$current_school_year = date('Y') . '-' . (date('Y') + 1);

// Base query for archived students
$query = "SELECT 
    s.LRN, 
    s.Name, 
    s.Sex, 
    s.Birthdate, 
    s.Age, 
    s.Religious_Affiliation, 
    s.House_Street_Sitio_Purok, 
    s.Barangay, 
    s.Municipality_City, 
    s.Province, 
    s.Fathers_Name, 
    s.Mothers_Maiden_Name, 
    s.`Name(Guardian)`, 
    s.Relationship, 
    s.Contact_Number, 
    s.Remarks, 
    s.archived_at,
    s.section,
    IFNULL(sec.class_name, 'Unassigned') as section_name,
    IFNULL(sec.grade_level, 'N/A') as grade_level
FROM 
    sf1 s
LEFT JOIN 
    section sec ON s.section = sec.section_id
WHERE 
    s.status = 'archived' 
    AND (s.Remarks LIKE '%kicked out%' OR s.Remarks LIKE '%dropped out%' OR s.Remarks LIKE '%transferred out%')
ORDER BY 
    s.archived_at DESC, s.Name ASC";

try {
    $result = $conn->query($query);
    if (!$result) {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

$pageTitle = "Archived Students";
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title><?php echo $pageTitle; ?> - DHS System</title>
        <!-- Tailwind CSS -->
        <script src="https://cdn.tailwindcss.com"></script>
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            
            ::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }
            
            ::-webkit-scrollbar-thumb {
                background: #cbd5e0;
                border-radius: 4px;
            }
            
            ::-webkit-scrollbar-thumb:hover {
                background: #a0aec0;
            }
            
            /* Animations */
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .animate-fade-in {
                animation: fadeIn 0.3s ease-out forwards;
            }
            
            /* Card hover effect */
            .student-card {
                transition: all 0.2s ease-in-out;
            }
            
            .student-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            }
            
            /* For the rotate chevron in accordion */
            .transform-rotate-180 {
                transform: rotate(180deg);
            }
            
            /* Print styles */
            @media print {
                .no-print {
                    display: none !important;
                }
                
                body {
                    background: white;
                    color: black;
                    font-size: 12pt;
                }
                
                .card {
                    border: 1px solid #e2e8f0;
                    box-shadow: none;
                    page-break-inside: avoid;
                }
            }
        </style>
    </head>
    <body class="bg-gray-50">
        <div class="flex h-screen overflow-hidden">
            <!-- Include Unified Sidebar -->
            <?php include '../includes/unified_sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="flex-1 overflow-auto ml-0 md:ml-16 lg:ml-64 transition-all duration-300">
                <div class="p-4 md:p-6 lg:p-8">
                    <!-- Header Section -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo $pageTitle; ?></h1>
                            <p class="text-gray-600 mt-1">
                                <?php 
                                    if ($role === 'Adviser') {
                                        echo 'View archived students from your assigned sections';
                                    } else {
                                        echo 'View and manage archived student records';
                                    }
                                ?>
                            </p>
                        </div>
                        <div class="mt-4 md:mt-0 flex space-x-3">
                            <button onclick="window.print()" class="flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-print mr-2"></i> Print List
                            </button>
                            <div class="relative">
                                <button id="filterDropdown" class="flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-filter mr-2"></i> Filter
                                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                                </button>
                                <!-- Dropdown menu -->
                                <div id="filterMenu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                                    <div class="p-3">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Filter by Status</p>
                                        <div class="space-y-2">
                                            <label class="flex items-center">
                                                <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                                <span class="ml-2 text-sm text-gray-700">All Students</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                                <span class="ml-2 text-sm text-gray-700">Kicked Out</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                                <span class="ml-2 text-sm text-gray-700">Dropped Out</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                                <span class="ml-2 text-sm text-gray-700">Transferred Out</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-100 text-red-600">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Kicked Out</p>
                                    <p class="text-2xl font-semibold text-gray-800">
                                        <?php 
                                            $kicked = $conn->query("SELECT COUNT(*) as count FROM sf1 WHERE status = 'archived' AND Remarks LIKE '%kicked out%'")->fetch_assoc();
                                            echo $kicked['count'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Dropped Out</p>
                                    <p class="text-2xl font-semibold text-gray-800">
                                        <?php 
                                            $dropped = $conn->query("SELECT COUNT(*) as count FROM sf1 WHERE status = 'archived' AND Remarks LIKE '%dropped out%'")->fetch_assoc();
                                            echo $dropped['count'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Transferred Out</p>
                                    <p class="text-2xl font-semibold text-gray-800">
                                        <?php 
                                            $transferred = $conn->query("SELECT COUNT(*) as count FROM sf1 WHERE status = 'archived' AND Remarks LIKE '%transferred out%'")->fetch_assoc();
                                            echo $transferred['count'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Cards -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-4 border-b border-gray-200">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center">
                                    <div class="relative">
                                        <input type="text" placeholder="Search students..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-64" id="searchInput">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-search text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 sm:mt-0">
                                    <span class="text-sm text-gray-500">
                                        Showing <span class="font-medium"><?php echo $result->num_rows; ?></span> students
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                        <?php while ($student = $result->fetch_assoc()): 
                                            $archived_date = !empty($student['archived_at']) ? date('M d, Y', strtotime($student['archived_at'])) : 'N/A';
                                            $remarks = !empty($student['Remarks']) ? $student['Remarks'] : 'No remarks';
                                            
                                            // Determine badge color based on remarks
                                            $badge_class = 'bg-gray-500';
                                            if (stripos($remarks, 'kicked out') !== false) {
                                                $badge_class = 'bg-red-500';
                                            } elseif (stripos($remarks, 'dropped out') !== false) {
                                                $badge_class = 'bg-yellow-500';
                                            } elseif (stripos($remarks, 'transferred out') !== false) {
                                                $badge_class = 'bg-blue-500';
                                            }
                                        ?>
                                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200">
                                                <div class="p-4 border-b flex justify-between items-center bg-gray-50">
                                                    <div class="flex items-center">
                                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                                            <i class="fas fa-user-graduate"></i>
                                                        </div>
                                                        <div class="ml-3">
                                                            <h3 class="text-sm font-medium text-gray-900">Student Record</h3>
                                                            <p class="text-xs text-gray-500">LRN: <?php echo htmlspecialchars($student['LRN']); ?></p>
                                                        </div>
                                                    </div>
                                                    <span class="px-2.5 py-1 text-xs font-semibold text-white rounded-full <?php echo $badge_class; ?> shadow-sm">
                                                        <?php 
                                                            if (stripos($remarks, 'kicked out') !== false) {
                                                                echo '<i class="fas fa-ban mr-1"></i> Kicked Out';
                                                            } elseif (stripos($remarks, 'dropped out') !== false) {
                                                                echo '<i class="fas fa-sign-out-alt mr-1"></i> Dropped';
                                                            } elseif (stripos($remarks, 'transferred out') !== false) {
                                                                echo '<i class="fas fa-exchange-alt mr-1"></i> Transferred';
                                                            } else {
                                                                echo '<i class="fas fa-archive mr-1"></i> Archived';
                                                            }
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="p-5">
                                                    <div class="flex flex-col items-center text-center mb-4">
                                                        <div class="relative mb-3">
                                                            <img src="../images/default-avatar.png" alt="Student Photo" class="w-20 h-20 rounded-full border-4 border-white shadow-md">
                                                            <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-blue-100 rounded-full border-2 border-white flex items-center justify-center">
                                                                <i class="fas fa-<?php echo strtolower($student['Sex']) === 'male' ? 'male' : 'female'; ?> text-xs text-blue-600"></i>
                                                            </div>
                                                        </div>
                                                        <h5 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($student['Name']); ?></h5>
                                                        <p class="text-sm text-gray-500">
                                                            <?php 
                                                                $age = !empty($student['Age']) ? $student['Age'] . ' years' : 'Age not specified';
                                                                echo $age . ' • ' . (!empty($student['Sex']) ? htmlspecialchars($student['Sex']) : 'Gender not specified');
                                                            ?>
                                                        </p>
                                                    </div>
                                                    
                                                    <div class="space-y-3 text-sm">
                                                        <div class="flex items-start">
                                                            <div class="flex-shrink-0 w-5 text-gray-400">
                                                                <i class="fas fa-calendar-day"></i>
                                                            </div>
                                                            <div class="ml-3">
                                                                <p class="text-gray-500">Birthdate</p>
                                                                <p class="font-medium text-gray-900"><?php echo !empty($student['Birthdate']) ? date('M d, Y', strtotime($student['Birthdate'])) : 'Not specified'; ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-start">
                                                            <div class="flex-shrink-0 w-5 text-gray-400">
                                                                <i class="fas fa-phone"></i>
                                                            </div>
                                                            <div class="ml-3">
                                                                <p class="text-gray-500">Contact</p>
                                                                <p class="font-medium text-gray-900"><?php echo !empty($student['Contact_Number']) ? htmlspecialchars($student['Contact_Number']) : 'Not available'; ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-start">
                                                            <div class="flex-shrink-0 w-5 text-gray-400">
                                                                <i class="fas fa-calendar-times"></i>
                                                            </div>
                                                            <div class="ml-3">
                                                                <p class="text-gray-500">Archived on</p>
                                                                <p class="font-medium text-gray-900"><?php echo $archived_date; ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- More Details Accordion -->
                                                    <div class="mt-4 space-y-3">
                                                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                                                            <button class="w-full px-4 py-3 text-left text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none flex items-center justify-between"
                                                                    onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('i').classList.toggle('transform-rotate-180')">
                                                                <span><i class="fas fa-info-circle text-blue-500 mr-2"></i> More Details</span>
                                                                <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200"></i>
                                                            </button>
                                                            <div class="hidden p-4 bg-white border-t border-gray-100">
                                                                <div class="space-y-3">
                                                                    <div class="flex items-start">
                                                                        <div class="flex-shrink-0 w-5 text-gray-400">
                                                                            <i class="fas fa-map-marker-alt"></i>
                                                                        </div>
                                                                        <div class="ml-3">
                                                                            <p class="text-gray-500">Address</p>
                                                                            <p class="text-sm text-gray-900">
                                                                                <?php 
                                                                                    $address_parts = array_filter([
                                                                                        $student['House_Street_Sitio_Purok'] ?? null,
                                                                                        $student['Barangay'] ?? null,
                                                                                        $student['Municipality_City'] ?? null,
                                                                                        $student['Province'] ?? null
                                                                                    ]);
                                                                                    echo !empty($address_parts) ? htmlspecialchars(implode(', ', $address_parts)) : 'Not specified';
                                                                                ?>
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                                                        <div>
                                                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Father</p>
                                                                            <p class="text-sm text-gray-900"><?php echo !empty($student['Fathers_Name']) ? htmlspecialchars($student['Fathers_Name']) : 'Not specified'; ?></p>
                                                                        </div>
                                                                        <div>
                                                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Mother</p>
                                                                            <p class="text-sm text-gray-900"><?php echo !empty($student['Mothers_Maiden_Name']) ? htmlspecialchars($student['Mothers_Maiden_Name']) : 'Not specified'; ?></p>
                                                                        </div>
                                                                        <div>
                                                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Guardian</p>
                                                                            <p class="text-sm text-gray-900"><?php echo !empty($student['Name(Guardian)']) ? htmlspecialchars($student['Name(Guardian)']) : 'Not specified'; ?></p>
                                                                        </div>
                                                                        <div>
                                                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Relationship</p>
                                                                            <p class="text-sm text-gray-900"><?php echo !empty($student['Relationship']) ? htmlspecialchars($student['Relationship']) : 'Not specified'; ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                                                            <button class="w-full px-4 py-3 text-left text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none flex items-center justify-between"
                                                                    onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('i').classList.toggle('transform-rotate-180')">
                                                                <span><i class="fas fa-comment-alt text-blue-500 mr-2"></i> Remarks</span>
                                                                <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200"></i>
                                                            </button>
                                                            <div class="hidden p-4 bg-white border-t border-gray-100">
                                                                <p class="text-sm text-gray-700">
                                                                    <?php 
                                                                        if (!empty($remarks) && $remarks !== 'No remarks') {
                                                                            echo nl2br(htmlspecialchars($remarks));
                                                                        } else {
                                                                            echo '<span class="text-gray-400">No remarks available</span>';
                                                                        }
                                                                    ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Action Buttons -->
                                                    <div class="mt-6 pt-4 border-t border-gray-100 flex flex-col sm:flex-row justify-center gap-2">
                                                        <?php if (in_array($role, ['Guidance', 'Registrar', 'Admin', 'IT'])): ?>
                                                        <div class="flex-1">
                                                            <button onclick="showRestoreModal('<?php echo $student['LRN']; ?>', '<?php echo addslashes($student['Name']); ?>')" 
                                                                    class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                                <i class="fas fa-undo-alt mr-2"></i> Restore
                                                            </button>
                                                        </div>
                                                        <?php endif; ?>
                                                        <div class="flex-1">
                                                            <a href="generate_sf9.php?lrn=<?php echo urlencode($student['LRN']); ?>" 
                                                               class="w-full flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                                                <i class="fas fa-file-pdf mr-2"></i> SF9
                                                            </a>
                                                        </div>
                                                        <div class="flex-1">
                                                            <a href="generate_sf10.php?lrn=<?php echo urlencode($student['LRN']); ?>" 
                                                               class="w-full flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                                                <i class="fas fa-file-pdf mr-2"></i> SF10
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-gray-50 px-4 py-2 text-center text-xs text-gray-500 flex items-center justify-between">
                                                    <span>ID: <?php echo substr(md5($student['LRN']), 0, 8); ?></span>
                                                    <span>Archived: <?php echo $archived_date; ?></span>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-12">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
                                            <i class="fas fa-archive text-2xl text-blue-600"></i>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 mb-1">No archived students</h3>
                                        <p class="text-gray-500 mb-6">There are currently no students in the archive.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Confirmation Modal -->
        <div id="restoreModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <form id="restoreForm" action="restore_student.php" method="POST">
                    <input type="hidden" name="lrn" id="restoreLrn">
                    <div class="p-6">
                        <div class="flex items-center justify-center mb-4">
                            <div class="rounded-full bg-yellow-100 p-3">
                                <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                            </div>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Confirm Restore Student</h3>
                        <p class="text-sm text-gray-500 mb-6">
                            Are you sure you want to restore <span id="studentName" class="font-medium"></span>? 
                            This will make the student active again in the system.
                        </p>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="document.getElementById('restoreModal').classList.add('hidden')" 
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-check mr-1"></i> Confirm Restore
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- jQuery and Bootstrap JS -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        
        <script>
            // Toggle filter dropdown
            document.getElementById('filterDropdown').addEventListener('click', function() {
                document.getElementById('filterMenu').classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dropdown = document.getElementById('filterDropdown');
                const menu = document.getElementById('filterMenu');
                if (dropdown && menu && !dropdown.contains(event.target) && !menu.contains(event.target)) {
                    menu.classList.add('hidden');
                }
            });

            // Search functionality
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const cards = document.querySelectorAll('.student-card');
                    
                    cards.forEach(card => {
                        const name = card.querySelector('h5').textContent.toLowerCase();
                        const lrn = card.querySelector('p.text-gray-500').textContent.toLowerCase();
                        
                        if (name.includes(searchTerm) || lrn.includes(searchTerm)) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            // Show restore confirmation modal
            function showRestoreModal(lrn, name) {
                document.getElementById('restoreLrn').value = lrn;
                document.getElementById('studentName').textContent = name;
                document.getElementById('restoreModal').classList.remove('hidden');
            }

            // Handle form submission with fetch
            document.getElementById('restoreForm')?.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success message
                        alert('Student restored successfully');
                        // Reload the page to reflect changes
                        window.location.reload();
                    } else {
                        alert('Error: ' + (result.message || 'Failed to restore student'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request');
                }
            });
                .finally(() => {
                    hideRestoreModal();
                });
            }
        </script>
    </body>
</html>
<?php $conn->close(); ?>