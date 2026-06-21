<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Get Today's Stats
$today = date('Y-m-d');

// OPD Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE visit_date = ?");
$stmt->execute([$today]);
$opd_count = $stmt->fetchColumn();

// New Patients Added
$stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$patient_count = $stmt->fetchColumn();

// IPD Admitted
$stmt = $pdo->query("SELECT COUNT(*) FROM ipd_admissions WHERE status = 'Admitted'");
$ipd_count = $stmt->fetchColumn();

// Today's Revenue
$stmt = $pdo->prepare("SELECT SUM(paid_amount) FROM bills WHERE DATE(bill_date) = ?");
$stmt->execute([$today]);
$revenue = $stmt->fetchColumn() ?: 0;

?>

<div class="container-fluid">
    <h1 class="mt-4">Dashboard</h1>
    <p>Welcome back, <strong><?php echo $_SESSION['full_name']; ?></strong> (<?php echo $_SESSION['role']; ?>)</p>

    <!-- Quick Stats Cards -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-user-md"></i> Today's OPD</h5>
                    <p class="card-text display-4"><?php echo $opd_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-rupee-sign"></i> Today's Revenue</h5>
                    <p class="card-text display-4"><?php echo CURRENCY . number_format($revenue, 0); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-white bg-warning text-dark h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-procedures"></i> IPD Occupancy</h5>
                    <p class="card-text display-4"><?php echo $ipd_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-white bg-info text-dark h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-user-plus"></i> New Patients</h5>
                    <p class="card-text display-4"><?php echo $patient_count; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Quick Actions</div>
                <div class="card-body">
                    <a href="opd.php" class="btn btn-outline-primary btn-lg m-2"><i class="fas fa-notes-medical"></i> New OPD Visit</a>
                    <a href="patients.php" class="btn btn-outline-success btn-lg m-2"><i class="fas fa-user-plus"></i> Register Patient</a>
                    <a href="billing.php" class="btn btn-outline-dark btn-lg m-2"><i class="fas fa-file-invoice"></i> Create Bill</a>
                    <a href="laboratory.php" class="btn btn-outline-warning btn-lg m-2"><i class="fas fa-flask"></i> Lab Reports</a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
