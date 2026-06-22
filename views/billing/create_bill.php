<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - <?= htmlspecialchars($settings['hospital_name'] ?? 'Sankhla Hospital') ?></title>
    <link rel="stylesheet" href="assets/css/billing.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?= htmlspecialchars($settings['primary_color'] ?? '#007bff') ?>;
            --secondary-color: <?= htmlspecialchars($settings['secondary_color'] ?? '#6c757d') ?>;
        }

        /* --- Discount Section Styles --- */
        .discount-section {
            background: #fff8e1;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 10px;
            margin: 10px 0;
        }
        .discount-toggle-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }
        .discount-toggle-row label {
            font-size: 12px;
            font-weight: 600;
            color: #555;
            margin: 0;
        }
        .disc-type-btn {
            padding: 3px 12px;
            border: 1.5px solid #ccc;
            border-radius: 20px;
            background: #fff;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
            color: #555;
        }
        .disc-type-btn.active-disc {
            background: #007bff;
            border-color: #007bff;
            color: #fff;
        }
        .discount-input-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        #global_discount_val {
            width: 90px;
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        .disc-label-badge {
            font-size: 11px;
            color: #888;
        }

        /* --- Split Payment Styles --- */
        #split_payment_section {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 10px;
            margin-top: 8px;
            display: none; /* Hidden by default */
        }
        .split-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        .split-row label {
            font-size: 12px;
            font-weight: 600;
            width: 80px;
            flex-shrink: 0;
        }
        .split-row input {
            flex: 1;
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 13px;
        }
        #split_error_msg {
            font-size: 11px;
            color: red;
            font-weight: 600;
            display: none;
            margin-top: 4px;
        }
        .split-balance-info {
            font-size: 12px;
            color: #2e7d32;
            font-weight: 600;
            margin-top: 4px;
        }

        /* --- Edit Mode Banner --- */
        #edit_mode_banner {
            background: linear-gradient(135deg, #ff9800, #e65100);
            color: #fff;
            padding: 8px 14px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 13px;
            font-weight: 600;
            display: none;
        }
        #edit_mode_banner i { margin-right: 6px; }
    </style>
</head>
<body class="marg-theme">
    <div class="toast-container" id="toastContainer"></div>

    <form id="billingForm">
        <!-- HEADER SECTION -->
        <header class="bill-header">
            <div class="hosp-info">
                <?php if(!empty($settings['hospital_logo'])): ?>
                    <img src="assets/uploads/<?= htmlspecialchars($settings['hospital_logo']) ?>" alt="Logo" class="hosp-logo">
                <?php endif; ?>
                <div>
                    <h2><?= htmlspecialchars($settings['hospital_name'] ?? 'Hospital Name') ?></h2>
                    <p><?= htmlspecialchars($settings['hospital_address'] ?? '') ?> | Ph: <?= htmlspecialchars($settings['hospital_phone'] ?? '') ?></p>
                </div>
            </div>
            
            <div class="bill-meta">
                <div class="form-group row">
                    <label>Bill No:</label>
                    <input type="text" name="bill_number" id="bill_number" value="<?= htmlspecialchars($billNumber ?? '') ?>" readonly class="highlight-input">
                </div>
                <div class="form-group row">
                    <label>Date:</label>
                    <input type="date" name="bill_date" id="bill_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group row">
                    <label>Type:</label>
                    <select name="bill_type" id="bill_type" required>
                        <option value="OPD">OPD</option>
                        <option value="IPD">IPD</option>
                        <option value="Pharmacy">Pharmacy</option>
                        <option value="Lab">Lab / Pathology</option>
                    </select>
                </div>
            </div>
        </header>

        <div class="main-workspace">
            <!-- LEFT PANEL: PATIENT INFO -->
            <aside class="patient-panel" style="width: 280px; min-width: 260px; max-width: 280px; flex: 0 0 280px; overflow-y: auto;">
                <!-- Edit mode banner -->
                <div id="edit_mode_banner">
                    <i class="fas fa-edit"></i> Edit Mode — Bill <span id="edit_bill_no_display"></span>
                </div>

                <div class="panel-heading">
                    <h3>Patient Details</h3>
                    <button type="button" class="btn-sm btn-outline" id="btnNewPatient" title="New Patient"><i class="fas fa-plus"></i> New</button>
                </div>
                <div class="panel-body">
                    
                    <input type="hidden" id="hpid"    name="patient_id"     value="<?= isset($prefill_patient) ? $prefill_patient['id'] : '' ?>" required>
                    <input type="hidden" id="hpname"  name="patient_name"   value="">
                    <input type="hidden" id="hpage"   name="patient_age"    value="">
                    <input type="hidden" id="hpgen"   name="patient_gender" value="">
                    <input type="hidden" id="hpph"    name="patient_phone"  value="">
                    <input type="hidden" id="hpmr"    name="patient_mr"     value="">
                    <input type="hidden" id="hpbg"    name="patient_blood"  value="">
                    <input type="hidden" id="hpaddr"  name="patient_addr"   value="">
                    <!-- Edit mode ke liye hidden field -->
                    <input type="hidden" id="edit_bill_id" name="edit_bill_id" value="">

                    <div class="form-group" style="position:relative;">
                        <label>Search Patient <span class="hotkey">Alt+P</span></label>
                        <input type="text" id="patient_search_input" placeholder="Search by Name / Phone / MR No" autocomplete="off" style="width:100%">
                        <div id="patient_dropdown" class="search-dropdown" style="display:none"></div>
                    </div>

                    <div id="patient_card" style="display:none; background:#e8f4fd;border:2px solid #007bff; border-radius:8px;padding:10px;margin-top:8px;font-size:13px">
                    </div>
                    <a href="#" id="change_patient_btn" style="display:none;font-size:12px;color:red;margin-top:5px;display:inline-block;text-decoration:none;">✕ Change Patient</a>

                    <div class="form-group" style="margin-top:15px;">
                        <label>Doctor Name <span class="hotkey">Alt+D</span></label>
                        <select name="doctor_id" id="doctor_id">
                            <option value="">-- Select Doctor --</option>
                            <?php if(isset($doctors) && is_array($doctors)): ?>
                                <?php foreach($doctors as $doc): ?>
                                    <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ward/Room (If IPD)</label>
                        <input type="text" name="ward_room" id="ward_room">
                    </div>
                </div>
            </aside>

            <!-- CENTER PANEL: ITEMS GRID -->
            <section class="items-grid-section">
                <table class="marg-grid" id="itemsTable">
                    <thead>
                        <tr>
                            <th width="5%">Sr</th>
                            <th width="35%">Item / Service Name</th>
                            <th width="10%">Qty</th>
                            <th width="12%">Rate</th>
                            <?php if(($settings['show_discount_col'] ?? '1') == '1'): ?>
                            <th width="10%">Disc%</th>
                            <?php endif; ?>
                            <th width="15%">Amount</th>
                            <th width="5%">Act</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <!-- Rows injected via JS -->
                    </tbody>
                </table>
                <div class="grid-actions">
                    <span class="hint">Use <strong>Tab</strong> to navigate. A new row will appear automatically.</span>
                </div>
            </section>

            <!-- RIGHT PANEL: PAYMENT SUMMARY -->
            <aside class="summary-panel sticky">
                <div class="panel-heading">
                    <h3>Billing Summary</h3>
                </div>
                <div class="panel-body">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <input type="text" name="subtotal" id="subtotal" value="0.00" readonly class="num-input">
                    </div>

                    <!-- FEATURE 1: Discount Section (% ya ₹) -->
                    <div class="discount-section">
                        <div class="discount-toggle-row">
                            <label>Discount:</label>
                            <button type="button" class="disc-type-btn active-disc" id="discTypePercent" onclick="setDiscountType('%')">%</button>
                            <button type="button" class="disc-type-btn" id="discTypeAmount" onclick="setDiscountType('₹')">₹</button>
                            <span class="disc-label-badge" id="discModeLabelSmall">Percent mode</span>
                        </div>
                        <div class="discount-input-row">
                            <input type="number" id="global_discount_val" name="global_discount_val" 
                                   value="0" min="0" step="any" placeholder="0" 
                                   oninput="applyGlobalDiscount()">
                            <span id="discSuffix" style="font-size:13px;font-weight:600;color:#007bff;">%</span>
                        </div>
                        <input type="hidden" id="discount_type_hidden" name="discount_type" value="percent">
                    </div>

                    <div class="summary-row text-danger">
                        <span>Discount</span>
                        <input type="text" name="total_discount" id="total_discount" value="0.00" readonly class="num-input">
                    </div>

                    <hr>
                    <div class="summary-row grand-total-row">
                        <span>Grand Total</span>
                        <input type="text" name="grand_total" id="grand_total" value="0.00" readonly class="num-input">
                    </div>
                    
                    <div class="summary-row mt-3">
                        <span>Amount Paid</span>
                        <input type="number" step="0.01" name="paid_amount" id="paid_amount" value="0.00" class="num-input highlight-input editable">
                    </div>
                    <div class="summary-row text-warning">
                        <span>Balance Due</span>
                        <input type="text" name="balance_due" id="balance_due" value="0.00" readonly class="num-input">
                    </div>
                    
                    <!-- FEATURE 3: Payment Mode with Split Option -->
                    <div class="form-group mt-3">
                        <label>Payment Mode</label>
                        <select name="payment_mode" id="payment_mode" class="highlight-select" onchange="handlePaymentModeChange()">
                            <option value="Cash" <?= ($settings['default_payment_mode'] ?? '') == 'Cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="Card" <?= ($settings['default_payment_mode'] ?? '') == 'Card' ? 'selected' : '' ?>>Card</option>
                            <option value="UPI" <?= ($settings['default_payment_mode'] ?? '') == 'UPI' ? 'selected' : '' ?>>UPI</option>
                            <option value="Insurance" <?= ($settings['default_payment_mode'] ?? '') == 'Insurance' ? 'selected' : '' ?>>Insurance</option>
                            <option value="Split">💰 Cash + UPI (Split)</option>
                        </select>
                    </div>

                    <!-- Split Payment Section (conditional) -->
                    <div id="split_payment_section">
                        <div class="split-row">
                            <label>💵 Cash:</label>
                            <input type="number" id="split_cash" name="split_cash" min="0" step="0.01" 
                                   value="0" placeholder="Cash amount" oninput="validateSplitPayment()">
                        </div>
                        <div class="split-row">
                            <label>📱 UPI:</label>
                            <input type="number" id="split_upi" name="split_upi" min="0" step="0.01" 
                                   value="0" placeholder="UPI amount" oninput="validateSplitPayment()">
                        </div>
                        <div id="split_error_msg">
                            <i class="fas fa-exclamation-triangle"></i> Cash + UPI total Grand Total se match nahi kar raha!
                        </div>
                        <div class="split-balance-info" id="split_balance_info"></div>
                    </div>

                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary w-100" id="btnSave" title="F2"><i class="fas fa-save"></i> Save Bill (F2)</button>
                        <button type="button" class="btn btn-success w-100" id="btnSavePrint" title="F5"><i class="fas fa-print"></i> Save & Print (F5)</button>
                        <button type="button" class="btn btn-info w-100" id="btnSavePdf" title="F6"><i class="fas fa-file-pdf"></i> Save & PDF (F6)</button>
                        <button type="button" class="btn btn-danger w-100" id="btnCancel" title="Esc"><i class="fas fa-times"></i> Cancel (Esc)</button>
                    </div>
                </div>
            </aside>
        </div>
    </form>

    <!-- Passing PHP settings to JS -->
    <script>
        window.billSettings = <?= json_encode([
            'show_discount_col' => $settings['show_discount_col'] ?? '1',
            'show_tax_col' => '0',
            'enable_gst' => '0'
        ]) ?>;
        window.prefillPatient = <?= isset($prefill_patient) && $prefill_patient ? json_encode($prefill_patient) : 'null' ?>;
        // Edit mode data (Feature 2)
        window.editBillData = <?= isset($editBillData) && $editBillData ? json_encode($editBillData) : 'null' ?>;
    </script>
    <script src="assets/js/billing.js"></script>
</body>
</html>
