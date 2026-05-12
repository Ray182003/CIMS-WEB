<?php
require_once("../../config/db.php");

header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(['error' => 'Invalid or missing ID']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM death_certificates WHERE id = ?");
    $stmt->execute([$id]);

    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        try {
            $releaseStmt = $conn->prepare("SELECT release_claimant, release_relationship, pickup_time AS release_time, release_date FROM certificate_releases WHERE certificate_type = 'death' AND certificate_id = ? ORDER BY id DESC LIMIT 1");
            $releaseStmt->execute([$id]);
            $release = $releaseStmt->fetch(PDO::FETCH_ASSOC);
            if ($release) {
                $record['release_claimant'] = $release['release_claimant'] ?? '';
                $record['release_relationship'] = $release['release_relationship'] ?? '';
                $record['release_time'] = $release['release_time'] ?? '';
                $record['release_date'] = $release['release_date'] ?? '';
            }
        } catch (PDOException $e) {
        }

        echo json_encode($record);
    } else {
        echo json_encode(['error' => 'Record not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
