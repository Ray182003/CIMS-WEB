<?php
require "../../config/db.php";
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eventId'])) {
    $eventId = intval($_POST['eventId']); 
    $eventType = $_POST['eventType'];
    $eventDate = $_POST['eventDate'];
    $eventTime = $_POST['eventTime'];
    $eventDescription = $_POST['eventDescription'];
    $scheduledBy = $_POST['scheduledBy'] ?? '';

    $checkStmt = $conn->prepare("SELECT scheduled_by, type FROM events WHERE id = :id");
    $checkStmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo json_encode(['success' => false, 'error' => 'Event not found.']);
        exit();
    }

    $existingType = $existing['type'] ?? '';
    $hasCertificateType = is_string($existingType) && stripos($existingType, 'certificate') !== false;

    if ($hasCertificateType && !empty(trim($existing['scheduled_by'] ?? ''))) {
        echo json_encode(['success' => false, 'error' => 'Auto-generated certificate events cannot be edited.']);
        exit();
    }

    if (empty($eventType) || empty($eventDescription) || empty($scheduledBy)) {
        echo json_encode(value: ['success' => false, 'error' => "All fields are required."]);
        exit();
    }

    $query = "UPDATE events SET type = :type, event_date = :event_date, time = :time, description = :description, scheduled_by = :scheduled_by WHERE id = :id";
    $stmt = $conn->prepare($query);

    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->bindParam(':type', $eventType, PDO::PARAM_STR);
    $stmt->bindParam(':event_date', $eventDate, PDO::PARAM_STR);
    $stmt->bindParam(':time', $eventTime, PDO::PARAM_STR);
    $stmt->bindParam(':description', $eventDescription, PDO::PARAM_STR);
    $stmt->bindParam(':scheduled_by', $scheduledBy, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(value: ['success' => true, 'message' => "Event was successfully updated"]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update event']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
