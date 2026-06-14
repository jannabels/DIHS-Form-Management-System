<?php
session_start();
include 'db_connect.php';
require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set headers for JSON response
header('Content-Type: application/json');

function generateTempPassword($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
    $tempPassword = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $tempPassword .= $characters[random_int(0, $max)];
    }
    return $tempPassword;
}

function sendPasswordEmail($email, $username, $tempPassword) {
    $mail = new PHPMailer(true);
    try {
        // Enable verbose debug output
        $mail->SMTPDebug = 2; // Detailed debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug [$level]: $str"); // Log to error_log
        };

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hnsapd@gmail.com';
        $mail->Password = 'yytmzmpbbnqdzerk'; // Corrected App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('no-reply@dihs.edu', 'Dasmarinas Integrated High School');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'DIHS Password Reset';
        $mail->Body = "
            <h2>Password Reset Request</h2>
            <p>Dear {$username},</p>
            <p>Your temporary password is: <strong>{$tempPassword}</strong></p>
            <p>Please use this temporary password to log in and change your password immediately.</p>
            <p>If you did not request this, please contact the administrator.</p>
            <p>Best regards,<br>DIHS Admin</p>
        ";
        $mail->AltBody = "Dear {$username},\n\nYour temporary password is: {$tempPassword}\n\nPlease use this temporary password to log in and change your password immediately.\n\nIf you did not request this, please contact the administrator.\n\nBest regards,\nDasmariñas Integrated High School";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data
    $input = json_decode(file_get_contents('php://input'), true);
    $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $role = filter_var($input['role'] ?? '', FILTER_SANITIZE_STRING);

    if (empty($email) || empty($role)) {
        $response['message'] = 'Email and role are required.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        echo json_encode($response);
        exit;
    }

    // Check if user exists with given email and role
    $stmt = $conn->prepare("SELECT id, Username, Email, Role, Status FROM accounts WHERE Email = ? AND Role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (strtolower($user['Status']) !== 'active') {
            $response['message'] = 'Account is inactive. Please contact administrator.';
            echo json_encode($response);
            $stmt->close();
            exit;
        }

        // Generate temporary password
        $tempPassword = generateTempPassword();

        // Update password in database
        $updateStmt = $conn->prepare("UPDATE accounts SET Password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $tempPassword, $user['id']);
        
        if ($updateStmt->execute()) {
            // Send email with temporary password
            $emailResult = sendPasswordEmail($user['Email'], $user['Username'], $tempPassword);
            
            if ($emailResult === true) {
                $response['success'] = true;
                $response['message'] = 'A temporary password has been sent to your email.';
            } else {
                $response['message'] = $emailResult;
            }
        } else {
            $response['message'] = 'Failed to update password. Please try again.';
        }
        
        $updateStmt->close();
    } else {
        $response['message'] = 'No account found with the provided email and role.';
    }

    $stmt->close();
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>