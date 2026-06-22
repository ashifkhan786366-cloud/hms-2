<?php
/**
 * api_vouchers.php
 * AJAX API for Daily Voucher CRUD operations.
 * All responses are JSON.
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// ─────────── Helper: auto-generate voucher number ────────────
function generateVoucherNumber(PDO $pdo, string $date): string {
    $prefix = 'VCH-' . str_replace('-', '', $date) . '-';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_vouchers WHERE voucher_date = ?");
    $stmt->execute([$date]);
    $count = (int)$stmt->fetchColumn() + 1;
    return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
}

// ─────────── Helper: check lock ──────────────────────────────
function isLocked(PDO $pdo, string $date): bool {
    try {
        $stmt = $pdo->prepare("SELECT id FROM daily_report_locks WHERE report_date = ?");
        $stmt->execute([$date]);
        return (bool)$stmt->fetch();
    } catch (PDOException $e) { return false; }
}

switch ($action) {

    // ══════════════════════════════════════════════
    // LIST vouchers for a date
    // ══════════════════════════════════════════════
    case 'list':
        $date = $_GET['date'] ?? date('Y-m-d');
        $search = trim($_GET['search'] ?? '');
        try {
            $where = "WHERE voucher_date = :date";
            $params = [':date' => $date];
            if ($search !== '') {
                $where .= " AND (staff_name LIKE :s OR category LIKE :s2 OR purpose LIKE :s3)";
                $like = '%' . $search . '%';
                $params[':s']  = $like;
                $params[':s2'] = $like;
                $params[':s3'] = $like;
            }
            $stmt = $pdo->prepare("SELECT * FROM daily_vouchers $where ORDER BY voucher_time ASC, id ASC");
            $stmt->execute($params);
            $vouchers = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $vouchers, 'locked' => isLocked($pdo, $date)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ══════════════════════════════════════════════
    // SAVE (insert or update) a voucher
    // ══════════════════════════════════════════════
    case 'save':
        $date = $_POST['voucher_date'] ?? date('Y-m-d');
        if (isLocked($pdo, $date)) {
            echo json_encode(['success' => false, 'error' => 'Report is locked for this date.']);
            break;
        }
        try {
            $id        = (int)($_POST['id'] ?? 0);
            $type      = in_array($_POST['voucher_type'] ?? '', ['Receipt','Payment']) ? $_POST['voucher_type'] : 'Payment';
            $amount    = abs((float)($_POST['amount'] ?? 0));
            $category  = trim($_POST['category'] ?? 'Other');
            $staff     = trim($_POST['staff_name'] ?? '');
            $purpose   = trim($_POST['purpose'] ?? '');
            $refNo     = trim($_POST['reference_no'] ?? '');
            $time      = $_POST['voucher_time'] ?? date('H:i:s');
            $payMode   = in_array($_POST['payment_mode'] ?? '', ['Cash','UPI','Bank Transfer','Other'])
                         ? $_POST['payment_mode'] : 'Cash';
            $note      = trim($_POST['note'] ?? '');

            if ($purpose === '') throw new Exception('Purpose/Description is required.');
            if ($amount <= 0)    throw new Exception('Amount must be greater than zero.');

            if ($id > 0) {
                // UPDATE existing
                $stmt = $pdo->prepare("
                    UPDATE daily_vouchers SET
                        voucher_date  = ?, voucher_time = ?, voucher_type = ?,
                        staff_name    = ?, category     = ?, purpose      = ?,
                        amount        = ?, payment_mode = ?, reference_no = ?, note = ?
                    WHERE id = ?
                ");
                $stmt->execute([$date, $time, $type, $staff, $category, $purpose,
                                $amount, $payMode, $refNo, $note, $id]);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Voucher updated.']);
            } else {
                // INSERT new
                $vchNo = generateVoucherNumber($pdo, $date);
                $stmt = $pdo->prepare("
                    INSERT INTO daily_vouchers
                        (voucher_number, voucher_date, voucher_time, voucher_type,
                         staff_name, category, purpose, amount, payment_mode,
                         reference_no, note, created_by)
                    VALUES (?,?,?,?, ?,?,?,?,?,?, ?,?)
                ");
                $stmt->execute([
                    $vchNo, $date, $time, $type,
                    $staff, $category, $purpose, $amount, $payMode,
                    $refNo, $note,
                    $_SESSION['user_id'] ?? null
                ]);
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(),
                                  'voucher_number' => $vchNo, 'message' => 'Voucher saved.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ══════════════════════════════════════════════
    // GET single voucher (for edit pre-fill)
    // ══════════════════════════════════════════════
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("SELECT * FROM daily_vouchers WHERE id = ?");
            $stmt->execute([$id]);
            $v = $stmt->fetch();
            if ($v) echo json_encode(['success' => true, 'data' => $v]);
            else     echo json_encode(['success' => false, 'error' => 'Not found.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ══════════════════════════════════════════════
    // DELETE a voucher
    // ══════════════════════════════════════════════
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        try {
            // check lock
            $stmt = $pdo->prepare("SELECT voucher_date FROM daily_vouchers WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && isLocked($pdo, $row['voucher_date'])) {
                echo json_encode(['success' => false, 'error' => 'Report is locked.']);
                break;
            }
            $pdo->prepare("DELETE FROM daily_vouchers WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Voucher deleted.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ══════════════════════════════════════════════
    // CHECK lock status
    // ══════════════════════════════════════════════
    case 'check_lock':
        $date = $_GET['date'] ?? date('Y-m-d');
        echo json_encode(['success' => true, 'locked' => isLocked($pdo, $date)]);
        break;

    // ══════════════════════════════════════════════
    // LOCK / UNLOCK a date
    // ══════════════════════════════════════════════
    case 'toggle_lock':
        $date = $_POST['date'] ?? date('Y-m-d');
        try {
            if (isLocked($pdo, $date)) {
                $pdo->prepare("DELETE FROM daily_report_locks WHERE report_date = ?")->execute([$date]);
                echo json_encode(['success' => true, 'locked' => false, 'message' => 'Report unlocked.']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO daily_report_locks (report_date, locked_by) VALUES (?,?) ON DUPLICATE KEY UPDATE locked_at = NOW()");
                $stmt->execute([$date, $_SESSION['user_id'] ?? null]);
                echo json_encode(['success' => true, 'locked' => true, 'message' => 'Report locked.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ══════════════════════════════════════════════
    // SUMMARY for a date (totals)
    // ══════════════════════════════════════════════
    case 'summary':
        $date = $_GET['date'] ?? date('Y-m-d');
        try {
            $stmt = $pdo->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN voucher_type='Receipt' THEN amount ELSE 0 END), 0) as total_receipts,
                    COALESCE(SUM(CASE WHEN voucher_type='Payment' THEN amount ELSE 0 END), 0) as total_payments,
                    COUNT(*) as total_count
                FROM daily_vouchers WHERE voucher_date = ?
            ");
            $stmt->execute([$date]);
            $sum = $stmt->fetch();
            $sum['net'] = (float)$sum['total_receipts'] - (float)$sum['total_payments'];
            echo json_encode(['success' => true, 'data' => $sum, 'locked' => isLocked($pdo, $date)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . htmlspecialchars($action)]);
}
