<?php
// file: /bill_print.php
require_once 'config/db.php';
require_once 'controllers/TemplateController.php';
require_once 'controllers/BillController.php';

$bill_id = $_GET['id'] ?? null;
if (!$bill_id) {
    die("Invalid Bill ID");
}

$templateCtrl = new TemplateController($pdo);
$billCtrl = new BillController($pdo);

$settings = $templateCtrl->getSettings(1); // Hospital ID 1
$billResult = $billCtrl->getBillData($bill_id);

if (!$billResult || !$billResult['bill']) {
    die("Bill not found.");
}

$bill = $billResult['bill'];
$grouped_items = $billResult['groups'];
$partitions = $billCtrl->getPartitions();

// ── FIX 1: Robust bill date — check multiple column names ────
$_rawDate = $bill['bill_date']
         ?? $bill['created_at']
         ?? $bill['date']
         ?? null;
// Fallback to today if null or empty
$_billTimestamp = (!empty($_rawDate)) ? strtotime($_rawDate) : time();
// If strtotime() returned false (bad string), fall back to now
if ($_billTimestamp === false || $_billTimestamp <= 0) $_billTimestamp = time();
$bill['_display_date'] = date('d-M-Y', $_billTimestamp);
$bill['_display_time'] = date('h:i A', $_billTimestamp);

// PDF Download Logic Using mPDF
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    // Only executed if mpdf is installed via composer
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        try {
            ob_start();
            renderBillHTML(true); // render without UI chrome
            $html = ob_get_clean();
            
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($html);
            $mpdf->Output("Bill_".$bill['bill_number'].".pdf", \Mpdf\Output\Destination::DOWNLOAD);
            exit;
        } catch (\Mpdf\MpdfException $e) {
            die("PDF Generation Error: " . $e->getMessage());
        }
    } else {
        die("mPDF library not found. Run 'composer require mpdf/mpdf' in project root.");
    }
}

// Inline Helper to separate HTML render logic for PDF vs Browser
function renderBillHTML($is_pdf = false) {
    global $settings, $bill, $grouped_items, $partitions;
    
    // Fallback template defaults
    $font_family = $settings['font_family'] ?? 'Arial, sans-serif';
    $primary_color = $settings['primary_color'] ?? '#0056b3';
    $secondary_color = $settings['secondary_color'] ?? '#6c757d';
    $logo_path = $settings['logo_path'] ?? '';
    $header_text = $settings['header_text'] ?? '';
    $footer_text = $settings['footer_text'] ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Invoice - <?php echo htmlspecialchars($bill['bill_number']); ?></title>
        <style>
            body {
                font-family: <?php echo htmlspecialchars($font_family); ?>;
                color: #333;
                background: #fff;
                margin: 0;
                padding: <?php echo $is_pdf ? '0' : '20px'; ?>;
            }
            .bill-container {
                max-width: 800px;
                margin: 0 auto;
                background: #fff;
            }
            .header-sec {
                display: flex;
                align-items: center;
                border-bottom: 2px solid <?php echo htmlspecialchars($primary_color); ?>;
                padding-bottom: 15px;
                margin-bottom: 20px;
            }
            .header-logo {
                max-width: 150px;
                max-height: 80px;
                margin-right: 20px;
                <?php if ($is_pdf) echo 'float: left;'; ?>
            }
            .header-content {
                flex-grow: 1;
                <?php if ($is_pdf) echo 'float: left; width: 70%; padding-left: 20px;'; ?>
            }
            /* ── FIX 2: Professional Patient Header ── */
            .patient-header-block {
                border: 2px solid <?php echo htmlspecialchars($primary_color); ?>;
                border-radius: 6px;
                overflow: hidden;
                margin-bottom: 18px;
                font-family: <?php echo htmlspecialchars($font_family); ?>;
            }
            .ph-top {
                background: <?php echo htmlspecialchars($primary_color); ?>;
                color: #fff;
                padding: 8px 14px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .ph-patient-name {
                font-size: 18px;
                font-weight: 700;
                letter-spacing: 0.5px;
                text-transform: uppercase;
            }
            .ph-invoice-badge {
                background: rgba(255,255,255,0.22);
                border: 1px solid rgba(255,255,255,0.5);
                border-radius: 4px;
                padding: 4px 12px;
                font-size: 13px;
                font-weight: 700;
                letter-spacing: 0.5px;
                white-space: nowrap;
            }
            .ph-grid {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr 1fr;
                background: #f8fafc;
                border-top: 1px solid #dce3ea;
            }
            .ph-cell {
                padding: 7px 12px;
                border-right: 1px solid #dce3ea;
                border-bottom: 1px solid #dce3ea;
            }
            .ph-cell:last-child { border-right: none; }
            .ph-cell .ph-key {
                font-size: 10px;
                text-transform: uppercase;
                font-weight: 700;
                color: #7a8595;
                letter-spacing: 0.4px;
                margin-bottom: 2px;
            }
            .ph-cell .ph-val {
                font-size: 13px;
                font-weight: 600;
                color: #1a2332;
            }
            .ph-cell .ph-val.highlight {
                color: <?php echo htmlspecialchars($primary_color); ?>;
                font-size: 14px;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            .items-table th {
                background-color: <?php echo htmlspecialchars($primary_color); ?>;
                color: #fff;
                padding: 10px;
                text-align: left;
                border: 1px solid #ccc;
            }
            .items-table td {
                padding: 8px 10px;
                border: 1px solid #ccc;
            }
            .partition-heading td {
                background-color: <?php echo htmlspecialchars($secondary_color); ?>;
                color: #fff;
                font-size: 14px;
            }
            .partition-subtotal {
                background-color: #f9f9f9;
                font-weight: bold;
                text-align: right;
            }
            .grand-total {
                background-color: <?php echo htmlspecialchars($primary_color); ?>;
                color: #fff;
                font-weight: bold;
                text-align: right;
            }
            .footer-sec {
                border-top: 1px dashed <?php echo htmlspecialchars($secondary_color); ?>;
                padding-top: 15px;
                text-align: center;
                font-size: 13px;
                margin-top: 40px;
            }
            
            <?php if (!$is_pdf && !empty($settings['watermark_path']) && $settings['show_watermark'] == 1): ?>
            .bill-container::before {
                content: "";
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background-image: url('<?php echo htmlspecialchars($settings['watermark_path']); ?>');
                background-repeat: no-repeat;
                background-position: center;
                background-size: 50%;
                opacity: 0.1;
                z-index: -1;
                pointer-events: none;
            }
            <?php endif; ?>

            .ui-chrome {
                background: #f1f3f5;
                padding: 15px;
                text-align: right;
                border-bottom: 1px solid #ccc;
                margin-bottom: 20px;
            }
            .btn {
                padding: 10px 20px;
                color: #fff;
                text-decoration: none;
                border-radius: 4px;
                margin-left: 10px;
                font-weight: bold;
                border: none;
                cursor: pointer;
            }
            .btn-print { background: #007bff; }
            .btn-pdf { background: #dc3545; }
            .btn-back { background: #6c757d; }
            
            @media print {
                .ui-chrome { display: none !important; }
                body { padding: 0; }
                .bill-container { width: 100%; max-width: none; }
            }
        </style>
    </head>
    <body>
        <?php if (!$is_pdf): ?>
        <div class="ui-chrome no-print">
            <button onclick="window.print()" class="btn btn-print">🖨 Print Bill</button>
            <a href="?id=<?php echo $bill['id']; ?>&download=pdf" class="btn btn-pdf">📄 Download PDF</a>
            <a href="billing.php" class="btn btn-back">⬅ Back</a>
        </div>
        <?php endif; ?>

        <div class="bill-container">
            <!-- Header -->
            <div class="header-sec">
                <?php if ($logo_path): ?>
                    <img src="<?php echo htmlspecialchars($logo_path); ?>" class="header-logo" alt="Logo">
                <?php endif; ?>
                <div class="header-content">
                    <?php echo $header_text; ?>
                </div>
                <div style="clear: both;"></div>
            </div>

            <!-- FIX 2: Professional Patient Info Header -->
            <div class="patient-header-block">
                <!-- Top bar: Patient name (left) + Invoice badge (right) -->
                <div class="ph-top">
                    <div class="ph-patient-name">
                        <?php echo htmlspecialchars($bill['full_name'] ?? 'Unknown Patient'); ?>
                    </div>
                    <div class="ph-invoice-badge">
                        📄 <?php echo htmlspecialchars($bill['bill_number']); ?>
                    </div>
                </div>
                <!-- 4-column info grid -->
                <div class="ph-grid">
                    <div class="ph-cell">
                        <div class="ph-key">UHID / MR No</div>
                        <div class="ph-val highlight"><?php echo htmlspecialchars($bill['mr_number'] ?? '—'); ?></div>
                    </div>
                    <div class="ph-cell">
                        <div class="ph-key">Age / Gender</div>
                        <div class="ph-val"><?php echo htmlspecialchars($bill['age'] ?? '—') . ' Yr / ' . strtoupper(htmlspecialchars($bill['gender'] ?? '—')); ?></div>
                    </div>
                    <div class="ph-cell">
                        <div class="ph-key">Contact</div>
                        <div class="ph-val"><?php echo htmlspecialchars($bill['phone'] ?? '—'); ?></div>
                    </div>
                    <div class="ph-cell">
                        <div class="ph-key">Bill Type</div>
                        <div class="ph-val"><?php echo htmlspecialchars($bill['bill_type'] ?? $bill['payment_status'] ?? 'OPD'); ?></div>
                    </div>
                    <div class="ph-cell">
                        <div class="ph-key">Bill Date</div>
                        <div class="ph-val"><?php echo $bill['_display_date']; ?></div>
                    </div>
                    <div class="ph-cell">
                        <div class="ph-key">Bill Time</div>
                        <div class="ph-val"><?php echo $bill['_display_time']; ?></div>
                    </div>
                    <div class="ph-cell">
                        <div class="ph-key">Payment Mode</div>
                        <div class="ph-val"><?php echo htmlspecialchars($bill['payment_method'] ?? '—'); ?></div>
                    </div>
                    <div class="ph-cell">
                        <div class="ph-key">Payment Status</div>
                        <div class="ph-val"><?php echo htmlspecialchars($bill['payment_status'] ?? '—'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Partitioned Grid Phase 3 -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th width="5%">Sr.</th>
                        <th width="55%">Description of Service / Item</th>
                        <th width="10%">Qty</th>
                        <th width="15%">Rate</th>
                        <th width="15%">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grandTotal = 0;
                    $serial = 1;

                    // Display matched partitions ordered by DB sort_order
                    foreach ($partitions as $part) {
                        $key = $part['partition_key'];
                        $label = $part['label'];
                        $showSubtotal = $part['sub_total_visible'];

                        if (isset($grouped_items[$key]) && count($grouped_items[$key]['items']) > 0) {
                            $group = $grouped_items[$key];
                            
                            // Partition Heading
                            echo '<tr class="partition-heading">';
                            echo '<td colspan="5"><strong>' . htmlspecialchars($label) . '</strong></td>';
                            echo '</tr>';

                            // Items
                            foreach ($group['items'] as $item) {
                                echo '<tr>';
                                echo '<td>' . $serial++ . '</td>';
                                echo '<td>' . strtoupper(htmlspecialchars($item['service_name'])) . '</td>';
                                echo '<td>' . htmlspecialchars($item['quantity']) . '</td>';
                                echo '<td>' . number_format($item['cost'], 2) . '</td>';
                                echo '<td>' . number_format($item['amount'], 2) . '</td>';
                                echo '</tr>';
                            }

                            // Subtotal
                            if ($showSubtotal) {
                                echo '<tr class="partition-subtotal">';
                                echo '<td colspan="4">SUB-TOTAL — ' . htmlspecialchars($label) . '</td>';
                                echo '<td>₹' . number_format($group['subtotal'], 2) . '</td>';
                                echo '</tr>';
                            }
                            
                            $grandTotal += $group['subtotal'];
                        }
                    }

                    // Display 'OTHER' if any items did not map to active partitions
                    if (isset($grouped_items['OTHER']) && count($grouped_items['OTHER']['items']) > 0) {
                        $group = $grouped_items['OTHER'];
                        echo '<tr class="partition-heading">';
                        echo '<td colspan="5"><strong>HOSPITAL OTHER CHARGES</strong></td>';
                        echo '</tr>';

                        foreach ($group['items'] as $item) {
                            echo '<tr>';
                            echo '<td>' . $serial++ . '</td>';
                            echo '<td>' . strtoupper(htmlspecialchars($item['service_name'])) . '</td>';
                            echo '<td>' . htmlspecialchars($item['quantity']) . '</td>';
                            echo '<td>' . number_format($item['cost'], 2) . '</td>';
                            echo '<td>' . number_format($item['amount'], 2) . '</td>';
                            echo '</tr>';
                        }
                        echo '<tr class="partition-subtotal">';
                        echo '<td colspan="4">SUB-TOTAL — HOSPITAL OTHER CHARGES</td>';
                        echo '<td>₹' . number_format($group['subtotal'], 2) . '</td>';
                        echo '</tr>';
                        
                        $grandTotal += $group['subtotal'];
                    }
                    ?>
                    
                    <!-- Taxes and Discount (if applicable) -->
                    <?php if ($bill['discount'] > 0): ?>
                    <tr class="partition-subtotal" style="color:#d9534f;">
                        <td colspan="4">DISCOUNT</td>
                        <td>-₹<?php echo number_format($bill['discount'], 2); ?></td>
                    </tr>
                    <?php endif; ?>

                    <tr class="grand-total">
                        <td colspan="4">NET PAYABLE AMOUNT</td>
                        <td>₹<?php echo number_format($bill['net_amount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Footer -->
            <div class="footer-sec">
                <?php echo $footer_text; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
}

// Call the renderer for web preview
renderBillHTML(false);
?>