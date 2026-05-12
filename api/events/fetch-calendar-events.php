<?php
require_once '../../config/db.php';

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

$whereClause = '';
$params = [];

if ($start && $end) {
    $whereClause = ' WHERE event_date BETWEEN :start AND :end';
    $params[':start'] = $start;
    $params[':end'] = $end;
}

$sql = "SELECT id,
               type AS title,
               event_date AS start,
               event_date,
               type,
               time,
               description,
               scheduled_by
        FROM events" . $whereClause;
$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($events);
