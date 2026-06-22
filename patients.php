<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search Query
$sql = "SELECT * FROM patients WHERE full_name LIKE ? OR mr_number LIKE ? OR phone LIKE ? ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$patients = $stmt->fetchAll();

// Count for pagination
$count_sql = "SELECT COUNT(*) FROM patients WHERE full_name LIKE ? OR mr_number LIKE ? OR phone LIKE ?";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute(["%$search%", "%$search%", "%$search%"]);
$total_patients = $count_stmt->fetchColumn();
$total_pages = ceil($total_patients / $limit);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1>Patient Management</h1>
        <a href="patient_add.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add New Patient</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" placeholder="Search by Name, MR Number, or Phone" value="<?php echo htmlspecialchars($search); ?>">
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
                            <th>MR No</th>
                            <th>Name</th>
                            <th>Age/Gender</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($patients) > 0): ?>
                            <?php foreach ($patients as $row): ?>
                            <tr>
                                <td><span class="badge bg-info"><?php echo $row['mr_number']; ?></span></td>
                                <td><?php echo $row['full_name']; ?></td>
                                <td><?php echo $row['age']; ?> Y / <?php echo $row['gender']; ?></td>
                                <td><?php echo $row['phone']; ?></td>
                                <td><?php echo substr($row['address'], 0, 30) . '...'; ?></td>
                                <td><span class="text-muted"><i class="far fa-calendar-alt"></i> <?php echo date('d-M-Y', strtotime($row['created_at'])); ?></span></td>
                                <td>
                                    <a href="patient_view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info text-white" title="View Details"><i class="fas fa-eye"></i> View</a>
                                    <a href="patient_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit Patient"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="opd.php?patient_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success" title="New OPD Visit"><i class="fas fa-stethoscope"></i> OPD</a>
                                    <a href="billing_app.php?action=create&patient_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Create Bill" style="background-color: #007bff;"><i class="fas fa-credit-card"></i> Bill</a>
                                </td>
                            </tr>
                            <?php
    endforeach; ?>
                        <?php
else: ?>
                            <tr><td colspan="6" class="text-center">No patients found.</td></tr>
                        <?php
endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php
endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
