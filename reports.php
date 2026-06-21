<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Date Range
$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');

// Revenue Report
$stmt = $pdo->prepare("SELECT SUM(paid_amount) FROM bills WHERE DATE(bill_date) BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$revenue = $stmt->fetchColumn() ?: 0;

// OPD Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE visit_date BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$opd = $stmt->fetchColumn();

// Detailed Bill Report
$stmt = $pdo->prepare("SELECT * FROM bills WHERE DATE(bill_date) BETWEEN ? AND ? ORDER BY bill_date DESC");
$stmt->execute([$from, $to]);
$bills = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <h2>Reports & Analytics</h2>
    
    <form method="GET" class="row mb-4 bg-light p-3 rounded">
        <div class="col-md-3">
            <label>From Date</label>
            <input type="date" name="from" class="form-control" value="<?php echo $from; ?>">
        </div>
        <div class="col-md-3">
            <label>To Date</label>
            <input type="date" name="to" class="form-control" value="<?php echo $to; ?>">
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="button" onclick="window.print()" class="btn btn-secondary w-100">Print Report</button>
        </div>
    </form>

    <div class="row text-center mb-4">
        <div class="col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3><?php echo CURRENCY . number_format($revenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h3><?php echo $opd; ?></h3>
                    <p>Total OPD Visits</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Detailed Transaction Report</div>
        <div class="card-body">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Bill No</th>
                        <th>Date</th>
                        <th>Patient ID</th>
                        <th>Total</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bills as $b): ?>
                    <tr>
                        <td><?php echo $b['bill_number']; ?></td>
                        <td><?php echo $b['bill_date']; ?></td>
                        <td><?php echo $b['patient_id']; ?></td>
                        <td><?php echo CURRENCY . $b['total_amount']; ?></td>
                        <td><?php echo $b['payment_status']; ?></td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
