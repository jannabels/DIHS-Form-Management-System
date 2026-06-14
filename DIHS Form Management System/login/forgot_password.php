<?php
session_start();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../db_connect.php';
    
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

    if (empty($email) || empty($role)) {
        $error_message = 'Please enter your email and select your role.';
    } else {
        // Check if user exists with this email and role
        $stmt = $conn->prepare("SELECT `Username` FROM accounts WHERE `Email`=? AND `Role`=?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            $error_message = 'No account found with that email and role combination.';
        } else {
            $stmt->bind_result($username);
            $stmt->fetch();
            
            // Generate new password and hash it
            $resetPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8);
            $hashedPassword = password_hash($resetPassword, PASSWORD_DEFAULT);
            
            // Update password in database
            $update = $conn->prepare("UPDATE accounts SET `Password`=? WHERE `Email`=? AND `Role`=?");
            $update->bind_param("sss", $hashedPassword, $email, $role);
            
            if ($update->execute()) {
                // Send email
                $subject = 'DIHS Password Reset Request';
                $message = "Hello,\n\nYour password for the DIHS system has been reset.\n\nRole: $role\nNew Password: $resetPassword\n\nPlease log in and change your password immediately.\n\nIf you did not request this, please contact the school IT department.";
                $headers = 'From: no-reply@dihs.edu.ph' . "\r\n" .
                         'Reply-To: no-reply@dihs.edu.ph' . "\r\n" .
                         'X-Mailer: PHP/' . phpversion();
                
                if (mail($email, $subject, $message, $headers)) {
                    $success_message = 'Password reset instructions have been sent to your email if an account exists.';
                } else {
                    $error_message = 'Failed to send password reset email. Please try again later.';
                }
            } else {
                $error_message = 'Failed to reset password. Please try again.';
            }
            $update->close();
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - DIHS</title>
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
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
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
        
        .back-to-login {
            display: block;
            text-align: center;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: var(--text-light);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .back-to-login:hover {
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
            <h1>Password Reset</h1>
            <p>Enter your details to reset your password</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="forgot-password-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required 
                       placeholder="Enter your email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="role">Account Type</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="" disabled selected>Select your role</option>
                    <option value="Super Admin" <?php echo (isset($role) && $role === 'Super Admin') ? 'selected' : ''; ?>>Super Admin</option>
                    <option value="Principal" <?php echo (isset($role) && $role === 'Principal') ? 'selected' : ''; ?>>Principal</option>
                    <option value="Guidance" <?php echo (isset($role) && $role === 'Guidance') ? 'selected' : ''; ?>>Guidance</option>
                    <option value="Registrar" <?php echo (isset($role) && $role === 'Registrar') ? 'selected' : ''; ?>>Registrar</option>
                    <option value="Adviser" <?php echo (isset($role) && $role === 'Adviser') ? 'selected' : ''; ?>>Adviser</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Reset Password</button>
            <a href="index.php" class="back-to-login">Back to Login</a>
        </form>
    </div>
</body>
</html>