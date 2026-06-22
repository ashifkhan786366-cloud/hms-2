<?php
require_once 'config/db.php';
require_once 'includes/auth_check.php'; // Ensure user is logged in

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];

    // Update status to 'Completed'
    $sql = "UPDATE appointments SET status = 'Completed' WHERE id = ?";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$appointment_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            // Might already be completed or ID not found
            echo json_encode(['success' => false, 'message' => 'No changes made. Status might already be updated.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
