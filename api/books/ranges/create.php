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

    $startYear = isset($payload['start_year']) ? (int)$payload['start_year'] : 0;
    $label = isset($payload['label']) ? trim((string)$payload['label']) : null;

    if ($startYear <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'start_year is required']);
        exit;
    }

    $endYear = $startYear + 4;

    $stmt = $conn->prepare("INSERT INTO books_ranges (start_year, end_year, label) VALUES (:start_year, :end_year, :label)");
    $stmt->bindValue(':start_year', $startYear, PDO::PARAM_INT);
    $stmt->bindValue(':end_year', $endYear, PDO::PARAM_INT);
    if ($label === null || $label === '') {
        $stmt->bindValue(':label', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':label', $label, PDO::PARAM_STR);
    }
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'range' => [
            'id' => (int)$conn->lastInsertId(),
            'start_year' => $startYear,
            'end_year' => $endYear,
            'label' => $label
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
