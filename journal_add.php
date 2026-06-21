<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Fetch Accounts
$stmt = $pdo->query("SELECT * FROM ac_accounts WHERE is_active = 1 ORDER BY type, name");
$accounts = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entry_date = $_POST['entry_date'];
    $reference_no = $_POST['reference_no'];
    $description = $_POST['description'];

    $acc_ids = $_POST['account_id'];
    $debits = $_POST['debit'];
    $credits = $_POST['credit'];

    $total_debit = array_sum($debits);
    $total_credit = array_sum($credits);

    if (round($total_debit, 2) !== round($total_credit, 2)) {
        $error = "Total Debit (" . $total_debit . ") must equal Total Credit (" . $total_credit . ")";
    } else {
        $pdo->beginTransaction();
        try {
            // Insert Journal Head
            $stmt = $pdo->prepare("INSERT INTO ac_journal_entries (entry_date, reference_no, description, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$entry_date, $reference_no, $description, $_SESSION['user_id'] ?? 0]);
            $journal_id = $pdo->lastInsertId();

            // Insert Items
            for ($i = 0; $i < count($acc_ids); $i++) {
                if ($debits[$i] > 0 || $credits[$i] > 0) {
                    $insert_item = $pdo->prepare("INSERT INTO ac_journal_items (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");
                    $insert_item->execute([$journal_id, $acc_ids[$i], $debits[$i] ?: 0, $credits[$i] ?: 0]);
                }
            }

            $pdo->commit();
            header("Location: financials.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error saving entry: " . $e->getMessage();
        }
    }
}

?>

<div class="container-fluid mt-4">
    <h2 class="mb-4">Add Journal Entry</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Date</label>
                        <input type="date" name="entry_date" class="form-control" required
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Reference No (Invoice/Receipt No.)</label>
                        <input type="text" name="reference_no" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label>Description/Narration</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Journal Accounts (Debit must equal Credit)</div>
            <div class="card-body">
                <table class="table" id="journalTable">
                    <thead>
                        <tr>
                            <th>Account Name</th>
                            <th width="150">Debit (
                                <?php echo CURRENCY; ?>)
                            </th>
                            <th width="150">Credit (
                                <?php echo CURRENCY; ?>)
                            </th>
                            <th width="50"><button type="button" class="btn btn-success btn-sm"
                                    onclick="addRow()">+</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="account_id[]" class="form-select" required>
                                    <option value="">-- Select Account --</option>
                                    <?php foreach ($accounts as $a): ?>
                                        <option value="<?php echo $a['id']; ?>">
                                            <?php echo $a['name']; ?> [
                                            <?php echo $a['type']; ?>]
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" step="0.01" name="debit[]" class="form-control debit"
                                    onchange="calcTotal()"></td>
                            <td><input type="number" step="0.01" name="credit[]" class="form-control credit"
                                    onchange="calcTotal()"></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">X</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <select name="account_id[]" class="form-select" required>
                                    <option value="">-- Select Account --</option>
                                    <?php foreach ($accounts as $a): ?>
                                        <option value="<?php echo $a['id']; ?>">
                                            <?php echo $a['name']; ?> [
                                            <?php echo $a['type']; ?>]
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" step="0.01" name="debit[]" class="form-control debit"
                                    onchange="calcTotal()"></td>
                            <td><input type="number" step="0.01" name="credit[]" class="form-control credit"
                                    onchange="calcTotal()"></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">X</button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-end">Total:</th>
                            <th><input type="text" id="totalDebit" class="form-control" readonly></th>
                            <th><input type="text" id="totalCredit" class="form-control" readonly></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                <br>
                <button type="submit" class="btn btn-primary btn-lg float-end">Save Entry</button>
            </div>
        </div>
    </form>
</div>

<script>
    function calcTotal() {
        let tDebit = 0;
        document.querySelectorAll('.debit').forEach(input => {
            tDebit += parseFloat(input.value) || 0;
        });
        document.getElementById('totalDebit').value = tDebit.toFixed(2);

        let tCredit = 0;
        document.querySelectorAll('.credit').forEach(input => {
            tCredit += parseFloat(input.value) || 0;
        });
        document.getElementById('totalCredit').value = tCredit.toFixed(2);
    }

    function addRow() {
        let table = document.getElementById('journalTable').getElementsByTagName('tbody')[0];
        let newRow = table.rows[0].cloneNode(true);
        newRow.querySelector('.debit').value = '';
        newRow.querySelector('.credit').value = '';
        newRow.querySelector('select').selectedIndex = 0;
        table.appendChild(newRow);
    }

    function removeRow(btn) {
        let row = btn.closest('tr');
        if (document.querySelectorAll('#journalTable tbody tr').length > 2) {
            row.remove();
            calcTotal();
        } else {
            alert("Minimum 2 rows required for a journal entry.");
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>