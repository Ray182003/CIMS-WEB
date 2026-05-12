<?php
require "../../config/db.php";

header("Content-Type: application/json");

try {
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $year = isset($_GET['year']) ? trim($_GET['year']) : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $archived = isset($_GET['archived']) ? intval($_GET['archived']) : 0;
    $limit = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 5;
    $offset = ($page - 1) * $limit;

    $searchParam = "%" . $searchTerm . "%";

  
    $conditions = "WHERE is_archived = :archived
                          AND (child_name LIKE :search
                               OR parent_name1 LIKE :search
                               OR parent_name2 LIKE :search
                               OR bishop LIKE :search)";
    if (!empty($year)) {
        $conditions .= " AND YEAR(baptism_date) = :year";
    }

    $countQuery = "SELECT COUNT(*) FROM baptismal_records $conditions";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bindValue(':archived', $archived, PDO::PARAM_INT);
    $countStmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
    if (!empty($year)) {
        $countStmt->bindValue(':year', $year, PDO::PARAM_INT);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();

    
    $query = "SELECT * FROM baptismal_records $conditions
              ORDER BY created_at DESC
              LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':archived', $archived, PDO::PARAM_INT);
    $stmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
    if (!empty($year)) {
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'records' => $records,
        'total' => $totalRecords,
        'page' => $page,
        'limit' => $limit
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
