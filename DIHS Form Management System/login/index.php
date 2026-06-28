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
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT Username, Password, Role, Status, id FROM accounts WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if account is active
            if (strtolower($user['Status']) === 'active') {
                // Verify password (assuming plain text for now, should be hashed in production)
                if ($password === $user['Password']) {
                    // Set session variables
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['role'] = $user['Role'];
                    $_SESSION['user_id'] = $user['id'];   // <-- store adviser id
                    
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
        
        $stmt->close();
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #16a34a;
            --primary-light: #dcfce7;
            --text: #1f2937;
            --text-light: #6b7280;
            --white: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            line-height: 1.5;
        }
        
        .login-container {
            background: var(--white);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo img {
            height: 80px;
            width: auto;
            margin-bottom: 1rem;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.25rem;
        }
        
        .logo p {
            color: var(--text-light);
            font-size: 0.875rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            transition: all 0.2s;
            background-color: #f9fafb;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
            background-color: var(--white);
        }
        
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.25rem;
        }
        
        .password-toggle:hover {
            color: var(--primary);
            background-color: var(--primary-light);
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        
        .btn:hover {
            background-color: #15803d;
            transform: translateY(-1px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .forgot-password {
            display: block;
            text-align: right;
            margin-top: 0.5rem;
            font-size: 0.8125rem;
            color: var(--text-light);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .forgot-password:hover {
            color: var(--primary);
            text-decoration: underline;
        }
        
        .error-message {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            display: <?php echo !empty($error_message) ? 'block' : 'none'; ?>;
        }
        
        .success-message {
            background-color: #dcfce7;
            color: #166534;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            display: <?php echo !empty($success_message) ? 'block' : 'none'; ?>;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="../images/dihslogo.png" alt="DIHS Logo" style="max-height: 100px; width: auto;">
            <h1>School Forms Management System</h1>
            <p>Dasmariñas Integrated High School</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <div class="message-container"></div>
        <form id="loginForm" method="POST" action="" class="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required 
                       placeholder="Enter your username" autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" id="passwordToggle">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <a href="forgot_password.php" class="forgot-password">
                    Forgot password?
                </a>
            </div>
            
            <button id="loginBtn" type="submit" class="btn">Log In</button>
        </form>
    </div>

    <script>
        // Password toggle functionality
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        const passwordIcon = passwordToggle.querySelector('i');
        
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            passwordIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            passwordToggle.setAttribute('title', type === 'password' ? 'Show password' : 'Hide password');
        });
        
        // Form validation and submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const loginBtn = document.getElementById('loginBtn');
            const messageContainer = document.querySelector('.message-container');
            
            // Clear previous messages
            const existingMessages = messageContainer.querySelectorAll('.feedback');
            existingMessages.forEach(msg => msg.remove());
            
            if (!username || !password) {
                e.preventDefault();
                const errorDiv = document.createElement('div');
                errorDiv.className = 'feedback error';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter both username and password.';
                messageContainer.appendChild(errorDiv);
                return;
            }
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            
            // Form will submit normally to PHP backend
            // The backend will handle authentication and redirect
        });
        
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.feedback');
            messages.forEach(msg => {
                if (msg.classList.contains('error') || msg.classList.contains('success')) {
                    msg.style.opacity = '0';
                    setTimeout(() => msg.remove(), 300);
                }
            });
        }, 5000);
        // Focus management
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('username');
            if (usernameInput && !usernameInput.value) {
                usernameInput.focus();
            }
        });
    </script>
</body>
</html>