<?php
require_once("../config/db.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400); // Added status code
    die("Invalid request. No ID provided.");
}

$id = intval($_GET['id']);

try {
    $sql = "SELECT * FROM marriage_permits WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404); // Added status code
        die("No record found with this ID.");
    }

    // Default values para maiwasan ang error kung may kulang
    $name = htmlspecialchars($row['name'] ?? '___________________________');

    // Format birthdate as Month day, Year (e.g. November 19, 2025)
    $raw_birthdate = $row['birthdate'] ?? null;
    if (!empty($raw_birthdate)) {
        $birth_ts = strtotime($raw_birthdate);
        if ($birth_ts !== false) {
            $birthdate = htmlspecialchars(date('F d, Y', $birth_ts));
        } else {
            $birthdate = htmlspecialchars($raw_birthdate);
        }
    } else {
        $birthdate = '___________________________';
    }
    $parents = htmlspecialchars($row['parents'] ?? '___________________________');
    $parents_second = htmlspecialchars($row['parents_second'] ?? '___________________________');
    $residence = htmlspecialchars($row['residence'] ?? '___________________________');
    $issue_date = htmlspecialchars($row['issue_date'] ?? '____');
    $day_of = htmlspecialchars($row['day_of'] ?? '_________________');
    $year_of_lord = htmlspecialchars($row['year_of_lord'] ?? '____');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Permit to Marry Certificate</title>
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

        .marry-title {
            font-family: "Old English Text MT", serif;
            font-size: 28px;
            margin-top: 4mm;
            margin-bottom: 8mm;
        }

        .body-text {
            text-align: justify;
            margin: 0 auto;
            width: 100%;
            font-size: 13.5px;
            line-height: 1.7;
            padding: 10px 0;
            margin-top: 20mm;

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

        /* Parish Seal - Lower-Bottom Left */
        .seal {
            position: absolute;
            bottom: 8mm;
            left: 5mm;
            width: 40mm;
            font-size: 12px;
            text-align: left;
            line-height: 1.6;
        }

        /* Signature Block - Lower-Bottom Right */
        .signature {
            position: absolute;
            bottom: 20mm;
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
        <div class="content">
            <div class="title">SAN ISIDRO LABRADOR PARISH</div>
            <div class="subtitle">
                Salong, Kabankalan City, Negros Occidental<br>
                Diocese of Kabankalan
            </div>
            <div class="marry-title">PERMIT TO MARRY</div>

            <div class="body-text">
                Permission is hereby granted to
                <span class="underline" ><?= $name ?></span>,
                born on <span class="underline" style="min-width: 30mm;"><?= $birthdate ?></span>,
                whose parents are
                <span class="underline" style="min-width: 30mm;"><?= $parents_second ?></span> and
                <span class="underline" style="min-width: 30mm;"><?= $parents ?></span>,
                both residents of
                <span class="underline" style="min-width: 30mm;"><?= $residence ?></span>.<br><br>
                According to our parish record, SHE is free to marry outside this parish.<br><br>
                This permission is signed, sealed, and issued on the
                <span class="underline" ><?= $issue_date ?></span> day of
                <span class="underline"><?= $day_of ?></span>,
                in the year of our Lord
                <span class="underline" style="min-width: 15mm;"><?= $year_of_lord ?></span>,
                at San Isidro Labrador Parish, Salong, Kabankalan City, Negros Occidental.
            </div>
        </div>

        <div class="seal"><span>Parish Seal:</span> ________________________</div>

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
                        // Ignore errors when opener is not accessible
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
