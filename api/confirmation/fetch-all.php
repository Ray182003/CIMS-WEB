<?php
require_once '../../config/db.php';

$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$archived = isset($_GET['archived']) ? (int)$_GET['archived'] : 0;
$limit = 5;
$offset = ($page - 1) * $limit;

try {
    
    $conditions = "WHERE is_archived = :archived AND child_name LIKE :search";
    if (!empty($year)) {
        $conditions .= " AND YEAR(confirmation_date) = :year";
    }

    
    $countSql = "SELECT COUNT(*) FROM confirmation_records $conditions";
    $stmt = $conn->prepare($countSql);
    $stmt->bindValue(':archived', $archived, PDO::PARAM_INT);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    if (!empty($year)) $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->execute();
    $total = $stmt->fetchColumn();

    
    $sql = "SELECT * FROM confirmation_records $conditions 
            ORDER BY confirmation_date DESC 
            LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':archived', $archived, PDO::PARAM_INT);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    if (!empty($year)) $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'records' => $records,
        'total' => $total,
        'limit' => $limit,
        'page' => $page
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
