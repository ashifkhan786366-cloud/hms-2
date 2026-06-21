<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Quick check and auto-migrate accounting DB on first load of financials.php
$check = $pdo->query("SHOW TABLES LIKE 'ac_accounts'")->rowCount();
if ($check == 0) {
    if (file_exists('db_migrate.php')) {
        require_once 'db_migrate.php';
    }
}

// Stats
$asset_stmt = $pdo->query("SELECT SUM(debit - credit) FROM ac_journal_items ji JOIN ac_accounts a ON ji.account_id = a.id WHERE a.type = 'Asset'");
$total_assets = $asset_stmt->fetchColumn() ?: 0.00;

$liab_stmt = $pdo->query("SELECT SUM(credit - debit) FROM ac_journal_items ji JOIN ac_accounts a ON ji.account_id = a.id WHERE a.type = 'Liability'");
$total_liab = $liab_stmt->fetchColumn() ?: 0.00;

$rev_stmt = $pdo->query("SELECT SUM(credit - debit) FROM ac_journal_items ji JOIN ac_accounts a ON ji.account_id = a.id WHERE a.type = 'Revenue'");
$total_rev = $rev_stmt->fetchColumn() ?: 0.00;

$exp_stmt = $pdo->query("SELECT SUM(debit - credit) FROM ac_journal_items ji JOIN ac_accounts a ON ji.account_id = a.id WHERE a.type = 'Expense'");
$total_exp = $exp_stmt->fetchColumn() ?: 0.00;

// Update dynamically from bills table just for view
$total_bills = $pdo->query("SELECT SUM(paid_amount) FROM bills")->fetchColumn() ?: 0.00;

?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between mb-4">
        <h2>Financial Accounting Module</h2>
        <div>
            <a href="journal_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Entry</a>
            <a href="ledger.php" class="btn btn-outline-dark"><i class="fas fa-book"></i> Ledgers</a>
            <a href="profit_loss.php" class="btn btn-outline-success"><i class="fas fa-chart-line"></i> Profit &
                Loss</a>
            <a href="balance_sheet.php" class="btn btn-outline-primary"><i class="fas fa-balance-scale"></i> Balance
                Sheet</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <p class="card-text display-6">
                        <?php echo CURRENCY . number_format($total_rev, 2); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Expenses</h5>
                    <p class="card-text display-6">
                        <?php echo CURRENCY . number_format($total_exp, 2); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Net Profit</h5>
                    <p class="card-text display-6">
                        <?php echo CURRENCY . number_format($total_rev - $total_exp, 2); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Assets</h5>
                    <p class="card-text display-6">
                        <?php echo CURRENCY . number_format($total_assets, 2); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-dark text-white">Recent Transactions</div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Ref No</th>
                        <th>Description</th>
                        <th>Account</th>
                        <th>Debit</th>
                        <th>Credit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q = "SELECT j.entry_date, j.reference_no, j.description, a.name, ji.debit, ji.credit 
                          FROM ac_journal_entries j 
                          JOIN ac_journal_items ji ON j.id = ji.journal_id
                          JOIN ac_accounts a ON ji.account_id = a.id
                          ORDER BY j.entry_date DESC, j.id DESC LIMIT 20";
                    $stmt = $pdo->query($q);
                    while ($row = $stmt->fetch()):
                        ?>
                        <tr>
                            <td>
                                <?php echo date('d-M-Y', strtotime($row['entry_date'])); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['reference_no']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['description']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['name']); ?>
                            </td>
                            <td class="text-success">
                                <?php echo $row['debit'] > 0 ? CURRENCY . $row['debit'] : '-'; ?>
                            </td>
                            <td class="text-danger">
                                <?php echo $row['credit'] > 0 ? CURRENCY . $row['credit'] : '-'; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>