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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400); // Added status code
    die("Invalid request. No ID provided.");
}

$id = intval($_GET['id']);

try {
    $sql = "SELECT * FROM death_certificates WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404); // Added status code
        die("No record found with this ID.");
    }

    // Default values para maiwasan ang error
    $name = htmlspecialchars($row['name'] ?? '___________________________');
    $address = htmlspecialchars($row['address'] ?? '___________________________');
    $date_of_death = htmlspecialchars($row['date_of_death'] ?? '___________________________');
    $cause_of_death = htmlspecialchars($row['cause_of_death'] ?? '___________________________');
    $issue_place = htmlspecialchars($row['issue_place'] ?? '___________________________');

    $rawIssueDay = $row['issue_day'] ?? '';
    $issueDayDisplay = '____';
    if (is_string($rawIssueDay) && trim($rawIssueDay) !== '') {
        $digits = preg_replace('/[^0-9]/', '', $rawIssueDay);
        if ($digits !== '') {
            $dayNumber = (int)$digits;
            if ($dayNumber > 0) {
                $issueDayDisplay = $dayNumber . getOrdinalSuffix($dayNumber);
            } else {
                $issueDayDisplay = trim($rawIssueDay);
            }
        } else {
            $issueDayDisplay = trim($rawIssueDay);
        }
    }
    $issue_day = htmlspecialchars($issueDayDisplay);
    $issue_year = htmlspecialchars($row['issue_year'] ?? '____');
    $book_no = htmlspecialchars($row['book_no'] ?? '____');
    $page_no = htmlspecialchars($row['page_no'] ?? '____');
    $entry_no = htmlspecialchars($row['entry_no'] ?? '____');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Certificate of Death</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
            background-color: #ffffff;
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
            border: 1px solid #000;
            font-size: 13.5px;
        }

        /* CONTENT POSITIONING (Main Text) */
        .content {
            position: absolute;
            top: 15mm;
            left: 50%;
            transform: translateX(-50%);
            width: 120mm;
            height: auto;
            color: #000;
            text-align: center;
            font-size: 13.5px;
            line-height: 1.3;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .subtitle {
            font-size: 12px;

            margin-bottom: 3mm;
        }

        .death-title {
            font-family: "Old English Text MT", serif;
            font-size: 28px;
            margin-top: 4mm;
            margin-bottom: 8mm;
        }

        .section {
            margin-bottom: 15px;
            text-align: justify;
            font-size: 13.5px;
            line-height: 1.7;

        }

        .underline {
            display: inline-block;
            border-bottom: 1px solid black;
            min-width: 25mm;
            padding: 0 3px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.2;
        }

        /* REGISTER NUMBERS (NEW) - Inilipat dito mula sa loob ng .content */
        .register-numbers {
            position: absolute;
            bottom: 70mm;
            left: 5mm;
            text-align: left;
            font-size: 10px;
            line-height: 1.4;
        }

        /* Parish Seal - Lower-Bottom Left */
        .seal {
            position: absolute;
            bottom: 8mm;
            left: 45px;
            width: 40mm;
            font-size: 12px;
            text-align: left;
            line-height: 1.6;
        }

        /* Signature Block - Lower-Bottom Right */
        .signature {
            position: absolute;
            bottom: 30mm;
            right: 5mm;
            left: auto;
            width: 60mm;
            text-align: center;
            font-size: 12px;
            line-height: 1.2;
        }

        .signature strong {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #000;
        }


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
                background: #ffffff;
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
                border: 1px solid #000;
            }

            .register-numbers {
                font-size: 10px !important;
            }
        }
    </style>
</head>

<body>
    <div class="certificate">
        <div class="content">
            <div class="title">SAN ISIDRO LABRADOR PARISH</div>
            <div class="subtitle">
                Salong, Kabankalan City, Negros Occidental<br>
                Diocese of Kabankalan
            </div>
            <div class="death-title">CERTIFICATE OF DEATH</div>

            <div class="section">
                This is to certify that
                <span class="underline" style="min-width: 40mm;"><?= $name ?></span>,
                of <span class="underline" style="min-width: 35mm;"><?= $address ?></span>,
                who died on <span class="underline" style="min-width: 30mm;"><?= $date_of_death ?></span>
                of <span class="underline" style="min-width: 30mm;"><?= $cause_of_death ?></span>
                was buried in the Cemetery of this Parish.
            </div>

            <div class="section" style="margin-top: 10mm;">
                Signed, sealed, and issued this
                <span class="underline" style="min-width: 10mm;"><?= $issue_place ?></span> day of
                <span class="underline" style="min-width: 20mm;"><?= $issue_day ?></span>
                in the Year of our Lord
                <span class="underline" style="min-width: 15mm;"><?= $issue_year ?></span>,
                at San Isidro Labrador Parish, Salong, Kabankalan City, Negros Occidental.
            </div>
        </div>

        <div class="register-numbers">
            Book No.: <span class="underline" style="min-width: 20mm;"><?= $book_no ?></span><br>
            Page No.: <span class="underline" style="min-width: 20mm;"><?= $page_no ?></span><br>
            Entry No.: <span class="underline" style="min-width: 20mm;"><?= $entry_no ?></span>
        </div>

        <div class="seal">
            <span>Parish Seal:</span> ________________________________
        </div>

        <div class="signature">
            <strong>REV. FR. HENRY PINEDA</strong><br>
            <i>Parish Priest</i>
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
