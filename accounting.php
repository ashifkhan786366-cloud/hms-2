<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Handle adding transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $type = $_POST['type'] ?? 'Income';
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';
    $reference_id = $_POST['reference_id'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO accounting_ledger (type, amount, description, reference_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type, $amount, $description, $reference_id]);
        $success = "Transaction recorded successfully!";
    } catch (Exception $e) {
        $error = "Failed to record transaction. " . $e->getMessage();
    }
}

// Fetch Ledger
$stmt = $pdo->query("SELECT * FROM accounting_ledger ORDER BY transaction_date DESC LIMIT 100");
$ledger = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_income = 0;
$total_expense = 0;
foreach($ledger as $row) {
    if($row['type'] == 'Income') $total_income += $row['amount'];
    if($row['type'] == 'Expense') $total_expense += $row['amount'];
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1>Financial Ledger</h1>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTransactionModal"><i class="fas fa-plus"></i> Add Transaction</button>
    </div>

    <?php if (isset($success)): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Total Income</h5>
                    <h3>₹<?php echo number_format($total_income, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5>Total Expense</h5>
                    <h3>₹<?php echo number_format($total_expense, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Net Balance</h5>
                    <h3>₹<?php echo number_format($total_income - $total_expense, 2); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Reference ID</th>
                            <th>Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($ledger) > 0): ?>
                            <?php foreach ($ledger as $row): ?>
                            <tr>
                                <td><?php echo date('d-m-Y H:i', strtotime($row['transaction_date'])); ?></td>
                                <td>
                                    <?php if($row['type'] == 'Income'): ?>
                                        <span class="badge bg-success">Income</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Expense</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo htmlspecialchars($row['reference_id']); ?></td>
                                <td><strong>₹<?php echo number_format($row['amount'], 2); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No transactions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Record Transaction</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
      <div class="modal-body">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
              <label class="form-label">Transaction Type</label>
              <select name="type" class="form-select" required>
                  <option value="Income">Income (+)</option>
                  <option value="Expense">Expense (-)</option>
              </select>
          </div>
          <div class="mb-3">
              <label class="form-label">Amount (₹)</label>
              <input type="number" step="0.01" name="amount" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Description</label>
              <input type="text" name="description" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Reference (e.g., Bill No / Receipt)</label>
              <input type="text" name="reference_id" class="form-control">
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Save</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
