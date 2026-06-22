<?php
require_once 'config/db.php';
require_once 'includes/auth_check.php';

if (!isset($_GET['appt_id']) || !isset($_GET['bill_id'])) {
    die("Invalid Request");
}

$appt_id = $_GET['appt_id'];
$bill_id = $_GET['bill_id'];

// ---------------------------------------------------------
// 1. FETCH OPD SLIP DATA
// ---------------------------------------------------------
$sql = "SELECT a.*, p.full_name, p.age, p.gender, p.mr_number, p.phone as mobile, p.address as p_address,
        u.full_name as doctor_name
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        JOIN users u ON a.doctor_id = u.id 
        WHERE a.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appt_id]);
$data = $stmt->fetch();

if (!$data) die("Appointment not found");

$patient_id = $data['mr_number'];
$token_no = $data['token_number'];
$name = strtoupper($data['full_name']);
$gen = strtoupper($data['gender']); 
$age = $data['age'] . 'Y';
$mobile = $data['mobile'];
$date = str_replace('-', '\\', date('d-m-y', strtotime($data['visit_date']))); 
$clean_doc_name = preg_replace('/^dr\.?\s*/i', '', $data['doctor_name']);
$ref_by = "DR. " . strtoupper($clean_doc_name);
$address_part = strtoupper(substr($data['p_address'], 0, 15)); 
$time = date('h:i A', strtotime($data['created_at'])); 
$bp = $data['bp'] ?: '......./.......';
$spo2 = '......'; 
$plus = $data['pulse'] ?: '.......';
$hight = '..............'; 
$waight = $data['weight'] ?: '..............';
$blood_group = '..........'; 

// ---------------------------------------------------------
// 2. FETCH OPD BILL DATA
// ---------------------------------------------------------
$bstmt = $pdo->prepare("SELECT b.*, p.full_name, p.address, p.mr_number, p.age, p.gender, p.phone FROM bills b JOIN patients p ON b.patient_id = p.id WHERE b.id = ?");
$bstmt->execute([$bill_id]);
$bill = $bstmt->fetch();

if (!$bill) die("Bill not found");

$istmt = $pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
$istmt->execute([$bill_id]);
$items = $istmt->fetchAll();

$doctor_name = $data['doctor_name'];
$admission_date = "";
$discharge_date = "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Both - <?php echo $name; ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
        }

        /* --- OPD SLIP STYLES --- */
        .slip-container {
            padding-top: 1.8in;
            padding-left: 40px;
            padding-right: 15mm; /* Extra gap for 10mm margin on right side */
            position: relative;
            min-height: 100vh; /* Take full page so they print separately */
            box-sizing: border-box;
            font-size: 17px;
        }

        .header-grid {
            display: grid;
            grid-template-columns: 32% 16% 28% 24%;
            row-gap: 30px;
            margin-bottom: 50px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
            text-transform: uppercase;
            align-items: baseline;
        }

        .header-grid > div { white-space: nowrap; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .vitals-row {
            display: flex;
            margin-bottom: 30px; 
            font-weight: bold;
            font-size: 14px; 
            text-transform: uppercase;
        }

        .vital-item { margin-right: 25px; }

        .rx-symbol {
            font-size: 38px; 
            font-weight: bold;
            font-family: Arial, sans-serif;
            margin-left: 130px; 
            margin-top: 15px;
        }

        .slip-footer-text {
            position: absolute;
            bottom: 1in; 
            right: 40px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 15px; 
        }

        /* --- OPD BILL STYLES --- */
        .bill-container {
            width: 800px;
            margin: 0 auto;
            padding: 10px;
            padding-top: 5mm;
            box-sizing: border-box;
            font-size: 13px;
        }

        .bill-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bill-table th, .bill-table td {
            border: 1px solid #000;
            padding: 4px 6px;
        }

        .bill-header-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            padding: 10px 0;
            border-bottom: 2px solid #000;
            border-top: 2px solid #000;
            border-left: 2px solid #000;
            border-right: 2px solid #000;
        }
        
        .patient-table td {
            border-top: 0; border-bottom: 0; border-left: 0; border-right: 0;
            padding: 2px 6px;
        }

        .items-heading {
            background-color: <?php echo defined('PRINT_THEME_COLOR') ? PRINT_THEME_COLOR : '#9dc3e6'; ?> !important;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .totals-bg-blue {
            background-color: <?php echo defined('PRINT_THEME_COLOR') ? PRINT_THEME_COLOR : '#9dc3e6'; ?> !important;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .totals-bg-red {
            background-color: #fce4d6 !important;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
            .bill-container { width: 100%; margin: 0; padding: 0; page-break-before: always; }
        }

        .controls {
            margin-bottom: 10px;
            background: #f4f4f4;
            padding: 10px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        .controls button, .controls a {
            padding: 8px 16px; margin-left: 10px;
            background-color: #007bff; color: white; border: none;
            cursor: pointer; border-radius: 4px; text-decoration: none; font-size: 14px;
        }
        .controls a { background-color: #6c757d; }
    </style>
</head>
<body>

    <div class="controls no-print">
        <button onclick="window.print()">🖨 Print Both</button>
        <a href="opd.php">⬅ Back to OPD</a>
    </div>

    <!-- 1. OPD SLIP (TOP HALF) -->
    <div class="slip-container">
        <div class="header-grid">
            <div>PATIENT ID _ <strong><?php echo $patient_id; ?></strong></div>
            <div>TOKEN No. : <strong><?php echo $token_no; ?></strong></div>
            <div>NAME : <?php echo $name; ?></div>
            <div>GEN. <?php echo $gen; ?> &nbsp;&nbsp; AGE.<?php echo $age; ?></div>

            <div style="grid-column: 1 / span 2;">MOBILE NO : <?php echo $mobile; ?></div>
            <div></div> 
            <div>DATE _ <strong><?php echo $date; ?></strong></div>

            <div style="grid-column: 1 / span 2;">REF. BY - <strong><?php echo $ref_by; ?></strong></div>
            <div>ADDRESS : <?php echo $address_part; ?></div>
            <div>TIMING : <?php echo $time; ?></div>
        </div>

        <div class="vitals-row">
            <div class="vital-item">BP <?php echo $bp; ?></div>
            <div class="vital-item">SPO2 <?php echo $spo2; ?></div>
            <div class="vital-item">PLUS <?php echo $plus; ?></div>
            <div class="vital-item">HIGHT <?php echo $hight; ?></div>
            <div class="vital-item">WAIGHT <?php echo $waight; ?></div>
            <div class="vital-item" style="margin-right: 0;">BLOOD GROUP <?php echo $blood_group; ?></div>
        </div>

        <div><div class="rx-symbol">Rx</div></div>

        <div class="slip-footer-text">OPD VALID FOR 3 DAYS</div>
    </div>

    <!-- 2. OPD BILL (PAGE 2) -->
    <div class="bill-container">
        <table class="bill-table">
            <tr>
                <td colspan="10" class="bill-header-title" style="border-bottom: 0; padding-bottom: 2px;"><?php echo defined('APP_NAME') ? APP_NAME : 'SANKHLA HOSPITAL'; ?></td>
            </tr>
            <tr>
                <td colspan="5" style="border-right: 0; border-top: 1px solid #000; border-bottom: 0;">
                    <div>Powered by Vitaid Health Care Foundation</div>
                    <div>Reg. No. U86100RJ2023NPL086879</div>
                </td>
                <td colspan="5" class="text-right" style="border-left: 0; border-top: 1px solid #000; border-bottom: 0;">
                    <div>Phone: <?php echo defined('APP_PHONE') ? APP_PHONE : '9829208462'; ?></div>
                    <div>Email: <?php echo defined('APP_EMAIL') ? APP_EMAIL : 'bksankhlahospital@gmail.com'; ?></div>
                </td>
            </tr>
            <tr>
                <td colspan="10" style="border-top: 0; border-bottom: 2px solid #000; font-weight: bold;">
                    TAX INVOICE / BILL OF SUPPLY / CASH MEMO
                </td>
            </tr>

            <tr>
                <td colspan="1" style="border-right: 0; border-bottom: 0;">Invoice No.</td>
                <td colspan="4" style="border-left: 0; border-bottom: 0;"><?php echo $bill['bill_number']; ?></td>
                <td colspan="2" class="text-right" style="border-right:0; border-bottom: 0; font-weight:bold; font-size: 16px;">Date</td>
                <td colspan="3" class="text-center" style="border-left:0; border-bottom: 0; font-weight:bold; font-size: 22px;"><?php echo date('d-m-y', strtotime($bill['bill_date'])); ?></td>
            </tr>
            <tr><td colspan="10" style="border-top: 0;border-bottom:0; height: 10px;"></td></tr>

            <tr>
                <td colspan="10" style="padding: 0; border-top: 0; border-bottom: 0;">
                    <table class="patient-table" style="width:100%;">
                        <tr><td style="width: 25%;">Patient Name</td><td style="width: 75%;"><?php echo strtoupper($bill['full_name']); ?></td></tr>
                        <tr><td>Age / Gender</td><td><?php echo $bill['age']; ?>/<?php echo strtoupper($bill['gender']); ?></td></tr>
                        <tr><td>Contact No.</td><td><?php echo $bill['phone']; ?></td></tr>
                        <tr><td>UHID / OPD No.</td><td><?php echo $bill['mr_number']; ?></td></tr>
                        <tr><td>Doctor</td><td><?php echo strtoupper($doctor_name); ?></td></tr>
                    </table>
                </td>
            </tr>
            <tr><td colspan="10" style="border-top: 0; height: 10px;"></td></tr>

            <tr class="items-heading">
                <td style="width: 5%;">Sr</td>
                <td colspan="3" style="width: 45%;">Service / Item Description</td>
                <td style="width: 5%;">Qty</td>
                <td style="width: 10%;">Rate</td>
                <td style="width: 12%;">Amount</td>
                <td style="width: 10%;">Discount</td>
                <td colspan="2" style="width: 13%;">Net Amount</td>
            </tr>

            <?php
            $totalItemsFound = 0;
            foreach ($items as $i => $item):
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

            <!-- Generate exactly 2 empty rows for half-page billing -->
            <?php for ($j = count($items) + 1; $j <= count($items) + 1; $j++): ?>
                <tr><td><?php echo $j; ?></td><td colspan="3"></td><td></td><td></td><td class="text-right">0</td><td></td><td colspan="2"></td></tr>
            <?php endfor; ?>

            <tr>
                <td></td><td colspan="3"></td><td class="text-right"><?php echo $totalItemsFound > 0 ? $totalItemsFound : 1; ?></td>
                <td></td><td class="text-right">0</td><td></td><td colspan="2"></td>
            </tr>

            <tr class="totals-bg-blue">
                <td colspan="6" class="text-center">Total Amount</td>
                <td class="text-right"><?php echo round($bill['total_amount']); ?></td>
                <td></td><td colspan="2"></td>
            </tr>
            <tr>
                <td colspan="6" class="text-center">Discount</td>
                <td class="text-right totals-bg-red"><?php echo $bill['discount'] > 0 ? '-' . round($bill['discount']) : '0'; ?></td>
                <td></td><td colspan="2"></td>
            </tr>
            <tr class="totals-bg-blue">
                <td colspan="6" class="text-center">NET AMOUNT PAYABLE</td>
                <td class="text-right"><?php echo round($bill['net_amount']); ?></td>
                <td></td><td colspan="2"></td>
            </tr>

            <tr>
                <td colspan="8" style="border-right: 0; border-bottom: 0;">
                    <div style="margin-top: 10px;">Terms & Conditions :</div>
                    <div><?php echo nl2br(defined('BILL_TERMS_CONDITIONS') ? BILL_TERMS_CONDITIONS : ''); ?></div>
                </td>
                <td colspan="2" class="text-center" style="border-left: 0; border-bottom: 0; vertical-align: bottom;">
                    <div style="width: 80px; height: 80px; border: 1px dashed #ccc; display: inline-block; text-align: center; line-height: 80px; color: #999; font-size: 10px; margin: 0 auto;">QR Code Off</div>
                </td>
            </tr>
            <tr>
                <td colspan="7" style="border-right: 0; border-top: 0; border-bottom: 0; padding-top: 10px;">Thank You for Choosing Sankhla Hospital</td>
                <td colspan="3" class="text-right" style="border-left: 0; border-top: 0; border-bottom: 0; padding-top: 10px;">SCAN FOR RATE US ON GMB</td>
            </tr>
            <tr>
                <td colspan="10" class="text-center fw-bold" style="border-top: 1px solid #000; padding: 5px;">
                    <?php echo defined('APP_ADDRESS') ? APP_ADDRESS : 'Govt. Dispensary Near Kanji Petrol Pump, Niwaru Road, Jhotwara, Jaipur - 302012'; ?>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
