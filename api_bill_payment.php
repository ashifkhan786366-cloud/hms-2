<?php
/**
 * api_bill_payment.php — AJAX endpoint for adding previous/additional payment to a bill
 * Called by: daily_report_view.php payment modal (submitPayment())
 * Action: add_payment
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action !== 'add_payment') {
    echo json_encode(['success' => false, 'error' => 'Invalid action.']);
    exit;
}

$bill_id  = (int)($_POST['bill_id'] ?? 0);
$amount   = (float)($_POST['amount'] ?? 0);
$mode     = trim($_POST['mode'] ?? 'Cash');
$remarks  = trim($_POST['remarks'] ?? '');

if (!$bill_id || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid bill ID or amount.']);
    exit;
}

// Allowed payment modes
$allowedModes = ['Cash', 'UPI', 'Card', 'Other'];
if (!in_array($mode, $allowedModes)) $mode = 'Other';

try {
    $pdo->beginTransaction();

    // 1. Load current bill
    $stmt = $pdo->prepare("SELECT id, net_amount, paid_amount, balance_due, payment_status FROM bills WHERE id = ?");
    $stmt->execute([$bill_id]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bill) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Bill not found.']);
        exit;
    }

    $netAmount  = (float)$bill['net_amount'];
    $prevPaid   = (float)$bill['paid_amount'];
    $newPaid    = $prevPaid + $amount;

    // Don't overpay
    if ($newPaid > $netAmount + 0.01) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error'   => sprintf(
                'Payment of ₹%.2f exceeds balance due ₹%.2f',
                $amount,
                max(0, $netAmount - $prevPaid)
            )
        ]);
        exit;
    }

    $newBalance = max(0, $netAmount - $newPaid);
    $newStatus  = 'Paid';
    if ($newBalance > 0 && $newPaid > 0) $newStatus = 'Partial';
    elseif ($newBalance > 0 && $newPaid <= 0) $newStatus = 'Pending';

    // 2. Update bills table
    try {
        $upd = $pdo->prepare("
            UPDATE bills SET
                paid_amount    = ?,
                balance_due    = ?,
                payment_status = ?,
                modified_at    = NOW(),
                modified_by    = ?
            WHERE id = ?
        ");
        $upd->execute([$newPaid, $newBalance, $newStatus, $_SESSION['user_id'] ?? 1, $bill_id]);
    } catch (PDOException $e) {
        // Fallback if modified_at/modified_by columns don't exist yet
        $upd = $pdo->prepare("
            UPDATE bills SET
                paid_amount    = ?,
                payment_status = ?
            WHERE id = ?
        ");
        $upd->execute([$newPaid, $newStatus, $bill_id]);
    }

    // 3. Log payment in payments table (if it exists)
    try {
        // Try to insert into payments table
        $cashAmt = ($mode === 'Cash') ? $amount : 0;
        $upiAmt  = ($mode === 'UPI')  ? $amount : 0;
        $cardAmt = ($mode === 'Card') ? $amount : 0;

        $payStmt = $pdo->prepare("
            INSERT INTO payments (
                bill_id, amount, payment_mode, remarks, created_at,
                cash_amount, upi_amount, card_amount
            ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)
        ");
        $payStmt->execute([
            $bill_id, $amount, $mode,
            $remarks ?: 'Additional payment via daily report',
            $cashAmt, $upiAmt, $cardAmt
        ]);
    } catch (PDOException $e) {
        // payments table may not exist or have different schema — not critical
        error_log('api_bill_payment: payments table insert skipped: ' . $e->getMessage());
    }

    $pdo->commit();

    echo json_encode([
        'success'       => true,
        'new_paid'      => number_format($newPaid, 2),
        'new_balance'   => number_format($newBalance, 2),
        'new_status'    => $newStatus,
        'message'       => sprintf(
            'Payment of ₹%.2f recorded. New balance: ₹%.2f (%s)',
            $amount, $newBalance, $newStatus
        )
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('api_bill_payment error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
