<?php
$title = "Confirmation Form";

$hide_login = true;
require_once("../config/db.php");
require_once("../includes/event_link_helper.php");

ensureEventLinkColumn($conn, 'confirmation_records');

$monthOptions = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December"
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $child_name = $_POST['child_name'];
    $parent_name1 = $_POST['parent_name1'];
    $parent_name2 = $_POST['parent_name2'];
    $bishop = $_POST['bishop'];
    $date = $_POST['date'];
    $sponsors = $_POST['sponsors'];
    $book_no = trim($_POST['book_no']);
    $page_no = trim($_POST['page_no']);
    $entry_no = trim($_POST['entry_no']);
    $release_claimant = trim($_POST['release_claimant'] ?? '');
    $release_relationship = trim($_POST['release_relationship'] ?? '');
    $release_time = trim($_POST['release_time'] ?? '');

    if (!ctype_digit($book_no) || !ctype_digit($page_no) || !ctype_digit($entry_no)) {
        echo "<script>alert('Book No, Page No, and Entry No must be numeric.'); window.history.back();</script>";
        exit;
    }

    if ($release_claimant === '') {
        echo "<script>alert('Please enter the name of the person who will claim the certificate.'); window.history.back();</script>";
        exit;
    }

    $day = $_POST['day'];
    $month = $_POST['month'];

    try {
        $conn->beginTransaction();

        $query = "INSERT INTO confirmation_records 
            (child_name, parent_name1, parent_name2, bishop, confirmation_date, book_no, page_no, entry_no, issue_day, issue_month) 
            VALUES (:child_name, :parent_name1, :parent_name2, :bishop, :confirmation_date, :book_no, :page_no, :entry_no, :issue_day, :issue_month)";

        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':child_name' => $child_name,
            ':parent_name1' => $parent_name1,
            ':parent_name2' => $parent_name2,
            ':bishop' => $bishop,
            ':confirmation_date' => $date,
            ':book_no' => $book_no,
            ':page_no' => $page_no,
            ':entry_no' => $entry_no,
            ':issue_day' => $day,
            ':issue_month' => $month
        ]);

        $confirmation_id = $conn->lastInsertId();

        if (!empty($sponsors) && is_array($sponsors)) {
            $sponsorStmt = $conn->prepare("INSERT INTO confirmation_sponsors (confirmation_id, sponsor_name) VALUES (:confirmation_id, :sponsor_name)");
            foreach ($sponsors as $sponsor) {
                $sponsorStmt->execute([
                    ':confirmation_id' => $confirmation_id,
                    ':sponsor_name' => $sponsor
                ]);
            }
        }

        $conn->commit();

        $eventDate = null;
        $yearFromDate = null;
        if (!empty($date) && preg_match('/^(\d{4})-\d{2}-\d{2}$/', $date, $m)) {
            $yearFromDate = (int) $m[1];
        }

        if ($yearFromDate) {
            $monthIndex = array_search($month, $monthOptions, true);
            $dayInt = (int) $day;
            if ($monthIndex !== false && $dayInt > 0 && $dayInt <= 31) {
                $monthNumber = $monthIndex + 1;
                $eventDate = sprintf('%04d-%02d-%02d', $yearFromDate, $monthNumber, $dayInt);
            }
        }

        $createdEventId = null;
        if ($eventDate !== null) {
            $claimDetails = '';
            if ($release_claimant !== '') {
                $claimDetails = ' | To be claimed by: ' . $release_claimant;
                if ($release_relationship !== '') {
                    $claimDetails .= ' (' . $release_relationship . ')';
                }
                if ($release_time !== '') {
                    $claimDetails .= ' at ' . $release_time;
                }
            }
            try {
                $eventStmt = $conn->prepare("INSERT INTO events (type, event_date, time, description, scheduled_by) VALUES (:type, :event_date, :time, :description, :scheduled_by)");
                $eventStmt->execute([
                    ':type' => 'Confirmation Certificate',
                    ':event_date' => $eventDate,
                    ':time' => date('H:i:s'),
                    ':description' => trim('Confirmation record created for ' . $child_name . $claimDetails),
                    ':scheduled_by' => $_SESSION['user_fullname'] ?? ''
                ]);
                $createdEventId = (int) $conn->lastInsertId();
            } catch (Exception $e) {
            }
        }

        if (!empty($createdEventId)) {
            $updateEventLink = $conn->prepare("UPDATE confirmation_records SET event_id = :event_id WHERE id = :id");
            $updateEventLink->execute([
                ':event_id' => $createdEventId,
                ':id' => $confirmation_id
            ]);
        }

        if ($eventDate !== null && $release_claimant !== '') {
            try {
                $conn->exec("CREATE TABLE IF NOT EXISTS certificate_releases (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    certificate_type VARCHAR(50) NOT NULL,
                    certificate_id INT NOT NULL,
                    release_claimant VARCHAR(255) NOT NULL,
                    release_relationship VARCHAR(255) DEFAULT NULL,
                    release_date DATE NOT NULL,
                    pickup_time TIME DEFAULT NULL,
                    user_id INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $releaseStmt = $conn->prepare("INSERT INTO certificate_releases (certificate_type, certificate_id, release_claimant, release_relationship, release_date, pickup_time, user_id) VALUES (:certificate_type, :certificate_id, :release_claimant, :release_relationship, :release_date, :pickup_time, :user_id)");
                $releaseStmt->execute([
                    ':certificate_type' => 'confirmation',
                    ':certificate_id' => $confirmation_id,
                    ':release_claimant' => $release_claimant,
                    ':release_relationship' => $release_relationship !== '' ? $release_relationship : null,
                    ':release_date' => $eventDate,
                    ':pickup_time' => $release_time !== '' ? $release_time : null,
                    ':user_id' => $_SESSION['user_id'] ?? null
                ]);
            } catch (Exception $e) {
            }
        }

        echo "<script>alert('Confirmation record added successfully!'); window.location.href='index.php';</script>";
    } catch (PDOException $e) {
        $conn->rollBack();
        die("Error: " . $e->getMessage());
    }
}
?>

<?php include_once("../partials/html.head.php"); ?>

<style>
    :root {
        scroll-behavior: smooth;
    }

    .confirmation-layout {
        padding-bottom: 3rem;
    }

    .confirmation-card {
        border-radius: 1.5rem;
        overflow: hidden;
        backdrop-filter: blur(6px);
    }

    .confirmation-card .card-header {
        background: linear-gradient(90deg, #0d6efd, #6610f2);
        border-bottom: none;
        position: relative;
        overflow: hidden;
    }

    .confirmation-card .card-header .section-eyebrow {
        display: inline-block;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.12rem;
        opacity: 0.85;
    }

    .confirmation-card .card-header h2 {
        font-size: 1.85rem;
        font-weight: 700;
        letter-spacing: 0.015rem;
    }

    .header-main {
        position: relative;
        z-index: 1;
    }

    .hero-icon {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 1.35rem;
        background: rgba(255, 255, 255, 0.22);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: #ffffff;
        box-shadow: 0 18px 32px rgba(0, 0, 0, 0.15);
        backdrop-filter: blur(4px);
    }

    .floating-shape {
        position: absolute;
        border-radius: 50%;
        opacity: 0.35;
        pointer-events: none;
        filter: blur(0px);
    }

    .floating-shape--one {
        width: 14rem;
        height: 14rem;
        background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.6), transparent 70%);
        top: -5rem;
        right: -4rem;
    }

    .floating-shape--two {
        width: 10rem;
        height: 10rem;
        background: radial-gradient(circle at 70% 70%, rgba(255, 255, 255, 0.45), transparent 65%);
        bottom: -3rem;
        left: -3rem;
    }

    .card-intro {
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.08), rgba(102, 16, 242, 0.06));
        border-radius: 1.25rem;
        padding: 1.75rem;
        border: 1px solid rgba(13, 110, 253, 0.12);
        display: grid;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .card-intro h3 {
        font-weight: 700;
        color: #0b3c95;
        margin-bottom: 0.25rem;
    }

    .card-intro p {
        margin: 0;
        color: #495057;
        font-size: 0.95rem;
    }

    .card-intro .highlight-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 999px;
        padding: 0.4rem 1rem;
        font-weight: 600;
        color: #6610f2;
        box-shadow: 0 8px 16px rgba(102, 16, 242, 0.08);
    }

    .stepper {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2.25rem;
    }

    .stepper .step {
        flex: 1 1 14rem;
        background: rgba(255, 255, 255, 0.92);
        border-radius: 1.1rem;
        border: 1px solid rgba(13, 110, 253, 0.14);
        padding: 1rem 1.2rem;
        display: flex;
        gap: 0.85rem;
        align-items: center;
        position: relative;
        box-shadow: 0 12px 24px rgba(13, 110, 253, 0.08);
        cursor: pointer;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        color: #0d366b;
        text-align: left;
    }

    .stepper .step:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 28px rgba(13, 110, 253, 0.12);
    }

    .stepper .step:focus-visible {
        outline: 3px solid rgba(13, 110, 253, 0.35);
        outline-offset: 4px;
    }

    .stepper .step:not(:last-child)::after {
        content: "";
        position: absolute;
        right: -0.8rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1.6rem;
        height: 2px;
        background: linear-gradient(90deg, rgba(13, 110, 253, 0.3), rgba(102, 16, 242, 0.3));
    }

    .stepper .step::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.1), transparent 65%);
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }

    .stepper .step:hover::after,
    .stepper .step.active::after {
        opacity: 1;
    }

    .stepper .step.active {
        border-color: rgba(102, 16, 242, 0.35);
        box-shadow: 0 16px 36px rgba(102, 16, 242, 0.15);
    }

    .stepper .step-index {
        width: 2.3rem;
        height: 2.3rem;
        border-radius: 0.9rem;
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.2), rgba(13, 110, 253, 0.05));
        color: #0d6efd;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .stepper .step h6 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #0d366b;
    }

    .stepper .step span {
        display: block;
        font-size: 0.8rem;
        color: #6c757d;
    }

    .form-section-title {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        font-weight: 600;
        font-size: 1.05rem;
        color: #0d6efd;
        margin-bottom: 0.35rem;
    }

    .form-section-title .section-index {
        width: 2rem;
        height: 2rem;
        border-radius: 0.75rem;
        background: rgba(13, 110, 253, 0.12);
        color: #0d6efd;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
    }

    .form-section-subtitle {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .section-shell {
        padding: 1.25rem 1.5rem;
        border: 1px solid rgba(13, 110, 253, 0.08);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.85);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.55);
    }

    .section-shell + .section-shell {
        margin-top: 1.5rem;
    }

    .section-shell.highlight {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(102, 16, 242, 0.15);
        box-shadow: 0 16px 32px rgba(102, 16, 242, 0.08);
    }

    .form-label {
        font-size: 0.9rem;
        color: #374151;
    }

    .input-group-text {
        background: #f1f5f9;
        font-weight: 600;
        color: #0d6efd;
    }

    .form-control,
    .form-select {
        border-radius: 0.9rem;
        border-color: rgba(13, 110, 253, 0.2);
        padding: 0.65rem 0.9rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: rgba(102, 16, 242, 0.55);
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        transform: translateY(-1px);
    }

    .card-footer-actions {
        background: transparent;
        border-top: 1px solid rgba(13, 110, 253, 0.1);
    }

    .card-footer-actions .btn {
        min-width: 7rem;
        font-weight: 600;
        padding-inline: 1.5rem;
    }

    @media (max-width: 575.98px) {
        .confirmation-card .card-header {
            text-align: center;
        }

        .form-section-title {
            justify-content: center;
        }

        .stepper {
            flex-direction: column;
        }

        .section-shell {
            padding: 1.25rem 1rem;
        }
    }
</style>

<body class="sb-nav-fixed gradient-page">
    <?php include_once("../partials/navbar.php"); ?>
    <div id="layoutSidenav">
        <?php include_once("../partials/sidebar.php"); ?>
        <div id="layoutSidenav_content">
            <main class="confirmation-layout">
                <div class="container-fluid px-3 px-lg-4 pt-3">
                    <div class="row justify-content-center">
                        <div class="col-12 col-lg-11 col-xl-10 col-xxl-9">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                                <div class="card confirmation-card border-0 shadow-lg">
                                    <div class="card-header text-white px-4 px-lg-5 py-4">
                                        <div class="header-main d-flex flex-column gap-3 gap-lg-4">
                                            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="hero-icon" aria-hidden="true">✅</span>
                                                    <div>
                                                        <span class="section-eyebrow">Sacramental Records</span>
                                                        <h2 class="mb-1">New Confirmation Registration</h2>
                                                        <p class="mb-0 text-white-50">Guided workflow to encode confirmation details accurately.</p>
                                                    </div>
                                                </div>
                                                <div class="ms-lg-auto">
                                                    <span class="badge bg-white text-primary fw-semibold px-3 py-2 shadow-sm">Complete all steps to save the record</span>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="floating-shape floating-shape--one"></span>
                                        <span class="floating-shape floating-shape--two"></span>
                                    </div>

                                    <div class="card-body px-4 px-lg-5 py-5">
                                        <div class="card-intro">
                                            <span class="highlight-pill">🕊️ Sacrament of Confirmation</span>
                                            <div>
                                                <h3>Capture every detail of the confirmation.</h3>
                                                <p>Use this form to record the confirmand, family, presider, sponsors, and registry numbers in one structured flow.</p>
                                            </div>
                                        </div>

                                        <div class="stepper" role="tablist" aria-label="Confirmation form progress">
                                            <button type="button" class="step active" data-step="1" data-step-target="#section-step-1" aria-current="step">
                                                <span class="step-index">1</span>
                                                <div>
                                                    <h6>Child &amp; Family</h6>
                                                    <span>Names and basic information</span>
                                                </div>
                                            </button>
                                            <button type="button" class="step" data-step="2" data-step-target="#section-step-2">
                                                <span class="step-index">2</span>
                                                <div>
                                                    <h6>Confirmation Details</h6>
                                                    <span>Confirmation date &amp; presider</span>
                                                </div>
                                            </button>
                                            <button type="button" class="step" data-step="3" data-step-target="#section-step-3">
                                                <span class="step-index">3</span>
                                                <div>
                                                    <h6>Sponsors</h6>
                                                    <span>List of sponsors</span>
                                                </div>
                                            </button>
                                            <button type="button" class="step" data-step="4" data-step-target="#section-step-4">
                                                <span class="step-index">4</span>
                                                <div>
                                                    <h6>Date of Release &amp; Registry</h6>
                                                    <span>Issue date and book references</span>
                                                </div>
                                            </button>
                                        </div>

                                        <input type="hidden" name="id" id="id">

                                        <div class="section-shell highlight" id="section-step-1" data-section-step="1">
                                            <div class="form-section-title">
                                                <span class="section-index">1</span>
                                                <span>Child &amp; Family Information</span>
                                            </div>
                                            <p class="form-section-subtitle mb-4">Provide the confirmand's name and parents as they should appear on the certificate.</p>

                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold">Child Name</label>
                                                    <input type="text" name="child_name" class="form-control" required>
                                                </div>
                                                <div class="col-12 col-lg-6">
                                                    <label class="form-label fw-semibold">Father Name</label>
                                                    <input type="text" name="parent_name1" class="form-control" required>
                                                </div>
                                                <div class="col-12 col-lg-6">
                                                    <label class="form-label fw-semibold">Mother Name</label>
                                                    <input type="text" name="parent_name2" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="section-shell" id="section-step-2" data-section-step="2">
                                            <div class="form-section-title">
                                                <span class="section-index">2</span>
                                                <span>Confirmation Details</span>
                                            </div>
                                            <p class="form-section-subtitle mb-4">Encode the confirmation date and the presider who administered the sacrament.</p>

                                            <div class="row g-3">
                                                <div class="col-12 col-lg-6">
                                                    <label class="form-label fw-semibold">Presider</label>
                                                    <input type="text" name="bishop" class="form-control" required>
                                                </div>
                                                <div class="col-12 col-lg-6">
                                                    <label class="form-label fw-semibold">Confirmation Date</label>
                                                    <input type="date" name="date" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="section-shell" id="section-step-3" data-section-step="3">
                                            <div class="form-section-title">
                                                <span class="section-index">3</span>
                                                <span>Sponsors</span>
                                            </div>
                                            <p class="form-section-subtitle mb-4">List the sponsors in the order they will appear on the certificate.</p>

                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Sponsors</label>
                                                <div id="sponsorContainer">
                                                    <div class="d-flex gap-2 mb-2">
                                                        <input type="text" class="form-control" name="sponsors[]">
                                                        <button id="addSponsor" class="btn btn-outline-primary btn-sm">ADD</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="section-shell mb-0" id="section-step-4" data-section-step="4">
                                            <div class="form-section-title">
                                                <span class="section-index">4</span>
                                                <span>Date of Release &amp; Registry References</span>
                                            </div>
                                            <p class="form-section-subtitle mb-4">Set the issue date of the certificate and confirm the registry references.</p>

                                            <div class="row g-3 mb-3">
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold">Date of Release</label>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label fw-semibold">Month</label>
                                                    <select name="month" class="form-select" required>
                                                        <option value="" selected>Select month</option>
                                                        <?php foreach ($monthOptions as $monthName): ?>
                                                            <option value="<?php echo $monthName; ?>"><?php echo $monthName; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label fw-semibold">Day</label>
                                                    <div class="input-group">
                                                        <input type="number" name="day" class="form-control" required inputmode="numeric" autocomplete="off" min="1" max="31" data-suffix-target="confirmationDaySuffix">
                                                        <span class="input-group-text" id="confirmationDaySuffix">th</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label fw-semibold">Book No</label>
                                                    <input type="number" name="book_no" class="form-control" min="0" step="1" required>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label fw-semibold">Page No</label>
                                                    <input type="number" name="page_no" class="form-control" min="0" step="1" required>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label fw-semibold">Entry No</label>
                                                    <input type="number" name="entry_no" class="form-control" min="0" step="1" required>
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" name="release_claimant" id="release_claimant">
                                        <input type="hidden" name="release_relationship" id="release_relationship">
                                        <input type="hidden" name="release_time" id="release_time">
                                    </div>

                                    <div class="card-footer card-footer-actions px-4 px-lg-5 py-4">
                                        <div class="d-flex flex-column flex-md-row justify-content-md-end gap-2">
                                            <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                                            <button type="button" id="openReleaseModalBtn" class="btn btn-primary">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>

            <div class="modal fade" id="releaseInfoModal" tabindex="-1" aria-labelledby="releaseInfoModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="releaseInfoModalLabel">Certificate Release Information</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Name of Claimant</label>
                                <input type="text" class="form-control" id="modal_release_claimant">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Relationship to Owner (optional)</label>
                                <input type="text" class="form-control" id="modal_release_relationship">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Date of Release</label>
                                <input type="text" class="form-control" id="modal_release_date" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pickup Time (optional)</label>
                                <input type="time" class="form-control" id="modal_release_time">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                            <button type="button" id="confirmReleaseBtn" class="btn btn-primary">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once("../partials/footer.php"); ?>
        </div>
    </div>

    <?php include_once("../partials/html.footer.php"); ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const addButton = document.getElementById("addSponsor");
            const sponsorContainer = document.getElementById("sponsorContainer");
            const dayInput = document.querySelector('input[name="day"]');
            const daySuffix = dayInput ? document.getElementById(dayInput.dataset.suffixTarget) : null;
            const form = document.querySelector('form[action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>"]');
            const openReleaseModalBtn = document.getElementById('openReleaseModalBtn');
            const releaseModalEl = document.getElementById('releaseInfoModal');
            const modalReleaseClaimant = document.getElementById('modal_release_claimant');
            const modalReleaseRelationship = document.getElementById('modal_release_relationship');
            const modalReleaseDate = document.getElementById('modal_release_date');
            const modalReleaseTime = document.getElementById('modal_release_time');
            const hiddenReleaseClaimant = document.getElementById('release_claimant');
            const hiddenReleaseRelationship = document.getElementById('release_relationship');
            const hiddenReleaseTime = document.getElementById('release_time');
            const monthSelect = document.querySelector('select[name="month"]');
            const confirmationDateInput = document.querySelector('input[name="date"]');
            let releaseModal = null;

            function getOrdinalSuffix(day) {
                const tens = day % 100;
                if (tens >= 11 && tens <= 13) {
                    return "th";
                }

                switch (day % 10) {
                    case 1:
                        return "st";
                    case 2:
                        return "nd";
                    case 3:
                        return "rd";
                    default:
                        return "th";
                }
            }

            function updateDaySuffix(rawValue) {
                if (!daySuffix) {
                    return;
                }

                const numericDay = parseInt(rawValue, 10);
                if (!Number.isNaN(numericDay) && numericDay > 0) {
                    daySuffix.textContent = getOrdinalSuffix(numericDay);
                } else {
                    daySuffix.textContent = "th";
                }
            }

            function clampDayValue(rawValue) {
                if (!dayInput) {
                    return;
                }

                if (rawValue === undefined || rawValue === null || rawValue === "") {
                    dayInput.value = "";
                    updateDaySuffix("");
                    return;
                }

                const digitsOnly = String(rawValue).replace(/[^0-9]/g, "");
                if (digitsOnly === "") {
                    dayInput.value = "";
                    updateDaySuffix("");
                    return;
                }

                const numericDay = parseInt(digitsOnly, 10);
                if (Number.isNaN(numericDay) || numericDay <= 0) {
                    dayInput.value = "";
                    updateDaySuffix("");
                    return;
                }

                const clampedDay = Math.min(Math.max(numericDay, 1), 31);
                dayInput.value = clampedDay;
                updateDaySuffix(clampedDay);
            }

            if (dayInput) {
                dayInput.addEventListener("input", function() {
                    const digitsOnly = this.value.replace(/[^0-9]/g, "");
                    this.value = digitsOnly;
                    updateDaySuffix(digitsOnly);
                });

                dayInput.addEventListener("blur", function() {
                    clampDayValue(this.value);
                });

                updateDaySuffix(dayInput.value);
            }
            addButton.addEventListener("click", function(event) {
                event.preventDefault();

                const newDiv = document.createElement("div");
                newDiv.classList.add("d-flex", "gap-2", "mb-1");
                newDiv.innerHTML = `
            <input type="text" class="form-control" name="sponsors[]">
            <button class="btn btn-danger btn-sm removeSponsor">REMOVE</button>
        `;

                sponsorContainer.appendChild(newDiv);
            });

            sponsorContainer.addEventListener("click", function(event) {
                if (event.target.classList.contains("removeSponsor")) {
                    event.preventDefault();
                    event.target.parentElement.remove();
                }
            });

            if (releaseModalEl) {
                releaseModal = new bootstrap.Modal(releaseModalEl);
            }

            function buildReleaseDateDisplay() {
                if (!dayInput || !monthSelect || !confirmationDateInput || !modalReleaseDate) {
                    return;
                }
                const d = dayInput.value;
                const dateValue = confirmationDateInput.value;
                let year = '';
                if (dateValue && /^\d{4}-\d{2}-\d{2}$/.test(dateValue)) {
                    year = dateValue.substring(0, 4);
                }
                const monthText = monthSelect.options[monthSelect.selectedIndex] ? monthSelect.options[monthSelect.selectedIndex].text : '';
                if (!d || !year || !monthText) {
                    modalReleaseDate.value = '';
                    return;
                }
                modalReleaseDate.value = monthText + ' ' + d + ', ' + year;
            }

            if (openReleaseModalBtn && form && releaseModal) {
                openReleaseModalBtn.addEventListener('click', function () {
                    if (!form.reportValidity()) {
                        return;
                    }
                    buildReleaseDateDisplay();
                    modalReleaseClaimant.value = hiddenReleaseClaimant.value || '';
                    modalReleaseRelationship.value = hiddenReleaseRelationship.value || '';
                    modalReleaseTime.value = hiddenReleaseTime.value || '';
                    releaseModal.show();
                });
            }

            const confirmReleaseBtn = document.getElementById('confirmReleaseBtn');
            if (confirmReleaseBtn && form && releaseModal) {
                confirmReleaseBtn.addEventListener('click', function () {
                    const claimant = modalReleaseClaimant.value.trim();
                    if (!claimant) {
                        alert('Please enter the name of the person who will claim the certificate.');
                        return;
                    }
                    hiddenReleaseClaimant.value = claimant;
                    hiddenReleaseRelationship.value = modalReleaseRelationship.value.trim();
                    hiddenReleaseTime.value = modalReleaseTime.value;
                    releaseModal.hide();
                    form.submit();
                });
            }
        });
    </script>


</body>

</html>