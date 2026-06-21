<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Only Admin can access
if ($_SESSION['role'] != 'Admin') {
    die("Access Denied");
}

$msg = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update Config File (Primitive CMS)
    $config_file = 'config/db.php';
    $content = file_get_contents($config_file);

    // Update Constants
    $updates = [
        'APP_NAME' => $_POST['app_name'],
        'APP_SHORT_NAME' => $_POST['app_short_name'],
        'APP_ADDRESS' => $_POST['app_address'],
        'APP_PHONE' => $_POST['app_phone'],
        'APP_EMAIL' => $_POST['app_email'],
    ];

    foreach ($updates as $key => $value) {
        $pattern = "/define\('$key', '(.*?)'\);/";
        $replacement = "define('$key', '$value');";
        $content = preg_replace($pattern, $replacement, $content);
    }

    // Handle Logo Upload
    if (isset($_FILES['app_logo']) && $_FILES['app_logo']['error'] == 0) {
        $target_dir = "assets/";
        $target_file = $target_dir . "logo.png"; // Force rename to logo.png

        if (move_uploaded_file($_FILES['app_logo']['tmp_name'], $target_file)) {
            // Ensure config points to this file
            $content = preg_replace("/define\('APP_LOGO', '(.*?)'\);/", "define('APP_LOGO', 'assets/logo.png');", $content);
        }
    }

    file_put_contents($config_file, $content);
    $msg = "Settings updated successfully! Refresh to see changes.";
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">🏥 Hospital Settings (Admin)</h5>
                </div>
                <div class="card-body">
                    <?php if ($msg): ?>
                        <div class="alert alert-success">
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Hospital Name</label>
                            <input type="text" name="app_name" class="form-control" value="<?php echo APP_NAME; ?>"
                                required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Short Name</label>
                                <input type="text" name="app_short_name" class="form-control"
                                    value="<?php echo APP_SHORT_NAME; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="app_phone" class="form-control"
                                    value="<?php echo APP_PHONE; ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="app_email" class="form-control" value="<?php echo APP_EMAIL; ?>"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="app_address" class="form-control" rows="2"
                                required><?php echo APP_ADDRESS; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Update Logo</label>
                            <input type="file" name="app_logo" class="form-control" accept="image/*">
                            <small class="text-muted">Current Logo:</small><br>
                            <img src="<?php echo APP_LOGO; ?>" height="50" class="mt-2 border p-1">
                        </div>

                        <button type="submit" class="btn btn-primary">💾 Save Settings</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>