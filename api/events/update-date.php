<?php
require '../../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$eventId = intval($_POST['eventId'] ?? 0);
$eventDate = trim($_POST['eventDate'] ?? '');

if (!$eventId || empty($eventDate)) {
    echo json_encode(['success' => false, 'error' => 'Event ID and new date are required.']);
    exit;
}

try {
    $stmt = $conn->prepare('UPDATE events SET event_date = :event_date WHERE id = :id');
    $stmt->bindParam(':event_date', $eventDate, PDO::PARAM_STR);
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event date updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update event date']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
