<?php
// Start the session
session_start();

// Clear the import success data
unset($_SESSION['import_success']);

// Return a success response
header('Content-Type: application/json');
echo json_encode(['success' => true]);
