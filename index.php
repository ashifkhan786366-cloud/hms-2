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

// Today's Lab Patients
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT b.patient_id) FROM bill_items bi JOIN bills b ON bi.bill_id = b.id WHERE DATE(b.bill_date) = ? AND bi.report_status IN ('Pending', 'Completed')");
$stmt->execute([$today]);
$lab_patient_count = $stmt->fetchColumn() ?: 0;

?>

<div class="container-fluid">
    <h1 class="mt-4">Dashboard</h1>
    <p>Welcome back, <strong><?php echo $_SESSION['full_name']; ?></strong> (<?php echo $_SESSION['role']; ?>)</p>

    <!-- Dashboard Hover Styles -->
    <style>
        .stat-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>

    <!-- Quick Stats Cards -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card text-white bg-primary h-100 stat-card" onclick="window.location.href='opd.php'">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-user-md"></i> Today's OPD</h5>
                    <p class="card-text display-4"><?php echo $opd_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-white bg-success h-100 stat-card" onclick="window.location.href='laboratory.php'">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-microscope"></i> Today's Lab Patients</h5>
                    <p class="card-text display-4"><?php echo $lab_patient_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-white bg-warning text-dark h-100 stat-card" onclick="window.location.href='ipd.php'">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-procedures"></i> IPD Occupancy</h5>
                    <p class="card-text display-4"><?php echo $ipd_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-white bg-info text-dark h-100 stat-card" onclick="window.location.href='patients.php'">
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
                <div class="card-header bg-secondary text-white">Quick Actions</div>
                <div class="card-body">
                    <a href="patients.php" class="btn btn-outline-primary btn-lg m-2"><i class="fas fa-notes-medical"></i> New OPD Visit</a>
                    <a href="patient_add.php" class="btn btn-outline-success btn-lg m-2"><i class="fas fa-user-plus"></i> Register Patient</a>
                    <a href="billing_app.php?action=create" class="btn btn-outline-dark btn-lg m-2"><i class="fas fa-file-invoice"></i> Create Bill</a>
                    <a href="laboratory.php" class="btn btn-outline-warning btn-lg m-2"><i class="fas fa-flask"></i> Lab Reports</a>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Fetch Today's Detailed OPD Patients
    $stmt = $pdo->prepare("
        SELECT 
            a.id as appointment_id,
            a.patient_id,
            a.token_number,
            a.status,
            a.symptoms,
            p.mr_number,
            p.full_name as patient_name,
            p.age,
            p.gender,
            p.phone,
            u.full_name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        LEFT JOIN users u ON a.doctor_id = u.id
        WHERE a.visit_date = ?
        ORDER BY a.token_number ASC
    ");
    $stmt->execute([$today]);
    $todays_patients = $stmt->fetchAll();
    ?>

    <!-- Today's OPD Detailed List -->
    <div class="row mt-4 mb-5">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Today's OPD Patients</h5>
                    <span class="badge bg-light text-primary rounded-pill"><?php echo count($todays_patients); ?> Patients</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Token No.</th>
                                    <th>MR Number</th>
                                    <th>Patient Name</th>
                                    <th>Age/Gender</th>
                                    <th>Phone</th>
                                    <th>Consulting Doctor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($todays_patients) > 0): ?>
                                    <?php foreach ($todays_patients as $tp): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary rounded-circle px-2 py-1"><?php echo htmlspecialchars($tp['token_number']); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($tp['mr_number']); ?></strong></td>
                                        <td class="fw-bold">
                                            <a href="patient_view.php?id=<?php echo $tp['patient_id']; ?>" class="text-primary text-decoration-none">
                                                <?php echo htmlspecialchars($tp['patient_name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($tp['age']) . ' / ' . htmlspecialchars($tp['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($tp['phone']); ?></td>
                                        <td><?php echo $tp['doctor_name'] ? 'Dr. ' . htmlspecialchars($tp['doctor_name']) : 'N/A'; ?></td>
                                        <td id="status-cell-<?php echo $tp['appointment_id']; ?>">
                                            <?php if ($tp['status'] == 'Pending'): ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="form-check mb-0 me-2" title="Mark as Visited">
                                                        <input class="form-check-input" type="checkbox" onchange="markVisitOk(<?php echo $tp['appointment_id']; ?>, this)" id="chk-<?php echo $tp['appointment_id']; ?>" style="cursor: pointer; transform: scale(1.3);">
                                                    </div>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>
                                                </div>
                                            <?php elseif ($tp['status'] == 'Completed'): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Visit OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($tp['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-calendar-times fa-3x mb-3 text-light-gray"></i>
                                            <h5>No OPD patients yet for today.</h5>
                                            <a href="patients.php" class="btn btn-sm btn-outline-primary mt-2">Go to Patient List</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Script for OPD Status Update -->
<script>
function markVisitOk(appointmentId, checkbox) {
    if (checkbox.checked) {
        if(confirm("Mark this patient's visit as OK / Completed?")) {
            // Disable to prevent multiple clicks
            checkbox.disabled = true;
            
            // Send AJAX Request
            let formData = new FormData();
            formData.append('appointment_id', appointmentId);
            
            fetch('ajax_update_opd_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Instantly update UI without reload
                    document.getElementById('status-cell-' + appointmentId).innerHTML = '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Visit OK</span>';
                } else {
                    alert('Error updating status: ' + (data.message || 'Unknown error'));
                    checkbox.checked = false;
                    checkbox.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Connection error occurred.');
                checkbox.checked = false;
                checkbox.disabled = false;
            });
        } else {
            // User cancelled
            checkbox.checked = false;
        }
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
