<?php
require_once("../../config/db.php");

$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$archived = isset($_GET['archived']) ? (int)$_GET['archived'] : 0;
$limit = 5;
$offset = ($page - 1) * $limit;

try {
    $conditions = "WHERE is_archived = :archived AND (name LIKE :search OR address LIKE :search OR cause_of_death LIKE :search)";
    if (!empty($year)) {
        $conditions .= " AND YEAR(date_of_death) = :year";
    }


    $countSql = "SELECT COUNT(*) FROM death_certificates $conditions";
    $stmt = $conn->prepare($countSql);
    $stmt->bindValue(':archived', $archived, PDO::PARAM_INT);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    if (!empty($year)) $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->execute();
    $total = $stmt->fetchColumn();

  
    $sql = "SELECT * FROM death_certificates $conditions 
            ORDER BY id DESC LIMIT :limit OFFSET :offset";
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
