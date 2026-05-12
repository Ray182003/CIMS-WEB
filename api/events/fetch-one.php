<?php
require "../../config/db.php";

header("Content-Type: application/json");



if (isset($_GET['id'])) {
    $id = intval($_GET['id']) ?? null;

    $query = "SELECT * FROM events WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($event) {
        echo json_encode($event);
    } else {
        echo json_encode(['error' => 'Event not found']);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}
