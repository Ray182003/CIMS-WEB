<?php
require_once '../../config/db.php';
require_once '../../security_helper.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$logId = isset($input['log_id']) ? (int) $input['log_id'] : 0;
$increment = isset($input['increment']) ? (int) $input['increment'] : 1;

if ($logId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid log ID'
    ]);
    exit;
}

if ($increment <= 0) {
    $increment = 1;
}

try {
    $conn->beginTransaction();

    $updateStmt = $conn->prepare(
        'UPDATE print_logs SET copies = copies + :increment, user_id = :user_id WHERE id = :id'
    );
    $updateStmt->execute([
        ':increment' => $increment,
        ':user_id' => $_SESSION['user_id'] ?? null,
        ':id' => $logId
    ]);

    if ($updateStmt->rowCount() === 0) {
        $conn->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Print log not found'
        ]);
        exit;
    }

    $selectStmt = $conn->prepare('SELECT copies FROM print_logs WHERE id = :id');
    $selectStmt->execute([':id' => $logId]);
    $copies = (int) $selectStmt->fetchColumn();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'copies' => $copies
    ]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
