<?php
$title = "Event List";
require_once "../security_helper.php";
requireAuth();
include "../partials/html.head.php";

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $deleteSql = "DELETE FROM events WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    if ($deleteStmt->execute([$id])) {
        echo "<script>alert('Event deleted successfully!'); window.location.href='index.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error deleting event.');</script>";
    }
}

?>

<body class="sb-nav-fixed gradient-page">
    <?php include_once("../partials/navbar.php"); ?>
    <style>
        .baptismal-bg {
            min-height: 100vh;
            background: linear-gradient(135deg, #61d2ff 0%, #7cecc2 50%, #ffe36e 100%);
            position: relative;
            overflow: visible;
        }

        .baptismal-bg::before,
        .baptismal-bg::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            opacity: 0.3;
            background: rgba(255, 255, 255, 0.85);
            filter: blur(60px);
        }

        .baptismal-bg::before {
            width: 460px;
            height: 460px;
            top: -160px;
            right: -120px;
        }

        .baptismal-bg::after {
            width: 320px;
            height: 320px;
            bottom: -100px;
            left: -80px;
        }

        .baptismal-panel {
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(25, 52, 94, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            font-weight: 600;
            letter-spacing: 0.2px;
            padding: 0.9rem 1.25rem;
        }

        .baptismal-panel .card-body {
            padding: 0;
        }

        .baptismal-panel table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .baptismal-panel table thead th {
            background: #f8fafc;
            border-top: none;
            border-bottom: 1px solid #e1e6ef;
            color: #1f2d3d;
            font-weight: 600;
            padding: 0.75rem 1rem;
        }

        .baptismal-panel table tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }

        .baptismal-panel table tbody tr {
            background: #ffffff;
            border-bottom: 1px solid #eef1f6;
        }

        .baptismal-panel table tbody tr:nth-child(even) {
            background: #f7f9fc;
        }

        .baptismal-panel table tbody tr:last-child {
            border-bottom-left-radius: 1rem;
            border-bottom-right-radius: 1rem;
        }

        .baptismal-panel table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 1rem;
        }

        .baptismal-panel table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 1rem;
        }

        .baptismal-panel .pagination {
            margin: 0;
            padding: 0.75rem 1rem 1rem;
            justify-content: flex-end;
            gap: 0.35rem;
        }
        
        /* Custom Button Styles - Baptismal Style */
        .edit-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #28a745;
            color: white;
            border: none;
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
        
        .edit-btn .edit-icon {
            position: relative;
            width: 1.1rem;
            height: 1.1rem;
            display: inline-block;
            transform: rotate(-45deg);
            pointer-events: none;
        }
        
        .edit-btn .edit-icon .edit-pencil-body,
        .edit-btn .edit-icon .edit-pencil-ferrule,
        .edit-btn .edit-icon .edit-pencil-tip {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .edit-btn .edit-icon .edit-pencil-body {
            left: 0.12rem;
            width: 0.62rem;
            height: 0.26rem;
            border-radius: 0.15rem 0 0 0.15rem;
            background: currentColor;
        }
        
        .edit-btn .edit-icon .edit-pencil-ferrule {
            left: 0.72rem;
            width: 0.16rem;
            height: 0.26rem;
            border-radius: 0.08rem;
            background: rgba(255, 255, 255, 0.82);
        }
        
        .edit-btn .edit-icon .edit-pencil-tip {
            left: 0.88rem;
            width: 0;
            height: 0;
            border-left: 0.22rem solid currentColor;
            border-top: 0.13rem solid transparent;
            border-bottom: 0.13rem solid transparent;
        }
        
        .edit-btn .edit-icon::after {
            content: "";
            position: absolute;
            top: 64%;
            left: 0.28rem;
            width: 0;
            height: 0.12rem;
            background: currentColor;
            border-radius: 0.12rem;
            opacity: 0.75;
        }
        
        .edit-btn .edit-icon::before {
            content: "";
            position: absolute;
            width: 0.18rem;
            height: 0.18rem;
            border-radius: 50%;
            background: currentColor;
            top: 15%;
            right: 5%;
            opacity: 0;
            transform: scale(0.5);
        }
        
        .edit-btn .edit-label {
            line-height: 1;
            pointer-events: none;
        }
        
        .edit-btn:hover,
        .edit-btn:focus-visible {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
            background: #218838;
        }
        
        .edit-btn:active {
            transform: translateY(1px);
        }
        
        @keyframes pencilWrite {
            0% {
                transform: rotate(-45deg) translate(0, 0) scale(1);
            }
            25% {
                transform: rotate(-30deg) translate(0.12rem, -0.08rem) scale(1.02);
            }
            50% {
                transform: rotate(-20deg) translate(0.18rem, -0.12rem) scale(1.04);
            }
            75% {
                transform: rotate(-35deg) translate(0.08rem, -0.06rem) scale(1.01);
            }
            100% {
                transform: rotate(-45deg) translate(0, 0) scale(1);
            }
        }
        
        @keyframes pencilStroke {
            0% {
                width: 0;
                opacity: 0;
            }
            50% {
                width: 0.12rem;
                opacity: 0.75;
            }
            100% {
                width: 0;
                opacity: 0;
            }
        }
        
        @keyframes pencilSpark {
            0% {
                opacity: 0;
                transform: scale(0.5);
            }
            50% {
                opacity: 1;
                transform: scale(1.2);
            }
            100% {
                opacity: 0;
                transform: scale(0.4);
            }
        }
        
        .edit-btn.animate-once .edit-icon {
            animation: pencilWrite 0.75s ease-in-out;
        }
        
        .edit-btn.animate-once .edit-icon::after {
            animation: pencilStroke 0.75s ease-in-out;
        }
        
        .edit-btn.animate-once .edit-icon::before {
            animation: pencilSpark 0.75s ease-in-out;
        }
        
        .trash-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
        
        .trash-btn .trash-icon {
            position: relative;
            width: 1.2rem;
            height: 1.2rem;
            pointer-events: none;
            display: inline-block;
        }
        
        .trash-btn .trash-lid,
        .trash-btn .trash-body,
        .trash-btn .trash-handle {
            position: absolute;
            background: currentColor;
            left: 0;
            pointer-events: none;
        }
        
        .trash-btn .trash-lid {
            top: 0.08rem;
            width: 100%;
            height: 0.2rem;
            border-radius: 0.25rem;
            transform: translateY(0);
            transform-origin: center;
            transition: transform 0.35s ease;
        }
        
        .trash-btn .trash-handle {
            top: -0.32rem;
            left: 50%;
            width: 0.55rem;
            height: 0.22rem;
            border: 2px solid currentColor;
            border-bottom: none;
            border-radius: 0.35rem 0.35rem 0 0;
            background: transparent;
            transform: translateX(-50%);
        }
        
        .trash-btn .trash-body {
            bottom: 0;
            left: 12%;
            width: 76%;
            height: 0.95rem;
            border: 2px solid currentColor;
            border-top: none;
            border-radius: 0 0 0.35rem 0.35rem;
            background: transparent;
            transition: transform 0.2s ease;
        }
        
        .trash-btn .trash-body::before,
        .trash-btn .trash-body::after {
            content: "";
            position: absolute;
            top: 20%;
            bottom: 18%;
            width: 2px;
            background: currentColor;
        }
        
        .trash-btn .trash-body::before {
            left: 34%;
        }
        
        .trash-btn .trash-body::after {
            right: 34%;
        }
        
        .trash-btn .trash-label {
            line-height: 1;
            pointer-events: none;
        }
        
        .trash-btn:hover,
        .trash-btn:focus-visible {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
            background: #c82333;
        }
        
        .trash-btn:active {
            transform: translateY(1px);
        }
        
        .trash-btn:hover .trash-lid,
        .trash-btn:focus-visible .trash-lid {
            transform: translateY(-0.3rem);
        }
        
        .trash-btn:hover .trash-body,
        .trash-btn:focus-visible .trash-body {
            transform: translateY(-1px);
        }
        
        @keyframes trashLidWave {
            0% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-0.32rem);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        @keyframes pulseTrash {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .trash-btn.animate-once {
            animation: pulseTrash 0.85s ease-out;
        }
        
        .trash-btn.animate-once .trash-lid {
            animation: trashLidWave 0.85s ease-out;
        }
        
        /* Smooth transitions for table rows */
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-toolbar .form-label {
            letter-spacing: 0.08em;
        }

        .filter-toolbar .form-control {
            border-radius: 0.65rem;
            box-shadow: none;
            border: 1px solid rgba(31, 45, 61, 0.15);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .filter-toolbar .form-control:focus {
            border-color: rgba(17, 122, 101, 0.6);
            box-shadow: 0 0 0 0.2rem rgba(17, 122, 101, 0.1);
        }

        .filter-toolbar .btn {
            border-radius: 0.65rem;
            font-weight: 600;
        }
    </style>
    <div id="layoutSidenav">
        <?php include_once("../partials/sidebar.php"); ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 pt-3 baptismal-bg">
                    <div class="row g-3 align-items-end mb-3 filter-toolbar">
                        <div class="col-12 col-md-4 col-lg-3">
                            <label for="search-input" class="form-label fw-semibold text-muted small text-uppercase">Search</label>
                            <input class="form-control" type="search" placeholder="Search" id="search-input">
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label for="filter-start-date" class="form-label fw-semibold text-muted small text-uppercase">From date</label>
                            <input class="form-control" type="date" id="filter-start-date">
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label for="filter-end-date" class="form-label fw-semibold text-muted small text-uppercase">To date</label>
                            <input class="form-control" type="date" id="filter-end-date">
                        </div>
                        <div class="col-12 col-lg d-flex flex-wrap gap-2 justify-content-lg-end">
                            <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/events/insert.php">New</a>
                            <button class="btn btn-outline-secondary" id="clear-date-filters" type="button">Clear filters</button>
                        </div>
                    </div>

                    <div class="row gap-3">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm baptismal-panel">
                                <h5 class="card-header bg-primary text-white">Event List</h5>
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-12">
                                            <table class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center">#</th>
                                                        <th>Type</th>
                                                        <th>Date</th>
                                                        <th>Time</th>
                                                        <th>Description</th>
                                                        <th class="text-center" style="width: 150px;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="table-event"></tbody>
                                            </table>
                                        </div>
                                        <div class="col-auto ms-auto">
                                            <ul class="pagination pagination-sm" id="pagination"></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 shadow-sm baptismal-panel">
                                <h5 class="card-header bg-secondary text-white">Calendar</h5>
                                <div class="card-body">
                                    <div id="calendar"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <?php include "update.php"; ?>

            <!-- Date Events Modal -->
            <div class="modal fade" id="dateEventsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="dateEventsModalLabel">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="dateEventsModalLabel">Events for <span id="selectedDate"></span></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Time</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dateEventsTable"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Month Events Modal -->
            <div class="modal fade" id="monthEventsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="monthEventsModalLabel">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="monthEventsModalLabel">Events for <span id="selectedMonth"></span></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Time</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody id="monthEventsTable"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php include "../partials/footer.php"; ?>
        </div>
    </div>

    <?php include "../partials/html.footer.php"; ?>
    <!-- <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script> -->
    <?php include "script.php"; ?>
</body>

</html>