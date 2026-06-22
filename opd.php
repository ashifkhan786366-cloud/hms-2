<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$success = $error = '';
$patient_id = $_GET['patient_id'] ?? '';

// Fetch Doctors
$stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'Doctor'");
$doctors = $stmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $visit_date = date('Y-m-d');
    $symptoms = $_POST['symptoms'];

    // Generate Token
    // Count existing appointments for this doctor today to get next token
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND visit_date = ?");
    $stmt->execute([$doctor_id, $visit_date]);
    $token = $stmt->fetchColumn() + 1;

    $sql = "INSERT INTO appointments (patient_id, doctor_id, visit_date, token_number, symptoms, status) VALUES (?, ?, ?, ?, ?, 'Pending')";
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$patient_id, $doctor_id, $visit_date, $token, $symptoms]);
        $appt_id = $pdo->lastInsertId();

        // ----------------------------------------------------
        // Auto-Generate OPD Bill
        // ----------------------------------------------------
        $s_stmt = $pdo->query("SELECT id, service_name, cost FROM services WHERE service_name LIKE '%OPD%' OR type='OPD' LIMIT 1");
        $opd_service = $s_stmt->fetch();
        $bill_id = 0;

        if ($opd_service) {
            $bill_no = "INV-" . date('ymd') . "-" . rand(100, 999);
            $b_sql = "INSERT INTO bills (bill_number, patient_id, appointment_id, doctor_id, bill_date, total_amount, net_amount, generated_by) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)";
            $b_stmt = $pdo->prepare($b_sql);
            $b_stmt->execute([$bill_no, $patient_id, $appt_id, $doctor_id, $opd_service['cost'], $opd_service['cost'], $_SESSION['user_id']]);
            $bill_id = $pdo->lastInsertId();

            $i_sql = "INSERT INTO bill_items (bill_id, service_name, cost, quantity, amount) VALUES (?, ?, ?, 1, ?)";
            $i_stmt = $pdo->prepare($i_sql);
            $i_stmt->execute([$bill_id, $opd_service['service_name'], $opd_service['cost'], $opd_service['cost']]);
        }
        // ----------------------------------------------------

        $pdo->commit();

        $success = true;
    }
    catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-notes-medical"></i> New OPD Appointment</h4>
                </div>
                <div class="card-body">

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php
endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Patient ID / MR Number (Search first if needed)</label>
                            <div class="input-group">
                                <input type="number" name="patient_id" class="form-control" value="<?php echo htmlspecialchars($patient_id); ?>" required placeholder="Enter Patient System ID" readonly onclick="alert('Please go to Patients list and click OPD button to select a patient.')">
                                <a href="patients.php" class="btn btn-outline-secondary">Select Patient</a>
                            </div>
                            <small class="text-muted">Currently selected Patient ID: <?php echo htmlspecialchars($patient_id); ?></small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Consulting Doctor</label>
                                <select name="doctor_id" class="form-select" required>
                                    <option value="">-- Select Doctor --</option>
                                    <?php foreach ($doctors as $d): ?>
                                        <option value="<?php echo $d['id']; ?>"><?php echo $d['full_name']; ?></option>
                                    <?php
endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Visit Date</label>
                                <input type="text" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Key Symptoms / Reason for Visit</label>
                            <textarea name="symptoms" class="form-control" rows="3" placeholder="Fever, Cold, etc."></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">Generate Token</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($success) && $success === true): ?>
<!-- Post OPD Action Modal -->
<div class="modal fade" id="postOpdModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
      <div class="modal-header bg-success text-white" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
        <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i> OPD Token Generated!</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-4">
        <h4 class="mb-4 text-primary">Token Number: <strong><?php echo $token; ?></strong></h4>
        <p class="text-muted mb-4 fs-5">Please select a print option:</p>
        
        <div class="d-grid gap-3">
            <a href="opd_print.php?id=<?php echo $appt_id; ?>" target="_blank" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-notes-medical me-2"></i> 1. Print OPD Slip
            </a>
            <?php if ($bill_id > 0): ?>
            <a href="opd_bill_print.php?id=<?php echo $bill_id; ?>" target="_blank" class="btn btn-outline-success btn-lg">
                <i class="fas fa-file-invoice-dollar me-2"></i> 2. Print OPD Bill
            </a>
            <a href="print_both_opd_bill.php?appt_id=<?php echo $appt_id; ?>&bill_id=<?php echo $bill_id; ?>" target="_blank" class="btn btn-primary btn-lg shadow-sm">
                <i class="fas fa-print me-2"></i> 3. Print Both (Slip + Bill)
            </a>
            <?php else: ?>
            <div class="alert alert-warning mt-2">No automatic bill generated (OPD service not found).</div>
            <?php endif; ?>
        </div>
      </div>
      <div class="modal-footer justify-content-center border-0 pb-4">
        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var myModal = new bootstrap.Modal(document.getElementById('postOpdModal'));
        myModal.show();
    });
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
