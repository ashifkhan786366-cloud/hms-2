<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Admit Patient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admit_enc'])) {
    $pid = $_POST['patient_id'];
    $doc = $_POST['doctor_id'];
    $bed = $_POST['bed_number'];
    $ward = $_POST['ward_type'];
    $diag = $_POST['diagnosis'];

    $stmt = $pdo->prepare("INSERT INTO ipd_admissions (patient_id, doctor_id, admission_date, bed_number, ward_type, diagnosis, status) VALUES (?, ?, NOW(), ?, ?, ?, 'Admitted')");
    $stmt->execute([$pid, $doc, $bed, $ward, $diag]);
}

// Discharge Patient
if (isset($_GET['discharge_id'])) {
    $id = $_GET['discharge_id'];
    $stmt = $pdo->prepare("UPDATE ipd_admissions SET discharge_date = NOW(), status = 'Discharged' WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location.href='ipd.php';</script>";
}

// Fetch Admissions
$sql = "SELECT i.*, p.full_name, p.mr_number, u.full_name as doctor_name FROM ipd_admissions i JOIN patients p ON i.patient_id = p.id JOIN users u ON i.doctor_id = u.id ORDER BY i.id DESC";
$admissions = $pdo->query($sql)->fetchAll();

// Fetch Doctors for Dropdown
$docs = $pdo->query("SELECT id, full_name FROM users WHERE role='Doctor'")->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h2>IPD Management (Admissions)</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#admitModal"><i class="fas fa-procedures"></i> Admit New Patient</button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>MR No</th>
                        <th>Patient Name</th>
                        <th>Doctor</th>
                        <th>Ward/Bed</th>
                        <th>Admission Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admissions as $a): ?>
                    <tr>
                        <td><?php echo $a['mr_number']; ?></td>
                        <td><?php echo $a['full_name']; ?></td>
                        <td><?php echo $a['doctor_name']; ?></td>
                        <td><?php echo $a['ward_type'] . ' - ' . $a['bed_number']; ?></td>
                        <td><?php echo $a['admission_date']; ?></td>
                        <td><span class="badge bg-<?php echo($a['status'] == 'Admitted' ? 'danger' : 'success'); ?>"><?php echo $a['status']; ?></span></td>
                        <td>
                            <?php if ($a['status'] == 'Admitted'): ?>
                                <a href="?discharge_id=<?php echo $a['id']; ?>" class="btn btn-warning btn-sm" onclick="return confirm('Discharge this patient?')">Discharge</a>
                            <?php
    else: ?>
                                <small><?php echo $a['discharge_date']; ?></small>
                            <?php
    endif; ?>
                        </td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Admit Modal -->
<div class="modal fade" id="admitModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="admit_enc" value="1">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Admit Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Patient ID (System ID)</label>
                        <input type="number" name="patient_id" class="form-control" required value="<?php echo $_GET['patient_id'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label>Doctor</label>
                        <select name="doctor_id" class="form-select" required>
                            <?php foreach ($docs as $d):
    echo "<option value='{$d['id']}'>{$d['full_name']}</option>";
endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>Ward Type</label>
                            <select name="ward_type" class="form-select">
                                <option>General Ward</option>
                                <option>ICU</option>
                                <option>Private Room</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label>Bed Number</label>
                            <input type="text" name="bed_number" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Diagnosis/Reason</label>
                        <textarea name="diagnosis" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Admit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
