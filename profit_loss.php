<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Prepare query for Revenues
$rev_stmt = $pdo->query("SELECT a.name, SUM(ji.credit - ji.debit) AS balance 
                        FROM ac_journal_items ji JOIN ac_accounts a ON ji.account_id = a.id 
                        WHERE a.type = 'Revenue' 
                        GROUP BY a.id HAVING balance != 0");
$revenues = $rev_stmt->fetchAll();

// Prepare query for Expenses
$exp_stmt = $pdo->query("SELECT a.name, SUM(ji.debit - ji.credit) AS balance 
                        FROM ac_journal_items ji JOIN ac_accounts a ON ji.account_id = a.id 
                        WHERE a.type = 'Expense' 
                        GROUP BY a.id HAVING balance != 0");
$expenses = $exp_stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between mb-4">
        <h2>Profit & Loss</h2>
        <a href="financials.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="row">
        <!-- REVENUE -->
        <div class="col-md-6 mb-4">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white">
                    <h5>Revenues (Credit)</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0 table-striped">
                        <tbody>
                            <?php
                            $total_rev = 0;
                            foreach ($revenues as $r):
                                $total_rev += $r['balance'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['name']); ?></td>
                                    <td class="text-end text-success">
                                        <?php echo CURRENCY . number_format($r['balance'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-success">
                            <tr>
                                <th>Total Revenue</th>
                                <th class="text-end fw-bold fs-5"><?php echo CURRENCY . number_format($total_rev, 2); ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- EXPENSES -->
        <div class="col-md-6 mb-4">
            <div class="card border-danger h-100">
                <div class="card-header bg-danger text-white">
                    <h5>Expenses (Debit)</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0 table-striped">
                        <tbody>
                            <?php
                            $total_exp = 0;
                            foreach ($expenses as $e):
                                $total_exp += $e['balance'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($e['name']); ?></td>
                                    <td class="text-end text-danger">
                                        <?php echo CURRENCY . number_format($e['balance'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-danger">
                            <tr>
                                <th>Total Expenses</th>
                                <th class="text-end fw-bold fs-5"><?php echo CURRENCY . number_format($total_exp, 2); ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php $net_pl = $total_rev - $total_exp; ?>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card <?php echo ($net_pl >= 0) ? 'border-success' : 'border-danger'; ?>">
                <div class="card-body text-center">
                    <h3 class="mb-0">
                        Net <?php echo ($net_pl >= 0) ? 'Profit' : 'Loss'; ?>:
                        <span class="<?php echo ($net_pl >= 0) ? 'text-success' : 'text-danger'; ?>">
                            <?php echo CURRENCY . number_format(abs($net_pl), 2); ?>
                        </span>
                    </h3>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>