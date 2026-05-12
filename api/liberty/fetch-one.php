<?php
require_once("../../config/db.php");

// Set header for JSON response
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Validate and sanitize the ID
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(['error' => 'Invalid or missing ID']);
    exit;
}

try {
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT * FROM liberty_records WHERE id = ?");
    $stmt->execute([$id]);

    // Fetch record
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        echo json_encode($record);
    } else {
        echo json_encode(['error' => 'Record not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
