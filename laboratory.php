<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Lab Management usually involves seeing requested tests and entering results
// For simplicity in this structure, we'll assume Lab Tech sees Paid Bills with Lab items OR Prescribed Tests
// Let's go with Prescribed Tests that aren't done yet.

// However, a simpler flow is: Doctor prescribes test -> Lab Tech adds result.
// I didn't create a 'lab_results' table in the schema initially (My bad, I created `prescription_tests`).
// Let's stick to `prescription_tests` updates.

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_id = $_POST['test_id'];
    $notes = $_POST['result_notes'];

    $stmt = $pdo->prepare("UPDATE prescription_tests SET notes = ? WHERE id = ?");
    $stmt->execute([$notes, $test_id]);
    $success = "Result updated!";
}

// Fetch Pending/Completed Tests
$sql = "SELECT pt.*, p.full_name, p.mr_number, u.full_name as doctor_name 
        FROM prescription_tests pt 
        JOIN prescriptions pr ON pt.prescription_id = pr.id 
        JOIN patients p ON pr.patient_id = p.id 
        JOIN users u ON pr.doctor_id = u.id 
        ORDER BY pt.id DESC";
$tests = $pdo->query($sql)->fetchAll();
?>

<div class="container-fluid mt-4">
    <h2>Laboratory Management</h2>
    
    <div class="card mt-3">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Test Name</th>
                        <th>Doctor</th>
                        <th>Status/Result</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tests as $t): ?>
                    <tr>
                        <td><?php echo $t['id']; ?></td>
                        <td><?php echo $t['full_name']; ?> <small>(<?php echo $t['mr_number']; ?>)</small></td>
                        <td><?php echo $t['test_name']; ?></td>
                        <td><?php echo $t['doctor_name']; ?></td>
                        <td>
                            <?php if ($t['notes']): ?>
                                <span class="text-success"><i class="fas fa-check"></i> <?php echo $t['notes']; ?></span>
                            <?php
    else: ?>
                                <span class="text-danger">Pending</span>
                            <?php
    endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#testModal<?php echo $t['id']; ?>">
                                Enter Result
                            </button>
                        </td>
                    </tr>

                    <!-- Result Modal -->
                    <div class="modal fade" id="testModal<?php echo $t['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST">
                                <input type="hidden" name="test_id" value="<?php echo $t['id']; ?>">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Enter Result: <?php echo $t['test_name']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label>Result / Values</label>
                                        <textarea name="result_notes" class="form-control" rows="3" required><?php echo $t['notes']; ?></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save Result</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
