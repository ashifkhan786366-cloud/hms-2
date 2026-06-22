<?php
require_once 'includes/auth_check.php';

// Handle AJAX Request to Update Status BEFORE any HTML is output via header.php
if (isset($_GET['update_status']) && isset($_GET['item_id'])) {
    $item_id = $_GET['item_id'];
    $status = $_GET['status'] == 'completed' ? 'Completed' : 'Pending';
    
    $stmt = $pdo->prepare("UPDATE bill_items SET report_status = ? WHERE id = ?");
    $stmt->execute([$status, $item_id]);
    
    // Clear any previous output buffers to ensure clean JSON
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(["success" => true, "status" => $status]);
    exit;
}

require_once 'includes/header.php';

// Fetch Billed Lab Tests
$sql = "SELECT 
            bi.id as item_id, 
            bi.service_name, 
            bi.cost, 
            bi.report_status,
            b.bill_number,
            b.net_amount,
            (b.net_amount - b.paid_amount) as pending_amount,
            p.mr_number, 
            p.full_name as patient_name, 
            u.full_name as doctor_name 
        FROM bill_items bi
        JOIN bills b ON bi.bill_id = b.id
        JOIN patients p ON b.patient_id = p.id
        LEFT JOIN users u ON b.doctor_id = u.id
        WHERE bi.report_status IN ('Pending', 'Completed')
        ORDER BY b.id DESC, bi.id ASC"; // Order by Bill ID descending, then item ID ascending

$tests_raw = $pdo->query($sql)->fetchAll();

// Group tests by bill_number
$grouped_tests = [];
foreach ($tests_raw as $t) {
    $bill_no = $t['bill_number'];
    if (!isset($grouped_tests[$bill_no])) {
        $grouped_tests[$bill_no] = [
            'mr_number' => $t['mr_number'],
            'patient_name' => $t['patient_name'],
            'doctor_name' => $t['doctor_name'],
            'pending_amount' => $t['pending_amount'],
            'total_cost' => 0,
            'items' => [],
            'all_completed' => true // Assume true until proven false
        ];
    }
    
    $grouped_tests[$bill_no]['total_cost'] += $t['cost'];
    $grouped_tests[$bill_no]['items'][] = [
        'item_id' => $t['item_id'],
        'service_name' => $t['service_name'],
        'report_status' => $t['report_status'],
        'cost' => $t['cost']
    ];
    
    if ($t['report_status'] != 'Completed') {
        $grouped_tests[$bill_no]['all_completed'] = false;
    }
}
?>

<style>
.lab-card {
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border: none;
}
.lab-table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}
.checkbox-lg {
    width: 20px;
    height: 20px;
    cursor: pointer;
}
.main-checkbox-lg {
    width: 25px;
    height: 25px;
    cursor: default; /* Make it clear it's auto-managed */
    pointer-events: none; /* Prevent manual clicking */
}
.status-badge {
    position: relative;
    top: -2px;
}
.test-list-container {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.test-item-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 4px 8px;
    border-radius: 6px;
    background-color: #f8f9fa;
    transition: background-color 0.2s;
}
.test-item-row:hover {
    background-color: #e9ecef;
}
.test-item-completed .test-name-span {
    text-decoration: line-through;
    color: #6c757d;
}
</style>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-flask text-primary"></i> Laboratory Live Reporting</h2>
        <span class="badge bg-info text-dark fs-6" id="pendingCounter">Loading...</span>
    </div>
    
    <div class="card lab-card mt-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover lab-table mb-0 align-middle">
                    <thead class="py-3">
                        <tr>
                            <th class="ps-4" title="Automatically ticked when all tests are done">Done?</th>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th style="min-width: 250px;">Test Name(s)</th>
                            <th>Referring Doctor</th>
                            <th>Total Cost</th>
                            <th>Bill Pending</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($grouped_tests) > 0): ?>
                            <?php foreach ($grouped_tests as $bill_no => $bill_data): ?>
                            <?php $is_row_completed = $bill_data['all_completed']; ?>
                            <tr id="row-<?php echo htmlspecialchars($bill_no); ?>" class="bill-row <?php echo $is_row_completed ? 'table-light text-muted' : ''; ?>">
                                <td class="ps-4">
                                    <div class="form-check">
                                        <input class="form-check-input main-checkbox-lg row-main-checkbox" 
                                               type="checkbox" 
                                               id="main-check-<?php echo htmlspecialchars($bill_no); ?>"
                                               <?php echo $is_row_completed ? 'checked' : ''; ?>
                                               disabled> <!-- Disabled to enforce auto-tick behavior -->
                                    </div>
                                </td>
                                <td><strong><?php echo htmlspecialchars($bill_data['mr_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($bill_data['patient_name']); ?></td>
                                <td>
                                    <div class="test-list-container">
                                        <?php foreach ($bill_data['items'] as $item): ?>
                                            <?php $is_item_completed = ($item['report_status'] == 'Completed'); ?>
                                            <div class="test-item-row <?php echo $is_item_completed ? 'test-item-completed' : ''; ?>" id="test-row-<?php echo $item['item_id']; ?>">
                                                <input class="form-check-input checkbox-lg indv-checkbox" 
                                                       type="checkbox" 
                                                       data-bill-id="<?php echo htmlspecialchars($bill_no); ?>"
                                                       value="<?php echo $item['item_id']; ?>" 
                                                       <?php echo $is_item_completed ? 'checked' : ''; ?>>
                                                <span class="test-name-span fw-bold text-primary <?php echo $is_item_completed ? 'text-muted' : ''; ?>"><?php echo htmlspecialchars($item['service_name']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td><?php echo $bill_data['doctor_name'] ? 'Dr. ' . htmlspecialchars($bill_data['doctor_name']) : '<i>Self / None</i>'; ?></td>
                                <td>₹<?php echo number_format($bill_data['total_cost'], 2); ?></td>
                                <td>
                                    <?php if ($bill_data['pending_amount'] > 0): ?>
                                        <span class="text-danger fw-bold">₹<?php echo number_format($bill_data['pending_amount'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-success"><i class="fas fa-check-circle"></i> Paid</span>
                                    <?php endif; ?>
                                </td>
                                <td id="status-col-<?php echo htmlspecialchars($bill_no); ?>">
                                    <?php if ($is_row_completed): ?>
                                        <span class="badge bg-success status-badge row-status-badge">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark status-badge row-status-badge">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-microscope fa-3x mb-3"></i>
                                    <h5>No Lab Tests Billed Yet</h5>
                                    <p>Go to Billing and bill a Lab service to see it here.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    updateCounter();

    const indvCheckboxes = document.querySelectorAll('.indv-checkbox');
    indvCheckboxes.forEach(box => {
        box.addEventListener('change', function() {
            const itemId = this.value;
            const isChecked = this.checked;
            const statusStr = isChecked ? 'completed' : 'pending';
            const billId = this.getAttribute('data-bill-id');
            
            // 1. Visual Update for the individual test
            const testRow = document.getElementById('test-row-' + itemId);
            const testNameSpan = testRow.querySelector('.test-name-span');
            
            if(isChecked) {
                testRow.classList.add('test-item-completed');
                testNameSpan.classList.add('text-muted');
                testNameSpan.classList.remove('text-primary');
            } else {
                testRow.classList.remove('test-item-completed');
                testNameSpan.classList.remove('text-muted');
                testNameSpan.classList.add('text-primary');
            }

            // 2. Check row completion status
            checkRowCompletion(billId);

            // 3. Update top counter
            updateCounter();

            // 4. Background Ajax Update
            fetch('?update_status=1&item_id=' + itemId + '&status=' + statusStr)
            .then(response => response.json())
            .then(data => {
                if(!data.success) {
                    alert('Error updating status. Please try again.');
                    // Revert visually if failed
                    this.checked = !isChecked;
                    // Trigger change visually to revert the row state if needed
                    this.dispatchEvent(new Event('change')); 
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error updating status.');
                this.checked = !isChecked;
                this.dispatchEvent(new Event('change'));
            });
        });
    });

    function checkRowCompletion(billId) {
        // Get all checkboxes for this bill
        const checkboxes = document.querySelectorAll('.indv-checkbox[data-bill-id="' + billId + '"]');
        const mainCheckbox = document.getElementById('main-check-' + billId);
        const row = document.getElementById('row-' + billId);
        const statusCol = document.getElementById('status-col-' + billId);
        
        // Check if ALL are checked
        let allChecked = true;
        checkboxes.forEach(cb => {
            if (!cb.checked) {
                allChecked = false;
            }
        });

        // Update overall row state
        if (allChecked) {
            mainCheckbox.checked = true;
            row.classList.add('table-light', 'text-muted');
            statusCol.innerHTML = '<span class="badge bg-success status-badge row-status-badge">Completed</span>';
        } else {
            mainCheckbox.checked = false;
            row.classList.remove('table-light', 'text-muted');
            statusCol.innerHTML = '<span class="badge bg-warning text-dark status-badge row-status-badge">Pending</span>';
        }
    }

    function updateCounter() {
        // Count individual uncompleted tests
        const totalPending = document.querySelectorAll('.indv-checkbox:not(:checked)').length;
        document.getElementById('pendingCounter').innerText = totalPending + " Pending Individual Tests";
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
