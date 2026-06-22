<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Only Admin can access
if ($_SESSION['role'] != 'Admin') {
    die("Access Denied");
}

$msg = "";
$error = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {

    // Process Key-Value Updates
    $updates = [
        'APP_NAME' => $_POST['app_name'] ?? (defined('APP_NAME') ? APP_NAME : ''),
        'APP_SHORT_NAME' => $_POST['app_short_name'] ?? (defined('APP_SHORT_NAME') ? APP_SHORT_NAME : ''),
        'APP_ADDRESS' => $_POST['app_address'] ?? (defined('APP_ADDRESS') ? APP_ADDRESS : ''),
        'APP_PHONE' => $_POST['app_phone'] ?? (defined('APP_PHONE') ? APP_PHONE : ''),
        'APP_EMAIL' => $_POST['app_email'] ?? (defined('APP_EMAIL') ? APP_EMAIL : ''),
        'BILL_TERMS_CONDITIONS' => $_POST['bill_terms_conditions'] ?? (defined('BILL_TERMS_CONDITIONS') ? BILL_TERMS_CONDITIONS : ''),
        'BILL_FOOTER_MESSAGE' => $_POST['bill_footer_message'] ?? (defined('BILL_FOOTER_MESSAGE') ? BILL_FOOTER_MESSAGE : ''),
        'OPD_FOOTER_MESSAGE' => $_POST['opd_footer_message'] ?? (defined('OPD_FOOTER_MESSAGE') ? OPD_FOOTER_MESSAGE : ''),
        'PRINT_THEME_COLOR' => $_POST['print_theme_color'] ?? (defined('PRINT_THEME_COLOR') ? PRINT_THEME_COLOR : '#9dc3e6')
    ];

    try {
        $stmt = $pdo->prepare("UPDATE hospital_settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($updates as $key => $value) {
            $stmt->execute([$value, $key]);
        }

        // Handle Logo Upload
        if (isset($_FILES['app_logo']) && $_FILES['app_logo']['error'] == 0) {
            $target_dir = "assets/";
            $target_file = $target_dir . "logo.png"; // Ensure name is logo.png
            if (move_uploaded_file($_FILES['app_logo']['tmp_name'], $target_file)) {
                $stmt->execute(['assets/logo.png', 'APP_LOGO']);
            }
        }

        $msg = "Settings updated successfully! Changes will reflect across the system.";

        // Reload settings into constants since they just changed
        $set_stmt = $pdo->query("SELECT setting_key, setting_value FROM hospital_settings");
        $latest_settings = [];
        while ($row = $set_stmt->fetch()) {
            $latest_settings[$row['setting_key']] = $row['setting_value'];
        }

    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
} else {
    // Initial Load
    $set_stmt = $pdo->query("SELECT setting_key, setting_value FROM hospital_settings");
    $latest_settings = [];
    while ($row = $set_stmt->fetch()) {
        $latest_settings[$row['setting_key']] = $row['setting_value'];
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h2 class="mb-4">Master Control Settings</h2>

            <?php if ($msg): ?>
                <div class="alert alert-success"><?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white p-0">
                    <ul class="nav nav-tabs card-header-tabs m-0" id="settingsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active text-white" id="general-tab" data-bs-toggle="tab" href="#general"
                                role="tab">🏢 General Settings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" id="billing-tab" data-bs-toggle="tab" href="#billing"
                                role="tab">🧾 Billing Print Formats</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" id="opd-tab" data-bs-toggle="tab" href="#opd" role="tab">🩺
                                OPD Print Formats</a>
                        </li>
                    </ul>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_settings" value="1">

                    <div class="card-body">
                        <div class="tab-content" id="settingsTabsContent">

                            <!-- General Settings Tab -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <h5 class="mb-3 border-bottom pb-2">Hospital Identity</h5>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Hospital Full Name</label>
                                    <input type="text" name="app_name" class="form-control"
                                        value="<?php echo htmlspecialchars($latest_settings['APP_NAME'] ?? ''); ?>"
                                        required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Short Name</label>
                                        <input type="text" name="app_short_name" class="form-control"
                                            value="<?php echo htmlspecialchars($latest_settings['APP_SHORT_NAME'] ?? ''); ?>"
                                            required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Contact Phone</label>
                                        <input type="text" name="app_phone" class="form-control"
                                            value="<?php echo htmlspecialchars($latest_settings['APP_PHONE'] ?? ''); ?>"
                                            required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Contact Email</label>
                                    <input type="email" name="app_email" class="form-control"
                                        value="<?php echo htmlspecialchars($latest_settings['APP_EMAIL'] ?? ''); ?>"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Address (For Headers)</label>
                                    <textarea name="app_address" class="form-control" rows="2"
                                        required><?php echo htmlspecialchars($latest_settings['APP_ADDRESS'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Update Logo</label>
                                    <input type="file" name="app_logo" class="form-control" accept="image/*">
                                    <small class="text-muted d-block mt-1">Leave empty to keep current logo. Recommended
                                        size: 300x100px.</small>
                                    <img src="<?php echo htmlspecialchars($latest_settings['APP_LOGO'] ?? 'assets/logo.png'); ?>"
                                        height="50" class="mt-2 border p-1 bg-light">
                                </div>
                            </div>

                            <!-- Billing Print Tab -->
                            <div class="tab-pane fade" id="billing" role="tabpanel">
                                <h5 class="mb-3 border-bottom pb-2">Invoice Format Configuration</h5>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Theme Color (Headers / Highlights)</label>
                                    <input type="color" name="print_theme_color" class="form-control form-control-color"
                                        value="<?php echo htmlspecialchars($latest_settings['PRINT_THEME_COLOR'] ?? '#9dc3e6'); ?>"
                                        title="Choose your color">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Terms & Conditions</label>
                                    <small class="text-muted d-block mb-2">These will appear at the bottom left of the
                                        invoice. Use a new line for each term.</small>
                                    <textarea name="bill_terms_conditions" class="form-control"
                                        rows="6"><?php echo htmlspecialchars($latest_settings['BILL_TERMS_CONDITIONS'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Footer Acknowledgement Message</label>
                                    <small class="text-muted d-block mb-2">Example: "Get Well Soon!" or "Thank you for
                                        visiting."</small>
                                    <input type="text" name="bill_footer_message" class="form-control"
                                        value="<?php echo htmlspecialchars($latest_settings['BILL_FOOTER_MESSAGE'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- OPD Print Tab -->
                            <div class="tab-pane fade" id="opd" role="tabpanel">
                                <h5 class="mb-3 border-bottom pb-2">Prescription / OPD Ticket Format</h5>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Disclaimers / Footer Rules</label>
                                    <small class="text-muted d-block mb-2">These will appear at the bottom left of the
                                        prescription.</small>
                                    <textarea name="opd_footer_message" class="form-control"
                                        rows="5"><?php echo htmlspecialchars($latest_settings['OPD_FOOTER_MESSAGE'] ?? ''); ?></textarea>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer bg-light text-end">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save All Master
                            Settings</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
    // Enable tab switching memory so page refresh keeps them on same tab
    document.addEventListener("DOMContentLoaded", function (event) {
        var scrollpos = localStorage.getItem('scrollpos');
        if (scrollpos) window.scrollTo(0, scrollpos);

        var activeTab = localStorage.getItem('activeTab');
        if (activeTab) {
            var el = document.querySelector('a[href="' + activeTab + '"]');
            if (el) {
                new bootstrap.Tab(el).show();
                // fix colors
                document.querySelectorAll('#settingsTabs .nav-link').forEach(nav => {
                    nav.classList.add('text-white');
                    nav.classList.remove('text-dark');
                });
                el.classList.remove('text-white');
                el.classList.add('text-dark');
            }
        }

        document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function (el) {
            el.addEventListener('shown.bs.tab', function (e) {
                localStorage.setItem('activeTab', e.target.getAttribute('href'));

                // Text color fixing for dark background tabs
                document.querySelectorAll('#settingsTabs .nav-link').forEach(nav => {
                    nav.classList.add('text-white');
                    nav.classList.remove('text-dark');
                });
                e.target.classList.remove('text-white');
                e.target.classList.add('text-dark');
            });
        });
    });
    window.onbeforeunload = function (e) {
        localStorage.setItem('scrollpos', window.scrollY);
    };
</script>

<?php require_once 'includes/footer.php'; ?>