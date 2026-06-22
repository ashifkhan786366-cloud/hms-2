<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Check permissions (Admin, Receptionist, or Accountant typically handle billing items)
if (!in_array($_SESSION['role'], ['Admin', 'Receptionist', 'Accountant'])) {
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission to view this page.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $service_name = trim($_POST['service_name']);
    $cost = floatval($_POST['cost']);
    $type = $_POST['type'];

    if ($_POST['action'] == 'add') {
        $stmt = $pdo->prepare("INSERT INTO services (service_name, cost, type) VALUES (?, ?, ?)");
        $stmt->execute([$service_name, $cost, $type]);
        $success = "Service added successfully.";
    } elseif ($_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE services SET service_name = ?, cost = ?, type = ? WHERE id = ?");
        $stmt->execute([$service_name, $cost, $type, $id]);
        $success = "Service updated successfully.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Optional: Check if the service is used in any existing bills before deleting.
    // For now, simple delete:
    try {
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Service deleted successfully.";
    } catch (PDOException $e) {
         $error = "Cannot delete this service as it is already used in a bill or prescription.";
    }
}

// Fetch all services
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM services WHERE service_name LIKE ? OR type LIKE ? ORDER BY type, service_name LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%"]);
$services = $stmt->fetchAll();

// Count for pagination
$count_sql = "SELECT COUNT(*) FROM services WHERE service_name LIKE ? OR type LIKE ?";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute(["%$search%", "%$search%"]);
$total_services = $count_stmt->fetchColumn();
$total_pages = ceil($total_services / $limit);
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Services & Billing Items Master</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="fas fa-plus"></i> Add New Service
        </button>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" placeholder="Search by Service Name or Type" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service Name</th>
                            <th>Cost (₹)</th>
                            <th>Type Focus</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($services) > 0): ?>
                            <?php foreach ($services as $svc): ?>
                            <tr>
                                <td><?php echo $svc['id']; ?></td>
                                <td><?php echo htmlspecialchars($svc['service_name']); ?></td>
                                <td>₹<?php echo number_format($svc['cost'], 2); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($svc['type']); ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-info text-white me-1" onclick='editService(<?php echo json_encode($svc); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $svc['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this service?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No services found. Add an item starting with above button.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Service / Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="serviceId">
                    
                    <div class="mb-3">
                        <label>Service/Item Name</label>
                        <input type="text" name="service_name" id="serviceName" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Cost (₹)</label>
                        <input type="number" step="0.01" name="cost" id="serviceCost" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Type / Category</label>
                        <select name="type" id="serviceType" class="form-select" required>
                            <option value="OPD">OPD Services</option>
                            <option value="Lab">Pathology / Laboratory / Tests</option>
                            <option value="IPD">IPD / Wards</option>
                            <option value="Other">Other Miscellaneous</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveButton">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editService(svc) {
    document.getElementById('modalTitle').innerText = 'Edit Service / Item';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('serviceId').value = svc.id;
    document.getElementById('serviceName').value = svc.service_name;
    document.getElementById('serviceCost').value = svc.cost;
    document.getElementById('serviceType').value = svc.type;
    document.getElementById('saveButton').innerText = 'Update Item';
    
    var myModal = new bootstrap.Modal(document.getElementById('addServiceModal'));
    myModal.show();
}

// Reset modal when hidden so Add works correctly after an Edit
document.getElementById('addServiceModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').innerText = 'Add New Service / Item';
    document.getElementById('formAction').value = 'add';
    document.getElementById('serviceId').value = '';
    document.getElementById('serviceName').value = '';
    document.getElementById('serviceCost').value = '';
    document.getElementById('serviceType').value = 'OPD';
    document.getElementById('saveButton').innerText = 'Add Item';
});
</script>

<?php require_once 'includes/footer.php'; ?>
