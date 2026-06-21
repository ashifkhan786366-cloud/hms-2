<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

if (!isset($_GET['id'])) {
    die("Patient ID not specified.");
}

$pid = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$pid]);
$p = $stmt->fetch();

if (!$p)
    die("Patient not found.");

// Fetch History
$app_stmt = $pdo->prepare("SELECT a.*, u.full_name as doctor_name FROM appointments a JOIN users u ON a.doctor_id = u.id WHERE a.patient_id = ? ORDER BY a.visit_date DESC");
$app_stmt->execute([$pid]);
$history = $app_stmt->fetchAll();

$bill_stmt = $pdo->prepare("SELECT * FROM bills WHERE patient_id = ? ORDER BY bill_date DESC");
$bill_stmt->execute([$pid]);
$bills = $bill_stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <!-- Patient Profile Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <?php if ($p['photo_path']): ?>
                        <img src="<?php echo $p['photo_path']; ?>" class="img-fluid rounded-circle" style="max-height: 120px;">
                    <?php
else: ?>
                        <i class="fas fa-user-circle fa-6x text-secondary"></i>
                    <?php
endif; ?>
                </div>
                <div class="col-md-8">
                    <h2 class="mb-1"><?php echo $p['full_name']; ?> <small class="text-muted fs-5">(<?php echo $p['gender']; ?>, <?php echo $p['age']; ?> Y)</small></h2>
                    <p class="mb-1"><strong>MR No:</strong> <span class="text-primary"><?php echo $p['mr_number']; ?></span> | <strong>Phone:</strong> <?php echo $p['phone']; ?></p>
                    <p class="mb-1"><strong>Address:</strong> <?php echo $p['address']; ?></p>
                </div>
                <div class="col-md-2 text-end">
                    <a href="opd.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-success mb-2 w-100"><i class="fas fa-stethoscope"></i> New OPD</a>
                    <a href="ipd.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-warning w-100"><i class="fas fa-procedures"></i> Admit IPD</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs for EMR -->
    <ul class="nav nav-tabs" id="emrTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="visits-tab" data-bs-toggle="tab" href="#visits" role="tab">Visit History</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="bills-tab" data-bs-toggle="tab" href="#bills" role="tab">Billing History</a>
        </li>
    </ul>

    <div class="tab-content border border-top-0 p-3 bg-white" id="emrTabContent">
        <!-- Visits Tab -->
        <div class="tab-pane fade show active" id="visits" role="tabpanel">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Token</th>
                        <th>Doctor</th>
                        <th>Symptoms</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?php echo $h['visit_date']; ?></td>
                        <td><?php echo $h['token_number']; ?></td>
                        <td><?php echo $h['doctor_name']; ?></td>
                        <td><?php echo $h['symptoms']; ?></td>
                        <td><?php echo $h['status']; ?></td>
                        <td>
                            <a href="opd_print.php?id=<?php echo $h['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-print"></i> Prescription</a>
                        </td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Bills Tab -->
        <div class="tab-pane fade" id="bills" role="tabpanel">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Bill No</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bills as $b): ?>
                    <tr>
                        <td><?php echo $b['bill_number']; ?></td>
                        <td><?php echo $b['bill_date']; ?></td>
                        <td><?php echo CURRENCY . $b['net_amount']; ?></td>
                        <td><span class="badge bg-<?php echo($b['payment_status'] == 'Paid') ? 'success' : 'warning'; ?>"><?php echo $b['payment_status']; ?></span></td>
                        <td>
                            <a href="bill_print.php?id=<?php echo $b['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-print"></i> Invoice</a>
                        </td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
