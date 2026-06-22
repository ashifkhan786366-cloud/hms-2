<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Bill - <?= htmlspecialchars($bill['bill_number']) ?></title>
    <style>
        :root {
            --primary: <?= htmlspecialchars($bill['settings']['primary_color'] ?? '#000') ?>;
        }
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
        
        .print-area { margin: 0 auto; background: #fff; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .mb-2 { margin-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px;}
        th, td { border: 1px solid #ddd; padding: 6px; font-size: 13px;}
        th { background-color: #2b4c7e; color: #fff; font-weight: bold;}
        
        /* A4 Layout */
        .layout-a4 { width: 210mm; min-height: 297mm; padding: 15mm; box-sizing: border-box; }
        .layout-a4 .header { text-align:center; padding-bottom: 10px; margin-bottom: 10px; border-bottom: 2px solid var(--primary); }
        .layout-a4 .header img { max-width: 120px; max-height: 80px; }
        .layout-a4 .hospital-info h2 { margin: 0; font-size: 26px; color: #000; font-weight: 800;}
        .layout-a4 .hospital-info p { margin: 2px 0; font-size: 13px; color: #333;}
        
        .layout-a4 .title-strip { background: #e0edff; border: 1px solid #1f497d; padding: 5px; text-align: center; font-weight: bold; font-size: 14px; color: #1f497d; margin-bottom: 15px; margin-top: -10px;}

        .layout-a4 .info-table { border: 1px solid #1f497d; margin-bottom: 15px;}
        .layout-a4 .info-table td { border: 1px solid #ccc; padding: 4px 8px; font-size: 12px;}
        .layout-a4 .info-label { color: #1f497d; font-weight: bold; width: 15%;}
        
        .layout-a4 .cat-header { color: #fff; font-weight: bold; padding: 4px 10px; font-size: 12px;}
        .layout-a4 .cat-subtotal-row { background: #f9f9f9; }
        .layout-a4 .cat-subtotal { color: #1f497d; font-weight: bold; text-align: right; }
        
        .layout-a4 .footer { margin-top: 30px; }
        .layout-a4 .signature { text-align: center; width: 180px; margin-top: 25px; border-top: 1px solid #000; padding-top: 5px; float: right; font-size: 12px; font-weight:bold;}
        .layout-a4 .terms-box { border: 1px solid #1f497d; padding: 10px; margin-top: 50px; font-size: 11px; clear:right;}
        .layout-a4 .terms-box p { margin: 0 0 5px 0;}
        
        .layout-a4 .summary-box { border: 1px solid #1f497d; margin-top: 10px; width: 100%;}
        .layout-a4 .summary-box th { background: #1f497d; color: #fff;}
        .layout-a4 .summary-box td, .layout-a4 .summary-box th { border: 1px solid #ccc; padding: 5px; font-size: 12px;}
        .layout-a4 .grand-total-val { background: #1f497d; color: #fff; font-weight: 800;}
        
        /* Thermal Layout (80mm) */
        .layout-thermal { width: 80mm; padding: 2mm; box-sizing: border-box; font-family: monospace; font-size: 12px; }
        .layout-thermal .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }
        .layout-thermal table th, .layout-thermal table td { border: none; padding: 2px 0; }
        .layout-thermal table th { border-bottom: 1px solid #000; border-top: 1px solid #000; background: transparent; color: #000; }
        .layout-thermal .summary { border-top: 1px dashed #000; margin-top: 5px; padding-top: 5px; }
        .layout-thermal .grand-total { font-weight: bold; font-size: 14px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0; }

        .hide-on-screen { display: none; }
        @media print {
            body { background: transparent; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            .hide-on-screen { display: block; }
            .print-area { border: none; box-shadow: none; margin: 0; padding: 0; }
            .layout-a4 { width: 100%; height: 100%; padding: 0; }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="no-print" style="margin-bottom: 20px; text-align: center; padding: 10px; background: #eee;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px;">Print Bills</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px;">Close</button>
    </div>

    <?php 
    $printSize = $bill['settings']['print_size'] ?? 'A4'; 
    
    $grouped_items = [];
    $cat_totals = [];
    foreach($bill['items'] as $item) {
        $type = strtoupper(trim($item['item_type'] ?? 'HOSPITAL OTHER CHARGES'));
        if (empty($type) || $type == 'UNKNOWN' || $type == 'GENERAL') $type = 'HOSPITAL OTHER CHARGES';
        if ($type == 'IPD') $type = 'BED CHARGES';
        if ($type == 'OPD') $type = 'DOCTOR VISIT CHARGES';
        if ($type == 'LAB') $type = 'LABORATORY / DIAGNOSTIC CHARGES';
        
        if (!isset($grouped_items[$type])) {
            $grouped_items[$type] = [];
            $cat_totals[$type] = 0;
        }
        $grouped_items[$type][] = $item;
        $cat_totals[$type] += $item['amount'];
    }

    function getCatBg($cat) {
        if(strpos($cat, 'BED') !== false) return '#208493'; // teal
        if(strpos($cat, 'DOCTOR') !== false) return '#c85e17'; // orange/brown
        if(strpos($cat, 'NURS') !== false) return '#2e5192'; // dark blue
        if(strpos($cat, 'LAB') !== false) return '#296123'; // green
        if(strpos($cat, 'PHARM') !== false) return '#e48f1c'; // orange
        return '#7b7b7b'; // grey
    }
    ?>

    <?php if ($printSize === 'A4'): ?>
    <!-- A4 LAYOUT -->
    <div class="print-area layout-a4">
        <div class="header">
            <?php if(!empty($bill['settings']['hospital_logo'])): ?>
                <img src="assets/uploads/<?= htmlspecialchars($bill['settings']['hospital_logo']) ?>" alt="Logo" style="position:absolute; left: 15mm;">
            <?php endif; ?>
            <div class="hospital-info text-center">
                <h2><?= htmlspecialchars($bill['settings']['hospital_name'] ?? 'Sankhla Hospital Heart & Trauma Center') ?></h2>
                <p>Powered by Vitaid Health Care Foundation • Reg No. U86100RJ2023NPL086879</p>
                <p><?= htmlspecialchars($bill['settings']['hospital_address'] ?? '') ?> • Ph: <?= htmlspecialchars($bill['settings']['hospital_phone'] ?? '') ?></p>
            </div>
        </div>
        
        <div class="title-strip">CONSOLIDATED MEDICAL BILL</div>

        <table class="info-table">
            <tr>
                <td class="info-label">Patient Name</td>
                <td style="font-weight:bold;"><?= htmlspecialchars(strtoupper($bill['patient_name'] ?? 'Walk-in')) ?></td>
                <td class="info-label">Invoice No.</td>
                <td><?= htmlspecialchars($bill['bill_number']) ?></td>
            </tr>
            <tr>
                <td class="info-label">Age / Gender</td>
                <td><?= htmlspecialchars($bill['age'] ?? '-') ?>Y / <?= htmlspecialchars(strtoupper($bill['gender'] ?? '-')) ?></td>
                <td class="info-label">Bill Date</td>
                <td><?= date('d-M-Y H:i', strtotime($bill['created_at'])) ?></td>
            </tr>
            <tr>
                <td class="info-label">Contact No.</td>
                <td><?= htmlspecialchars($bill['phone'] ?? '-') ?></td>
                <td class="info-label">Type</td>
                <td><?= htmlspecialchars(strtoupper($bill['bill_type'] ?? 'OPD')) ?></td>
            </tr>
            <tr>
                <td class="info-label">Treating Doctor</td>
                <td><?= htmlspecialchars(strtoupper($bill['doctor_name'] ?? $bill['doctor_id'] ?? 'Self')) ?></td>
                <td class="info-label">MR. / UHID No.</td>
                <td><?= htmlspecialchars($bill['mr_number'] ?? '-') ?></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th width="5%" class="text-center">Sr.</th>
                    <th class="text-left" width="45%">Description of Service</th>
                    <th class="text-center" width="8%">Qty</th>
                    <th class="text-right" width="12%">Rate (₹)</th>
                    <th class="text-right" width="10%">Discount</th>
                    <th class="text-right" width="15%">Net Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sr = 1;
                foreach ($grouped_items as $category => $items): 
                    $catColor = getCatBg($category);
                ?>
                <tr>
                    <td colspan="6" class="cat-header" style="background-color: <?= $catColor ?>;">
                        ▶ <?= htmlspecialchars($category) ?>
                    </td>
                </tr>
                <?php foreach ($items as $item): 
                    $discountAmt = ($item['quantity'] * $item['cost']) - $item['amount']; // Back-calculate flat discount
                ?>
                <tr>
                    <td class="text-center"><?= $sr++ ?></td>
                    <td><?= htmlspecialchars($item['service_name']) ?></td>
                    <td class="text-center"><?= rtrim(rtrim(number_format($item['quantity'] ?? 1, 2), '0'), '.') ?></td>
                    <td class="text-right"><?= number_format($item['cost'] ?? ($item['rate'] ?? 0), 2) ?></td>
                    <td class="text-right"><?= $discountAmt > 0 ? number_format($discountAmt, 2) : '-' ?></td>
                    <td class="text-right"><?= number_format($item['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="cat-subtotal-row">
                    <td colspan="5" class="cat-subtotal">Sub-Total — <?= htmlspecialchars($category) ?></td>
                    <td class="text-right" style="border: 2px solid #208493; font-weight:bold;">₹<?= number_format($cat_totals[$category], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="summary-box">
            <tr>
                <th colspan="2" class="text-center" style="font-size:14px;">BILL SUMMARY — CATEGORY-WISE TOTALS</th>
            </tr>
            <tr style="background:#e0edff; font-weight:bold; color:#1f497d;">
                <td width="80%">Category of Charges</td>
                <td class="text-right" width="20%">Amount (₹)</td>
            </tr>
            <?php foreach($cat_totals as $cat => $tot): ?>
            <tr>
                <td><?= htmlspecialchars($cat) ?></td>
                <td class="text-right"><?= number_format($tot, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr>
                <td class="text-right bold" style="color:#1f497d; background:#e0edff;">GROSS TOTAL</td>
                <td class="text-right bold" style="color:#fff; background:#1f497d;">₹<?= number_format($bill['total_amount'] ?? $bill['subtotal'], 2) ?></td>
            </tr>
            <tr>
                <td class="text-right bold" style="color:red; background:#ffebeb;">
                    Less: Discount / Concession
                    <?php if (!empty($bill['discount_type']) && $bill['discount_type'] === 'percent' && !empty($bill['discount_percent'])): ?>
                        <small style="color:#888;"> (<?= number_format($bill['discount_percent'], 1) ?>%)</small>
                    <?php endif; ?>
                </td>
                <td class="text-right bold" style="color:red; background:#ffebeb;"><?= ($bill['discount'] > 0) ? '₹'.number_format($bill['discount'], 2) : 'NIL' ?></td>
            </tr>
            <tr>
                <td class="text-center bold" style="font-size:16px; background:#1f497d; color:#fff; padding:8px;">NET AMOUNT PAYABLE</td>
                <td class="text-right bold" style="font-size:16px; background:#1f497d; color:#fff; padding:8px;">₹<?= number_format($bill['net_amount'] ?? $bill['grand_total'], 2) ?></td>
            </tr>
            <tr>
                <td colspan="2" style="font-size:11px; font-style:italic;">All amounts in Indian Rupees (INR)</td>
            </tr>
        </table>
        
        <div style="font-weight:bold; margin-top:5px; font-size:13px;">
            <?php if (!empty($bill['payment_method']) && $bill['payment_method'] === 'Split'): ?>
                Payment: 
                Cash ₹<?= number_format($bill['payment_mode_cash'] ?? 0, 2) ?>
                + UPI ₹<?= number_format($bill['payment_mode_upi'] ?? 0, 2) ?>
                = ₹<?= number_format(($bill['payment_mode_cash'] ?? 0) + ($bill['payment_mode_upi'] ?? 0), 2) ?>
            <?php else: ?>
                Payment Mode: <?= htmlspecialchars($bill['payment_method'] ?? 'Cash') ?>
            <?php endif; ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            Amount Paid: ₹<?= number_format($bill['paid_amount'] ?? 0, 2) ?> &nbsp;&nbsp;|&nbsp;&nbsp; 
            Balance Due: <span style="color:red;">₹<?= number_format(($bill['net_amount'] ?? $bill['grand_total']) - ($bill['paid_amount'] ?? 0), 2) ?></span>
        </div>

        <div class="footer">
            <div class="signature">Authorized Signature</div>
            
            <div class="terms-box">
                <div style="background:#1f497d; color:#fff; padding:4px 8px; font-weight:bold; margin:-10px -10px 10px -10px;">HOSPITAL DECLARATION</div>
                <p>• This is to certify that the above mentioned patient was treated at Sankhla Hospital Heart & Trauma Center.</p>
                <p>• All charges mentioned above are actual charges incurred during the course of treatment and are correct to the best of our knowledge.</p>
                <p>• The patient has been treated as per the standard medical protocols and the charges are as per the hospital's approved rate list.</p>
                <p>• This consolidated bill has been prepared for the purpose of patient record and/or health insurance reimbursement / TPA claim settlement.</p>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- THERMAL LAYOUT (80mm) -->
    <div class="print-area layout-thermal">
        <div class="header">
            <h2 style="margin:0; font-size:16px;"><?= htmlspecialchars($bill['settings']['hospital_name'] ?? 'Sankhla Hospital') ?></h2>
            <p style="margin:2px 0;"><?= htmlspecialchars($bill['settings']['hospital_address'] ?? '') ?></p>
            <p style="margin:2px 0;">Ph: <?= htmlspecialchars($bill['settings']['hospital_phone'] ?? '') ?></p>
            <h4 style="margin:5px 0 0 0;">TAX INVOICE</h4>
        </div>

        <div style="margin-bottom:5px;">
            <div><span class="bold">Bill No:</span> <?= htmlspecialchars($bill['bill_number']) ?></div>
            <div><span class="bold">Date:</span> <?= date('d/m/Y H:i', strtotime($bill['created_at'])) ?></div>
            <div><span class="bold">Pt Name:</span> <?= htmlspecialchars($bill['patient_name'] ?? 'Walk-in') ?></div>
            <div><span class="bold">Type:</span> <?= htmlspecialchars($bill['bill_type'] ?? 'OPD') ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-left" style="width:50%">Item</th>
                    <th class="text-center" style="width:20%">Qty</th>
                    <th class="text-right" style="width:30%">Amt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bill['items'] as $item): ?>
                <tr>
                    <td colspan="3">
                        <div style="border-bottom: 1px dashed #ccc; padding: 4px 0;">
                            <div style="font-weight:bold;"><?= htmlspecialchars($item['service_name']) ?></div>
                            <div style="display:flex; justify-content:space-between;">
                                <span><?= rtrim(rtrim(number_format($item['quantity'] ?? 1, 2), '0'), '.') ?> x <?= number_format($item['cost'] ?? ($item['rate'] ?? 0), 2) ?></span>
                                <span><?= number_format($item['amount'], 2) ?></span>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary">
            <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                <span>Subtotal:</span>
                <span><?= number_format($bill['total_amount'] ?? $bill['subtotal'], 2) ?></span>
            </div>
            <?php if($bill['discount'] > 0): ?>
            <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                <span>Discount:</span>
                <span>- <?= number_format($bill['discount'], 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="grand-total" style="display:flex; justify-content:space-between; margin-bottom:3px;">
                <span>Total:</span>
                <span><?= number_format($bill['net_amount'] ?? $bill['grand_total'], 2) ?></span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                <span>Paid:</span>
                <span><?= number_format($bill['paid_amount'] ?? 0, 2) ?></span>
            </div>
        </div>

        <div class="text-center" style="margin-top:10px; font-size:10px;">
            Thank you. Visit again.
        </div>
    </div>
    <?php endif; ?>

</body>
</html>
