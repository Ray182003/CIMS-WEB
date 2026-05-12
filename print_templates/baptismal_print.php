<?php
require_once("../config/db.php");

if (!function_exists('getOrdinalSuffix')) {
    function getOrdinalSuffix($day)
    {
        $tens = $day % 100;
        if ($tens >= 11 && $tens <= 13) {
            return 'th';
        }

        switch ($day % 10) {
            case 1:
                return 'st';
            case 2:
                
                return 'nd';
            case 3:
                return 'rd';
            default:
                return 'th';
        }
    }
}

function formatDateWithMonthName($value)
{
    if ($value === null) {
        return '';
    }

    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $patterns = ['Y-m-d', 'Y/m/d', 'm/d/Y', 'd/m/Y'];
    foreach ($patterns as $pattern) {
        $date = DateTime::createFromFormat($pattern, $value);
        if ($date instanceof DateTime) {
            return $date->format('F j, Y');
        }
    }

    $timestamp = strtotime($value);
    if ($timestamp !== false) {
        return date('F j, Y', $timestamp);
    }

    return $value;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid request. No ID provided.");
}

$id = intval($_GET['id']);

$sql = "SELECT * FROM baptismal_records WHERE id = :id";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error.");
}

$stmt->bindParam(":id", $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    die("No record found with this ID.");
}

// Get sponsors
$sponsorsQuery = "SELECT sponsor_name FROM sponsors WHERE baptismal_id = :baptismal_id";
$sponsorStmt = $conn->prepare($sponsorsQuery);
if (!$sponsorStmt) {
    die("SQL Error.");
}

$sponsorStmt->bindParam(":baptismal_id", $id, PDO::PARAM_INT);
$sponsorStmt->execute();
$sponsors = $sponsorStmt->fetchAll(PDO::FETCH_COLUMN, 0);
$sanitizedSponsors = array_map('htmlspecialchars', $sponsors);
$sponsorsList = empty($sanitizedSponsors) ? "No sponsors listed" : implode(", ", $sanitizedSponsors);
$birthDateDisplay = htmlspecialchars(formatDateWithMonthName($row['birth_date'] ?? ''));
$baptismDateDisplay = htmlspecialchars(formatDateWithMonthName($row['baptism_date'] ?? ''));

$rawCertificateDay = $row['day'] ?? '';
$certificateDayDisplay = '';
if (is_string($rawCertificateDay)) {
    $trimmedDay = trim($rawCertificateDay);
    if ($trimmedDay !== '') {
        $digitsOnly = preg_replace('/[^0-9]/', '', $trimmedDay);
        if ($digitsOnly !== '') {
            $dayNumber = (int) $digitsOnly;
            if ($dayNumber > 0) {
                $certificateDayDisplay = $dayNumber . getOrdinalSuffix($dayNumber);
            } else {
                $certificateDayDisplay = $trimmedDay;
            }
        } else {
            $certificateDayDisplay = $trimmedDay;
        }
    }
}
$certificateDayDisplay = htmlspecialchars($certificateDayDisplay);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Baptism</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', serif;
            width: 100%;
            height: 100%;
        }

        /* --- CERTIFICATE CONTAINER (A5 PORTRAIT SIZE - HALF A4) --- */
        .certificate {
            position: absolute;
            /* A5 Portrait size: 148.5mm x 210mm */
            width: 148.5mm;
            height: 210mm;

            /* BINAGO: Naka-angat at naka-left side para sa two-column print */
            top: 50%;
            /* Naka-sentro vertically */
            left: 25%;
            /* Naka-kaliwa (Left Half) */
            transform: translate(-50%, -50%);
            /* Binago ang transform para sa left: 25% */

            background: url('../public/assets/img/cims_1.jpg') no-repeat center center;
            background-size: 100% 100%;
            /* Cover the A5 area */
            overflow: hidden;
            /* border: 1px solid red; */

        }

        /* CONTENT POSITIONING (Scaled down margins for A5) */
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
            margin-right: 80px;

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

        .footer {
            top: 175mm;
        }

        .section {
            font-size: 12px;
            line-height: 1.4;
            margin-top: 10px;
            margin-left: 10px;
            margin-right: 75px;
            /* Tanggalin ang 10px margin-left para magkasya */
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

        .signature {
            position: absolute;
            top: 150mm;
            /* Absolute position sa A5 */
            right: 12mm;
            text-align: right;
            font-size: 12px;
            width: 60mm;
        }

        .signature strong {
            display: block;
            margin-top: 3px;
            font-size: 13px;
        }

        .seal {
            position: absolute;
            top: 180mm;
            left: 0;
            font-size: 12px;
            text-align: left;
        }

        .print-btn {
            display: block;
            text-align: center;
            margin: 20px auto;
        }

        .register-numbers {
            position: absolute;
            top: 145mm;
            left: 5mm;
            text-align: left;
            font-size: 10px;
            line-height: 1.4;
        }

        /* --- PRINT SETTINGS (A4 Landscape) --- */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: A4 landscape;
                /* ITO ang kailangan: A4 Landscape */
                margin: 0;
            }

            html,
            body {
                width: 297mm;
                /* Landscape width */
                height: 210mm;
                /* Landscape height */
            }

            .print-btn {
                display: none;
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
                font-size: 25px !important;
            }



        }

        /* Kung gusto mo ng pangalawang certificate sa kanan, kailangan mo ng isa pang .certificate div sa HTML */
    </style>
</head>

<body>
    <br>
    <div class="certificate">
        <div class="content">

            <div class="center">
                <div class="title">SAN ISIDRO LABRADOR PARISH</div>
                <div class="subtitle">Salong, Kabankalan City, Negros Occidental<br>Diocese of Kabankalan</div>
                <div class="baptism-title">Certificate of Baptism</div>
            </div>
            <br>
            <div class="section">
                <span class="underline" style="margin-left:45px;"><?= htmlspecialchars($row['child_name']) ?></span><br>
                Child of <span class="underline"><?= htmlspecialchars($row['parent_name1']) ?></span><br>
                And <span class="underline"><?= htmlspecialchars($row['parent_name2']) ?></span><br>
                Born in <span class="underline"><?= htmlspecialchars($row['birth_place']) ?></span><br>
                On <span class="underline"><?= $birthDateDisplay ?></span><br><br><br>
                <center>
                    <div class="center">
                        WAS SOLEMNLY BAPTIZED<br>
                        ACCORDING TO THE RITES OF THE<br>
                        ROMAN CATHOLIC CHURCH
                    </div>
                </center>
                <br>On <span class="underline"><?= $baptismDateDisplay ?></span><br>
                By the Rev. Fr. <span class="underline"><?= htmlspecialchars($row['bishop']) ?></span><br>
                Sponsors: <span class="underline"><?= $sponsorsList ?></span>
            </div>

            <div class="section">
                As appears from the Baptismal Register of the Parish.<br>
                These data are in accordance with the original to which refer.
            </div>

            <div class="section">
                In witness thereof I hereunto set my signature this
                <span class="underline"><?= $certificateDayDisplay ?></span> day of
                <span class="underline"><?= htmlspecialchars($row['month']) ?></span>, 20
                <span class="underline"><?= htmlspecialchars(substr(str_pad((string) ($row['year'] ?? ''), 2, '0', STR_PAD_LEFT), -2)) ?></span> in the Parish of San Isidro
                Labrador, Salong, Kabankalan City, Negros Occidental.
            </div>

            <div class="register-numbers">
                Book No. <span class="underline"><?= htmlspecialchars($row['book_no']) ?></span><br>
                Page No. <span class="underline"><?= htmlspecialchars($row['page_no']) ?></span><br>
                Entry No. <span class="underline"><?= htmlspecialchars($row['entry_no']) ?></span>
            </div>

            <div class="signature">
                <strong>REV. FR. HENRY PINEDA</strong><br>
                <i>Parish Priest</i>
            </div>


        </div>
        <div class="seal"
            style="position:absolute; bottom:100px; left:60px; font-size:14px; text-align:left;">
            Parish Seal: _________________
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
                    } catch (err) {
                        // Ignore errors if opener cannot be accessed
                    }
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
