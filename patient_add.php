<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $dob = !empty($_POST['dob']) ? trim($_POST['dob']) : null;

    // Generate MR Number (YearMonth-Random)
    $mr_number = "MR-" . date('ym') . "-" . rand(1000, 9999);

    // Handle Photo Upload (Simple)
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);
        $target_file = $target_dir . basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
        $photo_path = $target_file;
    }

    $sql = "INSERT INTO patients (mr_number, full_name, gender, age, dob, phone, address, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mr_number, $full_name, $gender, $age, $dob, $phone, $address, $photo_path]);
        $new_patient_id = $pdo->lastInsertId(); // Get the ID for redirection
        $success = true;
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="row justify-content-center mt-4">
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-plus"></i> New Patient Registration</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($success) && $success === true): ?>
                        <div class="alert alert-success">
                            Patient registered successfully! MR Number: <strong><?php echo $mr_number; ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Age <span class="text-danger">*</span></label>
                                <input type="number" name="age" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Patient Photo</label>
                                <input type="file" name="photo" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="patients.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-5">Register Patient</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($success) && $success === true): ?>
<!-- Post Registration Action Modal -->
<div class="modal fade" id="postRegModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="postRegModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
      <div class="modal-header bg-success text-white" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
        <h5 class="modal-title" id="postRegModalLabel">
            <i class="fas fa-check-circle me-2"></i> Registration Successful!
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-4">
        <h4 class="mb-4 text-primary">Patient MR No: <strong><?php echo $mr_number; ?></strong></h4>
        <p class="text-muted mb-4 fs-5">What is the category of this visit? Please select an action to proceed.</p>
        
        <div class="row g-3 justify-content-center">
            <div class="col-md-6 col-lg-3">
                <a href="opd.php?patient_id=<?php echo $new_patient_id; ?>&category=OPD" class="btn btn-outline-primary w-100 py-4 h-100 d-flex flex-column align-items-center justify-content-center" style="border-radius: 12px; transition: all 0.3s;">
                    <i class="fas fa-stethoscope fa-3x mb-2"></i>
                    <span class="fs-5 fw-bold">OPD Consult</span>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="opd.php?patient_id=<?php echo $new_patient_id; ?>&category=Injection" class="btn btn-outline-info w-100 py-4 h-100 d-flex flex-column align-items-center justify-content-center" style="border-radius: 12px; transition: all 0.3s;">
                    <i class="fas fa-syringe fa-3x mb-2"></i>
                    <span class="fs-5 fw-bold">Injection Only</span>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="opd.php?patient_id=<?php echo $new_patient_id; ?>&category=Dressing" class="btn btn-outline-warning w-100 py-4 h-100 d-flex flex-column align-items-center justify-content-center" style="border-radius: 12px; transition: all 0.3s;">
                    <i class="fas fa-band-aid fa-3x mb-2"></i>
                    <span class="fs-5 fw-bold">Dressing</span>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="opd.php?patient_id=<?php echo $new_patient_id; ?>&category=Other" class="btn btn-outline-secondary w-100 py-4 h-100 d-flex flex-column align-items-center justify-content-center" style="border-radius: 12px; transition: all 0.3s;">
                    <i class="fas fa-ellipsis-h fa-3x mb-2"></i>
                    <span class="fs-5 fw-bold">Other</span>
                </a>
            </div>
            <div class="col-md-6 col-lg-3 mt-3 mt-lg-0">
                <a href="billing.php?patient_id=<?php echo $new_patient_id; ?>" class="btn btn-outline-danger w-100 py-4 h-100 d-flex flex-column align-items-center justify-content-center" style="border-radius: 12px; transition: all 0.3s;">
                    <i class="fas fa-microscope fa-3x mb-2"></i>
                    <span class="fs-5 fw-bold">Lab Bill</span>
                </a>
            </div>
        </div>
      </div>
      <div class="modal-footer justify-content-center border-0 pb-4">
        <a href="patients.php" class="btn btn-light px-4">Skip & Go to Patient List</a>
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var myModal = new bootstrap.Modal(document.getElementById('postRegModal'));
        myModal.show();
    });
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
