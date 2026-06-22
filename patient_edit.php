<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$success = $error = '';
$patient_id = $_GET['id'] ?? null;

if (!$patient_id) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>No patient ID provided.</div></div>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch existing patient data
try {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
    
    if (!$patient) {
        echo "<div class='container mt-4'><div class='alert alert-danger'>Patient not found.</div></div>";
        require_once 'includes/footer.php';
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching patient: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $dob = !empty($_POST['dob']) ? trim($_POST['dob']) : null;

    $sql = "UPDATE patients SET full_name = ?, gender = ?, age = ?, dob = ?, phone = ?, address = ? WHERE id = ?";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $gender, $age, $dob, $phone, $address, $patient_id]);
        $success = true;
        
        // Refresh patient data to show updated values in the form
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch();
        
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="row justify-content-center mt-4">
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-user-edit"></i> Edit Patient Details</h4>
                    <span class="badge bg-light text-dark">MR No: <?php echo htmlspecialchars($patient['mr_number']); ?></span>
                </div>
                <div class="card-body">
                    <?php if (isset($success) && $success === true): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Patient details updated successfully!
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($patient['full_name']); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="Male" <?php echo $patient['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $patient['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $patient['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Age <span class="text-danger">*</span></label>
                                <input type="number" name="age" class="form-control" value="<?php echo htmlspecialchars($patient['age']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($patient['dob']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($patient['phone']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($patient['address']); ?></textarea>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="patients.php" class="btn btn-secondary me-md-2">Back to List</a>
                            <button type="submit" class="btn btn-warning px-5"><i class="fas fa-save"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
