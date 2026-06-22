<?php
require_once 'config/db.php';
header('Content-Type: application/json');

// Simple API Key authentication for n8n
$api_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
$env_api_key = 'SANKHLA_N8N_SECRET_123'; // In production, move to env

if ($api_key !== $env_api_key) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_pending_tasks':
        $stmt = $pdo->query("SELECT * FROM n8n_tasks WHERE status = 'Pending'");
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $tasks]);
        break;

    case 'update_status':
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['id']) && isset($data['status'])) {
            $stmt = $pdo->prepare("UPDATE n8n_tasks SET status = :status WHERE id = :id");
            $updated = $stmt->execute(['status' => $data['status'], 'id' => $data['id']]);
            echo json_encode(['success' => $updated]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid parameters']);
        }
        break;

    case 'add_bug':
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['task_name']) && isset($data['module'])) {
            $stmt = $pdo->prepare("INSERT INTO n8n_tasks (task_name, module, description) VALUES (:task_name, :module, :description)");
            $inserted = $stmt->execute([
                'task_name' => $data['task_name'],
                'module' => $data['module'],
                'description' => $data['description'] ?? ''
            ]);
            echo json_encode(['success' => $inserted, 'id' => $pdo->lastInsertId()]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid parameters']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>