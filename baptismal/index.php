<?php
$title = "Baptismal";
require_once "../security_helper.php";
include "../partials/html.head.php";

requireAuth();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    if ($_GET['action'] === 'delete') {
        $archiveSql = "UPDATE baptismal_records SET is_archived = 1, archived_at = NOW() WHERE id = ?";
        $archiveStmt = $conn->prepare($archiveSql);
        if ($archiveStmt->execute([$id])) {
            echo "<script>alert('Baptismal record archived successfully!'); window.location.href='index.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error archiving record.');</script>";
        }
    }

    if ($_GET['action'] === 'restore') {
        $restoreSql = "UPDATE baptismal_records SET is_archived = 0, archived_at = NULL WHERE id = ?";
        $restoreStmt = $conn->prepare($restoreSql);
        if ($restoreStmt->execute([$id])) {
            echo "<script>alert('Baptismal record restored successfully!'); window.location.href='index.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error restoring record.');</script>";
        }
    }
    
    if ($_GET['action'] === 'permanent_delete') {
        // Delete the main record
        $deleteSql = "DELETE FROM baptismal_records WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        if ($deleteStmt->execute([$id])) {
            echo "<script>alert('Baptismal record permanently deleted!'); window.location.href='../archive/index.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error deleting record.');</script>";
        }
    }
}
?>

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
        width: 380px;
        height: 380px;
        bottom: -140px;
        left: -110px;
    }

    .baptismal-bg>* {
        position: relative;
        z-index: 1;
    }

    .baptismal-controls {
        display: flex;
        gap: 0.85rem;
        align-items: center;
        margin-bottom: 1.35rem;
        flex-wrap: wrap;
    }

    .baptismal-controls .controls-left {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .baptismal-controls .controls-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .baptismal-controls .form-control,
    .baptismal-controls .form-select {
        border-radius: 14px;
        padding: 0.65rem 1.05rem;
        border: 1px solid rgba(15, 40, 77, 0.12);
        background-color: rgba(255, 255, 255, 0.96);
        box-shadow: 0 10px 24px rgba(11, 72, 140, 0.12);
        transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        max-width: 320px;
        font-weight: 500;
        color: #1f2d3d;
    }

    .baptismal-controls .form-control:focus,
    .baptismal-controls .form-select:focus {
        border-color: rgba(13, 110, 253, 0.6);
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.18);
        transform: translateY(-1px);
    }

    .baptismal-controls .controls-right .btn {
        box-shadow: 0 12px 24px rgba(13, 110, 253, 0.25);
        font-weight: 700;
        letter-spacing: 0.02em;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .baptismal-controls .controls-right .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 32px rgba(13, 110, 253, 0.32);
    }

    .baptismal-controls .controls-right .btn:active {
        transform: translateY(1px);
    }

    .baptismal-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .summary-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.15rem;
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(15, 40, 77, 0.08);
        backdrop-filter: blur(4px);
        box-shadow: 0 18px 36px rgba(17, 59, 106, 0.12);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .summary-card:hover,
    .summary-card:focus-within {
        transform: translateY(-3px);
        box-shadow: 0 22px 42px rgba(17, 59, 106, 0.18);
    }

    .summary-card.primary {
        background: linear-gradient(130deg, rgba(13, 110, 253, 0.18), rgba(102, 16, 242, 0.18));
        border: 1px solid rgba(13, 110, 253, 0.22);
        color: #0b2c61;
    }

    .summary-icon {
        width: 2.8rem;
        height: 2.8rem;
        border-radius: 1rem;
        background: rgba(13, 110, 253, 0.16);
        color: #0d6efd;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.22);
    }

    .summary-card.primary .summary-icon {
        background: rgba(255, 255, 255, 0.9);
        color: #0d6efd;
        box-shadow: none;
    }

    .summary-label {
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: rgba(17, 43, 86, 0.65);
        margin-bottom: 0.2rem;
    }

    .summary-value {
        font-size: 1.4rem;
        font-weight: 700;
        color: #10396e;
        margin: 0;
    }

    .summary-meta {
        font-size: 0.82rem;
        color: rgba(17, 43, 86, 0.55);
        margin: 0;
    }

    .baptismal-panel {
        border-radius: 1.15rem;
        box-shadow: 0 22px 48px rgba(15, 40, 77, 0.18);
        border: 1px solid rgba(15, 40, 77, 0.12);
        overflow: hidden;
    }

    .baptismal-panel .card-header {
        border-radius: 1.15rem 1.15rem 0 0;
        font-weight: 600;
        letter-spacing: 0.2px;
        padding: 1rem 1.35rem;
        background: linear-gradient(135deg, #0d6efd, #6610f2);
        border-bottom: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .baptismal-panel .header-icon {
        width: 2.6rem;
        height: 2.6rem;
        border-radius: 0.95rem;
        background: rgba(255, 255, 255, 0.22);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        box-shadow: 0 12px 26px rgba(0, 0, 0, 0.12);
    }

    .baptismal-panel .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.1rem;
    }

    .baptismal-panel .card-subtitle {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.75);
        letter-spacing: 0.04em;
    }

    .baptismal-panel .header-badge {
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 999px;
        padding: 0.35rem 0.9rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .table-modern-wrapper {
        position: relative;
        overflow: hidden;
        border-radius: 0 0 1.15rem 1.15rem;
    }

    .table-modern-wrapper::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: rgba(13, 110, 253, 0.08);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    .table-modern-wrapper:hover::before {
        opacity: 1;
    }

    .table-modern {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        color: #102a43;
    }

    .table-modern thead th {
        background: #f3f6fb;
        border-top: none;
        border-bottom: 1px solid rgba(16, 42, 67, 0.08);
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 0.85rem 1.15rem;
        color: #1d2c4d;
    }

    .table-modern tbody td {
        padding: 0.85rem 1.15rem;
        vertical-align: middle;
        border-top: 1px solid rgba(16, 42, 67, 0.05);
    }

    .table-modern tbody tr {
        background: rgba(255, 255, 255, 0.96);
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .table-modern tbody tr:nth-child(even) {
        background: rgba(239, 246, 255, 0.65);
    }

    .table-modern tbody tr:hover {
        background: rgba(13, 110, 253, 0.08);
        box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.18);
    }

    .table-modern tbody td:first-child {
        font-weight: 700;
        color: #0b2c61;
    }

    .table-modern .action-group {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
    }

    .btn-icon {
        border-radius: 0.85rem;
        width: 2.3rem;
        height: 2.3rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        position: relative;
        box-shadow: 0 12px 18px rgba(15, 40, 77, 0.18);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .btn-icon:hover,
    .btn-icon:focus-visible {
        transform: translateY(-2px);
        box-shadow: 0 16px 24px rgba(15, 40, 77, 0.22);
    }

    .btn-icon:active {
        transform: translateY(1px);
    }

    .btn-icon .tooltip-label {
        position: absolute;
        bottom: calc(100% + 0.35rem);
        background: rgba(16, 42, 67, 0.9);
        color: #ffffff;
        border-radius: 0.45rem;
        padding: 0.25rem 0.55rem;
        font-size: 0.68rem;
        font-weight: 600;
        opacity: 0;
        transform: translateY(4px);
        pointer-events: none;
        transition: opacity 0.18s ease, transform 0.18s ease;
        white-space: nowrap;
    }

    .btn-icon:hover .tooltip-label,
    .btn-icon:focus-visible .tooltip-label {
        opacity: 1;
        transform: translateY(0);
    }

    .table-modern tbody tr.empty-row td {
        padding: 2.5rem 1.5rem;
        text-align: center;
        font-weight: 600;
        letter-spacing: 0.03em;
        color: rgba(16, 42, 67, 0.55);
    }

    .baptismal-panel .pagination {
        margin: 0;
        padding: 1rem 1.35rem 1.35rem;
        justify-content: flex-end;
        gap: 0.45rem;
    }

    .baptismal-panel .page-item .page-link {
        border: none;
        border-radius: 0.75rem;
        padding: 0.45rem 0.85rem;
        font-weight: 600;
        color: #0d366b;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 10px 18px rgba(15, 40, 77, 0.12);
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .baptismal-panel .page-item .page-link:hover,
    .baptismal-panel .page-item .page-link:focus-visible {
        transform: translateY(-2px);
        background: rgba(13, 110, 253, 0.18);
        color: #0b2c61;
    }

    .baptismal-panel .page-item.active .page-link {
        background: linear-gradient(135deg, #0d6efd, #6610f2);
        color: #ffffff;
        box-shadow: 0 14px 24px rgba(13, 110, 253, 0.28);
    }

    .baptismal-panel .page-item.disabled .page-link {
        opacity: 0.45;
        transform: none;
        box-shadow: none;
    }

    .baptismal-year-dropdown {
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

    .baptismal-year-dropdown .year-dropdown-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.6rem;
    }

    .baptismal-year-dropdown .year-range {
        flex: 1;
        text-align: center;
        font-weight: 600;
        font-size: 0.95rem;
        color: #114069;
    }

    .baptismal-year-dropdown .year-nav {
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

    .baptismal-year-dropdown .year-nav:hover,
    .baptismal-year-dropdown .year-nav:focus-visible {
        background: rgba(13, 110, 253, 0.2);
        color: #0a58ca;
        outline: none;
    }

    .baptismal-year-dropdown .year-clear {
        border: none;
        background: none;
        color: #0d6efd;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: auto;
        cursor: pointer;
        padding: 0.15rem 0.35rem;
    }

    .baptismal-year-dropdown .year-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.45rem;
    }

    .baptismal-year-dropdown .year-cell {
        border: none;
        border-radius: 10px;
        padding: 0.55rem 0;
        background: #f4f7fb;
        color: #0c3d5d;
        font-weight: 600;
        transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }

    .baptismal-year-dropdown .year-cell:hover,
    .baptismal-year-dropdown .year-cell:focus-visible {
        background: rgba(13, 110, 253, 0.14);
        color: #0d6efd;
        outline: none;
    }

    .baptismal-year-dropdown .year-cell.active {
        background: #0d6efd;
        color: #ffffff;
        box-shadow: 0 6px 16px rgba(13, 110, 253, 0.35);
    }

    .trash-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 600;
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
    }

    .trash-btn:active {
        transform: translateY(1px);
    }

    .trash-btn:hover .trash-icon i,
    .trash-btn:focus-visible .trash-icon i {
        transform: translateY(-1px) scale(1.22) rotate(-12deg);
        filter: drop-shadow(0 1px 8px rgba(255,255,255,0.75));
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

    .modal-backdrop.show {
        background: transparent;
        opacity: 0;
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

    .baptismal-panel {
        border-radius: 1rem;
        box-shadow: 0 20px 40px rgba(25, 52, 94, 0.18);
        border: 1px solid rgba(15, 40, 77, 0.12);
    }

    .baptismal-panel .card-header {
        border-radius: 1rem 1rem 0 0;
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
                            echo "Restored $count records to Baptismal Records!";
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="baptismal-controls">
                        <div class="controls-left">
                            <input class="form-control" style="width: 240px;" type="search" placeholder="Search name or presider" id="search-input">
                            <input type="text" id="yearpicker" class="form-control" style="width: 240px;" placeholder="Search year" autocomplete="off">
                        </div>
                        <div class="controls-right">
                            <a class="btn btn-primary rounded-pill px-4" href="<?php echo BASE_URL; ?>/baptismal/insert.php">New</a>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card border-0 baptismal-panel">
                                <div class="card-header text-white">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="header-icon" aria-hidden="true"><i class="fa-solid fa-cross"></i></span>
                                        <div>
                                            <h5 class="card-title mb-0">Baptismal List</h5>
                                            <span class="card-subtitle">Central overview of baptismal records</span>
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
                                                    <th>Date of Baptism</th>
                                                    <th>Presider</th>
                                                    <th class="text-center" style="width: 200px;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="table-baptismal"></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <ul class="pagination pagination-sm" id="pagination"></ul>
                                </div>
                            </div>

                            <?php include "update.php"; ?>
                            <?php include "../partials/footer.php"; ?>
                        </div>
                    </div>

                    <?php include "../partials/html.footer.php"; ?>
                    <?php include "script.php"; ?>
</body>

</html>