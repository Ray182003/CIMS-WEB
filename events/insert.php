<?php
$title = "Event Form";
require "../config/db.php";
include "../partials/html.head.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];
    $event_date = $_POST['event_date'];
    $time = $_POST['time'];
    $description = $_POST['description'];

    try {
        $check_stmt = $conn->prepare(" SELECT time FROM events  WHERE event_date = ? ORDER BY time DESC LIMIT 1");
        $check_stmt->execute([$event_date]);
        $last_time = $check_stmt->fetchColumn();

        $canInsert = true;

        if ($last_time) {
            $lastDateTime = new DateTime("$event_date $last_time");
            $newDateTime = new DateTime("$event_date $time");
            $diffMinutes = abs($newDateTime->getTimestamp() - $lastDateTime->getTimestamp()) / 60;

            if ($diffMinutes < 180) {
                $canInsert = false;
            }
        }

        if (!$canInsert) {
            echo "<script>alert('You must wait at least 3 hours from the last event on the same date.'); window.history.back();</script>";
        } else {
            $stmt = $conn->prepare("INSERT INTO events (type, event_date, time, description) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$type, $event_date, $time, $description]);

            if ($result) {
                echo "<script>alert('Event added successfully!'); window.location.href='index.php';</script>";
            } else {
                $errorInfo = $stmt->errorInfo();
                die("Execute failed: " . $errorInfo[2]);
            }
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
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
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                                <div class="card border-0 shadow baptismal-panel">
                                    <h5 class="card-header bg-primary text-white">New Event</h5>
                                    <div class="card-body p-4 baptismal-panel">

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Event Type</label>
                                            <select name="type" class="form-select" required>
                                                <option value="" selected disabled>Select Event Type</option>
                                                <option value="Baptism">Baptism</option>
                                                <option value="Confirmation">Confirmation</option>
                                                <option value="Marriage">Marriage</option>
                                                <!-- <option value="Permit to Marry">Permit to Marry</option> -->
                                                <option value="Requiem">Requiem</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Date</label>
                                            <input type="date" name="event_date" class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Time</label>
                                            <input type="time" name="time" class="form-control" required>
                                        </div>


                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Description</label>
                                            <textarea name="description" class="form-control" rows="4" required></textarea>
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary">Submit</button>
                                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <?php include "../partials/footer.php"; ?>
        </div>
    </div>
    <?php include "../partials/html.footer.php"; ?>
</body>

</html>