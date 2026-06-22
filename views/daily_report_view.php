<?php
/**
 * views/daily_report_view.php
 * UI for Daily Collection Report — all sections injected here.
 * Variables available from daily_report.php:
 *   $report_date, $time_from, $time_to, $opening_bal,
 *   $bills, $total_collection, $cash_total, $upi_total, $other_total,
 *   $lab_collection, $v_receipts, $v_payments, $net_balance,
 *   $is_locked, $shift_label, $display_date, $db_error
 */
$cur  = '₹';
$fmt  = fn($n) => number_format((float)$n, 2);
?>

<?php /* ═══════════ PRINT-ONLY CSS ═══════════ */ ?>
<style>
/* ── Screen styles ─────────────────────────────── */
.dr-card { border-radius:12px; padding:18px 22px; color:#fff; box-shadow:0 4px 16px rgba(0,0,0,.15); margin-bottom:5px; }
.dr-card .lbl  { font-size:13px; opacity:.85; text-transform:uppercase; letter-spacing:.5px; }
.dr-card .amt  { font-size:28px; font-weight:700; margin-top:4px; }
.dr-card .sub  { font-size:12px; opacity:.75; margin-top:2px; }
.card-green   { background:linear-gradient(135deg,#2e7d32,#43a047); }
.card-blue    { background:linear-gradient(135deg,#1565c0,#1e88e5); }
.card-purple  { background:linear-gradient(135deg,#6a1b9a,#8e24aa); }
.card-red     { background:linear-gradient(135deg,#b71c1c,#e53935); }
.card-teal    { background:linear-gradient(135deg,#00695c,#00897b); }
.shift-badge  { background:#fff3cd; color:#856404; border:1px solid #ffc107; border-radius:20px; padding:4px 14px; font-size:13px; font-weight:600; display:inline-block; }
.lock-badge   { background:#ffebee; color:#b71c1c; border:1px solid #ef9a9a; border-radius:20px; padding:4px 14px; font-size:13px; font-weight:600; }
.tbl-bills thead th { background:#1a237e; color:#fff; font-size:13px; }
.tbl-bills tbody tr:hover { background:#e8eaf6; }
.vchr-receipt { background:#e8f5e9 !important; }
.vchr-payment { background:#ffebee !important; }
/* ── print-color-adjust (standard + vendor) ── */
.tbl-bills thead th, .vchr-receipt, .vchr-payment {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.summary-calc { background:#f8f9fa; border:2px solid #dee2e6; border-radius:10px; padding:20px; }
.summary-calc .row-item { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed #dee2e6; font-size:15px; }
.summary-calc .row-total{ display:flex; justify-content:space-between; padding:10px 0; font-size:18px; font-weight:700; }
.text-pos { color:#2e7d32; }
.text-neg { color:#c62828; }
.quick-btn { border-radius:20px; font-size:13px; }
#voucherPanel { display:none; }

/* ── Print styles ──────────────────────────────── */
@media print {
    @page { size: A4 portrait; margin: 10mm; }
    .no-print, #sidebar-wrapper, nav.navbar,
    .filter-bar, .action-buttons, #voucherPanel .voucher-form-section,
    .voucher-actions, .nav-tabs, #voucherPanel .tab-pane > .d-flex { display:none !important; }
    body { background:#fff !important; font-size:11pt; }
    #page-content-wrapper { margin:0 !important; padding:0 !important; }
    .hospital-header { border-bottom:2px solid #000; margin-bottom:8px; }
    .print-section { display:block !important; }
    .dr-card { color:#000 !important; background:#f5f5f5 !important; border:1px solid #ccc; box-shadow:none; }
    .dr-card .amt { font-size:16pt; }
    .tbl-bills thead th { background:#ccc !important; color:#000 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .vchr-receipt { background:#d4edda !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .vchr-payment { background:#f8d7da !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    #voucherPanel { display:block !important; }
    .print-signature-row { margin-top:30px; display:flex !important; justify-content:space-between; }
}
</style>

<div class="container-fluid px-4 py-3">

<?php if (!empty($db_error)): ?>
<div class="alert alert-warning">⚠️ DB Notice: <?= htmlspecialchars($db_error) ?>
    — <a href="daily_report_migrate.php">Run Migration</a></div>
<?php endif; ?>

<?php /* ═══════════ HEADER ROW ═══════════ */ ?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3 no-print">
    <div>
        <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-bar text-primary me-2"></i>Daily Collection Report</h4>
        <small class="text-muted"><?= $display_date ?> &nbsp;|&nbsp;
            <span class="shift-badge"><i class="fas fa-clock me-1"></i><?= $shift_label ?></span>
            <?php if ($is_locked): ?>
                <span class="lock-badge ms-2">🔒 Report Locked</span>
            <?php endif; ?>
        </small>
    </div>
    <div class="action-buttons d-flex gap-2 flex-wrap mt-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="toggleVoucher()"><i class="fas fa-receipt me-1"></i>Vouchers</button>
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print Report</button>
        <button class="btn btn-success btn-sm" onclick="exportCSV()"><i class="fas fa-file-excel me-1"></i>Export CSV</button>
        <?php if (in_array($_SESSION['role']??'', ['Admin','Accountant'])): ?>
        <button id="lockBtn" class="btn btn-<?= $is_locked ? 'warning' : 'danger' ?> btn-sm" onclick="toggleLock()">
            <?= $is_locked ? '🔓 Unlock Report' : '🔒 Lock Report' ?>
        </button>
        <?php endif; ?>
        <a href="daily_report_migrate.php" class="btn btn-outline-dark btn-sm" title="Setup DB tables"><i class="fas fa-database me-1"></i>Setup DB</a>
    </div>
</div>

<?php /* ═══════════ FILTER BAR ═══════════ */ ?>
<div class="card mb-3 no-print filter-bar">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1 small fw-semibold">📅 Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= $report_date ?>" id="filterDate">
            </div>
            <div class="col-auto">
                <label class="form-label mb-1 small fw-semibold">⏰ From</label>
                <input type="time" name="time_from" class="form-control form-control-sm" value="<?= $time_from ?>" id="filterFrom">
            </div>
            <div class="col-auto">
                <label class="form-label mb-1 small fw-semibold">⏰ To</label>
                <input type="time" name="time_to" class="form-control form-control-sm" value="<?= $time_to ?>" id="filterTo">
            </div>
            <div class="col-auto">
                <label class="form-label mb-1 small fw-semibold">💰 Opening Balance (₹)</label>
                <input type="number" name="opening_bal" class="form-control form-control-sm" value="<?= $opening_bal ?>" min="0" step="0.01" id="openingBal">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Generate Report</button>
                <a href="daily_report.php" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php /* ═══════════ PRINT HEADER (only shown on print) ═══════════ */ ?>
<div class="print-section" style="display:none;">
    <div class="text-center mb-2">
        <?php if (defined('APP_LOGO')): ?><img src="<?= APP_LOGO ?>" height="50" alt="Logo" style="margin-bottom:4px"><br><?php endif; ?>
        <strong style="font-size:16pt"><?= defined('APP_NAME') ? APP_NAME : 'Hospital' ?></strong><br>
        <span style="font-size:10pt"><?= defined('APP_ADDRESS') ? APP_ADDRESS : '' ?></span><br>
        <hr style="border-top:2px solid #000;margin:6px 0">
        <strong>Daily Collection Report — <?= $display_date ?></strong><br>
        <small>Shift: <?= $shift_label ?> &nbsp;|&nbsp; Time: <?= $time_from ?> – <?= $time_to ?></small>
    </div>
</div>

<?php /* ═══════════ SUMMARY CARDS ═══════════ */ ?>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dr-card card-green">
            <div class="lbl"><i class="fas fa-coins me-1"></i>Total Collection</div>
            <div class="amt"><?= $cur . $fmt($total_collection) ?></div>
            <div class="sub"><?= count($bills) ?> bill(s)</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dr-card card-blue">
            <div class="lbl"><i class="fas fa-money-bill-wave me-1"></i>Cash</div>
            <div class="amt"><?= $cur . $fmt($cash_total) ?></div>
            <div class="sub">Cash payments only</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dr-card card-purple">
            <div class="lbl"><i class="fas fa-mobile-alt me-1"></i>UPI</div>
            <div class="amt"><?= $cur . $fmt($upi_total) ?></div>
            <div class="sub">UPI payments only</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dr-card" style="background:linear-gradient(135deg,#0277bd,#0288d1)">
            <div class="lbl"><i class="fas fa-credit-card me-1"></i>Card / Other</div>
            <div class="amt"><?= $cur . $fmt($card_total + $other_total) ?></div>
            <div class="sub">Card + others</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dr-card card-red">
            <div class="lbl"><i class="fas fa-flask me-1"></i>Lab Collection</div>
            <div class="amt"><?= $cur . $fmt($lab_collection) ?></div>
            <div class="sub">Lab services only</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dr-card card-teal">
            <div class="lbl"><i class="fas fa-balance-scale me-1"></i>Net Balance</div>
            <div class="amt" id="netBalCard"><?= $cur . $fmt($net_balance) ?></div>
            <div class="sub">After vouchers</div>
        </div>
    </div>
</div>
<?php if ($total_pending_balance > 0): ?>
<div class="alert alert-warning py-2 mb-3">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Pending / Partial Balance Outstanding: <?= $cur . $fmt($total_pending_balance) ?></strong>
    &nbsp;— Bills highlighted in amber below have unpaid balances.
</div>
<?php endif; ?>

<?php /* ═══════════ BILLS TABLE ═══════════ */ ?>
<div class="card mb-3">
    <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
        <span><i class="fas fa-file-invoice me-1"></i> Bill Details — <?= $display_date ?> (<?= $time_from ?> to <?= $time_to ?>)</span>
        <span class="badge bg-light text-dark"><?= count($bills) ?> bills</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover mb-0 tbl-bills" id="billsTable">
                <thead>
                    <tr>
                        <th>#</th><th>Bill No</th><th>Time</th><th>Patient</th>
                        <th>Amount</th><th>Paid / Due</th><th>Mode</th><th class="no-print" style="min-width:90px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($bills)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No bills found for this date/time range.</td></tr>
                <?php else: $i = 1; foreach ($bills as $b):
                    $isPending = in_array($b['payment_status'] ?? '', ['Pending','Partial']);
                    $balDue    = (float)($b['bal_due'] ?? 0);
                ?>
                    <?php
                    $payStatus = strtolower($b['payment_status'] ?? 'paid');
                    $rowClass  = ($payStatus === 'partial') ? 'table-warning'
                               : (($payStatus === 'pending' || $payStatus === 'due') ? 'table-danger' : '');
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td><?= $i++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($b['bill_number']) ?></strong>
                            <br><small class="badge bg-<?= ($payStatus==='paid')?'success':(($payStatus==='partial')?'warning text-dark':'danger') ?>">
                                <?= ucfirst($payStatus) ?>
                            </small>
                        </td>
                        <td><?= date('H:i', strtotime($b['bill_date'])) ?></td>
                        <td><?= htmlspecialchars($b['patient_name'] ?? '—') ?></td>
                        <td><?= $cur . $fmt($b['net_amount']) ?></td>
                        <td>
                            <?= $cur . $fmt($b['paid_amount']) ?>
                            <?php if ($balDue > 0): ?>
                            <br><small class="text-danger fw-bold">Due: <?= $cur . $fmt($balDue) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($b['payment_method']) ?></span>
                            <?php if (strtolower($b['payment_method'] ?? '') === 'split'): ?>
                            <br><small class="text-muted">Cash:<?= $cur . $fmt($b['pm_cash']) ?> UPI:<?= $cur . $fmt($b['pm_upi']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="no-print">
                            <div class="d-flex gap-1">
                                <a href="bill_print.php?id=<?= $b['id'] ?>" target="_blank"
                                   class="btn btn-xs btn-outline-primary py-0 px-1" title="Print Invoice">
                                    <i class="fas fa-print"></i>
                                </a>
                                <?php if ($balDue > 0): ?>
                                <button type="button"
                                    class="btn btn-xs btn-warning py-0 px-1"
                                    title="Add Previous/Additional Payment"
                                    onclick="openPayModal(<?= $b['id'] ?>, '<?= htmlspecialchars($b['bill_number']) ?>', '<?= htmlspecialchars($b['patient_name'] ?? '') ?>', <?= $fmt($b['net_amount']) ?>, <?= $fmt($b['paid_amount']) ?>, <?= $fmt($balDue) ?>)">
                                    <i class="fas fa-rupee-sign"></i> Pay
                                </button>
                                <?php endif; ?>
                                <a href="bill_modify.php?id=<?= $b['id'] ?>"
                                   class="btn btn-xs btn-outline-secondary py-0 px-1" title="Modify Bill">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <?php if (!empty($bills)): ?>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td colspan="4" class="text-end">Grand Total Paid:</td>
                        <td colspan="2"><?= $cur . $fmt($total_collection) ?></td>
                        <td colspan="2"></td>
                    </tr>
                    <?php if ($total_pending_balance > 0): ?>
                    <tr class="table-danger fw-bold">
                        <td colspan="4" class="text-end">Total Pending Balance:</td>
                        <td class="text-danger" colspan="2"><?= $cur . $fmt($total_pending_balance) ?></td>
                        <td colspan="2"></td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php /* ═══════════ FINAL BALANCE CALCULATION ═══════════ */ ?>
<div class="summary-calc mb-3" id="balanceCalc">
    <h6 class="fw-bold mb-3"><i class="fas fa-calculator me-2 text-primary"></i>Final Balance Calculation</h6>
    <div class="row-item"><span>Opening Balance / Cash in Hand</span><span class="fw-semibold"><?= $cur . $fmt($opening_bal) ?></span></div>
    <div class="row-item"><span>+ Total Daily Collection</span><span class="fw-semibold text-success"><?= $cur . $fmt($total_collection) ?></span></div>
    <div class="row-item"><span id="calcVReceipts">+ Voucher Receipts</span><span class="fw-semibold text-success" id="calcVReceiptsAmt"><?= $cur . $fmt($v_receipts) ?></span></div>
    <div class="row-item"><span id="calcVPayments">− Voucher Payments</span><span class="fw-semibold text-danger" id="calcVPaymentsAmt"><?= $cur . $fmt($v_payments) ?></span></div>
    <div class="row-total">
        <span>= NET CLOSING BALANCE</span>
        <span id="netBalFinal" class="<?= $net_balance >= 0 ? 'text-pos' : 'text-neg' ?>"><?= $cur . $fmt($net_balance) ?></span>
    </div>
</div>

<?php /* ═══════════ VOUCHER PANEL ═══════════ */ ?>
<div id="voucherPanel" class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center" style="background:#37474f;color:#fff;">
        <span><i class="fas fa-receipt me-2"></i>Voucher / Cash Management</span>
        <button class="btn btn-sm btn-outline-light" onclick="toggleVoucher()">✕ Close</button>
    </div>
    <div class="card-body">
        <?php if ($is_locked): ?>
        <div class="alert alert-warning"><i class="fas fa-lock me-2"></i>Report is <strong>locked</strong>. Vouchers are read-only.</div>
        <?php endif; ?>

        <!-- Voucher Summary Bar -->
        <div class="row g-2 mb-3" id="voucherSummaryBar">
            <div class="col"><div class="border rounded p-2 text-center small">Opening Bal<br><strong id="vsOpenBal"><?= $cur . $fmt($opening_bal) ?></strong></div></div>
            <div class="col"><div class="border rounded p-2 text-center small text-success">+ Receipts<br><strong id="vsReceipts"><?= $cur . $fmt($v_receipts) ?></strong></div></div>
            <div class="col"><div class="border rounded p-2 text-center small text-danger">− Payments<br><strong id="vsPayments"><?= $cur . $fmt($v_payments) ?></strong></div></div>
            <div class="col"><div class="border rounded p-2 text-center small"><span id="vsNetLbl">Net Balance</span><br><strong id="vsNet" class="<?= ($v_receipts-$v_payments)>=0?'text-success':'text-danger' ?>"><?= $cur . $fmt($v_receipts - $v_payments) ?></strong></div></div>
        </div>

        <!-- Add Voucher Form -->
        <?php if (!$is_locked): ?>
        <div class="voucher-form-section card bg-light mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3" id="vFormTitle"><i class="fas fa-plus-circle me-1 text-primary"></i>Add Voucher</h6>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Type</label>
                        <select id="vType" class="form-select form-select-sm">
                            <option value="Payment">💸 Payment (−)</option>
                            <option value="Receipt">💰 Receipt (+)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Category</label>
                        <select id="vCategory" class="form-select form-select-sm">
                            <option>Petty Cash</option><option>Staff Expense</option>
                            <option>Vendor Payment</option><option>Refund</option>
                            <option>Opening Balance</option><option>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Amount (₹)</label>
                        <input type="number" id="vAmount" class="form-control form-control-sm" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Payment Mode</label>
                        <select id="vPayMode" class="form-select form-select-sm">
                            <option>Cash</option><option>UPI</option>
                            <option>Bank Transfer</option><option>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Staff Name</label>
                        <input type="text" id="vStaff" class="form-control form-control-sm" placeholder="Staff name">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">Purpose / Description *</label>
                        <input type="text" id="vPurpose" class="form-control form-control-sm" placeholder="What was this for?">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Reference No</label>
                        <input type="text" id="vRefNo" class="form-control form-control-sm" placeholder="Optional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Time</label>
                        <input type="time" id="vTime" class="form-control form-control-sm" value="<?= date('H:i') ?>">
                    </div>
                    <div class="col-md-9 d-flex align-items-end gap-2 flex-wrap">
                        <span class="small text-muted me-1">Quick:</span>
                        <?php foreach (['Tea/Snacks'=>50,'Courier'=>100,'Stationery'=>200,'Staff Advance'=>500] as $lbl => $amt): ?>
                        <button class="btn btn-outline-secondary btn-sm quick-btn"
                            onclick="quickFill('<?= $lbl ?>',<?= $amt ?>)">
                            <?= $lbl ?> ₹<?= $amt ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-12">
                        <input type="hidden" id="vEditId" value="0">
                        <button class="btn btn-primary btn-sm me-2" onclick="saveVoucher()"><i class="fas fa-save me-1"></i>Save Voucher</button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="resetVoucherForm()">Reset</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Search + Voucher List -->
        <div class="d-flex gap-2 mb-2 no-print">
            <input type="text" id="vSearch" class="form-control form-control-sm w-auto" placeholder="🔍 Search staff/category..." oninput="loadVouchers()">
            <button class="btn btn-outline-primary btn-sm" onclick="printVouchers()"><i class="fas fa-print me-1"></i>Print Vouchers</button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm" id="voucherTable">
                <thead class="table-dark">
                    <tr><th>#</th><th>Time</th><th>Type</th><th>Staff</th><th>Category</th>
                        <th>Purpose</th><th>Amount</th><th>Mode</th><th class="no-print">Actions</th></tr>
                </thead>
                <tbody id="voucherBody">
                    <tr><td colspan="9" class="text-center text-muted">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php /* ═══════════ PRINT SIGNATURE ═══════════ */ ?>
<div class="print-signature-row" style="display:none;">
    <div style="text-align:center">
        <div style="border-top:1px solid #000;width:150px;margin:0 auto;padding-top:4px;font-size:10pt">Prepared By</div>
    </div>
    <div style="text-align:center">
        <div style="border-top:1px solid #000;width:150px;margin:0 auto;padding-top:4px;font-size:10pt">Authorized Signatory</div>
    </div>
</div>

</div><!-- /container-fluid -->

<!-- ═══════════ PREVIOUS PAYMENT MODAL ═══════════ -->
<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:#1a237e; color:#fff;">
        <h5 class="modal-title" id="payModalLabel">
            <i class="fas fa-rupee-sign me-2"></i>Add Payment to Bill
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="pm_bill_id">
        <!-- Bill Info Summary -->
        <div class="alert alert-info py-2 mb-3" id="pm_bill_info"></div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Amount to Pay Now (₹)</label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" id="pm_amount" class="form-control form-control-lg fw-bold"
                        min="0" step="0.01" placeholder="0.00" oninput="pmValidate()">
                </div>
                <small class="text-muted" id="pm_max_hint"></small>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Payment Mode</label>
                <select id="pm_mode" class="form-select">
                    <option value="Cash">💵 Cash</option>
                    <option value="UPI">📱 UPI</option>
                    <option value="Card">💳 Card</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold">Remarks (optional)</label>
                <input type="text" id="pm_remarks" class="form-control" placeholder="e.g. Balance received on follow-up">
            </div>
        </div>
        <!-- New Balance Preview -->
        <div class="mt-3 p-2 border rounded" id="pm_preview" style="display:none; background:#e8f5e9;">
            <strong>After this payment:</strong><br>
            Balance Due: <span id="pm_new_balance" class="text-danger fw-bold"></span>
            &nbsp;|&nbsp; Status: <span id="pm_new_status" class="badge"></span>
        </div>
        <div id="pm_error" class="alert alert-danger py-2 mt-2" style="display:none;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success fw-bold" onclick="submitPayment()" id="pm_submit_btn">
            <i class="fas fa-save me-1"></i>Save Payment
        </button>
      </div>
    </div>
  </div>
</div>

<?php /* ═══════════ JAVASCRIPT ═══════════ */ ?>
<script>
const REPORT_DATE   = '<?= $report_date ?>';
const OPENING_BAL   = <?= $opening_bal ?>;
const TOTAL_BILLS   = <?= $total_collection ?>;
const IS_LOCKED     = <?= $is_locked ? 'true' : 'false' ?>;
const TOTAL_PENDING = <?= $total_pending_balance ?>;
let   currentVoucherSummary = { receipts: <?= $v_receipts ?>, payments: <?= $v_payments ?> };

// ── Toggle voucher panel ──────────────────────────────────────
function toggleVoucher() {
    const p = document.getElementById('voucherPanel');
    if (p.style.display === 'none' || p.style.display === '') {
        p.style.display = 'block';
        loadVouchers();
        p.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        p.style.display = 'none';
    }
}

// ── Quick-fill form ───────────────────────────────────────────
function quickFill(purpose, amount) {
    document.getElementById('vPurpose').value = purpose;
    document.getElementById('vAmount').value  = amount;
    document.getElementById('vType').value    = 'Payment';
}

// ── Reset voucher form ────────────────────────────────────────
function resetVoucherForm() {
    ['vType','vCategory','vAmount','vPayMode','vStaff','vPurpose','vRefNo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = (el.tagName === 'SELECT') ? el.options[0].value : '';
    });
    const vt = document.getElementById('vTime');
    if (vt) vt.value = new Date().toTimeString().slice(0,5);
    document.getElementById('vEditId').value = '0';
    document.getElementById('vFormTitle').innerHTML = '<i class="fas fa-plus-circle me-1 text-primary"></i>Add Voucher';
}

// ── Save (Insert / Update) voucher ───────────────────────────
function saveVoucher() {
    const purpose = document.getElementById('vPurpose').value.trim();
    const amount  = parseFloat(document.getElementById('vAmount').value);
    if (!purpose) { alert('Purpose/Description is required.'); return; }
    if (!amount || amount <= 0) { alert('Please enter a valid amount.'); return; }

    const fd = new FormData();
    fd.append('action',       'save');
    fd.append('id',           document.getElementById('vEditId').value);
    fd.append('voucher_date', REPORT_DATE);
    fd.append('voucher_time', document.getElementById('vTime').value);
    fd.append('voucher_type', document.getElementById('vType').value);
    fd.append('category',     document.getElementById('vCategory').value);
    fd.append('staff_name',   document.getElementById('vStaff').value);
    fd.append('purpose',      purpose);
    fd.append('amount',       amount);
    fd.append('payment_mode', document.getElementById('vPayMode').value);
    fd.append('reference_no', document.getElementById('vRefNo').value);

    fetch('api_vouchers.php?action=save', { method: 'POST', body: fd })
    .then(r => r.json()).then(d => {
        if (d.success) {
            resetVoucherForm();
            loadVouchers();
            refreshVoucherSummary();
        } else {
            alert('Error: ' + (d.error || 'Unknown'));
        }
    }).catch(() => alert('Network error.'));
}

// ── Load voucher list ─────────────────────────────────────────
function loadVouchers() {
    const search = document.getElementById('vSearch')?.value || '';
    fetch(`api_vouchers.php?action=list&date=${REPORT_DATE}&search=${encodeURIComponent(search)}`)
    .then(r => r.json()).then(d => {
        const tbody = document.getElementById('voucherBody');
        if (!d.success || !d.data.length) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-3">No vouchers for this date.</td></tr>';
            return;
        }
        tbody.innerHTML = d.data.map((v, i) => {
            const cls   = v.voucher_type === 'Receipt' ? 'vchr-receipt' : 'vchr-payment';
            const sign  = v.voucher_type === 'Receipt' ? '+' : '−';
            const color = v.voucher_type === 'Receipt' ? 'text-success' : 'text-danger';
            const acts  = IS_LOCKED ? '' : `
                <button class="btn btn-xs btn-outline-primary" onclick="editVoucher(${v.id})">✏️</button>
                <button class="btn btn-xs btn-outline-danger ms-1" onclick="deleteVoucher(${v.id})">🗑️</button>`;
            return `<tr class="${cls}">
                <td>${i+1}</td>
                <td>${v.voucher_time?.slice(0,5)||''}</td>
                <td><span class="badge bg-${v.voucher_type==='Receipt'?'success':'danger'}">${v.voucher_type}</span></td>
                <td>${esc(v.staff_name||'—')}</td>
                <td>${esc(v.category||'')}</td>
                <td>${esc(v.purpose)}</td>
                <td class="${color} fw-bold">${sign}₹${parseFloat(v.amount).toFixed(2)}</td>
                <td>${esc(v.payment_mode||'')}</td>
                <td class="no-print">${acts}</td>
            </tr>`;
        }).join('');
    }).catch(() => {
        document.getElementById('voucherBody').innerHTML =
            '<tr><td colspan="9" class="text-danger text-center">Failed to load. <a href="daily_report_migrate.php">Run migration?</a></td></tr>';
    });
}

// ── Edit voucher ──────────────────────────────────────────────
function editVoucher(id) {
    fetch(`api_vouchers.php?action=get&id=${id}`)
    .then(r => r.json()).then(d => {
        if (!d.success) { alert(d.error); return; }
        const v = d.data;
        document.getElementById('vEditId').value   = v.id;
        document.getElementById('vType').value     = v.voucher_type;
        document.getElementById('vCategory').value = v.category;
        document.getElementById('vAmount').value   = v.amount;
        document.getElementById('vPayMode').value  = v.payment_mode;
        document.getElementById('vStaff').value    = v.staff_name || '';
        document.getElementById('vPurpose').value  = v.purpose;
        document.getElementById('vRefNo').value    = v.reference_no || '';
        document.getElementById('vTime').value     = (v.voucher_time||'').slice(0,5);
        document.getElementById('vFormTitle').innerHTML = '✏️ Edit Voucher #' + v.voucher_number;
        document.querySelector('.voucher-form-section')?.scrollIntoView({behavior:'smooth'});
    });
}

// ── Delete voucher ────────────────────────────────────────────
function deleteVoucher(id) {
    if (!confirm('Delete this voucher?')) return;
    const fd = new FormData(); fd.append('id', id);
    fetch('api_vouchers.php?action=delete', { method:'POST', body:fd })
    .then(r => r.json()).then(d => {
        if (d.success) { loadVouchers(); refreshVoucherSummary(); }
        else alert('Error: ' + d.error);
    });
}

// ── Refresh voucher summary bar + balance calc ────────────────
function refreshVoucherSummary() {
    fetch(`api_vouchers.php?action=summary&date=${REPORT_DATE}`)
    .then(r => r.json()).then(d => {
        if (!d.success) return;
        const s = d.data;
        currentVoucherSummary = { receipts: parseFloat(s.total_receipts), payments: parseFloat(s.total_payments) };
        const fmt = n => '₹' + parseFloat(n).toFixed(2);
        const el  = id => document.getElementById(id);
        if(el('vsReceipts')) el('vsReceipts').textContent = fmt(s.total_receipts);
        if(el('vsPayments')) el('vsPayments').textContent = fmt(s.total_payments);
        const net = parseFloat(s.total_receipts) - parseFloat(s.total_payments);
        if(el('vsNet')) { el('vsNet').textContent = fmt(net); el('vsNet').className = net>=0?'text-success':'text-danger'; }
        if(el('calcVReceiptsAmt')) el('calcVReceiptsAmt').textContent = fmt(s.total_receipts);
        if(el('calcVPaymentsAmt')) el('calcVPaymentsAmt').textContent = fmt(s.total_payments);
        // Update net balance display
        const nb = OPENING_BAL + TOTAL_BILLS + parseFloat(s.total_receipts) - parseFloat(s.total_payments);
        const nbFmt = '₹' + nb.toFixed(2);
        if(el('netBalFinal')) { el('netBalFinal').textContent = nbFmt; el('netBalFinal').className = nb>=0?'text-pos':'text-neg'; }
        if(el('netBalCard'))  el('netBalCard').textContent = nbFmt;
    });
}

// ── Toggle lock ───────────────────────────────────────────────
function toggleLock() {
    const msg = IS_LOCKED
        ? 'Unlock this report? Users will be able to add/edit vouchers again.'
        : 'Lock this report? No more changes will be allowed.';
    if (!confirm(msg)) return;
    const fd = new FormData(); fd.append('date', REPORT_DATE);
    fetch('api_vouchers.php?action=toggle_lock', { method:'POST', body:fd })
    .then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else alert('Error: ' + d.error);
    });
}

// ── Export bills to CSV ───────────────────────────────────────
function exportCSV() {
    const rows = [['#','Bill No','Time','Patient','Net Amount','Paid','Mode']];
    const trs  = document.querySelectorAll('#billsTable tbody tr');
    trs.forEach(tr => {
        const cells = tr.querySelectorAll('td');
        if (cells.length >= 7) rows.push([...cells].map(c => '"' + c.innerText.replace(/"/g,'""') + '"'));
    });
    const csv  = rows.map(r => r.join(',')).join('\n');
    const blob = new Blob(['\ufeff'+csv], { type:'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = 'daily_report_<?= $report_date ?>.csv';
    a.click(); URL.revokeObjectURL(url);
}

// ── Print vouchers only ───────────────────────────────────────
function printVouchers() {
    const el = document.getElementById('voucherPanel');
    el.style.display = 'block';
    window.print();
}

// ── Utility: escape html ──────────────────────────────────────
function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // If voucher panel was open on reload, keep it open
    loadVouchers();
});

// ════════════════════════════════════════════════════════
// ── PREVIOUS PAYMENT MODAL FUNCTIONS ─────────────────
// ════════════════════════════════════════════════════════
let _pm = { bill_id: 0, net: 0, paid: 0, balDue: 0 };

function openPayModal(billId, billNo, patientName, net, paid, balDue) {
    _pm = { bill_id: billId, net: parseFloat(net), paid: parseFloat(paid), balDue: parseFloat(balDue) };

    // Populate modal info
    document.getElementById('pm_bill_id').value = billId;
    document.getElementById('pm_bill_info').innerHTML =
        `<strong>Bill:</strong> ${esc(billNo)} &nbsp;|&nbsp; <strong>Patient:</strong> ${esc(patientName)}<br>` +
        `Net Amount: <strong>₹${_pm.net.toFixed(2)}</strong> &nbsp;|&nbsp; ` +
        `Previously Paid: <strong class="text-success">₹${_pm.paid.toFixed(2)}</strong> &nbsp;|&nbsp; ` +
        `Balance Due: <strong class="text-danger">₹${_pm.balDue.toFixed(2)}</strong>`;

    document.getElementById('pm_max_hint').textContent = `Max payable: ₹${_pm.balDue.toFixed(2)}`;
    document.getElementById('pm_amount').value = _pm.balDue.toFixed(2); // pre-fill full due
    document.getElementById('pm_remarks').value = '';
    document.getElementById('pm_error').style.display = 'none';
    document.getElementById('pm_preview').style.display = 'none';

    pmValidate();

    const modal = new bootstrap.Modal(document.getElementById('payModal'));
    modal.show();
}

function pmValidate() {
    const amt    = parseFloat(document.getElementById('pm_amount').value || 0);
    const newBal = Math.max(0, _pm.balDue - amt);
    const btn    = document.getElementById('pm_submit_btn');
    const prev   = document.getElementById('pm_preview');
    const errEl  = document.getElementById('pm_error');

    if (amt <= 0 || amt > _pm.balDue + 0.01) {
        btn.disabled = true;
        errEl.textContent = amt > _pm.balDue + 0.01
            ? `Amount ₹${amt.toFixed(2)} exceeds balance due ₹${_pm.balDue.toFixed(2)}`
            : 'Enter a valid amount greater than 0';
        errEl.style.display = 'block';
        prev.style.display  = 'none';
        return;
    }

    errEl.style.display = 'none';
    btn.disabled = false;

    let newStatus = 'Paid';
    let statusClass = 'bg-success';
    if (newBal > 0) { newStatus = 'Partial'; statusClass = 'bg-warning text-dark'; }

    document.getElementById('pm_new_balance').textContent = '₹' + newBal.toFixed(2);
    document.getElementById('pm_new_status').textContent  = newStatus;
    document.getElementById('pm_new_status').className    = 'badge ' + statusClass;
    prev.style.display = 'block';
}

function submitPayment() {
    const amt     = parseFloat(document.getElementById('pm_amount').value || 0);
    const mode    = document.getElementById('pm_mode').value;
    const remarks = document.getElementById('pm_remarks').value.trim();
    const btn     = document.getElementById('pm_submit_btn');

    if (amt <= 0) { alert('Enter a valid amount.'); return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

    const fd = new FormData();
    fd.append('action',      'add_payment');
    fd.append('bill_id',     _pm.bill_id);
    fd.append('amount',      amt.toFixed(2));
    fd.append('mode',        mode);
    fd.append('remarks',     remarks);
    fd.append('report_date', REPORT_DATE);

    fetch('api_bill_payment.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Save Payment';
        if (d.success) {
            bootstrap.Modal.getInstance(document.getElementById('payModal')).hide();
            // Reload page to refresh report
            location.reload();
        } else {
            document.getElementById('pm_error').textContent = 'Error: ' + (d.error || 'Unknown');
            document.getElementById('pm_error').style.display = 'block';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Save Payment';
        document.getElementById('pm_error').textContent = 'Network error. Please try again.';
        document.getElementById('pm_error').style.display = 'block';
    });
}
</script>
