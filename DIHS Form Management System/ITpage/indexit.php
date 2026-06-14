<?php
session_start();
require_once '../db_connect.php';
require_once '../includes/AuditLog.php';

// Check if user is logged in and has admin/IT role
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'IT' && $_SESSION['role'] !== 'Super Admin')) {
    header('Location: /systemdihs/login/index.php');
    exit();
}

$auditLog = new AuditLog($conn);
$message = '';
$messageType = '';

// Debug: Print session information
echo '<!-- Debug: Session Data -->';
echo '<!-- Username: ' . htmlspecialchars($_SESSION['username'] ?? 'Not set') . ' -->';
echo '<!-- Role: ' . htmlspecialchars($_SESSION['role'] ?? 'Not set') . ' -->';
echo '<!-- Session ID: ' . session_id() . ' -->';

// Handle form submission for adding/editing users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['username', 'first_name', 'email', 'role', 'status'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $missing_fields[] = str_replace('_', ' ', $field);
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("The following fields are required: " . implode(', ', $missing_fields));
        }
        
        // Validate password if provided
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        if (!empty($password) || !empty($confirm_password)) {
            if ($password !== $confirm_password) {
                throw new Exception("Passwords do not match");
            }
            
            if (strlen($password) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }
        }
        
        // Sanitize input
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $username = trim($_POST['username']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $status = trim($_POST['status']);

        if ($id > 0) {
            // Update existing user
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE accounts SET username = ?, `First Name` = ?, Email = ?, Role = ?, Status = ?, Password = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $username, $first_name, $email, $role, $status, $hashed_password, $id);
            } else {
                $stmt = $conn->prepare("UPDATE accounts SET username = ?, `First Name` = ?, Email = ?, Role = ?, Status = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $username, $first_name, $email, $role, $status, $id);
            }
            $action = 'updated';
        } else {
            // Insert new user - password is required
            if (empty($password)) {
                throw new Exception("Password is required for new users");
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO accounts (username, `First Name`, Email, Role, Status, Password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $first_name, $email, $role, $status, $hashed_password);
            $action = 'added';
        }

        if ($stmt->execute()) {
            $message = "User successfully $action";
            $messageType = 'success';
            
            // Log the action
            $auditLog->log($_SESSION['user_id'] ?? 'system', "User $action", 'user', $id, null, [
                'username' => $username,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'role' => $role,
                'status' => $status
            ]);
            
            // Redirect to avoid form resubmission
            header("Location: indexit.php?success=" . urlencode($message));
            exit();
        } else {
            throw new Exception("Error saving user: " . $conn->error);
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];
        
        // Get user info before deleting for audit log
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Delete the user
        $stmt = $conn->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Log the deletion
            if ($user) {
                $auditLog->log(
                    $_SESSION['user_id'] ?? 'system',
                    'User deleted',
                    'user',
                    $id,
                    [
                        'username' => $user['username'],
                        'first_name' => $user['First Name'],
                        'email' => $user['Email'],
                        'role' => $user['Role'],
                        'status' => $user['Status']
                    ],
                    null
                );
            }
            
            header("Location: indexit.php?success=User+deleted+successfully");
            exit();
        } else {
            throw new Exception("Error deleting user: " . $conn->error);
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Pagination settings
$users_per_page = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$offset = ($page - 1) * $users_per_page;

// Get total number of users
$total_users = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM accounts");
if ($result) {
    $total_users = $result->fetch_assoc()['count'];
}
$total_pages = ceil($total_users / $users_per_page);

// Handle search and sort parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'first_name';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Validate sort column to prevent SQL injection
$valid_columns = ['first_name', 'last_name', 'username', 'email', 'role', 'status'];
$sort_by = in_array($sort_by, $valid_columns) ? $sort_by : 'first_name';
$sort_order = $sort_order === 'desc' ? 'DESC' : 'ASC';

// Map sort columns to database columns
$sort_columns = [
    'first_name' => '`First Name`',
    'username' => 'username',
    'email' => 'Email',
    'role' => 'Role',
    'status' => 'Status'
];

$sort_column = $sort_columns[$sort_by] ?? '`First Name`';

// Get users for current page with search and sort
$users = [];
$roleOptions = [];
try {
    $query = "SELECT * FROM accounts ";
    $count_query = "SELECT COUNT(*) as count FROM accounts ";
    $params = [];
    $types = '';
    $where = [];
    
    // Add search condition if search term exists
    if (!empty($search)) {
        $where[] = "(username LIKE ? OR `First Name` LIKE ? OR Email LIKE ? OR Role LIKE ?)";
        $search_term = "%$search%";
        $params = array_merge($params, array_fill(0, 4, $search_term));
        $types .= str_repeat('s', 4);
    }
    
    // Build WHERE clause
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count with search
    $stmt = $conn->prepare($count_query . $where_clause);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total_users = $count_result ? $count_result->fetch_assoc()['count'] : 0;
    $total_pages = ceil($total_users / $users_per_page);
    
    // Get paginated results with search and sort
    $query .= "$where_clause ORDER BY $sort_column $sort_order LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    
    // Add pagination parameters
    $params[] = $users_per_page;
    $params[] = $offset;
    $types .= 'ii';
    
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $message = "Error fetching users: " . $e->getMessage();
    $messageType = 'error';
}

// Build role options list (include distinct roles from DB plus defaults)
try {
    $role_result = $conn->query("SELECT DISTINCT Role FROM accounts ORDER BY Role ASC");
    if ($role_result) {
        while ($role_row = $role_result->fetch_assoc()) {
            $role = trim($role_row['Role']);
            if ($role !== '') {
                $roleOptions[strtolower($role)] = $role;
            }
        }
    }
} catch (Exception $e) {
    // Log error if needed
    error_log("Error fetching roles: " . $e->getMessage());
}

// Check for success message in URL
if (isset($_GET['success'])) {
    $message = urldecode($_GET['success']);
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - IT Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Sidebar styles */
        .sidebar-collapsed {
            width: 4rem !important;
        }
        .sidebar-collapsed + #mainContent {
            margin-left: 4rem !important;
        }
        /* Animate menu text when collapsing/expanding */
        .menu-text, .user-name, .user-role, .menu-item-text {
            transition: opacity 0.3s ease, transform 0.3s ease, width 0.3s ease, margin-left 0.3s ease;
            display: inline-block;
            vertical-align: middle;
        }
        /* Hide text in menu items when collapsed */
        .sidebar-collapsed .menu-text,
        .sidebar-collapsed .user-name,
        .sidebar-collapsed .user-role,
        .sidebar-collapsed .menu-item-text {
            opacity: 0;
            width: 0 !important;
            margin-left: 0 !important;
            display: inline-block;
            transform: translateX(-10px);
        }
        /* Show text when expanded */
        .fixed.left-0:not(.sidebar-collapsed) .menu-text,
        .fixed.left-0:not(.sidebar-collapsed) .menu-item-text {
            opacity: 1;
            width: auto !important;
            margin-left: 0.75rem !important;
            transform: translateX(0);
        }
        /* Hide Menu heading when sidebar is collapsed */
        .sidebar-collapsed .menu-title {
            display: none !important;
        }
        /* Center icons when collapsed */
        .sidebar-collapsed .menu-icon {
            margin-right: 0;
            justify-content: center;
            width: 100%;
        }
        /* Adjust user profile section */
        .sidebar-collapsed .user-avatar {
            margin: 0 auto;
        }
        .sidebar-collapsed .user-info {
            display: none;
        }
        /* Adjust menu items */
        .sidebar-collapsed .nav-item {
            justify-content: center;
            padding: 0.75rem 0;
            position: relative;
        }
        /* Tooltip styles for collapsed sidebar */
        .nav-item {
            position: relative;
        }
        .nav-item::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: #1F2937;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            z-index: 50;
            margin-left: 1rem;
            pointer-events: none;
        }
        .nav-item::before {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: transparent transparent transparent #1F2937;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            z-index: 50;
            margin-left: -6px;
            pointer-events: none;
        }
        .sidebar-collapsed .nav-item:hover::after,
        .sidebar-collapsed .nav-item:hover::before {
            opacity: 1;
            visibility: visible;
        }
        /* Add a subtle shadow to the sidebar */
        .fixed.left-0 {
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        /* Table styles */
        .table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding: 1rem;
            margin: 0 auto;
            max-width: 100%;
        }
        
        table {
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            text-align: left;
        }
        
        th, td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background-color: #f3f4f6;
        }
        
        /* Status badges */
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Modal styles */
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }
        
        /* Form styles */
        .form-input {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            width: 100%;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .btn-primary {
            background-color: #10b981;
            color: white;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: background-color 0.15s ease-in-out;
        }
        
        .btn-primary:hover {
            background-color: #059669;
        }
        
        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: background-color 0.15s ease-in-out;
        }
        
        .btn-secondary:hover {
            background-color: #d1d5db;
        }
        
        /* Alert messages */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Ensure proper layout with sidebar */
        #mainContent {
            width: calc(100% - 16rem);
            margin-left: 16rem;
            transition: all 0.3s ease-in-out;
        }
        
        /* When sidebar is collapsed */
        .w-16 ~ #mainContent {
            width: calc(100% - 4rem);
            margin-left: 4rem;
        }
        
        /* Ensure content is properly spaced */
        main {
            padding: 1.5rem;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">
    <!-- Unified Sidebar -->
    <?php include '../includes/unified_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div id="mainContent" class="flex-1 flex flex-col overflow-hidden ml-64 transition-all duration-300 ease-in-out">
        <!-- Page Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 pt-12">
            <div class="max-w-7xl mx-auto">
            <!-- Page header -->
            <div class="md:flex md:items-center md:justify-between mb-6">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        User Management
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
                    <!-- Search Bar -->
                    <form method="GET" action="" class="flex-1 max-w-md" id="searchForm">
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                        <input type="hidden" name="order" value="<?php echo htmlspecialchars(strtolower($sort_order)); ?>">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="search" 
                                   id="searchInput"
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   class="block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" 
                                   placeholder="Search users..."
                                   autocomplete="off">
                            <?php if (!empty($search)): ?>
                            <a href="?sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode(strtolower($sort_order)); ?>" 
                               class="absolute inset-y-0 right-0 pr-10 flex items-center text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </a>
                            <?php endif; ?>
                            <button type="submit" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <button type="button" onclick="showUserModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-plus mr-2"></i> Add New User
                    </button>
                </div>
            </div>

            <!-- Alert messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> mb-6">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- User List -->
            <!-- Include search and sort parameters in pagination links -->
            <?php 
            function getPaginationLink($page) {
                global $search, $sort_by, $sort_order;
                $params = ['page' => $page];
                if (!empty($search)) $params['search'] = $search;
                if (!empty($sort_by)) $params['sort'] = $sort_by;
                if (!empty($sort_order)) $params['order'] = strtolower($sort_order) === 'asc' ? 'asc' : 'desc';
                return '?' . http_build_query($params);
            }
            ?>
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        User List
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <?php 
                                function getSortLink($column, $label) {
                                    global $sort_by, $sort_order, $search;
                                    $new_order = ($sort_by === $column && $sort_order === 'ASC') ? 'desc' : 'asc';
                                    $url = "?sort=$column&order=$new_order";
                                    if (!empty($search)) {
                                        $url .= "&search=" . urlencode($search);
                                    }
                                    $sort_icon = '';
                                    if ($sort_by === $column) {
                                        $sort_icon = $sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
                                    } else {
                                        $sort_icon = 'fa-sort';
                                    }
                                    return "<a href=\"$url\" class=\"flex items-center space-x-1 group\">
                                                <span>$label</span>
                                                <i class=\"fas $sort_icon text-gray-400 group-hover:text-gray-600\"></i>
                                            </a>";
                                }
                                ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php echo getSortLink('first_name', 'Full Name'); ?>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php echo getSortLink('username', 'Username'); ?>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php echo getSortLink('email', 'Email'); ?>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php echo getSortLink('role', 'Role'); ?>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php echo getSortLink('status', 'Status'); ?>
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-500"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($user['First Name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo isset($user['Username']) ? htmlspecialchars($user['Username']) : ''; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['Email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars(ucfirst($user['Role'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo strtolower($user['Status'] ?? '') === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst(strtolower($user['Status'] ?? '')); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No users found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                        <a href="<?php echo getPaginationLink($page - 1); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                        <a href="<?php echo getPaginationLink($page + 1); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?php echo ($offset + 1); ?></span> to 
                                <span class="font-medium"><?php echo min($offset + count($users), $total_users); ?></span> of 
                                <span class="font-medium"><?php echo $total_users; ?></span> users
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                <a href="<?php echo getPaginationLink($page - 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left h-5 w-5"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $start_page + 4);
                                $start_page = max(1, $end_page - 4);
                                
                                if ($start_page > 1) {
                                    echo '<a href="' . getPaginationLink(1) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                    if ($start_page > 2) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++): 
                                ?>
                                    <a href="<?php echo getPaginationLink($i); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium <?php echo $i == $page ? 'bg-green-50 text-green-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php 
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                    echo '<a href="?page=' . $total_pages . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                                }
                                ?>
                                
                                
                                <?php if ($page < $total_pages): ?>
                                <a href="<?php echo getPaginationLink($page + 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right h-5 w-5"></i>
                                </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div id="modalBackdrop" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="hideUserModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="userForm" method="POST" action="indexit.php">
                    <input type="hidden" name="id" id="userId" value="">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-user-edit text-green-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">
                                    Add New User
                                </h3>
                                <div class="mt-5">
                                    <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6">
                                        <div class="sm:col-span-6">
                                            <label for="first_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                            <input type="text" name="first_name" id="first_name" required class="mt-1 form-input">
                                        </div>
                                        <div class="sm:col-span-6">
                                            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                            <input type="text" name="username" id="username" required class="mt-1 form-input">
                                        </div>
                                        <div class="sm:col-span-6">
                                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                            <input type="email" name="email" id="email" required class="mt-1 form-input">
                                        </div>
                                        <div class="sm:col-span-6">
                                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                            <input type="password" name="password" id="password" class="mt-1 form-input" placeholder="Leave blank to keep current password">
                                        </div>
                                        <div class="sm:col-span-6">
                                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                            <input type="password" name="confirm_password" id="confirm_password" class="mt-1 form-input" oninput="checkPasswordMatch()">
                                            <p id="passwordMatch" class="text-sm mt-1"></p>
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                                            <select id="role" name="role" required class="mt-1 form-input">
                                                <option value="">Select a role</option>
                                                <?php foreach ($roleOptions as $roleKey => $roleLabel): ?>
                                                    <option value="<?php echo htmlspecialchars($roleLabel); ?>">
                                                        <?php echo htmlspecialchars(ucwords($roleLabel)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                            <select id="status" name="status" required class="mt-1 form-input">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
                        </button>
                        <button type="button" onclick="hideUserModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="deleteModalTitle">
                                Delete User
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to delete <span id="userToDelete" class="font-medium"></span>? This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <a href="#" id="confirmDeleteBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </a>
                    <button type="button" onclick="hideDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal is included in unified_sidebar.php -->

    <script>
        // Check if passwords match
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('passwordMatch');
            
            if (password === '' && confirmPassword === '') {
                matchText.textContent = '';
                matchText.className = 'text-sm mt-1';
                return true;
            }
            
            if (password !== confirmPassword) {
                matchText.textContent = 'Passwords do not match!';
                matchText.className = 'text-sm mt-1 text-red-600';
                return false;
            } else {
                matchText.textContent = 'Passwords match!';
                matchText.className = 'text-sm mt-1 text-green-600';
                return true;
            }
        }
        
        // Form validation
        document.getElementById('userForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Only validate password if at least one field is filled
            if (password || confirmPassword) {
                if (!checkPasswordMatch()) {
                    e.preventDefault();
                    return false;
                }
                
                if (password.length < 8) {
                    alert('Password must be at least 8 characters long');
                    e.preventDefault();
                    return false;
                }
            }
            return true;
        });
        
        // Initialize tooltips for sidebar items
        function initSidebarTooltips() {
            const menuItems = document.querySelectorAll('.nav-item');
            const sidebar = document.querySelector('.fixed.left-0');
            
            menuItems.forEach(item => {
                if (sidebar.classList.contains('w-16')) {
                    const text = item.querySelector('.menu-text').textContent;
                    item.setAttribute('data-tooltip', text);
                    
                    // Add tooltip styles
                    if (!item.hasAttribute('data-tooltip-init')) {
                        item.style.position = 'relative';
                        item.setAttribute('data-tooltip-init', 'true');
                        
                        item.addEventListener('mouseenter', function(e) {
                            const tooltip = document.createElement('div');
                            tooltip.className = 'tooltip';
                            tooltip.textContent = this.getAttribute('data-tooltip');
                            
                            // Position the tooltip
                            const rect = this.getBoundingClientRect();
                            tooltip.style.position = 'fixed';
                            tooltip.style.left = `${rect.right + 10}px`;
                            tooltip.style.top = `${rect.top + window.scrollY}px`;
                            tooltip.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                            tooltip.style.color = 'white';
                            tooltip.style.padding = '4px 8px';
                            tooltip.style.borderRadius = '4px';
                            tooltip.style.fontSize = '12px';
                            tooltip.style.whiteSpace = 'nowrap';
                            tooltip.style.zIndex = '9999';
                            
                            document.body.appendChild(tooltip);
                            this._tooltip = tooltip;
                        });
                        
                        item.addEventListener('mouseleave', function() {
                            if (this._tooltip) {
                                document.body.removeChild(this._tooltip);
                                this._tooltip = null;
                            }
                        });
                    }
                } else {
                    item.removeAttribute('data-tooltip');
                    item.removeAttribute('data-tooltip-init');
                    item.style.removeProperty('position');
                    
                    // Clean up event listeners
                    const newItem = item.cloneNode(true);
                    item.parentNode.replaceChild(newItem, item);
                }
            });
        }

        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.fixed.left-0');
            const toggleBtn = document.getElementById('toggleSidebar');
            const leftArrows = document.querySelectorAll('.fa-chevron-left');
            const rightArrows = document.querySelectorAll('.fa-chevron-right');
            const mainContent = document.getElementById('mainContent');
            const menuTexts = document.querySelectorAll('.menu-text');
            const menuIcons = document.querySelectorAll('.menu-icon');
            const menuHeaders = document.querySelectorAll('.menu-header-container');
            const userInfo = document.querySelector('.user-info');
            const navItems = document.querySelectorAll('.nav-item');
            const logoutModal = document.getElementById('logoutModal');
            const cancelLogout = document.getElementById('cancelLogout');
            const logoutBtn = document.getElementById('logoutBtn');

            // Check for saved state
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            
            // Apply saved state
            if (isCollapsed) {
                toggleSidebar(true);
            }

            // Toggle sidebar
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const isCollapsedNow = !sidebar.classList.contains('w-16');
                    toggleSidebar(isCollapsedNow);
                });
            }

            function toggleSidebar(collapse) {
                if (collapse) {
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-16');
                    mainContent.classList.remove('pl-64');
                    mainContent.classList.add('pl-16');
                    menuTexts.forEach(text => text.classList.add('opacity-0', 'invisible'));
                    menuIcons.forEach(icon => icon.classList.add('mx-auto'));
                    menuHeaders.forEach(header => {
                        header.classList.add('justify-center');
                        const menuTitle = header.querySelector('.menu-title');
                        if (menuTitle) menuTitle.classList.add('hidden');
                    });
                    if (userInfo) userInfo.classList.add('opacity-0', 'invisible');
                    leftArrows.forEach(arrow => arrow.classList.add('hidden'));
                    rightArrows.forEach(arrow => arrow.classList.remove('hidden'));
                } else {
                    sidebar.classList.remove('w-16');
                    sidebar.classList.add('w-64');
                    mainContent.classList.remove('pl-16');
                    mainContent.classList.add('pl-64');
                    menuTexts.forEach(text => text.classList.remove('opacity-0', 'invisible'));
                    menuIcons.forEach(icon => icon.classList.remove('mx-auto'));
                    menuHeaders.forEach(header => {
                        header.classList.remove('justify-center');
                        const menuTitle = header.querySelector('.menu-title');
                        if (menuTitle) menuTitle.classList.remove('hidden');
                    });
                    if (userInfo) userInfo.classList.remove('opacity-0', 'invisible');
                    leftArrows.forEach(arrow => arrow.classList.remove('hidden'));
                    rightArrows.forEach(arrow => arrow.classList.add('hidden'));
                }
                
                // Save state
                localStorage.setItem('sidebarCollapsed', collapse);
                
                // Initialize or destroy tooltips based on state
                if (collapse) {
                    initSidebarTooltips();
                } else {
                    // Clean up tooltips when expanding
                    document.querySelectorAll('.tooltip').forEach(tooltip => tooltip.remove());
                    navItems.forEach(item => {
                        item.removeAttribute('data-tooltip');
                        item.removeAttribute('data-tooltip-init');
                        item.style.removeProperty('position');
                    });
                }
            }

            // Logout modal functionality is handled in unified_sidebar.php
        });

        // Auto-submit search form when typing (with debounce)
        let searchTimeout;
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Update the hidden sort fields before submission
                document.querySelector('input[name="sort"]').value = '<?php echo $sort_by; ?>';
                document.querySelector('input[name="order"]').value = '<?php echo strtolower($sort_order); ?>';
                document.getElementById('searchForm').submit();
            }, 500);
        });

        // Show user modal
        function showUserModal(user = null) {
            const modal = document.getElementById('userModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('userForm');
            const getValue = (obj, ...keys) => {
                for (const key of keys) {
                    const value = obj?.[key];
                    if (value !== undefined && value !== null) {
                        return value;
                    }
                }
                return '';
            };
            const ensureSelectValue = (selectEl, value, displayValue = null) => {
                if (!selectEl) return;
                const targetValue = value ?? '';
                if (targetValue === '') {
                    selectEl.value = '';
                    return;
                }
                const normalized = targetValue.toString().toLowerCase();
                const matchedOption = Array.from(selectEl.options).find(
                    option => option.value.toString().toLowerCase() === normalized
                );
                if (matchedOption) {
                    selectEl.value = matchedOption.value;
                    return;
                }
                const option = document.createElement('option');
                option.value = targetValue;
                option.textContent = displayValue ?? targetValue;
                selectEl.appendChild(option);
                selectEl.value = targetValue;
            };
            
            if (user) {
                modalTitle.textContent = 'Edit User';
                document.getElementById('userId').value = getValue(user, 'id', 'ID');
                document.getElementById('first_name').value = getValue(user, 'First Name', 'first_name');
                document.getElementById('username').value = getValue(user, 'username', 'Username');
                document.getElementById('email').value = getValue(user, 'Email', 'email');
                const roleValue = getValue(user, 'Role', 'role');
                ensureSelectValue(document.getElementById('role'), roleValue, roleValue ? roleValue.charAt(0).toUpperCase() + roleValue.slice(1) : null);
                const statusRaw = getValue(user, 'Status', 'status') || 'active';
                const statusValue = statusRaw.toString().toLowerCase();
                ensureSelectValue(document.getElementById('status'), statusValue, statusRaw);
            } else {
                modalTitle.textContent = 'Add New User';
                form.reset();
                document.getElementById('userId').value = '';
            }
            
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        // Hide user modal
        function hideUserModal() {
            document.getElementById('userModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Edit user
        function editUser(user) {
            showUserModal(user);
        }

        // Show delete confirmation modal
        function confirmDelete(id, name) {
            const modal = document.getElementById('deleteModal');
            document.getElementById('userToDelete').textContent = name;
            document.getElementById('confirmDeleteBtn').href = `indexit.php?delete=${id}`;
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        // Hide delete confirmation modal
        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target === document.getElementById('userModal') || event.target === document.getElementById('deleteModal')) {
                hideUserModal();
                hideDeleteModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideUserModal();
                hideDeleteModal();
            }
        });
    </script>
            </div>
        </main>
    </div>
</body>
</html>