<?php
$title = "Confirmation";
require_once "../security_helper.php";
requireAuth();
include "../partials/html.head.php";

if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);

    if ($_GET['action'] === 'delete') {
        $archiveSql = "UPDATE confirmation_records SET is_archived = 1, archived_at = NOW() WHERE id = ?";
        $archiveStmt = $conn->prepare($archiveSql);
        if ($archiveStmt->execute([$id])) {
            echo "<script>alert('Confirmation record archived successfully!'); window.location.href='index.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error archiving record.');</script>";
        }
    }

    if ($_GET['action'] === 'restore') {
        $restoreSql = "UPDATE confirmation_records SET is_archived = 0, archived_at = NULL WHERE id = ?";
        $restoreStmt = $conn->prepare($restoreSql);
        if ($restoreStmt->execute([$id])) {
            echo "<script>alert('Confirmation record restored successfully!'); window.location.href='index.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error restoring record.');</script>";
        }
    }
    
    if ($_GET['action'] === 'permanent_delete') {
        // First delete related sponsors if any
        $deleteSponsorsSql = "DELETE FROM confirmation_sponsors WHERE confirmation_id = ?";
        $deleteSponsorsStmt = $conn->prepare($deleteSponsorsSql);
        $deleteSponsorsStmt->execute([$id]);
        
        // Then delete the main record
        $deleteSql = "DELETE FROM confirmation_records WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        if ($deleteStmt->execute([$id])) {
            echo "<script>alert('Confirmation record permanently deleted!'); window.location.href='../archive/index.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error deleting record.');</script>";
        }
    }
}
?>

<style>
    .gradient-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #61d2ff 0%, #7cecc2 50%, #ffe36e 100%);
        position: relative;
        overflow: visible;
    }

    .gradient-page::before,
    .gradient-page::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
        opacity: 0.3;
        background: rgba(255, 255, 255, 0.85);
        filter: blur(60px);
    }

    .gradient-page::before {
        width: 460px;
        height: 460px;
        top: -160px;
        right: -120px;
    }

    .gradient-page::after {
        width: 380px;
        height: 380px;
        bottom: -140px;
        left: -110px;
    }

    .gradient-page>* {
        position: relative;
        z-index: 1;
    }

    .confirmation-controls {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        margin-bottom: 1.1rem;
    }

    .confirmation-controls .controls-left {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .confirmation-controls .controls-right {
        margin-left: auto;
        display: flex;
        align-items: center;
    }

    .confirmation-controls .form-control,
    .confirmation-controls .form-select {
        border-radius: 6px;
        padding: 0.5rem 0.9rem;
        border: 1px solid #d6d9e0;
        background-color: #ffffff;
        box-shadow: 0 6px 14px rgba(15, 96, 180, 0.08);
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
        max-width: 300px;
    }

    .confirmation-controls .form-control:focus,
    .confirmation-controls .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.18);
    }

    .confirmation-year-dropdown {
        position: absolute;
        display: none;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        min-width: 220px;
        border: 1px solid rgba(17, 64, 105, 0.12);
        box-shadow: 0 18px 32px rgba(17, 59, 106, 0.22);
        background: #ffffff;
        z-index: 2050;
    }

    .confirmation-year-dropdown .year-dropdown-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.6rem;
    }

    .confirmation-year-dropdown .year-range {
        flex: 1;
        text-align: center;
        font-weight: 600;
        font-size: 0.95rem;
        color: #114069;
    }

    .confirmation-year-dropdown .year-nav {
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(13, 110, 253, 0.12);
        color: #0d6efd;
        font-weight: 600;
        transition: background 0.2s ease, color 0.2s ease;
        cursor: pointer;
    }

    .confirmation-year-dropdown .year-nav:hover,
    .confirmation-year-dropdown .year-nav:focus-visible {
        background: rgba(13, 110, 253, 0.2);
        color: #0a58ca;
        outline: none;
    }

    .confirmation-year-dropdown .year-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.45rem;
    }

    .confirmation-year-dropdown .year-cell {
        border: none;
        border-radius: 10px;
        padding: 0.55rem 0;
        background: #f4f7fb;
        color: #0c3d5d;
        font-weight: 600;
        transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }

    .confirmation-year-dropdown .year-cell:hover,
    .confirmation-year-dropdown .year-cell:focus-visible {
        background: rgba(13, 110, 253, 0.14);
        color: #0d6efd;
        outline: none;
    }

    .confirmation-year-dropdown .year-cell.active {
        background: #0d6efd;
        color: #ffffff;
        box-shadow: 0 6px 16px rgba(13, 110, 253, 0.35);
    }

    .trash-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .trash-btn .trash-icon {
        position: relative;
        width: 1.2rem;
        height: 1.2rem;
        pointer-events: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .trash-btn .trash-icon i {
        font-size: 0.95rem;
        transition: transform 0.32s cubic-bezier(.2,.7,.2,1), filter 0.32s cubic-bezier(.2,.7,.2,1);
        will-change: transform, filter;
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
        transform-origin: center;
        transform: translateY(0);
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

    .trash-btn:hover .trash-icon i,
    .trash-btn:focus-visible .trash-icon i {
        transform: translateY(-1px) scale(1.22) rotate(-12deg);
        filter: drop-shadow(0 1px 8px rgba(255,255,255,0.75));
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

    .trash-btn:hover,
    .trash-btn:focus-visible {
        transform: translateY(-1px);
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

    .edit-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 600;
        transition: transform 0.2s ease;
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
        55% {
            transform: rotate(-58deg) translate(-0.09rem, 0.08rem) scale(0.98);
        }
        80% {
            transform: rotate(-38deg) translate(0.06rem, -0.04rem) scale(1.01);
        }
        100% {
            transform: rotate(-45deg) translate(0, 0) scale(1);
        }
    }

    @keyframes pencilStroke {
        0% {
            width: 0;
            opacity: 0;
            left: 0.28rem;
        }
        28% {
            width: 0;
            opacity: 0;
            left: 0.28rem;
        }
        52% {
            width: 0.62rem;
            opacity: 0.9;
            left: 0.28rem;
        }
        75% {
            width: 0.62rem;
            opacity: 0.9;
            left: 0.48rem;
        }
        100% {
            width: 0;
            opacity: 0;
            left: 0.64rem;
        }
    }

    @keyframes pencilSpark {
        0%,
        40% {
            opacity: 0;
            transform: scale(0.4);
        }
        60% {
            opacity: 0.8;
            transform: scale(1);
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

    .print-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 600;
        transition: transform 0.2s ease;
    }

    .print-btn .print-icon {
        position: relative;
        width: 1.15rem;
        height: 1.1rem;
        display: inline-block;
        overflow: hidden;
        pointer-events: none;
    }

    .print-btn .print-top,
    .print-btn .print-body,
    .print-btn .print-paper,
    .print-btn .print-light {
        position: absolute;
        pointer-events: none;
    }

    .print-btn .print-top {
        top: 0;
        left: 0;
        width: 100%;
        height: 0.42rem;
        border: 2px solid currentColor;
        border-bottom: none;
        border-radius: 0.2rem 0.2rem 0 0;
        background: transparent;
    }

    .print-btn .print-body {
        bottom: 0;
        left: 0;
        width: 100%;
        height: 0.58rem;
        border: 2px solid currentColor;
        border-radius: 0.18rem;
        background: transparent;
    }

    .print-btn .print-paper {
        bottom: 0.14rem;
        left: 0.16rem;
        width: 0.84rem;
        height: 0.54rem;
        background: currentColor;
        opacity: 0.15;
        border-radius: 0.08rem;
    }

    .print-btn .print-paper::before,
    .print-btn .print-paper::after {
        content: "";
        position: absolute;
        left: 0.12rem;
        right: 0.12rem;
        height: 2px;
        background: currentColor;
        opacity: 0.45;
        border-radius: 1px;
    }

    .print-btn .print-paper::before {
        top: 0.16rem;
    }

    .print-btn .print-paper::after {
        bottom: 0.16rem;
    }

    .print-btn .print-light {
        top: 0.18rem;
        right: 0.22rem;
        width: 0.16rem;
        height: 0.16rem;
        border-radius: 50%;
        background: currentColor;
        opacity: 0.25;
    }

    .print-btn .print-label {
        line-height: 1;
        pointer-events: none;
    }

    .print-btn:hover,
    .print-btn:focus-visible {
        transform: translateY(-1px);
    }

    .print-btn:active {
        transform: translateY(1px);
    }

    @keyframes printPaperSlide {
        0% {
            transform: translateY(0);
            opacity: 0.95;
        }
        35% {
            transform: translateY(-0.26rem);
            opacity: 1;
        }
        65% {
            transform: translateY(0.16rem);
            opacity: 0.9;
        }
        100% {
            transform: translateY(0);
            opacity: 0.95;
        }
    }

    @keyframes printBodyShift {
        0% {
            transform: translateY(0);
        }
        45% {
            transform: translateY(-0.04rem);
        }
        100% {
            transform: translateY(0);
        }
    }

    @keyframes printLightPulse {
        0%,
        25%,
        100% {
            opacity: 0.25;
        }
        45% {
            opacity: 0.9;
        }
        70% {
            opacity: 0.4;
        }
    }

    .print-btn.animate-once .print-paper {
        animation: printPaperSlide 0.75s ease-in-out;
    }

    .print-btn.animate-once .print-body {
        animation: printBodyShift 0.75s ease-in-out;
    }

    .print-btn.animate-once .print-light {
        animation: printLightPulse 0.75s ease-in-out;
    }

    @keyframes trashLidWave {
        0% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-0.32rem);
        }
        65% {
            transform: translateY(-0.18rem);
        }
        100% {
            transform: translateY(0);
        }
    }

    @keyframes pulseTrash {
        0% {
            transform: translateY(0);
        }
        45% {
            transform: translateY(-1px);
        }
        100% {
            transform: translateY(0);
        }
    }

    .trash-btn.animate-once {
        animation: pulseTrash 0.85s ease-out;
    }

    .trash-btn.animate-once .trash-lid {
        animation: trashLidWave 0.85s ease-out;
    }
    .confirmation-summary {display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-bottom:1.5rem}
    .summary-card{display:flex;align-items:center;gap:1rem;padding:1rem 1.15rem;border-radius:1rem;background:rgba(255,255,255,.9);border:1px solid rgba(15,40,77,.08);box-shadow:0 18px 36px rgba(17,59,106,.12)}
    .summary-card.primary{background:linear-gradient(130deg,rgba(13,110,253,.18),rgba(102,16,242,.18));border:1px solid rgba(13,110,253,.22)}
    .summary-icon{width:2.6rem;height:2.6rem;border-radius:1rem;background:rgba(13,110,253,.16);color:#0d6efd;display:inline-flex;align-items:center;justify-content:center;font-size:1.3rem}
    .summary-label{font-size:.85rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:rgba(17,43,86,.65)}
    .summary-value{font-size:1.35rem;font-weight:700;color:#10396e;margin:0}
    .summary-meta{font-size:.82rem;color:rgba(17,43,86,.55);margin:0}
    .confirmation-panel{border-radius:1.15rem;box-shadow:0 22px 48px rgba(15,40,77,.18);border:1px solid rgba(15,40,77,.12)}
    .confirmation-panel .card-header{border-radius:1.15rem 1.15rem 0 0;padding:1rem 1.35rem;background:linear-gradient(135deg,#0d6efd,#6610f2);border-bottom:none;display:flex;align-items:center;justify-content:space-between}
    .header-icon{width:2.6rem;height:2.6rem;border-radius:.95rem;background:rgba(255,255,255,.22);display:inline-flex;align-items:center;justify-content:center;font-size:1.4rem}
    .card-title{font-size:1.25rem;font-weight:700;margin-bottom:.1rem}
    .card-subtitle{font-size:.85rem;color:rgba(255,255,255,.8)}
    .header-badge{background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);border-radius:999px;padding:.35rem .9rem;font-size:.75rem;font-weight:600;text-transform:uppercase}
    .table-modern-wrapper{position:relative;overflow:hidden;border-radius:0 0 1.15rem 1.15rem}
    .table-modern{margin-bottom:0;border-collapse:separate;border-spacing:0;color:#102a43}
    .table-modern thead th{background:#f3f6fb;border-top:none;border-bottom:1px solid rgba(16,42,67,.08);font-weight:700;font-size:.85rem;text-transform:uppercase;letter-spacing:.06em;padding:.85rem 1.15rem;color:#1d2c4d}
    .table-modern tbody td{padding:.85rem 1.15rem;vertical-align:middle;border-top:1px solid rgba(16,42,67,.05)}
    .table-modern tbody tr:nth-child(even){background:rgba(239,246,255,.65)}
    .action-group{display:inline-flex;align-items:center;justify-content:center;gap:.45rem}
    .btn-icon{border-radius:.85rem;width:2.3rem;height:2.3rem;display:inline-flex;align-items:center;justify-content:center;padding:0;position:relative;box-shadow:0 12px 18px rgba(15,40,77,.18);transition:transform .18s ease, box-shadow .18s ease}
    .btn-icon:hover,.btn-icon:focus-visible{transform:translateY(-2px);box-shadow:0 16px 24px rgba(15,40,77,.22)}
    .btn-icon:active{transform:translateY(1px)}
    .btn-icon .tooltip-label{position:absolute;bottom:calc(100% + .35rem);background:rgba(16,42,67,.9);color:#fff;border-radius:.45rem;padding:.25rem .55rem;font-size:.68rem;font-weight:600;opacity:0;transform:translateY(4px);pointer-events:none;transition:opacity .18s ease, transform .18s ease;white-space:nowrap}
    .btn-icon:hover .tooltip-label,.btn-icon:focus-visible .tooltip-label{opacity:1;transform:translateY(0)}
    .pagination{margin:0;padding:1rem 1.35rem 1.35rem;justify-content:flex-end}
    .confirmation-panel .page-item .page-link{border:none;border-radius:.75rem;padding:.45rem .85rem;font-weight:600;color:#0d366b;background:rgba(255,255,255,.9);box-shadow:0 10px 18px rgba(15,40,77,.12);transition:transform .18s ease, box-shadow .18s ease, background .18s ease}
    .confirmation-panel .page-item .page-link:hover,
    .confirmation-panel .page-item .page-link:focus-visible{transform:translateY(-2px);background:rgba(13,110,253,.18);color:#0b2c61}
    .confirmation-panel .page-item.active .page-link{background:linear-gradient(135deg,#0d6efd,#6610f2);color:#fff;box-shadow:0 14px 24px rgba(13,110,253,.28)}
    .confirmation-panel .page-item.disabled .page-link{opacity:.45;transform:none;box-shadow:none}
</style>

<body class="sb-nav-fixed gradient-page">
    <?php include_once("../partials/navbar.php"); ?>
    <div id="layoutSidenav">
        <?php include_once("../partials/sidebar.php"); ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 pt-3">
                    <?php if (isset($_GET['restore_success']) && $_GET['restore_success'] == 1): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <strong>Restore Completed Successfully!</strong>
                            <?php 
                            $tables = $_GET['tables'] ?? '';
                            $count = $_GET['count'] ?? 0;
                            echo "Restored $count records to Confirmation Records!";
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="confirmation-controls">
                        <div class="controls-left">
                            <input id="search-input" class="form-control" style="width: 240px;" type="search" placeholder="Search name or presider">
                            <input type="text" id="yearpicker" class="form-control" style="width: 240px;" placeholder="Search year" autocomplete="off">
                        </div>
                        <div class="controls-right">
                            <a class="btn btn-primary rounded-pill px-4" href="<?= BASE_URL ?>/confirmation/insert.php">New</a>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card border-0 confirmation-panel">
                                <div class="card-header text-white">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="header-icon" aria-hidden="true">✅</span>
                                        <div>
                                            <h5 class="card-title mb-0">Confirmation List</h5>
                                            <span class="card-subtitle">Central overview of confirmation records</span>
                                        </div>
                                    </div>
                                   
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-modern-wrapper">
                                        <table class="table table-modern align-middle">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">#</th>
                                                    <th>Child Name</th>
                                                    <th>Confirmation Date</th>
                                                    <th>Presider</th>
                                                    <th class="text-center" style="width: 200px;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="table-confirmation"></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <ul class="pagination pagination-sm" id="pagination"></ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php include "update.php"; ?>
                    <?php include "../partials/footer.php"; ?>
                </div>
            </main>
        </div>
    </div>

    <?php include "../partials/html.footer.php"; ?>
    <?php include "script.php"; ?>
</body>

</html>