<?php
require_once "../security_helper.php";

// Require admin permission
requirePermission('audit_view');

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid log ID']);
    exit();
}

$logId = intval($_GET['id']);

$query = "SELECT al.*, u.username, u.full_name 
          FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE al.id = :logId LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bindParam(":logId", $logId);
$stmt->execute();
$log = $stmt->fetch(PDO::FETCH_OBJ);

if (!$log) {
    http_response_code(404);
    echo json_encode(['error' => 'Log not found']);
    exit();
}

echo json_encode($log);
?>
