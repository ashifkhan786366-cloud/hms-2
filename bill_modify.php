<?php
/**
 * bill_modify.php — Modify an existing bill
 * Loads bill by ?id=, shows full editable form, saves via POST to billing_app.php?action=update
 */
require_once 'config/db.php';
require_once 'includes/auth_check.php';
require_once 'controllers/BillController.php';

$bill_id = (int)($_GET['id'] ?? 0);
if (!$bill_id) die("Bill ID not specified.");

$ctrl = new BillController();
$bill = $ctrl->getBillById($bill_id);
if (!$bill) die("Bill not found.");

// Load doctors list
$doctors_stmt = $pdo->query("SELECT id, full_name FROM users WHERE role='Doctor' ORDER BY full_name");
$doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);

// Return URL — back to patient billing history
$back_url = "patient_view.php?id=" . ($bill['patient_id'] ?? '');

require_once 'includes/header.php';
?>

<style>
/* ── Bill Modify Page Styles ── */
.bm-card { background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.08); margin-bottom:18px; }
.bm-card-header { background:linear-gradient(135deg,#1a237e,#283593); color:#fff; padding:12px 20px; border-radius:10px 10px 0 0; font-weight:700; font-size:15px; }
.patient-info-bar { background:#e8eaf6; border-left:4px solid #3949ab; padding:12px 18px; border-radius:6px; margin-bottom:18px; }
.patient-info-bar .pi-name { font-size:18px; font-weight:700; color:#1a237e; }
.patient-info-bar .pi-meta { font-size:13px; color:#546e7a; margin-top:3px; }
.items-table-wrap { overflow-x:auto; }
.items-tbl { width:100%; border-collapse:collapse; font-size:14px; }
.items-tbl th { background:#37474f; color:#fff; padding:10px 8px; text-align:left; white-space:nowrap; }
.items-tbl td { padding:7px 6px; border-bottom:1px solid #eceff1; vertical-align:middle; }
.items-tbl tr:hover td { background:#f5f5f5; }
.items-tbl input, .items-tbl select { font-size:13px; }
.total-box { background:#f8f9fa; border:1px solid #dee2e6; border-radius:8px; padding:16px; }
.total-row { display:flex; justify-content:space-between; padding:5px 0; font-size:15px; border-bottom:1px dashed #dee2e6; }
.total-row:last-child { border-bottom:none; font-size:17px; font-weight:700; color:#1a237e; }
.split-fields { display:none; margin-top:8px; }
.split-fields.active { display:flex; gap:10px; flex-wrap:wrap; }
.balance-highlight { background:#fff3e0; border:2px solid #ff9800; border-radius:6px; padding:10px 14px; font-weight:700; color:#e65100; font-size:16px; }
.btn-save-bill { background:linear-gradient(135deg,#2e7d32,#43a047); color:#fff; font-size:15px; padding:10px 28px; border:none; border-radius:6px; font-weight:700; cursor:pointer; }
.btn-save-bill:hover { filter:brightness(1.1); }
.btn-add-row { background:#1565c0; color:#fff; border:none; border-radius:5px; padding:6px 14px; font-size:13px; cursor:pointer; }
.btn-del-row { background:#c62828; color:#fff; border:none; border-radius:4px; padding:3px 9px; cursor:pointer; font-size:13px; }
</style>

<div class="container-fluid mt-3 mb-5">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-dark">
                <i class="fas fa-edit text-warning me-2"></i>Modify Bill
                <span class="badge bg-warning text-dark ms-2"><?= htmlspecialchars($bill['bill_number']) ?></span>
            </h4>
            <small class="text-muted">Edit bill details, line items and payment information</small>
        </div>
        <a href="<?= $back_url ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back to Patient
        </a>
    </div>

    <!-- Patient Info Bar -->
    <div class="patient-info-bar">
        <div class="pi-name">
            <i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($bill['patient_name'] ?? 'Unknown') ?>
        </div>
        <div class="pi-meta">
            MR No: <strong><?= htmlspecialchars($bill['mr_number'] ?? '—') ?></strong>
            &nbsp;|&nbsp; Age/Gender: <strong><?= htmlspecialchars($bill['age'] ?? '—') ?> / <?= htmlspecialchars($bill['gender'] ?? '—') ?></strong>
            &nbsp;|&nbsp; Phone: <strong><?= htmlspecialchars($bill['phone'] ?? '—') ?></strong>
            &nbsp;|&nbsp; Created Doctor: <strong><?= htmlspecialchars($bill['doctor_name'] ?? '—') ?></strong>
        </div>
    </div>

    <!-- ═══ MODIFY FORM ═══ -->
    <form id="modifyForm">
        <input type="hidden" name="action" value="update_bill">
        <input type="hidden" id="edit_bill_id" name="edit_bill_id" value="<?= $bill_id ?>">
        <input type="hidden" id="patient_id" name="patient_id" value="<?= (int)$bill['patient_id'] ?>">
        <input type="hidden" id="bill_number" name="bill_number" value="<?= htmlspecialchars($bill['bill_number']) ?>">
        <input type="hidden" id="items_json" name="items">

        <div class="row g-3">

            <!-- ── LEFT COLUMN ── -->
            <div class="col-lg-8">

                <!-- Bill Details Card -->
                <div class="bm-card">
                    <div class="bm-card-header"><i class="fas fa-file-invoice me-2"></i>Bill Details</div>
                    <div class="p-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Bill Date & Time</label>
                                <input type="datetime-local" id="bill_date" name="bill_date" class="form-control"
                                    value="<?= !empty($bill['bill_date']) ? date('Y-m-d\TH:i', strtotime($bill['bill_date'])) : date('Y-m-d\TH:i') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Bill Type</label>
                                <select id="bill_type" name="bill_type" class="form-select">
                                    <option value="OPD"  <?= ($bill['bill_type']??'OPD')==='OPD'  ?'selected':'' ?>>OPD</option>
                                    <option value="IPD"  <?= ($bill['bill_type']??'')==='IPD'  ?'selected':'' ?>>IPD</option>
                                    <option value="Emergency" <?= ($bill['bill_type']??'')==='Emergency' ?'selected':'' ?>>Emergency</option>
                                    <option value="Lab"  <?= ($bill['bill_type']??'')==='Lab'  ?'selected':'' ?>>Lab Only</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Treating Doctor</label>
                                <select id="doctor_id" name="doctor_id" class="form-select">
                                    <option value="">-- Select Doctor --</option>
                                    <?php foreach ($doctors as $doc): ?>
                                    <option value="<?= $doc['id'] ?>" <?= ($bill['doctor_id']==$doc['id'])?'selected':'' ?>>
                                        <?= htmlspecialchars($doc['full_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items Card -->
                <div class="bm-card">
                    <div class="bm-card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list me-2"></i>Bill Items</span>
                        <button type="button" class="btn-add-row" onclick="addRow()">
                            <i class="fas fa-plus me-1"></i>Add Row
                        </button>
                    </div>
                    <div class="p-3">
                        <div class="items-table-wrap">
                            <table class="items-tbl" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th width="30">#</th>
                                        <th width="32%">Item / Service Name</th>
                                        <th width="110">Type</th>
                                        <th width="60">Qty</th>
                                        <th width="90">Rate (₹)</th>
                                        <th width="80">Disc%</th>
                                        <th width="100">Net Amt (₹)</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTbody">
                                    <!-- Populated by JS from PHP data -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div><!-- /col-lg-8 -->

            <!-- ── RIGHT COLUMN ── -->
            <div class="col-lg-4">

                <!-- Discount Card -->
                <div class="bm-card">
                    <div class="bm-card-header"><i class="fas fa-tags me-2"></i>Discount</div>
                    <div class="p-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Type</label>
                                <select id="global_discount_type" name="global_discount_type" class="form-select" onchange="recalcTotals()">
                                    <option value="amount" <?= ($bill['discount_type']??'amount')==='amount'?'selected':'' ?>>Fixed Amount</option>
                                    <option value="percent" <?= ($bill['discount_type']??'')==='percent'?'selected':'' ?>>Percentage %</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Value</label>
                                <input type="number" id="global_discount_val" name="global_discount_val"
                                    class="form-control" min="0" step="0.01"
                                    value="<?= ($bill['discount_type']??'amount')==='percent' ? ($bill['discount_percent']??0) : ($bill['discount']??0) ?>"
                                    oninput="recalcTotals()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Totals Card -->
                <div class="bm-card">
                    <div class="bm-card-header"><i class="fas fa-calculator me-2"></i>Bill Summary</div>
                    <div class="p-3">
                        <div class="total-box">
                            <div class="total-row"><span>Subtotal</span><span id="disp_subtotal">₹0.00</span></div>
                            <div class="total-row text-danger"><span>Discount</span><span id="disp_discount">-₹0.00</span></div>
                            <div class="total-row"><span><strong>Net Payable</strong></span><span id="disp_net"><strong>₹0.00</strong></span></div>
                        </div>
                        <input type="hidden" id="subtotal"       name="subtotal">
                        <input type="hidden" id="total_discount" name="total_discount">
                        <input type="hidden" id="total_tax"      name="total_tax" value="0">
                        <input type="hidden" id="grand_total"    name="grand_total">
                    </div>
                </div>

                <!-- Payment Card -->
                <div class="bm-card">
                    <div class="bm-card-header"><i class="fas fa-credit-card me-2"></i>Payment</div>
                    <div class="p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Payment Mode</label>
                            <select id="payment_mode" name="payment_mode" class="form-select" onchange="toggleSplit()">
                                <option value="Cash"      <?= ($bill['payment_method']??'Cash')==='Cash'?'selected':'' ?>>💵 Cash</option>
                                <option value="UPI"       <?= ($bill['payment_method']??'')==='UPI'?'selected':'' ?>>📱 UPI</option>
                                <option value="Card"      <?= ($bill['payment_method']??'')==='Card'?'selected':'' ?>>💳 Card</option>
                                <option value="Insurance" <?= ($bill['payment_method']??'')==='Insurance'?'selected':'' ?>>🏥 Insurance</option>
                                <option value="Split"     <?= ($bill['payment_method']??'')==='Split'?'selected':'' ?>>🔀 Split</option>
                                <option value="Other"     <?= ($bill['payment_method']??'')==='Other'?'selected':'' ?>>Other</option>
                            </select>
                        </div>

                        <!-- Split fields -->
                        <div id="splitFields" class="split-fields <?= ($bill['payment_method']??'')==='Split'?'active':'' ?>">
                            <div class="flex-fill">
                                <label class="form-label small fw-semibold">Cash Amount</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" id="payment_mode_cash" name="payment_mode_cash"
                                        class="form-control" min="0" step="0.01"
                                        value="<?= number_format((float)($bill['payment_mode_cash']??0),2,'.','') ?>"
                                        oninput="updateSplitUPI()">
                                </div>
                            </div>
                            <div class="flex-fill">
                                <label class="form-label small fw-semibold">UPI Amount</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" id="payment_mode_upi" name="payment_mode_upi"
                                        class="form-control" min="0" step="0.01"
                                        value="<?= number_format((float)($bill['payment_mode_upi']??0),2,'.','') ?>"
                                        oninput="checkSplitTotal()">
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label small fw-semibold">Amount Received (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" id="paid_amount" name="paid_amount"
                                    class="form-control form-control-lg fw-bold"
                                    min="0" step="0.01"
                                    value="<?= number_format((float)($bill['paid_amount']??0),2,'.','') ?>"
                                    oninput="recalcBalance()">
                            </div>
                        </div>

                        <div class="mt-3 balance-highlight">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Balance Due: <span id="disp_balance">₹0.00</span>
                            <input type="hidden" id="balance_due" name="balance_due">
                        </div>

                        <div class="mt-2">
                            <small class="text-muted">
                                Status: <span id="disp_status" class="badge bg-secondary">Pending</span>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <button type="button" class="btn-save-bill" onclick="saveBill()">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="<?= $back_url ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                    <a href="bill_print.php?id=<?= $bill_id ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-print me-1"></i>Print Original Invoice
                    </a>
                </div>

            </div><!-- /col-lg-4 -->

        </div><!-- /row -->
    </form>
</div>

<!-- Toast notification -->
<div id="bm-toast" style="
    display:none; position:fixed; bottom:30px; right:30px; z-index:9999;
    background:#2e7d32; color:#fff; padding:14px 24px; border-radius:8px;
    font-weight:700; font-size:14px; box-shadow:0 4px 16px rgba(0,0,0,.25);
"></div>

<script>
/* ════════════════════════════════════════════════════════════════
   bill_modify.php — Client-side logic
   ════════════════════════════════════════════════════════════════ */

// Pre-loaded bill items from PHP
const EXISTING_ITEMS = <?= json_encode(array_values($bill['items'] ?? [])) ?>;
const BACK_URL       = <?= json_encode($back_url) ?>;
let rowCount = 0;

// ── Init on load ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Load existing items into the table
    if (EXISTING_ITEMS.length > 0) {
        EXISTING_ITEMS.forEach(item => addRow(item));
    } else {
        addRow();
    }
    recalcTotals();
    toggleSplit();
});

// ── Add a row to the items table ──────────────────────────────
function addRow(item = null) {
    rowCount++;
    const i = rowCount;
    const tbody = document.getElementById('itemsTbody');

    const name     = item ? escAttr(item.service_name || item.item_name || '') : '';
    const itype    = item ? (item.item_type || 'General') : 'General';
    const qty      = item ? (item.quantity || 1) : 1;
    const rate     = item ? parseFloat(item.cost || 0).toFixed(2) : '';
    const disc     = item ? parseFloat(item.discount_percent || 0).toFixed(2) : '0.00';
    const amount   = item ? parseFloat(item.amount || 0).toFixed(2) : '0.00';

    const typeOptions = ['General','Lab','Consultation','Procedure','Medicine','Room','Other'];

    const tr = document.createElement('tr');
    tr.id = `row_${i}`;
    tr.innerHTML = `
        <td class="text-muted small">${i}</td>
        <td>
            <input type="text" class="form-control form-control-sm item-name"
                placeholder="Item / service name" value="${name}"
                oninput="recalcRow(${i})" required>
        </td>
        <td>
            <select class="form-select form-select-sm item-type" onchange="recalcRow(${i})">
                ${typeOptions.map(t => `<option value="${t}" ${t===itype?'selected':''}>${t}</option>`).join('')}
            </select>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm item-qty"
                min="1" step="1" value="${qty}" oninput="recalcRow(${i})">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm item-rate"
                min="0" step="0.01" value="${rate}" placeholder="0.00" oninput="recalcRow(${i})">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm item-disc"
                min="0" max="100" step="0.01" value="${disc}" oninput="recalcRow(${i})">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm item-amount"
                value="${amount}" readonly tabindex="-1"
                style="background:#f8f9fa; font-weight:bold; color:#1a237e;">
        </td>
        <td>
            <button type="button" class="btn-del-row" onclick="delRow(${i})" title="Remove row">✕</button>
        </td>
    `;
    tbody.appendChild(tr);
    recalcTotals();
}

// ── Recalc a single row amount ────────────────────────────────
function recalcRow(i) {
    const row  = document.getElementById(`row_${i}`);
    if (!row) return;
    const qty  = parseFloat(row.querySelector('.item-qty')?.value  || 0);
    const rate = parseFloat(row.querySelector('.item-rate')?.value || 0);
    const disc = parseFloat(row.querySelector('.item-disc')?.value || 0);
    let amt = qty * rate;
    if (disc > 0) amt = amt - (amt * disc / 100);
    row.querySelector('.item-amount').value = amt.toFixed(2);
    recalcTotals();
}

// ── Delete a row ──────────────────────────────────────────────
function delRow(i) {
    const row = document.getElementById(`row_${i}`);
    if (row) { row.remove(); recalcTotals(); }
}

// ── Recalc all totals ─────────────────────────────────────────
function recalcTotals() {
    let subtotal = 0;
    document.querySelectorAll('.item-amount').forEach(el => {
        subtotal += parseFloat(el.value || 0);
    });

    const discType = document.getElementById('global_discount_type').value;
    const discVal  = parseFloat(document.getElementById('global_discount_val').value || 0);
    let discount = 0;
    if (discType === 'percent') {
        discount = subtotal * discVal / 100;
    } else {
        discount = discVal;
    }
    if (discount > subtotal) discount = subtotal;

    const net = subtotal - discount;

    document.getElementById('disp_subtotal').textContent  = '₹' + subtotal.toFixed(2);
    document.getElementById('disp_discount').textContent  = '-₹' + discount.toFixed(2);
    document.getElementById('disp_net').innerHTML         = '<strong>₹' + net.toFixed(2) + '</strong>';

    document.getElementById('subtotal').value       = subtotal.toFixed(2);
    document.getElementById('total_discount').value = discount.toFixed(2);
    document.getElementById('grand_total').value    = net.toFixed(2);

    recalcBalance();
}

// ── Recalculate balance due ───────────────────────────────────
function recalcBalance() {
    const net  = parseFloat(document.getElementById('grand_total').value || 0);
    const paid = parseFloat(document.getElementById('paid_amount').value || 0);
    const bal  = Math.max(0, net - paid);

    document.getElementById('disp_balance').textContent = '₹' + bal.toFixed(2);
    document.getElementById('balance_due').value         = bal.toFixed(2);

    let status = 'Paid';
    let statusClass = 'bg-success';
    if (bal > 0 && paid > 0) { status = 'Partial'; statusClass = 'bg-warning text-dark'; }
    else if (bal > 0 && paid <= 0) { status = 'Pending'; statusClass = 'bg-danger'; }

    const sd = document.getElementById('disp_status');
    sd.textContent = status;
    sd.className   = `badge ${statusClass}`;
}

// ── Toggle split payment fields ───────────────────────────────
function toggleSplit() {
    const mode = document.getElementById('payment_mode').value;
    const sf   = document.getElementById('splitFields');
    if (mode === 'Split') {
        sf.classList.add('active');
    } else {
        sf.classList.remove('active');
        document.getElementById('payment_mode_cash').value = '0.00';
        document.getElementById('payment_mode_upi').value  = '0.00';
    }
}

// ── When cash amount changes, auto-fill UPI as remainder ──────
function updateSplitUPI() {
    const net  = parseFloat(document.getElementById('grand_total').value || 0);
    const cash = parseFloat(document.getElementById('payment_mode_cash').value || 0);
    const upi  = Math.max(0, net - cash);
    document.getElementById('payment_mode_upi').value = upi.toFixed(2);
    // Also update paid_amount to full net if split mode
    document.getElementById('paid_amount').value = net.toFixed(2);
    recalcBalance();
}

// ── Validate split totals ─────────────────────────────────────
function checkSplitTotal() {
    const net  = parseFloat(document.getElementById('grand_total').value || 0);
    const cash = parseFloat(document.getElementById('payment_mode_cash').value || 0);
    const upi  = parseFloat(document.getElementById('payment_mode_upi').value || 0);
    const sum  = cash + upi;
    if (Math.abs(sum - net) > 0.5) {
        document.getElementById('payment_mode_upi').style.borderColor = '#f44336';
    } else {
        document.getElementById('payment_mode_upi').style.borderColor = '';
    }
}

// ── Collect items from table ─────────────────────────────────
function collectItems() {
    const items = [];
    document.querySelectorAll('#itemsTbody tr').forEach(row => {
        const name   = row.querySelector('.item-name')?.value.trim();
        const itype  = row.querySelector('.item-type')?.value || 'General';
        const qty    = parseFloat(row.querySelector('.item-qty')?.value  || 0);
        const rate   = parseFloat(row.querySelector('.item-rate')?.value || 0);
        const disc   = parseFloat(row.querySelector('.item-disc')?.value || 0);
        const amount = parseFloat(row.querySelector('.item-amount')?.value || 0);
        if (!name || qty === 0) return;
        items.push({ item_name: name, item_type: itype, qty, rate, discount_percent: disc, amount });
    });
    return items;
}

// ── Save bill via fetch POST ─────────────────────────────────
function saveBill() {
    const items = collectItems();
    if (items.length === 0) {
        showToast('⚠️ Add at least one valid item!', '#e65100');
        return;
    }

    const mode = document.getElementById('payment_mode').value;
    if (mode === 'Split') {
        const net  = parseFloat(document.getElementById('grand_total').value || 0);
        const cash = parseFloat(document.getElementById('payment_mode_cash').value || 0);
        const upi  = parseFloat(document.getElementById('payment_mode_upi').value || 0);
        if (Math.abs(cash + upi - net) > 1) {
            if (!confirm(`Split amounts (Cash: ₹${cash.toFixed(2)} + UPI: ₹${upi.toFixed(2)}) don't add up to Net ₹${net.toFixed(2)}. Save anyway?`)) return;
        }
    }

    document.getElementById('items_json').value = JSON.stringify(items);

    const fd = new FormData(document.getElementById('modifyForm'));

    showToast('⏳ Saving...', '#1565c0');

    fetch('billing_app.php?action=update', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast('✅ Bill updated successfully! ' + (d.bill_number || ''), '#2e7d32');
            setTimeout(() => {
                window.location.href = BACK_URL + '#bills';
            }, 1500);
        } else {
            showToast('❌ Error: ' + (d.error || 'Unknown error'), '#c62828');
        }
    })
    .catch(() => showToast('❌ Network error. Please try again.', '#c62828'));
}

// ── Toast notification ────────────────────────────────────────
function showToast(msg, color = '#2e7d32') {
    const t = document.getElementById('bm-toast');
    t.textContent   = msg;
    t.style.background = color;
    t.style.display = 'block';
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => { t.style.display = 'none'; }, 3500);
}

// ── Escape HTML attribute values ─────────────────────────────
function escAttr(s) {
    return String(s).replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
</script>

<?php require_once 'includes/footer.php'; ?>
