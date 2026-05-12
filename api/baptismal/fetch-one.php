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

   
    $query = "SELECT * FROM baptismal_records WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $baptismal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$baptismal) {
        http_response_code(404);
        echo json_encode(['error' => 'Baptismal record not found']);
        exit;
    }


    $sponsorQuery = "SELECT sponsor_name FROM sponsors WHERE baptismal_id = :id";
    $sponsorStmt = $conn->prepare($sponsorQuery);
    $sponsorStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $sponsorStmt->execute();
    $sponsors = $sponsorStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($sponsors)) {
        $sponsors = array_values(array_unique(array_filter(array_map(static function ($name) {
            return trim($name ?? '');
        }, $sponsors), static function ($name) {
            return $name !== '';
        })));
    }

    $baptismal['sponsors'] = $sponsors ?: [];

    // Kunin ang release information kung meron
    $releaseStmt = $conn->prepare("SELECT release_claimant, release_relationship, release_date, pickup_time FROM certificate_releases WHERE certificate_type = 'baptismal' AND certificate_id = :id ORDER BY id DESC LIMIT 1");
    $releaseStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $releaseStmt->execute();
    $releaseInfo = $releaseStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!empty($releaseInfo)) {
        $baptismal['release_claimant'] = $releaseInfo['release_claimant'] ?? '';
        $baptismal['release_relationship'] = $releaseInfo['release_relationship'] ?? '';
        $baptismal['release_date'] = $releaseInfo['release_date'] ?? '';
        $pickupTime = $releaseInfo['pickup_time'] ?? null;
        $baptismal['release_time'] = $pickupTime ? substr($pickupTime, 0, 5) : '';
    } else {
        $baptismal['release_claimant'] = '';
        $baptismal['release_relationship'] = '';
        $baptismal['release_date'] = '';
        $baptismal['release_time'] = '';
    }

    echo json_encode($baptismal);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
