<?php
session_start();
include '../db_connect.php';
require_once '../includes/AuditLog.php';

// Check if user is already logged in
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    // Redirect based on role
    switch ($_SESSION['role']) { 
        case 'Super Admin':
            header("Location: ../ITpage/indexit.php");
            break;
        case 'Principal':
            header("Location: ../principal/dashboard.php");
            break;
        case 'Guidance':
            header("Location: ../guidance/guidance_sf1.php");
            break;
        case 'Registrar':
            header("Location: ../registrar/sf10.php");
            break;
        case 'Adviser':
            header("Location: ../adviser/adviser_sf2.php");
            break;
        case 'OIC':
            header("Location: ../oic/dashboard.php");
            break;
        default:
            // Unknown role, stay on login page
            break;
    }
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT Username, Password, Role, Status, id FROM accounts WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (strtolower($user['Status']) === 'active') {
                if ($password === $user['Password']) {
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['role'] = $user['Role'];
                    $_SESSION['user_id'] = $user['id'];
                    
                    // Log the login action
                    $auditLog = new AuditLog($conn);
                    $auditLog->log($user['Username'], 'login', 'accounts', $user['id']);
                    
                    // Redirect based on role
                    switch ($user['Role']) {
                        case 'Super Admin':
                            header("Location: ../ITpage/indexit.php");
                            break;
                        case 'Principal':
                            header("Location: ../principal/dashboard.php");
                            break;
                        case 'Guidance':
                            header("Location: ../guidance/guidance_sf1.php");
                            break;
                        case 'Registrar':
                            header("Location: ../registrar/sf10.php");
                            break;
                        case 'Adviser':
                            header("Location: ../adviser/adviser_sf2.php");
                            break;
                        case 'OIC':
                            header("Location: ../oic/dashboard.php");
                            break;
                        default:
                            $error_message = "Unknown user role. Please contact administrator.";
                            break;
                    }
                    exit();
                } else {
                    $error_message = "Invalid username or password.";
                }
            } else {
                $error_message = "Account is inactive. Please contact administrator.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Please enter both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIHS - Login</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f3f4f6;
        }
        .login-container {
            min-height: 100vh;
        }
        .login-box {
            max-width: 28rem;
            width: 90%;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="login-container flex items-center justify-center p-4">
        <div class="login-box bg-white rounded-lg shadow-xl overflow-hidden">
            <!-- Header with Logo -->
            <div class="bg-blue-600 px-6 py-8 text-center">
                <div class="flex justify-center mb-4">
                    <img src="../images/dihslogo.png" alt="DIHS Logo" class="h-20 w-auto">
                </div>
                <h1 class="text-2xl font-bold text-white">DONA INES CHIONG LOYOLA MEMORIAL HIGH SCHOOL</h1>
                <p class="text-blue-100 mt-2">Student Information System</p>
            </div>

            <!-- Login Form -->
            <div class="p-8">
                <?php if ($error_message): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
                        <p><?php echo $error_message; ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
                        <p><?php echo $success_message; ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" name="username" id="username" required
                                class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md p-2 border"
                                placeholder="Enter your username">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" id="password" required
                                class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md p-2 border"
                                placeholder="Enter your password">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember_me" name="remember_me" type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                                Remember me
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="forgot_password.php" class="font-medium text-blue-600 hover:text-blue-500">
                                Forgot your password?
                            </a>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Sign in
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">
                                Don't have an account?
                            </span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="#" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-envelope mr-2"></i>
                            Contact Administrator
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
