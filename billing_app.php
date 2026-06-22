<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/controllers/BillController.php';

$controller = new BillController();
$action = $_GET['action'] ?? 'create';

switch ($action) {
    case 'create':
        $controller->createBill();
        break;
    case 'save':
        $controller->saveBill();
        break;
    // Feature 2: Bill edit actions
    case 'edit':
        $controller->editBill((int)$_GET['id']);
        break;
    case 'update':
        $controller->updateBill();
        break;
    // -----
    case 'search_patient':
        $controller->searchPatient();
        break;
    case 'search_item':
        $controller->searchItem();
        break;
    case 'print':
        $controller->printBill($_GET['id']);
        break;
    case 'pdf':
        $controller->downloadPDF($_GET['id']);
        break;
    case 'settings':
        $controller->settings();
        break;
    case 'list':
        $controller->listBills();
        break;
    default:
        $controller->createBill();
}
