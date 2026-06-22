<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Handle adding new TPA Company
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $company_name = $_POST['company_name'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    $discount = $_POST['discount_percentage'] ?? 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO tpa_companies (company_name, contact_person, contact_email, contact_phone, discount_percentage) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$company_name, $contact_person, $contact_email, $contact_phone, $discount]);
        $success = "TPA Company added successfully!";
    } catch (Exception $e) {
        $error = "Failed to add company. " . $e->getMessage();
    }
}

// Fetch Companies
$stmt = $pdo->query("SELECT * FROM tpa_companies ORDER BY company_name ASC");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1>Corporate & TPA Management</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCompanyModal"><i class="fas fa-plus"></i> Add New TPA</button>
    </div>

    <?php if (isset($success)): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Discount (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($companies) > 0): ?>
                            <?php foreach ($companies as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['company_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact_email']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact_phone']); ?></td>
                                <td><span class="badge bg-success"><?php echo htmlspecialchars($row['discount_percentage']); ?>%</span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No TPA Companies found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Company Modal -->
<div class="modal fade" id="addCompanyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New TPA Company</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
      <div class="modal-body">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
              <label class="form-label">Company Name</label>
              <input type="text" name="company_name" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Contact Person</label>
              <input type="text" name="contact_person" class="form-control">
          </div>
          <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="contact_email" class="form-control">
          </div>
          <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="contact_phone" class="form-control">
          </div>
          <div class="mb-3">
              <label class="form-label">Discount Percentage (%)</label>
              <input type="number" step="0.01" name="discount_percentage" class="form-control" value="0.00" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Company</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
