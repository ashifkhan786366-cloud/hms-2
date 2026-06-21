<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Add Medicine
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_medicine'])) {
    $name = $_POST['name'];
    $batch = $_POST['batch_no'];
    $exp = $_POST['expiry_date'];
    $qty = $_POST['stock_qty'];
    $price = $_POST['price_per_unit'];
    $mfr = $_POST['manufacturer'];

    $sql = "INSERT INTO medicines (name, batch_no, expiry_date, stock_qty, price_per_unit, manufacturer) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $batch, $exp, $qty, $price, $mfr]);
}

// Dispense (Simple Stock Reduction)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dispense'])) {
    $id = $_POST['medicine_id'];
    $qty = $_POST['dispense_qty'];

    $stmt = $pdo->prepare("UPDATE medicines SET stock_qty = stock_qty - ? WHERE id = ?");
    $stmt->execute([$qty, $id]);
}

$medicines = $pdo->query("SELECT * FROM medicines ORDER BY name")->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h2>Pharmacy Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedModal"><i class="fas fa-plus"></i> Add Medicine</button>
    </div>

    <!-- Stock Alerts -->
    <?php
$alerts = $pdo->query("SELECT * FROM medicines WHERE stock_qty < 50")->fetchAll();
if ($alerts): ?>
        <div class="alert alert-warning">
            <strong>Low Stock Alert:</strong> 
            <?php foreach ($alerts as $a)
        echo $a['name'] . " ({$a['stock_qty']}), "; ?>
        </div>
    <?php
endif; ?>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Manufacturer</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $m): ?>
                    <tr class="<?php echo($m['stock_qty'] < 10 ? 'table-danger' : ''); ?>">
                        <td><?php echo $m['name']; ?></td>
                        <td><?php echo $m['batch_no']; ?></td>
                        <td><?php echo $m['expiry_date']; ?></td>
                        <td><?php echo $m['stock_qty']; ?></td>
                        <td><?php echo CURRENCY . $m['price_per_unit']; ?></td>
                        <td><?php echo $m['manufacturer']; ?></td>
                        <td>
                            <form method="POST" class="d-flex">
                                <input type="hidden" name="dispense" value="1">
                                <input type="hidden" name="medicine_id" value="<?php echo $m['id']; ?>">
                                <input type="number" name="dispense_qty" class="form-control form-control-sm me-2" style="width: 80px;" placeholder="Qty" min="1" max="<?php echo $m['stock_qty']; ?>">
                                <button type="submit" class="btn btn-sm btn-success">Dispense</button>
                            </form>
                        </td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addMedModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="add_medicine" value="1">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Medicine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="row">
                        <div class="col-6 mb-3"><label>Batch No</label><input type="text" name="batch_no" class="form-control"></div>
                        <div class="col-6 mb-3"><label>Expiry</label><input type="date" name="expiry_date" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label>Stock Qty</label><input type="number" name="stock_qty" class="form-control" required></div>
                        <div class="col-6 mb-3"><label>Price/Unit</label><input type="text" name="price_per_unit" class="form-control" required></div>
                    </div>
                    <div class="mb-3"><label>Manufacturer</label><input type="text" name="manufacturer" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Medicine</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
