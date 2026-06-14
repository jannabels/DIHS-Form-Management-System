<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/index.php');
    exit();
}

// Get success message from session
$message = $_SESSION['import_message'] ?? 'Import completed successfully';
$backUrl = $_SESSION['back_url'] ?? 'guidance_sf1.php';

// Clear the session variables
unset($_SESSION['import_message']);
unset($_SESSION['back_url']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Successful</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .success-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        .btn-back {
            margin-top: 2rem;
            padding: 0.5rem 2rem;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2 class="mb-4">Import Successful</h2>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-primary btn-back">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
