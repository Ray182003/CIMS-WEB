<?php
require_once("../config/db.php");

if (!function_exists('formatOrdinalDay')) {
    function formatOrdinalDay($value)
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $value);
        if ($digits === '') {
            return null;
        }

        $day = (int) $digits;
        if ($day < 1 || $day > 31) {
            return null;
        }

        $suffix = 'th';
        $modHundred = $day % 100;
        if ($modHundred < 11 || $modHundred > 13) {
            switch ($day % 10) {
                case 1:
                    $suffix = 'st';
                    break;
                case 2:
                    $suffix = 'nd';
                    break;
                case 3:
                    $suffix = 'rd';
                    break;
            }
        }

        return $day . $suffix;
    }
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400); // Added status code
    die("Invalid request. No ID provided.");
}

$id = intval($_GET['id']);

try {
    $sql = "SELECT * FROM liberty_records WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404); // Added status code
        die("No record found with this ID.");
    }

    // Default values para maiwasan ang error kung may kulang
    $child_name = htmlspecialchars($row['child_name'] ?? '___________________________');
    $parent_name1 = htmlspecialchars($row['parent_name1'] ?? '___________________________');
    $parent_name2 = htmlspecialchars($row['parent_name2'] ?? '___________________________');
    $residence = htmlspecialchars($row['residence'] ?? '___________________________');
    $marriage_with = htmlspecialchars($row['marriage_with'] ?? '___________________________');
    // Month for the "day of _____" phrase: signed_sealed_given now stores the month name
    $raw_signed_sealed = $row['signed_sealed_given'] ?? null;
    $signed_sealed_given = !empty($raw_signed_sealed)
        ? htmlspecialchars($raw_signed_sealed)
        : '____________';

    // Display day with ordinal suffix (1st, 2nd, 3rd, 4th, etc.)
    $raw_day_of = $row['day_of'] ?? null;
    $formatted_day_of = formatOrdinalDay($raw_day_of);
    $day_of = $formatted_day_of ? htmlspecialchars($formatted_day_of) : htmlspecialchars($raw_day_of ?? '_________________');
    $year_of_our_lord = htmlspecialchars($row['year_of_our_lord'] ?? '____');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Status of Liberty Certificate</title>
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
            /* ➡️ Ginaya sa Baptismal: Mas maliit na size */
            width: 130mm;
            height: 195mm;
            margin: 10mm auto;

            /* Para sa screen display (centered) */
            left: 50%;
            transform: translateX(-50%);
            bottom: auto;
            right: auto;

            /* Ginaya sa Baptismal: Background */
            background: url('../public/assets/img/cims_1.jpg') no-repeat center top;
            background-size: 100% 100%;
            overflow: hidden;
            border: 1px solid #ccc;
            font-size: 13.5px;
            /* General font size */
        }


        /* Header Section */
        .header {
            position: absolute;
            top: 15mm;
            /* Inayos para sa 195mm height */
            left: 50%;
            transform: translateX(-50%);
            width: 120mm;
            text-align: center;
            color: #000;
        }

        .title {
            font-size: 20px;
            /* Ginaya sa Baptismal */
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .subtitle {
            font-size: 12px;
            /* Ginaya sa Baptismal */
            margin-bottom: 3mm;
        }

        .liberty-title {
            font-family: "Old English Text MT", serif;
            font-size: 28px;
            /* Ginaya sa Baptismal (.baptism-title) */
            margin-top: 4mm;
            margin-bottom: 8mm;
        }

        /* Main Content - Pinalitan ng absolute position para maging aligned sa gitna ng content block */
        .content {
            position: absolute;
            top: 60mm;
            /* Binaba para hindi makasagabal sa header */
            left: 50%;
            transform: translateX(-50%);
            width: 120mm;
            height: auto;

        }

        .section {
            text-align: justify;
            /* Ginawa kong justify para mas professional */
            margin: 0 auto 10px auto;
            width: 100%;
            /* Gamitin ang full content width */
            font-size: 13.5px;
            /* Ginaya sa Baptismal (.main-info) */
            line-height: 1.7;
            /* Para mas maluwag */
            padding: 0;
        }

        /* I-override ang margin-top para sa unang section lang */
        .section:first-of-type {
            margin-top: 0;
        }

        /* Iba-break ang line sa loob ng section */
        .section br {
            line-height: 1.7;
        }

        .underline {
            display: inline-block;
            border-bottom: 1px solid black;
            min-width: 20mm;
            /* Mas maliit na default width */
            padding: 0;
            text-align: center;
            vertical-align: middle;
            line-height: 1.2;
        }

        /* REGISTER NUMBERS (Wala sa Liberty, pero idinagdag ko lang para sa consistency) */

        /* Parish Seal - Lower-Bottom Left */
        .seal {
            position: absolute;
            bottom: 8mm;
            /* Ginaya sa Baptismal */
            left: 20mm;
            width: 40mm;
            font-size: 12px;
            text-align: left;
            line-height: 1.6;
        }

        /* Signature Block - Lower-Bottom Right */
        .signature {
            position: absolute;
            bottom: 30mm;
            /* Ginaya sa Baptismal */
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
        }
    </style>
</head>

<body>
    <div class="certificate">

        <div class="header">
            <div class="title">SAN ISIDRO LABRADOR PARISH</div>
            <div class="subtitle">Salong, Kabankalan City, Negros Occidental<br>Diocese of Kabankalan</div>
            <div class="liberty-title">STATUS OF LIBERTY</div>
        </div>

        <div class="content">
            <div class="section">
                This is to certify that
                <span class="underline" style="min-width: 30mm;"><?= $child_name ?></span>,<br>
                son of <span class="underline" style="min-width: 25mm;"><?= $parent_name1 ?></span> and
                <span class="underline" style="min-width: 25mm;"><?= $parent_name2 ?></span>,<br>
                both residents of <span class="underline" style="min-width: 35mm;"><?= $residence ?></span><br>
                is single and free to contract marriage with
                <span class="underline" style="min-width: 35mm;"><?= $marriage_with ?></span>.
            </div>

            <div class="section">
                This permission is issued upon the request of the above-named person for marriage purposes.
            </div>

            <div class="section">
                Signed, sealed, and given this
                <span class="underline" style="min-width: 10mm;"><?= $day_of ?></span> day of
                <span class="underline" style="min-width: 20mm;"><?= $signed_sealed_given ?></span>,
                in the year of Our Lord
                <span class="underline" style="min-width: 15mm;"><?= $year_of_our_lord ?></span>,
                at San Isidro Labrador Parish,
                Salong, Kabankalan City, Negros Occidental.
            </div>
        </div>

        <div class="signature">
            <strong>REV. FR. HENRY PINEDA</strong><br>
            <i>Parish Priest</i>
        </div>

        <div class="seal">
            <span>Parish Seal:</span> ________________________
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
                        // Ignore access errors for opener
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
