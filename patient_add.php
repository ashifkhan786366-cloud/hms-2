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
    $dob = $_POST['dob'];

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
        $success = "Patient registered successfully! MR Number: <strong>$mr_number</strong>";

    // Redirect to View or OPD (Optional)
    // header("Location: patients.php");
    }
    catch (PDOException $e) {
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
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php
endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php
endif; ?>

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

<?php require_once 'includes/footer.php'; ?>
