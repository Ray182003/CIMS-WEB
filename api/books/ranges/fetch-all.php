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

    $stmt = $conn->prepare("SELECT id, start_year, end_year, label, created_at FROM books_ranges ORDER BY start_year DESC");
    $stmt->execute();
    $ranges = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ranges' => $ranges]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
