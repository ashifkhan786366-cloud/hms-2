<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Fetch Services
$stmt = $pdo->query("SELECT * FROM services ORDER BY type, service_name");
$services = $stmt->fetchAll();

// Handle Bill Creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $items = $_POST['items'] ?? []; // Array of service IDs
    $quantities = $_POST['qty'] ?? [];

    if (!empty($items)) {
        // Generate Bill
        $bill_no = "INV-" . date('ymd') . "-" . rand(100, 999);
        $total = 0;

        // Create Bill Header
        $sql = "INSERT INTO bills (bill_number, patient_id, bill_date, generated_by) VALUES (?, ?, NOW(), ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bill_no, $patient_id, $_SESSION['user_id']]);
        $bill_id = $pdo->lastInsertId();

        // Insert Items
        foreach ($items as $index => $service_id) {
            if (empty($service_id))
                continue;

            // Fetch price again for security
            $s_stmt = $pdo->prepare("SELECT service_name, cost FROM services WHERE id = ?");
            $s_stmt->execute([$service_id]);
            $svc = $s_stmt->fetch();

            $qty = $quantities[$index];
            $amount = $svc['cost'] * $qty;
            $total += $amount;

            $i_stmt = $pdo->prepare("INSERT INTO bill_items (bill_id, service_name, cost, quantity, amount) VALUES (?, ?, ?, ?, ?)");
            $i_stmt->execute([$bill_id, $svc['service_name'], $svc['cost'], $qty, $amount]);
        }

        // Update Total
        $upd = $pdo->prepare("UPDATE bills SET total_amount = ?, net_amount = ? WHERE id = ?");
        $upd->execute([$total, $total, $bill_id]); // Tax/Discount simplified to 0 for now

        echo "<script>window.location.href = 'bill_print.php?id=$bill_id';</script>";
        exit;
    }
}
?>

<div class="container-fluid mt-4">
    <h2 class="mb-4">Generate Bill</h2>

    <form method="POST">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label>Patient ID</label>
                        <input type="number" name="patient_id" class="form-control" required placeholder="Patient ID">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Services & Charges</div>
            <div class="card-body">
                <table class="table" id="billingTable">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th width="150">Cost</th>
                            <th width="100">Qty</th>
                            <th>Total</th>
                            <th><button type="button" class="btn btn-success btn-sm" onclick="addRow()">+</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="items[]" class="form-select service-select" onchange="updatePrice(this)"
                                    required>
                                    <option value="" data-price="0">-- Select Service --</option>
                                    <?php foreach ($services as $s): ?>
                                        <option value="<?php echo $s['id']; ?>" data-price="<?php echo $s['cost']; ?>">
                                            <?php echo $s['service_name']; ?> (<?php echo $s['type']; ?>)
                                        </option>
                                        <?php
                                    endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" class="form-control price" readonly></td>
                            <td><input type="number" name="qty[]" class="form-control qty" value="1"
                                    onchange="calcRow(this)"></td>
                            <td><input type="text" class="form-control row-total" readonly></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">X</button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Grand Total:</th>
                            <th><input type="text" id="grandTotal" class="form-control" readonly></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                <br>
                <button type="submit" class="btn btn-primary btn-lg float-end">Generate & Print Bill</button>
            </div>
        </div>
    </form>
</div>

<script>
    function updatePrice(select) {
        let price = select.options[select.selectedIndex].getAttribute('data-price');
        let row = select.closest('tr');
        row.querySelector('.price').value = price;
        calcRow(select);
    }

    function calcRow(element) {
        let row = element.closest('tr');
        let price = parseFloat(row.querySelector('.price').value) || 0;
        let qty = parseInt(row.querySelector('.qty').value) || 1;
        let total = price * qty;
        row.querySelector('.row-total').value = total.toFixed(2);
        calcGrandTotal();
    }

    function calcGrandTotal() {
        let total = 0;
        document.querySelectorAll('.row-total').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('grandTotal').value = total.toFixed(2);
    }

    function addRow() {
        let table = document.getElementById('billingTable').getElementsByTagName('tbody')[0];
        let newRow = table.rows[0].cloneNode(true);
        // Reset values in new row
        newRow.querySelector('.price').value = '';
        newRow.querySelector('.row-total').value = '';
        newRow.querySelector('.qty').value = '1';
        newRow.querySelector('select').selectedIndex = 0;
        table.appendChild(newRow);
    }

    function removeRow(btn) {
        let row = btn.closest('tr');
        if (document.querySelectorAll('#billingTable tbody tr').length > 1) {
            row.remove();
            calcGrandTotal();
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>