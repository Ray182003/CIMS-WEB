<?php
require_once("../../../config/db.php");

header("Content-Type: application/json");

try {
    $conn->exec("CREATE TABLE IF NOT EXISTS books_ranges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        start_year INT NOT NULL,
        end_year INT NOT NULL,
        label VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_range (start_year, end_year)
    )");

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit;
    }

    $id = isset($payload['id']) ? (int)$payload['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'id is required']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM books_ranges WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
