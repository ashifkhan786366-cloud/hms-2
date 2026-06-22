<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Handle adding new Package
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $package_name = $_POST['package_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $total_cost = $_POST['total_cost'] ?? 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO treatment_packages (package_name, description, total_cost) VALUES (?, ?, ?)");
        $stmt->execute([$package_name, $description, $total_cost]);
        $success = "Treatment Package added successfully!";
    } catch (Exception $e) {
        $error = "Failed to add package. " . $e->getMessage();
    }
}

// Fetch Packages
$stmt = $pdo->query("SELECT * FROM treatment_packages ORDER BY package_name ASC");
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1>Treatment Packages</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal"><i
                class="fas fa-plus"></i> Add New Package</button>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Package Name</th>
                            <th>Description</th>
                            <th>Total Cost (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($packages) > 0): ?>
                            <?php foreach ($packages as $row): ?>
                                <tr>
                                    <td><strong>
                                            <?php echo htmlspecialchars($row['package_name']); ?>
                                        </strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($row['description']); ?>
                                    </td>
                                    <td><span class="badge bg-primary">₹
                                            <?php echo number_format($row['total_cost'], 2); ?>
                                        </span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No Treatment Packages found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Package Modal -->
<div class="modal fade" id="addPackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Treatment Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Package Name</label>
                        <input type="text" name="package_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Cost (₹)</label>
                        <input type="number" step="0.01" name="total_cost" class="form-control" value="0.00" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>