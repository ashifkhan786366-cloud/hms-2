<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Doctor Checklist
if ($_SESSION['role'] != 'Doctor' && $_SESSION['role'] != 'Admin') {
    echo "<div class='alert alert-danger m-4'>Access Denied. Doctors Only.</div>";
    require_once 'includes/footer.php';
    exit();
}

// Handle Vitals/Diagnosis Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_vitals'])) {
    $appt_id = $_POST['appointment_id'];
    $bp = $_POST['bp'];
    $temp = $_POST['temperature'];
    $weight = $_POST['weight'];
    $diagnosis = $_POST['diagnosis'];
    $advice = $_POST['advice'];

    // Update Appointment
    $stmt = $pdo->prepare("UPDATE appointments SET bp=?, temperature=?, weight=?, status='Checked' WHERE id=?");
    $stmt->execute([$bp, $temp, $weight, $appt_id]);

    // Create Prescription Entry if not exists
    $check = $pdo->prepare("SELECT id FROM prescriptions WHERE appointment_id = ?");
    $check->execute([$appt_id]);
    if ($check->rowCount() == 0) {
        // Fetch patient and doctor IDs
        $info = $pdo->prepare("SELECT patient_id, doctor_id FROM appointments WHERE id = ?");
        $info->execute([$appt_id]);
        $row = $info->fetch();

        $ins = $pdo->prepare("INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, diagnosis, advice) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$appt_id, $row['patient_id'], $row['doctor_id'], $diagnosis, $advice]);
    }
    else {
        // Update existing prescription
        $upd = $pdo->prepare("UPDATE prescriptions SET diagnosis=?, advice=? WHERE appointment_id=?");
        $upd->execute([$diagnosis, $advice, $appt_id]);
    }

    $success = "Consultation saved successfully!";
}

// Add Medicine
if (isset($_POST['add_medicine'])) {
    $pid = $_POST['prescription_id'];
    $med = $_POST['medicine_name'];
    $dos = $_POST['dosage'];
    $dur = $_POST['duration'];
    $ins = $_POST['instruction'];

    $stmt = $pdo->prepare("INSERT INTO prescription_medicines (prescription_id, medicine_name, dosage, duration, instruction) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$pid, $med, $dos, $dur, $ins]);
}

// List Appointments for Today
$uid = $_SESSION['user_id'];
$today = date('Y-m-d');
$sql = "SELECT a.*, p.full_name, p.age, p.gender, p.mr_number FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.visit_date = ? AND (a.doctor_id = ? OR ? = 1) ORDER BY a.status ASC, a.token_number ASC";
// Note: If admin (id 1 usually), show all. Here we just show assigned.
$stmt = $pdo->prepare($sql);
$stmt->execute([$today, $uid, ($_SESSION['role'] == 'Admin' ? 1 : 0)]);
$queue = $stmt->fetchAll();

$active_appt = null;
if (isset($_GET['appt_id'])) {
    $stmt = $pdo->prepare("SELECT a.*, p.full_name, p.age, p.gender, p.mr_number FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.id = ?");
    $stmt->execute([$_GET['appt_id']]);
    $active_appt = $stmt->fetch();

    // Fetch Prescription ID if exists
    $stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE appointment_id = ?");
    $stmt->execute([$_GET['appt_id']]);
    $presc = $stmt->fetch();

    // Fetch Medicines
    $meds = [];
    if ($presc) {
        $stmt = $pdo->prepare("SELECT * FROM prescription_medicines WHERE prescription_id = ?");
        $stmt->execute([$presc['id']]);
        $meds = $stmt->fetchAll();
    }
}
?>

<div class="container-fluid">
    <div class="row mt-3">
        <!-- Sidebar: Patient Queue -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">OPD Queue (Today)</div>
                <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($queue as $q): ?>
                        <a href="?appt_id=<?php echo $q['id']; ?>" class="list-group-item list-group-item-action <?php echo($active_appt && $active_appt['id'] == $q['id']) ? 'active' : ''; ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">T-<?php echo $q['token_number']; ?> <?php echo $q['full_name']; ?></h6>
                                <small><?php echo $q['status']; ?></small>
                            </div>
                            <small><?php echo $q['gender']; ?>, <?php echo $q['age']; ?>y | <?php echo $q['symptoms']; ?></small>
                        </a>
                    <?php
endforeach; ?>
                    <?php if (empty($queue))
    echo "<div class='p-3 text-muted'>No appointments today.</div>"; ?>
                </div>
            </div>
        </div>

        <!-- Main Area: Consultation -->
        <div class="col-md-9">
            <?php if ($active_appt): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Consultation: <?php echo $active_appt['full_name']; ?> (<?php echo $active_appt['mr_number']; ?>)</h5>
                        <?php if ($presc): ?>
                            <a href="opd_print.php?id=<?php echo $active_appt['id']; ?>" target="_blank" class="btn btn-warning btn-sm"><i class="fas fa-print"></i> Print Prescription</a>
                        <?php
    endif; ?>
                    </div>
                    <div class="card-body">
                        <!-- Vitals & Diagnosis Form -->
                        <form method="POST">
                            <input type="hidden" name="appointment_id" value="<?php echo $active_appt['id']; ?>">
                            <input type="hidden" name="update_vitals" value="1">
                            
                            <h6 class="text-primary">Vitals & Symptoms</h6>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label>BP</label>
                                    <input type="text" name="bp" class="form-control form-control-sm" value="<?php echo $active_appt['bp']; ?>" placeholder="120/80">
                                </div>
                                <div class="col-md-3">
                                    <label>Pulse</label>
                                    <input type="text" name="pulse" class="form-control form-control-sm" value="<?php echo $active_appt['pulse']; ?>" placeholder="72">
                                </div>
                                <div class="col-md-3">
                                    <label>Temp</label>
                                    <input type="text" name="temperature" class="form-control form-control-sm" value="<?php echo $active_appt['temperature']; ?>" placeholder="98.6">
                                </div>
                                <div class="col-md-3">
                                    <label>Weight</label>
                                    <input type="text" name="weight" class="form-control form-control-sm" value="<?php echo $active_appt['weight']; ?>" placeholder="kg">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>Diagnosis</label>
                                <textarea name="diagnosis" class="form-control" rows="2"><?php echo $presc['diagnosis'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label>Medical Advice / Notes</label>
                                <textarea name="advice" class="form-control" rows="2"><?php echo $presc['advice'] ?? ''; ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm mb-3">Save Clinical Notes</button>
                        </form>

                        <hr>

                        <!-- Medicines Section (Only if prescription saved) -->
                        <?php if ($presc): ?>
                            <h6 class="text-primary">Prescribed Medicines</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Medicine</th>
                                            <th>Dosage</th>
                                            <th>Duration</th>
                                            <th>Instruction</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($meds as $m): ?>
                                            <tr>
                                                <td><?php echo $m['medicine_name']; ?></td>
                                                <td><?php echo $m['dosage']; ?></td>
                                                <td><?php echo $m['duration']; ?></td>
                                                <td><?php echo $m['instruction']; ?></td>
                                            </tr>
                                        <?php
        endforeach; ?>
                                        
                                        <!-- Add Medicine Row -->
                                        <form method="POST">
                                            <input type="hidden" name="add_medicine" value="1">
                                            <input type="hidden" name="prescription_id" value="<?php echo $presc['id']; ?>">
                                            <tr>
                                                <td><input type="text" name="medicine_name" class="form-control form-control-sm" placeholder="Paracetamol" required></td>
                                                <td><input type="text" name="dosage" class="form-control form-control-sm" placeholder="1-0-1" required></td>
                                                <td><input type="text" name="duration" class="form-control form-control-sm" placeholder="3 days" required></td>
                                                <td><input type="text" name="instruction" class="form-control form-control-sm" placeholder="After food"></td>
                                                <td><button type="submit" class="btn btn-success btn-sm">+</button></td>
                                            </tr>
                                        </form>
                                    </tbody>
                                </table>
                            </div>
                        <?php
    else: ?>
                            <div class="alert alert-info">Save clinical notes first to add medicines.</div>
                        <?php
    endif; ?>
                    </div>
                </div>
            <?php
else: ?>
                <div class="jumbotron text-center mt-5">
                    <h4>Select a patient from the queue to start consultation.</h4>
                </div>
            <?php
endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
