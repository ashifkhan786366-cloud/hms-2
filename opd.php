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
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$patient_id, $doctor_id, $visit_date, $token, $symptoms]);
        $success = "Appointment booked! Token Number: <strong>$token</strong>";
    }
    catch (PDOException $e) {
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
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php
endif; ?>
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

<?php require_once 'includes/footer.php'; ?>
