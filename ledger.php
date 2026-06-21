<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Generate Ledgers
$accounts = $pdo->query("SELECT * FROM ac_accounts WHERE is_active = 1 ORDER BY type, name")->fetchAll();

$selected_acc = $_GET['account_id'] ?? ($accounts[0]['id'] ?? 0);
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$transactions = [];
$opening_balance = 0;

if ($selected_acc) {
    // Get account details
    $acc_stmt = $pdo->prepare("SELECT * FROM ac_accounts WHERE id = ?");
    $acc_stmt->execute([$selected_acc]);
    $account = $acc_stmt->fetch();

    // Calculate Opening Balance
    $open_stmt = $pdo->prepare("SELECT SUM(debit) as tot_debit, SUM(credit) as tot_credit FROM ac_journal_items ji JOIN ac_journal_entries je ON ji.journal_id = je.id WHERE ji.account_id = ? AND je.entry_date < ?");
    $open_stmt->execute([$selected_acc, $start_date]);
    $open_res = $open_stmt->fetch();
    
    if ($account['type'] == 'Asset' || $account['type'] == 'Expense') {
        $opening_balance = ($open_res['tot_debit'] ?? 0) - ($open_res['tot_credit'] ?? 0);
    } else {
        $opening_balance = ($open_res['tot_credit'] ?? 0) - ($open_res['tot_debit'] ?? 0);
    }

    // Get Transactions
    $trans_stmt = $pdo->prepare("
        SELECT je.entry_date, je.reference_no, je.description, ji.debit, ji.credit 
        FROM ac_journal_items ji 
        JOIN ac_journal_entries je ON ji.journal_id = je.id 
        WHERE ji.account_id = ? AND je.entry_date BETWEEN ? AND ?
        ORDER BY je.entry_date ASC, je.id ASC
    ");
    $trans_stmt->execute([$selected_acc, $start_date, $end_date]);
    $transactions = $trans_stmt->fetchAll();
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between mb-4">
        <h2>Account Ledger</h2>
        <a href="financials.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-4">
                    <label>Account</label>
                    <select name="account_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach($accounts as $a): ?>
                            <option value="<?php echo $a['id']; ?>" <?php echo ($selected_acc == $a['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a['name']); ?> [<?php echo $a['type']; ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>From Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <label>To Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-2 mt-4">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <?php if($selected_acc): ?>
    <div class="card">
        <div class="card-header bg-dark text-white">
            Ledger: <strong><?php echo htmlspecialchars($account['name']); ?></strong> 
            <span class="float-end">Period: <?php echo date('d-M-Y', strtotime($start_date)); ?> to <?php echo date('d-M-Y', strtotime($end_date)); ?></span>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Ref No</th>
                        <th>Description</th>
                        <th>Debit (<?php echo CURRENCY; ?>)</th>
                        <th>Credit (<?php echo CURRENCY; ?>)</th>
                        <th>Balance (<?php echo CURRENCY; ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-info">
                        <td colspan="3"><strong>Opening Balance</strong></td>
                        <td></td>
                        <td></td>
                        <td><strong><?php echo number_format($opening_balance, 2); ?></strong></td>
                    </tr>
                    <?php 
                    $running_balance = $opening_balance;
                    $tot_debit = 0;
                    $tot_credit = 0;
                    
                    foreach($transactions as $t): 
                        $tot_debit += $t['debit'];
                        $tot_credit += $t['credit'];
                        
                        // Calculate running balance based on account type
                        if ($account['type'] == 'Asset' || $account['type'] == 'Expense') {
                            $running_balance += ($t['debit'] - $t['credit']);
                        } else {
                            $running_balance += ($t['credit'] - $t['debit']);
                        }
                    ?>
                    <tr>
                        <td><?php echo date('d-M-Y', strtotime($t['entry_date'])); ?></td>
                        <td><?php echo htmlspecialchars($t['reference_no']); ?></td>
                        <td><?php echo htmlspecialchars($t['description']); ?></td>
                        <td class="text-success"><?php echo $t['debit'] > 0 ? number_format($t['debit'], 2) : '-'; ?></td>
                        <td class="text-danger"><?php echo $t['credit'] > 0 ? number_format($t['credit'], 2) : '-'; ?></td>
                        <td><?php echo number_format($running_balance, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <th colspan="3" class="text-end">Total Period Shift:</th>
                        <th class="text-success"><?php echo number_format($tot_debit, 2); ?></th>
                        <th class="text-danger"><?php echo number_format($tot_credit, 2); ?></th>
                        <th class="text-primary"><?php echo number_format($running_balance, 2); ?></th>
                    </tr>
                    <tr class="table-dark text-white">
                        <th colspan="5" class="text-end">Closing Balance:</th>
                        <th><?php echo number_format($running_balance, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
