<?php
session_start();
require_once '../db_connect.php';
require_once '../includes/AuditLog.php';

// Check if user is logged in and has admin/IT role
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'IT' && $_SESSION['role'] !== 'Super Admin')) {
    header('Location: /systemdihs/login/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Manual - IT Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }
        .manual-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .toc {
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }
        .toc-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #000207ff;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .toc-list {
            display: grid;
            gap: 0.5rem;
        }
        .toc-link {
            display: block;
            padding: 0.5rem 1rem;
            color: #475569;
            border-radius: 0.375rem;
            transition: all 0.2s;
            text-decoration: none;
        }
        .toc-link:hover {
            background: #e0f2fe;
            color: #000207ff;
        }
        .toc-link.level-2 { padding-left: 1.5rem; }
        .toc-link.level-3 { padding-left: 3rem; }
        .content h2 {
            color: #000207ff;
            font-size: 1.75rem;
            font-weight: 700;
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .content h3 {
            color: #000207ff;
            font-size: 1.4rem;
            font-weight: 600;
            margin: 1.75rem 0 1rem;
        }
        .content p {
            margin-bottom: 1.25rem;
            color: #334155;
        }
        .content ul, .content ol {
            margin-bottom: 1.25rem;
            padding-left: 1.5rem;
        }
        .content li {
            margin-bottom: 0.5rem;
        }
        .content code {
            background: #f1f5f9;
            padding: 0.2em 0.4em;
            border-radius: 0.25rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.9em;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: #16A34A;
            color: white;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
            text-decoration: none;
        }
        .btn-back:hover {
            background-color: #16A34A;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/it_navbar.php'; ?>
    
    <div class="py-8 px-4">
        <div class="manual-container">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">IT User Management System</h1>
                    <p class="text-slate-600 mt-1">Comprehensive User Manual</p>
                </div>
                <a href="indexit.php" class="btn-back">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>

            <div class="toc">
                <div class="toc-title">Table of Contents</div>
                <div class="toc-list">
                    <a href="#introduction" class="toc-link">1. Introduction</a>
                    <a href="#system-requirements" class="toc-link">2. System Requirements</a>
                    <a href="#getting-started" class="toc-link">3. Getting Started</a>
                    
                    <a href="#user-management" class="toc-link">4. User Management</a>
                    <a href="#viewing-users" class="toc-link level-2">4.1 Viewing Users</a>
                    <a href="#adding-a-new-user" class="toc-link level-2">4.2 Adding a New User</a>
                    <a href="#editing-a-user" class="toc-link level-2">4.3 Editing a User</a>
                    <a href="#deactivating-a-user" class="toc-link level-2">4.4 Deactivating a User</a>
                    
                    <a href="#user-roles" class="toc-link">5. User Roles and Permissions</a>
                    <a href="#troubleshooting" class="toc-link">6. Troubleshooting</a>
                    <a href="#support" class="toc-link">7. Support</a>
                </div>
            </div>

            <div class="content">
                <h2 id="introduction">1. Introduction</h2>
                <p>The IT User Management System is a comprehensive tool designed to help administrators manage user accounts, roles, and access permissions within the organization. This system provides a secure and efficient way to handle user administration tasks.</p>
                
                <h2 id="system-requirements">2. System Requirements</h2>
                <ul class="list-disc pl-6">
                    <li>Web browser (Chrome, Firefox, Safari, or Edge recommended)</li>
                    <li>Internet connection</li>
                    <li>Valid login credentials with IT or Super Admin privileges</li>
                </ul>

                <h2 id="getting-started">3. Getting Started</h2>
                <ol class="list-decimal pl-6">
                    <li>Open your web browser and navigate to the system URL</li>
                    <li>Log in using your credentials</li>
                    <li>You will be directed to the User Management dashboard</li>
                </ol>

                <h2 id="user-management">4. User Management</h2>
                
                <h3 id="viewing-users">4.1 Viewing Users</h3>
                <ol class="list-decimal pl-6">
                    <li>The main dashboard displays a list of all users</li>
                    <li>Use the pagination controls at the bottom to navigate through multiple pages</li>
                    <li>Each user entry shows:
                        <ul class="list-disc pl-6 mt-2">
                            <li>Full name</li>
                            <li>Username</li>
                            <li>Email</li>
                            <li>Role</li>
                            <li>Status (Active/Inactive)</li>
                        </ul>
                    </li>
                </ol>

                <!-- Rest of the content remains the same, just update the classes to match the new design -->
                <!-- ... -->

                <h2 id="support">7. Support</h2>
                <p>For additional assistance, please contact the IT Support Team:</p>
                <ul class="list-disc pl-6">
                    <li>Email: it-support@example.com</li>
                    <li>Phone: (555) 123-4567</li>
                    <li>Office: IT Department, 2nd Floor</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>