<?php
require "../../config/db.php";

header("Content-Type: application/json");

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid ID provided']);
        exit;
    }

    $id = intval($_GET['id']);

    
    $query = "SELECT * FROM confirmation_records WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $confirmation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$confirmation) {
        http_response_code(404);
        echo json_encode(['error' => 'Confirmation record not found']);
        exit;
    }

    
    $sponsorQuery = "SELECT sponsor_name FROM confirmation_sponsors WHERE confirmation_id = :id";
    $sponsorStmt = $conn->prepare($sponsorQuery);
    $sponsorStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $sponsorStmt->execute();
    $sponsors = $sponsorStmt->fetchAll(PDO::FETCH_COLUMN);

    $confirmation['sponsors'] = $sponsors ?: [];

    $releaseStmt = $conn->prepare("SELECT release_claimant, release_relationship, release_date, pickup_time FROM certificate_releases WHERE certificate_type = 'confirmation' AND certificate_id = :id ORDER BY id DESC LIMIT 1");
    $releaseStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $releaseStmt->execute();
    $releaseInfo = $releaseStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $confirmation['release_claimant'] = $releaseInfo['release_claimant'] ?? '';
    $confirmation['release_relationship'] = $releaseInfo['release_relationship'] ?? '';
    $confirmation['release_date'] = $releaseInfo['release_date'] ?? '';
    $pickupTime = $releaseInfo['pickup_time'] ?? null;
    $confirmation['release_time'] = $pickupTime ? substr($pickupTime, 0, 5) : '';

    echo json_encode($confirmation);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
