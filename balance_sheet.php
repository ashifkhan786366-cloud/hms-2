<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Prepare query for Assets
$asset_stmt = $pdo->query("SELECT a.name, SUM(ji.debit - ji.credit) AS balance 
                        FROM ac_journal_items ji JOIN ac_accounts a ON ji.account_id = a.id 
                        WHERE a.type = 'Asset' 
                        GROUP BY a.id HAVING balance != 0");
$assets = $asset_stmt->fetchAll();

// Prepare query for Liabilities
$liab_stmt = $pdo->query("SELECT a.name, SUM(ji.credit - ji.debit) AS balance 
                        FROM ac_journal_items ji JOIN ac_accounts a ON ji.account_id = a.id 
                        WHERE a.type = 'Liability' 
                        GROUP BY a.id HAVING balance != 0");
$liabilities = $liab_stmt->fetchAll();

// Prepare query for Equity
$equity_stmt = $pdo->query("SELECT a.name, SUM(ji.credit - ji.debit) AS balance 
                        FROM ac_journal_items ji JOIN ac_accounts a ON ji.account_id = a.id 
                        WHERE a.type = 'Equity' 
                        GROUP BY a.id HAVING balance != 0");
$equity = $equity_stmt->fetchAll();

// Net Profit goes into Equity
$rev_stmt = $pdo->query("SELECT SUM(credit - debit) FROM ac_journal_items ji JOIN ac_accounts a ON ji.account_id = a.id WHERE a.type = 'Revenue'");
$total_rev = $rev_stmt->fetchColumn() ?: 0.00;

$exp_stmt = $pdo->query("SELECT SUM(debit - credit) FROM ac_journal_items ji JOIN ac_accounts a ON ji.account_id = a.id WHERE a.type = 'Expense'");
$total_exp = $exp_stmt->fetchColumn() ?: 0.00;

$net_profit = $total_rev - $total_exp;

?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between mb-4">
        <h2>Balance Sheet</h2>
        <a href="financials.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="row">
        <!-- ASSETS -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Assets</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tbody>
                            <?php
                            $total_assets = 0;
                            foreach ($assets as $a):
                                $total_assets += $a['balance'];
                                ?>
                                <tr>
                                    <td>
                                        <?php echo $a['name']; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo CURRENCY . number_format($a['balance'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total Assets</th>
                                <th class="text-end text-primary font-weight-bold" style="font-size:1.2rem;">
                                    <?php echo CURRENCY . number_format($total_assets, 2); ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- LIABILITIES & EQUITY -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5>Liabilities & Equity</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-muted">Liabilities</h6>
                    <table class="table mb-4">
                        <tbody>
                            <?php
                            $total_liab = 0;
                            foreach ($liabilities as $l):
                                $total_liab += $l['balance'];
                                ?>
                                <tr>
                                    <td>
                                        <?php echo $l['name']; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo CURRENCY . number_format($l['balance'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total Liabilities</th>
                                <th class="text-end text-danger">
                                    <?php echo CURRENCY . number_format($total_liab, 2); ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>

                    <h6 class="text-muted">Equity</h6>
                    <table class="table">
                        <tbody>
                            <?php
                            $total_eq = 0;
                            foreach ($equity as $e):
                                $total_eq += $e['balance'];
                                ?>
                                <tr>
                                    <td>
                                        <?php echo $e['name']; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo CURRENCY . number_format($e['balance'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td>Retained Earnings (Net Profit)</td>
                                <td class="text-end">
                                    <?php echo CURRENCY . number_format($net_profit, 2); ?>
                                </td>
                            </tr>
                            <?php $total_eq += $net_profit; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total Equity</th>
                                <th class="text-end text-info">
                                    <?php echo CURRENCY . number_format($total_eq, 2); ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="d-flex justify-content-between mt-4">
                        <h5>Total Liab & Equity</h5>
                        <h5 class="text-primary">
                            <?php echo CURRENCY . number_format($total_liab + $total_eq, 2); ?>
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>