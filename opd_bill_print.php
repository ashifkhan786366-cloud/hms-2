<?php
require_once 'config/db.php';

if (!isset($_GET['id']))
    die("Invalid Request");
$id = $_GET['id'];

// Get Bill Info & Patient Details
$stmt = $pdo->prepare("SELECT b.*, p.full_name, p.address, p.mr_number, p.age, p.gender, p.phone FROM bills b JOIN patients p ON b.patient_id = p.id WHERE b.id = ?");
$stmt->execute([$id]);
$bill = $stmt->fetch();

if (!$bill)
    die("Bill not found");

// Get Items
$istmt = $pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
$istmt->execute([$id]);
$items = $istmt->fetchAll();

// Get Doctor details if this is an appointment/IPD bill
$doctor_name = "";
if (!empty($bill['appointment_id'])) {
    $dstmt = $pdo->prepare("SELECT u.full_name FROM appointments a JOIN users u ON a.doctor_id = u.id WHERE a.id = ?");
    $dstmt->execute([$bill['appointment_id']]);
    $doc = $dstmt->fetch();
    if ($doc)
        $doctor_name = $doc['full_name'];
} elseif (!empty($bill['ipd_admission_id'])) {
    $dstmt = $pdo->prepare("SELECT u.full_name FROM ipd_admissions i JOIN users u ON i.doctor_id = u.id WHERE i.id = ?");
    $dstmt->execute([$bill['ipd_admission_id']]);
    $doc = $dstmt->fetch();
    if ($doc)
        $doctor_name = $doc['full_name'];
}

$admission_date = "";
$discharge_date = "";
if (!empty($bill['ipd_admission_id'])) {
    $istmt2 = $pdo->prepare("SELECT admission_date, discharge_date FROM ipd_admissions WHERE id = ?");
    $istmt2->execute([$bill['ipd_admission_id']]);
    $ipd = $istmt2->fetch();
    if ($ipd) {
        $admission_date = date('d-m-Y', strtotime($ipd['admission_date']));
        if ($ipd['discharge_date']) {
            $discharge_date = date('d-m-Y', strtotime($ipd['discharge_date']));
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice - <?php echo $bill['bill_number']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
        }

        .print-container {
            width: 800px;
            margin: 0 auto;
            padding: 10px;
            padding-top: 5mm;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px 6px;
        }

        .header-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            padding: 10px 0;
            border-bottom: 2px solid #000;
            border-top: 2px solid #000;
            border-left: 2px solid #000;
            border-right: 2px solid #000;
        }

        .sub-header-table td {
            border-bottom: 0;
            border-top: 0;
        }

        .sub-header-table {
            border-bottom: 2px solid #000;
            border-top: 0;
        }

        .border-bottom {
            border-bottom: 1px solid #000 !important;
        }

        .no-border-bottom {
            border-bottom: none !important;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .fw-bold {
            font-weight: bold;
        }

        .patient-table td {
            border-top: 0;
            border-bottom: 0;
            border-left: 0;
            border-right: 0;
            padding: 2px 6px;
        }

        .items-heading {
            background-color:
                <?php echo defined('PRINT_THEME_COLOR') ? PRINT_THEME_COLOR : '#9dc3e6'; ?>
                !important;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .totals-bg-blue {
            background-color:
                <?php echo defined('PRINT_THEME_COLOR') ? PRINT_THEME_COLOR : '#9dc3e6'; ?>
                !important;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .totals-bg-red {
            background-color: #fce4d6 !important;
            /* Light reddish/orange from requested image */
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
                margin: 0;
            }

            .print-container {
                width: 100%;
                margin: 0;
                padding: 0;
            }
        }

        .btn-print {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 4px;
            margin: 10px;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 4px;
            margin: 10px;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="no-print" style="text-align: right; background: #f8f9fa; padding: 10px; border-bottom: 1px solid #ddd;">
        <button onclick="window.print()" class="btn-print">🖨 Print Invoice</button>
        <a href="billing.php" class="btn-back">⬅ Back</a>
    </div>

    <div class="print-container">
        <table>
            <tr>
                <td colspan="10" class="header-title" style="border-bottom: 0; padding-bottom: 2px;"><?php echo defined('APP_NAME') ? APP_NAME : 'SANKHLA HOSPITAL'; ?></td>
            </tr>
            <tr>
                <td colspan="5" style="border-right: 0; border-top: 1px solid #000; border-bottom: 0;">
                    <div>Powered by Vitaid Health Care Foundation</div>
                    <div>Reg. No. U86100RJ2023NPL086879</div>
                </td>
                <td colspan="5" class="text-right"
                    style="border-left: 0; border-top: 1px solid #000; border-bottom: 0;">
                    <div>Phone: <?php echo defined('APP_PHONE') ? APP_PHONE : '9829208462'; ?></div>
                    <div>Email: <?php echo defined('APP_EMAIL') ? APP_EMAIL : 'bksankhlahospital@gmail.com'; ?></div>
                </td>
            </tr>
            <tr>
                <td colspan="10" style="border-top: 0; border-bottom: 2px solid #000; font-weight: bold;">
                    TAX INVOICE / BILL OF SUPPLY / CASH MEMO
                </td>
            </tr>

            <!-- Invoice No and Date -->
            <tr>
                <td colspan="1" style="border-right: 0; border-bottom: 0;">Invoice No.</td>
                <td colspan="4" style="border-left: 0; border-bottom: 0;"><?php echo $bill['bill_number']; ?></td>

                <td colspan="2" class="text-right fw-bold" style="border-right:0; border-bottom: 0; font-size: 16px;">
                    Date</td>
                <td colspan="3" class="text-center fw-bold" style="border-left:0; border-bottom: 0; font-size: 22px;">
                    <?php echo date('d-m-y', strtotime($bill['bill_date'])); ?></td>
            </tr>

            <!-- Extra blank row as shown in the image -->
            <tr>
                <td colspan="10" style="border-top: 0;border-bottom:0; height: 10px;"></td>
            </tr>

            <!-- Patient Details Area -->
            <tr>
                <td colspan="10" style="padding: 0; border-top: 0; border-bottom: 0;">
                    <table class="patient-table">
                        <tr>
                            <td style="width: 25%;">Patient Name</td>
                            <td style="width: 75%;"><?php echo strtoupper($bill['full_name']); ?></td>
                        </tr>
                        <tr>
                            <td>Age / Gender</td>
                            <td><?php echo $bill['age']; ?>/<?php echo strtoupper($bill['gender']); ?></td>
                        </tr>
                        <tr>
                            <td>Contact No.</td>
                            <td><?php echo $bill['phone']; ?></td>
                        </tr>
                        <tr>
                            <td>UHID / OPD No. / IPD No.</td>
                            <td><?php echo $bill['mr_number']; ?></td>
                        </tr>
                        <tr>
                            <td>Doctor</td>
                            <td><?php echo strtoupper($doctor_name); ?></td>
                        </tr>
                        <tr>
                            <td>Admission Date (if IPD)</td>
                            <td><?php echo $admission_date; ?></td>
                        </tr>
                        <tr>
                            <td>Discharge Date (if IPD)</td>
                            <td><?php echo $discharge_date; ?></td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Extra blank row -->
            <tr>
                <td colspan="10" style="border-top: 0; height: 10px;"></td>
            </tr>

            <!-- Items Header -->
            <tr class="items-heading">
                <td style="width: 5%;">Sr</td>
                <td colspan="3" style="width: 45%;">Particulars / Service / Item Description</td>
                <td style="width: 5%;">Qty</td>
                <td style="width: 10%;">Rate</td>
                <td style="width: 12%;">Amount</td>
                <td style="width: 10%;">Discount</td>
                <td colspan="2" style="width: 13%;">Net Amount</td>
            </tr>

            <!-- Items Generation -->
            <?php
            $rowCount = 0;
            $maxRows = 2;
            $totalItemsFound = 0;

            foreach ($items as $i => $item):
                $rowCount++;
                $totalItemsFound += $item['quantity'];
                ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td colspan="3"><?php echo strtoupper($item['service_name']); ?></td>
                    <td class="text-right"><?php echo $item['quantity']; ?></td>
                    <td class="text-right"><?php echo round($item['cost']); ?></td>
                    <td class="text-right"><?php echo round($item['amount']); ?></td>
                    <td></td>
                    <td colspan="2"></td>
                </tr>
            <?php endforeach; ?>

            <!-- Fill empty rows to match layout -->
            <?php for ($j = $rowCount + 1; $j <= $maxRows; $j++): ?>
                <tr>
                    <td><?php echo $j; ?></td>
                    <td colspan="3"></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">0</td>
                    <td></td>
                    <td colspan="2"></td>
                </tr>
            <?php endfor; ?>

            <!-- Sum row before totals -->
            <tr>
                <td></td>
                <td colspan="3"></td>
                <td class="text-right"><?php echo $totalItemsFound > 0 ? $totalItemsFound : 9; ?></td>
                <td></td>
                <td class="text-right">0</td>
                <td></td>
                <td colspan="2"></td>
            </tr>

            <!-- Totals Area -->
            <tr class="totals-bg-blue">
                <td colspan="6" class="text-center">Total Amount</td>
                <td class="text-right"><?php echo round($bill['total_amount']); ?></td>
                <td></td>
                <td colspan="2"></td>
            </tr>

            <tr>
                <td colspan="6" class="text-center">Discount</td>
                <td class="text-right totals-bg-red"><?php echo $bill['discount'] > 0 ? '-' . round($bill['discount']) : '0'; ?></td>
                <td></td>
                <td colspan="2"></td>
            </tr>

            <tr class="totals-bg-blue">
                <td colspan="6" class="text-center">NET AMOUNT PAYABLE</td>
                <td class="text-right"><?php echo round($bill['net_amount']); ?></td>
                <td></td>
                <td colspan="2"></td>
            </tr>

            <!-- Footer Terms and QR -->
            <tr>
                <td colspan="8" style="border-right: 0; border-bottom: 0;">
                    <div style="margin-top: 10px;">Terms & Conditions :</div>
                    <div><?php echo nl2br(defined('BILL_TERMS_CONDITIONS') ? BILL_TERMS_CONDITIONS : ''); ?></div>
                </td>
                <td colspan="2" class="text-center" style="border-left: 0; border-bottom: 0; vertical-align: bottom;">
                    <!-- Placeholder QR code mimicking image -->
                    <div style="width: 100px; height: 100px; border: 1px dashed #ccc; display: inline-block; text-align: center; line-height: 100px; color: #999; font-size: 12px; margin: 0 auto;">QR Code Offline</div>
                </td>
            </tr>
            <tr>
                <td colspan="7" style="border-right: 0; border-top: 0; border-bottom: 0; padding-top: 15px;">
                    Thank You for Choosing Sankhla Hospital
                </td>
                <td colspan="3" class="text-right"
                    style="border-left: 0; border-top: 0; border-bottom: 0; padding-top: 15px;">
                    SCAN FOR RATE US ON GMB
                </td>
            </tr>
            <tr>
                <td colspan="10" style="border-top: 0; border-bottom: 0; text-align: center; font-style: italic;">
                    <?php echo defined('BILL_FOOTER_MESSAGE') ? BILL_FOOTER_MESSAGE : 'Get Well Soon!'; ?>
                </td>
            </tr>

            <!-- Bottom Address Bar text align center -->
            <tr>
                <td colspan="10" class="text-center fw-bold" style="border-top: 1px solid #000; padding: 5px;">
                    <?php echo defined('APP_ADDRESS') ? APP_ADDRESS : 'Govt. Dispensary Near Kanji Petrol Pump, Niwaru Road, Jhotwara, Jaipur - 302012'; ?>
                </td>
            </tr>

        </table>
    </div>

</body>

</html>
