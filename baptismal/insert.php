<?php
$title = "Baptismal Form";
$hide_login = true;
require_once("../config/db.php");
require_once("../includes/event_link_helper.php");

ensureEventLinkColumn($conn, 'baptismal_records');

$monthOptions = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$currentMonthName = date('F');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $child_name = $_POST['child_name'];
    $parent_name1 = $_POST['parent_name1'];
    $parent_name2 = $_POST['parent_name2'];
    $date = $_POST['date'];
    $in = $_POST['in'];
    $on = $_POST['on'];
    $bishop = $_POST['bishop'];
    $day = $_POST['day'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $book_no = trim($_POST['book_no']);
    $page_no = trim($_POST['page_no']);
    $entry_no = trim($_POST['entry_no']);
    $sponsors = isset($_POST['sponsors']) && is_array($_POST['sponsors'])
        ? array_values(array_unique(array_filter(array_map(static function ($sponsorName) {
            return trim($sponsorName ?? '');
        }, $_POST['sponsors']), static function ($sponsorName) {
            return $sponsorName !== '';
        })))
        : [];
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

    try {

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $conn->beginTransaction();

        $query = "INSERT INTO baptismal_records 
            (child_name, parent_name1, parent_name2, birth_date, birth_place, baptism_date, bishop, day, month, year, book_no, page_no, entry_no) 
            VALUES 
            (:child_name, :parent_name1, :parent_name2, :birth_date, :birth_place, :baptism_date, :bishop, :day, :month, :year, :book_no, :page_no, :entry_no)";

        $stmt = $conn->prepare($query);

        $stmt->execute([
            ':child_name' => $child_name,
            ':parent_name1' => $parent_name1,
            ':parent_name2' => $parent_name2,
            ':birth_place' => $date,
            ':birth_date' => $in,
            ':baptism_date' => $on,
            ':bishop' => $bishop,
            ':day' => $day,
            ':month' => $month,
            ':year' => $year,
            ':book_no' => $book_no,
            ':page_no' => $page_no,
            ':entry_no' => $entry_no
        ]);


        $baptismal_id = (int) $conn->lastInsertId();

        $cleanupSponsors = $conn->prepare("DELETE FROM sponsors WHERE baptismal_id = :baptismal_id");
        $cleanupSponsors->execute([':baptismal_id' => $baptismal_id]);

        if (!empty($sponsors)) {
            $sponsor_stmt = $conn->prepare("INSERT INTO sponsors (baptismal_id, sponsor_name) VALUES (:baptismal_id, :sponsor_name)");

            foreach ($sponsors as $sponsor) {
                $sponsor_stmt->execute([
                    ':baptismal_id' => $baptismal_id,
                    ':sponsor_name' => $sponsor
                ]);
            }
        }

        $conn->commit();

        $eventDate = null;
        $baptismDateRaw = trim((string) $on);
        if ($baptismDateRaw !== '') {
            $baptismDateObj = DateTime::createFromFormat('Y-m-d', $baptismDateRaw);
            if (!$baptismDateObj) {
                $baptismDateObj = DateTime::createFromFormat('m/d/Y', $baptismDateRaw);
            }
            if ($baptismDateObj) {
                $eventDate = $baptismDateObj->format('Y-m-d');
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
                    ':type' => 'Baptismal Certificate',
                    ':event_date' => $eventDate,
                    ':time' => date('H:i:s'),
                    ':description' => trim('Baptismal record created for ' . $child_name . $claimDetails),
                    ':scheduled_by' => $_SESSION['user_fullname'] ?? ''
                ]);
                $createdEventId = (int) $conn->lastInsertId();
            } catch (Exception $e) {
            }
        }

        if (!empty($createdEventId)) {
            $updateEventLink = $conn->prepare("UPDATE baptismal_records SET event_id = :event_id WHERE id = :id");
            $updateEventLink->execute([
                ':event_id' => $createdEventId,
                ':id' => $baptismal_id
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
                    ':certificate_type' => 'baptismal',
                    ':certificate_id' => $baptismal_id,
                    ':release_claimant' => $release_claimant,
                    ':release_relationship' => $release_relationship !== '' ? $release_relationship : null,
                    ':release_date' => $eventDate,
                    ':pickup_time' => $release_time !== '' ? $release_time : null,
                    ':user_id' => $_SESSION['user_id'] ?? null
                ]);
            } catch (Exception $e) {
            }
        }

        echo "<script>alert('Baptismal record added successfully!'); window.location.href='index.php';</script>";
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

    .baptismal-layout {
        padding-bottom: 3rem;
    }

    .baptismal-card {
        border-radius: 1.5rem;
        overflow: hidden;
        backdrop-filter: blur(6px);
    }

    .baptismal-card .card-header {
        background: linear-gradient(90deg, #0d6efd, #6610f2);
        border-bottom: none;
        position: relative;
        overflow: hidden;
    }

    .baptismal-card .card-header .section-eyebrow {
        display: inline-block;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.12rem;
        opacity: 0.85;
    }

    .baptismal-card .card-header h2 {
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

    .sponsor-row {
        background: #f8f9ff;
        border: 1px dashed rgba(13, 110, 253, 0.35);
        border-radius: 0.85rem;
        padding: 0.75rem;
    }

    .sponsor-row + .sponsor-row {
        margin-top: 0.75rem;
    }

    .info-panel {
        position: sticky;
        top: 3.5rem;
        border-radius: 1.2rem;
        background: linear-gradient(160deg, rgba(13, 110, 253, 0.12), rgba(102, 16, 242, 0.2));
        padding: 1.9rem 1.6rem;
        color: #fff;
        box-shadow: 0 18px 36px rgba(13, 110, 253, 0.2);
    }

    .info-panel h6 {
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08rem;
        margin-bottom: 1rem;
        opacity: 0.9;
    }

    .info-panel ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 0.85rem;
    }

    .info-panel li {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
    }

    .info-panel li .bullet {
        width: 1.65rem;
        height: 1.65rem;
        border-radius: 0.6rem;
        background: rgba(255, 255, 255, 0.22);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
    }

    .info-panel .contact-card {
        margin-top: 1.35rem;
        padding-top: 1.35rem;
        border-top: 1px solid rgba(255, 255, 255, 0.22);
        font-size: 0.85rem;
        opacity: 0.85;
    }

    .btn-add-sponsor {
        border-style: dashed;
        font-weight: 600;
        padding: 0.5rem 1.25rem;
    }

    .btn-add-sponsor:hover {
        background: rgba(13, 110, 253, 0.1);
    }

    .btn-outline-primary,
    .btn-primary,
    .btn-outline-secondary {
        border-radius: 0.85rem;
        font-weight: 600;
    }

    .btn-primary {
        box-shadow: 0 12px 24px rgba(13, 110, 253, 0.24);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 32px rgba(13, 110, 253, 0.3);
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
        .baptismal-card .card-header {
            text-align: center;
        }

        .form-section-title {
            justify-content: center;
        }

        .stepper {
            flex-direction: column;
        }

        .info-panel {
            position: static;
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
            <main class="baptismal-layout">
                <div class="container-fluid px-3 px-lg-4 pt-3">
                    <div class="row justify-content-center">
                        <div class="col-12 col-lg-11 col-xl-10 col-xxl-9">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                                <div class="card baptismal-card border-0 shadow-lg">
                                    <div class="card-header text-white px-4 px-lg-5 py-4">
                                        <div class="header-main d-flex flex-column gap-3 gap-lg-4">
                                            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="hero-icon" aria-hidden="true"><i class="fa-solid fa-cross"></i></span>
                                                    <div>
                                                        <span class="section-eyebrow">Sacramental Records</span>
                                                        <h2 class="mb-1">New Baptismal Registration</h2>
                                                        <p class="mb-0 text-white-50">Guided workflow to capture every essential detail of the baptism.</p>
                                                    </div>
                                                </div>
                                                <div class="ms-lg-auto">
                                                    <span class="badge bg-white text-primary fw-semibold px-3 py-2 shadow-sm">Complete all 4 stages to save the record</span>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="floating-shape floating-shape--one"></span>
                                        <span class="floating-shape floating-shape--two"></span>
                                    </div>

                                    <div class="card-body px-4 px-lg-5 py-5">
                                        <div class="card-intro">
                                            <span class="highlight-pill">📜 Official Parish Record</span>
                                            <div>
                                                <h3>Capture every detail with confidence.</h3>
                                                <p>Use this form to log baptismal information accurately. Each section groups related data so you can focus on one part at a time.</p>
                                            </div>
                                        </div>

                                        <div class="stepper" role="tablist" aria-label="Baptismal form progress">
                                            <button type="button" class="step active" data-step="1" data-step-target="#section-step-1" aria-current="step">
                                                <span class="step-index">1</span>
                                                <div>
                                                    <h6>Child &amp; Family</h6>
                                                    <span>Names and address for the record book</span>
                                                </div>
                                            </button>
                                            <button type="button" class="step" data-step="2" data-step-target="#section-step-2">
                                                <span class="step-index">2</span>
                                                <div>
                                                    <h6>Sacramental Details</h6>
                                                    <span>Birth, baptism, and presider</span>
                                                </div>
                                            </button>
                                            <button type="button" class="step" data-step="3" data-step-target="#section-step-3">
                                                <span class="step-index">3</span>
                                                <div>
                                                    <h6>Sponsors &amp; Release</h6>
                                                    <span>Godparents and certificate dates</span>
                                                </div>
                                            </button>
                                            <button type="button" class="step" data-step="4" data-step-target="#section-step-4">
                                                <span class="step-index">4</span>
                                                <div>
                                                    <h6>Registry Numbers</h6>
                                                    <span>Book, page, and entry details</span>
                                                </div>
                                            </button>
                                        </div>

                                        <div class="row g-4 align-items-start">
                                            <div class="col-12">
                                                <div class="section-shell highlight" id="section-step-1" data-section-step="1">
                                                    <div class="form-section-title">
                                                        <span class="section-index">1</span>
                                                        <span>Child &amp; Family Information</span>
                                                    </div>
                                                    <p class="form-section-subtitle mb-4">Provide the child's name and family details as they should appear on the certificate.</p>

                                                    <div class="row g-3">
                                                        <div class="col-12">
                                                            <label class="form-label fw-semibold">Child Name</label>
                                                            <input type="text" name="child_name" class="form-control" placeholder="Full name" required>
                                                        </div>
                                                        <div class="col-12 col-lg-6">
                                                            <label class="form-label fw-semibold">Father Name</label>
                                                            <input type="text" name="parent_name1" class="form-control" placeholder=" Full name" required>
                                                        </div>
                                                        <div class="col-12 col-lg-6">
                                                            <label class="form-label fw-semibold">Mother Name</label>
                                                            <input type="text" name="parent_name2" class="form-control" placeholder="Full name" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label fw-semibold">Address</label>
                                                            <input type="text" name="date" class="form-control" placeholder="Complete home address" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="section-shell" id="section-step-2" data-section-step="2">
                                                    <div class="form-section-title">
                                                        <span class="section-index">2</span>
                                                        <span>Baptism Details</span>
                                                    </div>
                                                    <p class="form-section-subtitle mb-4">Encode the important dates and the presider who officiated the sacrament.</p>

                                                    <div class="row g-3">
                                                        <div class="col-12 col-lg-6">
                                                            <label class="form-label fw-semibold">Date of Birth</label>
                                                            <input type="date" name="in" class="form-control" required>
                                                        </div>
                                                        <div class="col-12 col-lg-6">
                                                            <label class="form-label fw-semibold">Date of Baptism</label>
                                                            <input type="date" name="on" class="form-control" required>
                                                        </div>
                                                        <div class="col-12 col-lg-6">
                                                            <label class="form-label fw-semibold">Presider</label>
                                                            <input type="text" name="bishop" class="form-control" placeholder="Name of the presider" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="section-shell" id="section-step-3" data-section-step="3">
                                                    <div class="form-section-title">
                                                        <span class="section-index">3</span>
                                                        <span>Sponsors</span>
                                                    </div>
                                                    <p class="form-section-subtitle mb-4">List the godparents in the order they appear on official records.</p>

                                                    <div id="sponsorContainer">
                                                        <div class="sponsor-row d-flex align-items-center gap-2 flex-wrap">
                                                            <input type="text" class="form-control flex-grow-1" name="sponsors[]" placeholder="Sponsor name">
                                                            <button type="button" class="btn btn-outline-danger btn-sm removeSponsor">Remove</button>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-end mt-3">
                                                        <button type="button" id="addSponsor" class="btn btn-outline-primary btn-add-sponsor">
                                                            + Add another sponsor
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="section-shell" id="section-step-4" data-section-step="4">
                                                    <div class="form-section-title">
                                                        <span class="section-index">4</span>
                                                        <span>Date of Release</span>
                                                    </div>
                                                    <p class="form-section-subtitle mb-4">The day field automatically shows the correct ordinal suffix.</p>

                                                    <div class="row g-3">
                                                        <div class="col-12 col-lg-4">
                                                            <label class="form-label fw-semibold">Day</label>
                                                            <div class="input-group">
                                                                <input type="number" name="day" class="form-control" required inputmode="numeric" autocomplete="off" min="1" max="31" data-suffix-target="daySuffix">
                                                                <span class="input-group-text" id="daySuffix">th</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-lg-4">
                                                            <label class="form-label fw-semibold">Month</label>
                                                            <select name="month" class="form-select" required>
                                                                <option value="" disabled>Select month</option>
                                                                <?php foreach ($monthOptions as $monthName): ?>
                                                                    <option value="<?php echo $monthName; ?>" <?php echo $monthName === $currentMonthName ? 'selected' : ''; ?>><?php echo $monthName; ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-12 col-lg-4">
                                                            <label class="form-label fw-semibold">Year</label>
                                                            <input type="text" name="year" class="form-control" placeholder="e.g. 2024" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="section-shell mb-0" id="section-step-5" data-section-step="5">
                                                    <div class="form-section-title">
                                                        <span class="section-index">5</span>
                                                        <span>Registry References</span>
                                                    </div>
                                                    <p class="form-section-subtitle mb-4">Double-check these before submitting to avoid record discrepancies.</p>

                                                    <div class="row g-3">
                                                        <div class="col-12 col-md-4">
                                                            <label class="form-label fw-semibold">Book No</label>
                                                            <input type="number" name="book_no" class="form-control" min="0" step="1" placeholder="Numeric value" required>
                                                        </div>
                                                        <div class="col-12 col-md-4">
                                                            <label class="form-label fw-semibold">Page No</label>
                                                            <input type="number" name="page_no" class="form-control" min="0" step="1" placeholder="Numeric value" required>
                                                        </div>
                                                        <div class="col-12 col-md-4">
                                                            <label class="form-label fw-semibold">Entry No</label>
                                                            <input type="number" name="entry_no" class="form-control" min="0" step="1" placeholder="Numeric value" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="release_claimant" id="release_claimant">
                                                <input type="hidden" name="release_relationship" id="release_relationship">
                                                <input type="hidden" name="release_time" id="release_time">
                                            </div>
                                        </div>
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
                                <label class="form-label fw-semibold">Date of Baptism</label>
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
            const yearInput = document.querySelector('input[name="year"]');
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

            function updateSuffix(rawValue) {
                if (!daySuffix) {
                    return;
                }

                if (typeof rawValue === "number" && !Number.isNaN(rawValue) && rawValue > 0) {
                    daySuffix.textContent = getOrdinalSuffix(rawValue);
                    return;
                }

                const numericDay = parseInt(rawValue, 10);
                if (!Number.isNaN(numericDay) && numericDay > 0) {
                    daySuffix.textContent = getOrdinalSuffix(numericDay);
                } else {
                    daySuffix.textContent = "th";
                }
            }

            if (dayInput) {
                dayInput.addEventListener("input", function() {
                    const digitsOnly = this.value.replace(/[^0-9]/g, "");
                    this.value = digitsOnly;
                    if (digitsOnly === "") {
                        updateSuffix();
                        return;
                    }

                    updateSuffix(parseInt(digitsOnly, 10));
                });

                dayInput.addEventListener("blur", function() {
                    if (this.value === "") {
                        updateSuffix();
                        return;
                    }

                    const numericDay = parseInt(this.value, 10);
                    if (Number.isNaN(numericDay) || numericDay <= 0) {
                        this.value = "";
                        updateSuffix();
                        return;
                    }

                    this.value = Math.min(Math.max(numericDay, 1), 31);
                    updateSuffix(parseInt(this.value, 10));
                });
            }

            const steps = document.querySelectorAll('.stepper .step');
            const sections = document.querySelectorAll('[data-section-step]');
            let initialStep = sections.length ? sections[0].dataset.sectionStep : '1';

            function setActiveStep(stepNumber) {
                steps.forEach((step) => {
                    const isActive = step.dataset.step === stepNumber;
                    step.classList.toggle('active', isActive);
                    step.setAttribute('aria-current', isActive ? 'step' : 'false');
                });

                sections.forEach((section) => {
                    section.classList.toggle('is-active', section.dataset.sectionStep === stepNumber);
                });
            }

            const observer = new IntersectionObserver((entries) => {
                const visible = entries
                    .filter((entry) => entry.isIntersecting)
                    .sort((a, b) => b.intersectionRatio - a.intersectionRatio);

                if (visible.length > 0) {
                    const stepNumber = visible[0].target.dataset.sectionStep;
                    setActiveStep(stepNumber);
                }
            }, {
                threshold: 0.4
            });

            sections.forEach((section) => observer.observe(section));

            steps.forEach((step) => {
                step.addEventListener('click', () => {
                    const targetSelector = step.dataset.stepTarget;
                    const target = targetSelector ? document.querySelector(targetSelector) : null;
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    setActiveStep(step.dataset.step);
                });
            });

            sections.forEach((section) => {
                const inputs = section.querySelectorAll('input, select, textarea');
                inputs.forEach((field) => {
                    field.addEventListener('focus', () => setActiveStep(section.dataset.sectionStep));
                });
            });

            setActiveStep(initialStep);

            addButton.addEventListener("click", function(event) {
                event.preventDefault();

                const newDiv = document.createElement("div");
                newDiv.classList.add("sponsor-row", "d-flex", "align-items-center", "gap-2", "flex-wrap");
                newDiv.innerHTML = `
                    <input type="text" class="form-control flex-grow-1" name="sponsors[]" placeholder="Sponsor name">
                    <button type="button" class="btn btn-outline-danger btn-sm removeSponsor">Remove</button>
                `;

                sponsorContainer.appendChild(newDiv);
            });

            sponsorContainer.addEventListener("click", function(event) {
                if (event.target.classList.contains("removeSponsor")) {
                    event.preventDefault();
                    const rows = sponsorContainer.querySelectorAll('.sponsor-row');
                    if (rows.length === 1) {
                        const input = rows[0].querySelector('input[name="sponsors[]"]');
                        if (input) {
                            input.value = "";
                        }
                        return;
                    }
                    event.target.closest('.sponsor-row').remove();
                }
            });

            if (releaseModalEl) {
                releaseModal = new bootstrap.Modal(releaseModalEl);
            }

            function buildReleaseDateDisplay() {
                const baptismDateField = document.querySelector('input[name="on"]');
                if (!baptismDateField || !modalReleaseDate) {
                    return;
                }

                const rawValue = (baptismDateField.value || '').trim();
                if (!rawValue) {
                    modalReleaseDate.value = '';
                    return;
                }

                const dateObj = new Date(`${rawValue}T00:00:00`);
                if (Number.isNaN(dateObj.getTime())) {
                    modalReleaseDate.value = rawValue;
                    return;
                }

                modalReleaseDate.value = dateObj.toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                });
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