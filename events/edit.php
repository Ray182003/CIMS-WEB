<?php
$title = "Update Event";
require_once("../config/db.php");
include_once("../partials/html.head.php");

$id = $_GET['id'] ?? null;
$record = null;
$error = "";

// ✅ Validate ID
if (!$id) {
    die("Invalid request. No event ID provided.");
}

// ✅ Fetch the existing event
try {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        die("Record not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = trim($_POST['type']);
    $event_date = trim($_POST['event_date']);
    $time = trim($_POST['time']);
    $description = trim($_POST['description']);

    try {
        // ✅ Check for event conflict (same date within 3 hours)
        $conflictStmt = $conn->prepare("
            SELECT time FROM events 
            WHERE event_date = ? AND id != ?
        ");
        $conflictStmt->execute([$event_date, $id]);
        $conflicts = $conflictStmt->fetchAll(PDO::FETCH_ASSOC);

        $hasConflict = false;
        $newDateTime = new DateTime("$event_date $time");

        foreach ($conflicts as $row) {
            $existingDateTime = new DateTime("$event_date " . $row['time']);
            $diffMinutes = abs($existingDateTime->getTimestamp() - $newDateTime->getTimestamp()) / 60;

            if ($diffMinutes < 180) {
                $hasConflict = true;
                break;
            }
        }

        if ($hasConflict) {
            $error = "Another event is already scheduled within 3 hours of this time on the same date.";
        } else {
            // ✅ Update event in a transaction
            $conn->beginTransaction();

            $updateStmt = $conn->prepare("
                UPDATE events 
                SET type = ?, event_date = ?, time = ?, description = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$type, $event_date, $time, $description, $id]);

            $conn->commit();

            header("Location: index.php?success=Event updated successfully");
            exit();
        }

        // Update local record for re-populating the form in case of validation error
        $record = [
            'id' => $id,
            'type' => $type,
            'event_date' => $event_date,
            'time' => $time,
            'description' => $description,
        ];
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = "Error updating event: " . $e->getMessage();
    }
}
?>

<body class="sb-nav-fixed gradient-page">
    <?php include_once("../partials/navbar.php"); ?>
    <div id="layoutSidenav">
        <?php include_once("../partials/sidebar.php"); ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 pt-3 baptismal-bg">
                    <div class="row">
                        <div class="col-10">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $id; ?>" method="POST">
                                <div class="card border-0 shadow baptismal-panel">
                                    <h5 class="card-header bg-primary text-white">Update Event</h5>
                                    <div class="col-12 md-4">
                                        <div class="card p-4 shadow-lg baptismal-panel">
                                            <h4 class="text-center text-dark fw-bold">Event Update Form</h4>

                                            <?php if (!empty($error)) : ?>
                                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                            <?php endif; ?>

                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($record['id']); ?>">

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Event Type</label>
                                                <select name="type" class="form-select" required>
                                                    <option value="" disabled>Select Event Type</option>
                                                    <?php
                                                    $types = ["Confirmation", "Baptismal", "Status of Liberty", "Permit to Marry", "Death"];
                                                    foreach ($types as $t) {
                                                        $selected = ($record['type'] == $t) ? "selected" : "";
                                                        echo "<option value=\"$t\" $selected>$t</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Date</label>
                                                <input type="date" name="event_date" class="form-control"
                                                    value="<?php echo htmlspecialchars($record['event_date']); ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Time</label>
                                                <input type="time" name="time" class="form-control"
                                                    value="<?php echo htmlspecialchars($record['time']); ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Description</label>
                                                <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($record['description']); ?></textarea>
                                            </div>

                                            <button type="submit" class="btn btn-primary">Update</button>
                                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <?php include_once("../partials/footer.php"); ?>
        </div>
    </div>
    <?php include_once("../partials/html.footer.php"); ?>
</body>

</html>