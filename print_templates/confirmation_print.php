<?php
require_once("../config/db.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400); // Added HTTP status code
    die("Invalid request. No ID provided.");
}

$id = intval($_GET['id']);

try {
    $stmt = $conn->prepare("SELECT * FROM confirmation_records WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404); // Added HTTP status code
        die("No record found with this ID.");
    }

    $sponsorStmt = $conn->prepare("SELECT sponsor_name FROM confirmation_sponsors WHERE confirmation_id = ?");
    $sponsorStmt->execute([$id]);
    $sponsors = $sponsorStmt->fetchAll(PDO::FETCH_COLUMN);

    $sponsorsList = empty($sponsors)
        ? "________________________, ________________________" // Changed to underline
        : implode(", ", array_map("htmlspecialchars", $sponsors));

    // Date formatting for signing block
    $issue_day = !empty($row['issue_day']) ? htmlspecialchars($row['issue_day']) : "____";
    $issue_month = !empty($row['issue_month']) ? htmlspecialchars($row['issue_month']) : "____________";
    $issue_year = !empty($row['issue_year']) ? htmlspecialchars($row['issue_year']) : "____";

    // Format confirmation_date as Month day, Year (e.g. December 12, 2025)
    $formattedConfirmationDate = '';
    if (!empty($row['confirmation_date'])) {
        $timestamp = strtotime($row['confirmation_date']);
        if ($timestamp !== false) {
            $formattedConfirmationDate = date('F d, Y', $timestamp);
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Certificate of Confirmation</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
            background-color: #f0f0f0;
            width: 100%;
            height: 100%;
        }

        .certificate {
            position: relative;
            width: 130mm;
            height: 195mm;
            margin: 10mm auto;
            left: 50%;
            transform: translateX(-50%);
            bottom: auto;
            right: auto;
            background: url('../public/assets/img/cims_1.jpg') no-repeat center top;
            background-size: 100% 100%;
            overflow: hidden;
            border: 1px solid #ccc;
        }

        /* CONTENT POSITIONING (Main Text) */
        .content {
            position: absolute;
            top: 15mm;
            left: 15mm;
            right: 15mm;
            width: 118.5mm;
        }

        .center {
            text-align: center;
            margin-bottom: 15px;
            margin-right: 0;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
        }

        .subtitle {
            font-size: 11px;
            line-height: 1.2;
        }

        .confirmation-title {
            font-size: 24px;
            font-family: 'Old English Text MT', serif;
            margin: 10px 0;
        }

        .section {
            margin-left: 20px;
            margin-bottom: 8px;
            text-align: justify;
            font-size: 13.5px;
            line-height: 1.7;
            font-weight: normal;

        }

        .underline {
            display: inline-block;
            border-bottom: 1px solid black;
            min-width: 25mm;
            padding: 0;
            text-align: center;
            vertical-align: middle;
            line-height: 1.2;
        }

        /* REGISTER NUMBERS (NEW) */
        .register-numbers {
            position: absolute;
            bottom: 35mm;
            left: 6mm;
            text-align: left;
            font-size: 10px;
            line-height: 1.4;
            margin-left: auto;

        }


        .register-number {
            position: absolute;
            bottom: 23mm;
            left: 6mm;
            text-align: left;
            font-size: 10px;
            line-height: 1.4;

        }


        /* Parish Seal - Lower-Bottom Left */
        .seal {
            position: absolute;
            top: 165mm;
            left: 5mm;
            font-size: 12px;
            text-align: left;
            line-height: 1.6;
        }

        /* Signature Block - Lower-Bottom Right */
        .signature {
            position: absolute;
            top: 140mm;
            right: 5mm;
            left: auto;
            text-align: center;
            width: 60mm;
            font-size: 12px;
            line-height: 1.2;
        }

        .signature strong {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #000;
            position: static;
            /* Binalik sa static/normal flow */
            left: auto;
            top: auto;
            margin-top: 10px;
            /* Dagdag space sa itaas ng pangalan */
        }


        /* Print settings */
        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }

            html,
            body {
                width: 297mm;
                height: 210mm;
                overflow: hidden;
            }

            .certificate {
                width: 130mm;
                height: 195mm;
                margin: 0;

                position: absolute;
                top: 5mm;
                left: 5mm;
                transform: none;

                background: url('../public/assets/img/cims_1.jpg') no-repeat center top !important;
                background-size: 100% 100% !important;
            }

            .content {
                top: 15mm;
                left: 50%;
                transform: translateX(-50%);
                width: 120mm;
                font-size: 13.5px !important;
            }

            .register-numbers {
                font-size: 13px !important;
            }

            .register-number {
                font-size: 13px !important;
            }
        }
    </style>
</head>

<body>

    <div class="certificate">
        <div class="content">
            <div class="center">
                <div class="title">SAN ISIDRO LABRADOR PARISH</div>
                <div class="subtitle">Salong, Kabankalan City, Negros Occidental<br>Diocese of Kabankalan</div>
                <div class="confirmation-title">Certificate of Confirmation</div>
            </div>

            <div class="section">
                To whom It may Concern;<br><br>

                This is to certify that
                <span class="underline" style="min-width: 25mm; margin-top: 5px; display: inline-block;">
                    <?= htmlspecialchars($row['child_name']) ?>
                </span>,<br>
                child of <span class="underline"><?= htmlspecialchars($row['parent_name1']) ?></span> and
                <span class="underline"><?= htmlspecialchars($row['parent_name2']) ?></span>,<br>
                received the Sacrament of Confirmation administered by His Excellency,<br>
                Most Rev. <span class="underline"><?= htmlspecialchars($row['bishop']) ?></span><br>
                on <span class="underline"><?= htmlspecialchars($formattedConfirmationDate !== '' ? $formattedConfirmationDate : $row['confirmation_date']) ?></span>
                with <span class="underline" style="min-width: 40mm;"><?= htmlspecialchars($sponsorsList) ?></span> acting as sponsor. <br>
            </div>
            <br>
            <br>
            <br>
            <br>
            <div class="register-numbers">
                As appears in our Confirmation Book No. <span class="underline"><?= htmlspecialchars($row['book_no']) ?></span><br>
                Page No. <span class="underline"><?= htmlspecialchars($row['page_no']) ?></span>,
                Entry No. <span class="underline"><?= htmlspecialchars($row['entry_no']) ?></span>.
        <br>
        <br>
        <br>
            </div>
        
            <div class="register-number">
                Issued this <span class="underline"><?= htmlspecialchars($row['issue_day']) ?></span> day of
                <span class="underline"><?= htmlspecialchars($row['issue_month']) ?></span>
                at the Parish of San Isidro Labrador, Salong, Kabankalan City, Negros Occidental.
            </div>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <div class="signature">
                <strong>REV. FR. HENRY PINEDA</strong><br>
                <i>Parish Priest</i>
            </div>

            <div class="seal">
                Parish Seal: ______________________________
            </div>
        </div>
    </div>


    <script>
        window.addEventListener('load', function() {
            const logsUrl = '<?php echo BASE_URL; ?>/print_logs/';

            const redirectParent = function() {
                if (window.opener && !window.opener.closed) {
                    try {
                        window.opener.location.href = logsUrl;
                        return true;
                    } catch (err) {}
                }
                return false;
            };

            const finishPrinting = function() {
                const redirected = redirectParent();
                if (!redirected) {
                    window.location.href = logsUrl;
                }
                window.close();
            };

            setTimeout(function() {
                window.print();
            }, 300);

            window.addEventListener('afterprint', finishPrinting);
            setTimeout(finishPrinting, 800);
        });
    </script>

</body>

</html>
