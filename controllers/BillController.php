<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/BillModel.php';

class BillController {
    private $model;

    /**
     * @param PDO|null $pdoArg  Optional — bill_print.php passes $pdo directly;
     *                          billing_app.php calls without args (uses global $pdo)
     */
    public function __construct($pdoArg = null) {
        global $pdo;
        if ($pdoArg instanceof PDO) {
            $pdo = $pdoArg; // use the passed-in connection
        }
        if (!($pdo instanceof PDO)) {
            $db  = require __DIR__ . '/../config/db.php';
            $pdo = $db;
        }
        $this->model = new BillModel($pdo);
    }

    public function createBill() {
        $settings = $this->model->getSettings();
        $billNumber = $this->model->generateBillNumber();
        
        global $pdo;
        if(!isset($pdo)){
            $db = require __DIR__ . '/../config/db.php';
            $pdo = isset($pdo) ? $pdo : $db;
        }
        $stmt = $pdo->query("SELECT id, full_name as name FROM users WHERE role='Doctor'");
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $prefill_patient = null;
        if (isset($_GET['patient_id'])) {
            $prefill_patient = $this->model->getPatientById($_GET['patient_id']);
        }
        
        // Edit mode nahi hai — fresh bill
        $editBillData = null;
        
        require_once __DIR__ . '/../views/billing/create_bill.php';
    }

    // Feature 2: Edit Bill — purana bill load karke form mein pre-fill karo
    public function editBill($id) {
        $settings = $this->model->getSettings();
        $bill = $this->model->getBill($id);
        
        if (!$bill) {
            die("Bill not found.");
        }

        // Naya bill number generate nahi karna — existing use karo
        $billNumber = $bill['bill_number'];

        global $pdo;
        $stmt = $pdo->query("SELECT id, full_name as name FROM users WHERE role='Doctor'");
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $prefill_patient = null; // Patient JS se fill hoga editBillData se
        $editBillData = $bill;   // JS ko pass karo
        
        require_once __DIR__ . '/../views/billing/create_bill.php';
    }

    public function saveBill() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $patient_id = (int)($_POST['patient_id'] ?? 0);
                if($patient_id === 0){
                    echo json_encode(['success'=>false, 'error'=>'No patient selected. Please search and select a patient.']);
                    exit;
                }

                $subtotal    = (float)($_POST['subtotal']    ?? 0);
                $discount    = (float)($_POST['total_discount'] ?? 0);
                $tax         = (float)($_POST['total_tax'] ?? 0);
                $grand_total = (float)($_POST['grand_total'] ?? 0);
                $paid_amount = (float)($_POST['paid_amount'] ?? 0);
                $balance_due = (float)($_POST['balance_due'] ?? 0);
                $doctor_id   = (int)($_POST['doctor_id']     ?? 0);

                // Feature 1: Discount type info save karo
                $discount_type    = $_POST['global_discount_type'] ?? 'amount';
                $discount_percent = (float)($_POST['global_discount_val'] ?? 0);

                // Feature 3: Split payment handle karo
                $payment_mode = $_POST['payment_mode'] ?? 'Cash';
                $payment_mode_cash = (float)($_POST['payment_mode_cash'] ?? 0);
                $payment_mode_upi  = (float)($_POST['payment_mode_upi']  ?? 0);

                $billData = [
                    'bill_number'       => $_POST['bill_number'],
                    'patient_id'        => $patient_id,
                    'doctor_id'         => $doctor_id ?: null,
                    'bill_type'         => $_POST['bill_type'] ?? 'OPD',
                    'subtotal'          => $subtotal,
                    'discount'          => $discount,
                    'discount_type'     => $discount_type,
                    'discount_percent'  => ($discount_type === 'percent') ? $discount_percent : 0,
                    'tax'               => $tax,
                    'grand_total'       => $grand_total,
                    'paid_amount'       => $paid_amount,
                    'balance_due'       => $balance_due,
                    'payment_mode'      => $payment_mode,
                    'payment_mode_cash' => $payment_mode_cash,
                    'payment_mode_upi'  => $payment_mode_upi,
                    'status'            => ($balance_due > 0 && $paid_amount > 0) ? 'Partial' : ($balance_due > 0 ? 'Pending' : 'Paid')
                ];
                
                $rawItems = json_decode($_POST['items'], true);
                if (empty($rawItems)) throw new Exception("Must have at least one valid item in bill.");
                
                $cleanItems = [];
                foreach($rawItems as $item){
                    $name = trim($item['item_name'] ?? '');
                    $qty  = (float)($item['qty']  ?? 0);
                    $rate = (float)($item['rate'] ?? 0);
                    $disc = (float)($item['discount_percent'] ?? 0);
                    $amt  = (float)($item['amount'] ?? ($qty*$rate));
                    $type = trim($item['item_type'] ?? 'General');
                    
                    if(empty($name) || $qty==0 || $rate==0) continue;
                    
                    $cleanItems[] = [
                        'item_name'        => $name,
                        'qty'              => $qty,
                        'rate'             => $rate,
                        'discount_percent' => $disc,
                        'amount'           => $amt,
                        'item_type'        => $type
                    ];
                }

                if (empty($cleanItems)) throw new Exception("No valid items provided.");
                
                $billId = $this->model->saveBill($billData, $cleanItems);
                
                echo json_encode(['success' => true, 'bill_id' => $billId, 'message' => 'Bill saved successfully. Bill No: ' . $billData['bill_number'], 'bill_number' => $billData['bill_number']]);
            } catch(PDOException $e){
                error_log('saveBill error: '.$e->getMessage());
                echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }

    // Feature 2: Update existing bill (edit save hone par)
    public function updateBill() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $bill_id = (int)($_POST['edit_bill_id'] ?? 0);
                if ($bill_id === 0) {
                    echo json_encode(['success' => false, 'error' => 'Invalid bill ID for update.']);
                    exit;
                }

                $patient_id  = (int)($_POST['patient_id'] ?? 0);
                $subtotal    = (float)($_POST['subtotal']    ?? 0);
                $discount    = (float)($_POST['total_discount'] ?? 0);
                $tax         = (float)($_POST['total_tax'] ?? 0);
                $grand_total = (float)($_POST['grand_total'] ?? 0);
                $paid_amount = (float)($_POST['paid_amount'] ?? 0);
                $balance_due = (float)($_POST['balance_due'] ?? 0);
                $doctor_id   = (int)($_POST['doctor_id']     ?? 0);

                $discount_type    = $_POST['global_discount_type'] ?? 'amount';
                $discount_percent = (float)($_POST['global_discount_val'] ?? 0);

                $payment_mode      = $_POST['payment_mode'] ?? 'Cash';
                $payment_mode_cash = (float)($_POST['payment_mode_cash'] ?? 0);
                $payment_mode_upi  = (float)($_POST['payment_mode_upi']  ?? 0);

                $billData = [
                    'patient_id'        => $patient_id,
                    'doctor_id'         => $doctor_id ?: null,
                    'bill_type'         => $_POST['bill_type'] ?? 'OPD',
                    'subtotal'          => $subtotal,
                    'discount'          => $discount,
                    'discount_type'     => $discount_type,
                    'discount_percent'  => ($discount_type === 'percent') ? $discount_percent : 0,
                    'tax'               => $tax,
                    'grand_total'       => $grand_total,
                    'paid_amount'       => $paid_amount,
                    'balance_due'       => $balance_due,
                    'payment_mode'      => $payment_mode,
                    'payment_mode_cash' => $payment_mode_cash,
                    'payment_mode_upi'  => $payment_mode_upi,
                    'status'            => ($balance_due > 0 && $paid_amount > 0) ? 'Partial' : ($balance_due > 0 ? 'Pending' : 'Paid')
                ];

                $rawItems = json_decode($_POST['items'], true);
                if (empty($rawItems)) throw new Exception("Must have at least one valid item.");

                $cleanItems = [];
                foreach ($rawItems as $item) {
                    $name = trim($item['item_name'] ?? '');
                    $qty  = (float)($item['qty']  ?? 0);
                    $rate = (float)($item['rate'] ?? 0);
                    $disc = (float)($item['discount_percent'] ?? 0);
                    $amt  = (float)($item['amount'] ?? ($qty * $rate));
                    $type = trim($item['item_type'] ?? 'General');
                    if (empty($name) || $qty == 0) continue;
                    $cleanItems[] = [
                        'item_name'        => $name,
                        'qty'              => $qty,
                        'rate'             => $rate,
                        'discount_percent' => $disc,
                        'amount'           => $amt,
                        'item_type'        => $type
                    ];
                }

                if (empty($cleanItems)) throw new Exception("No valid items provided.");

                $this->model->updateBill($bill_id, $billData, $cleanItems);
                $existingBill = $this->model->getBill($bill_id);

                echo json_encode([
                    'success'     => true,
                    'bill_id'     => $bill_id,
                    'bill_number' => $existingBill['bill_number'],
                    'message'     => 'Bill updated successfully! Bill edit ho gaya — ' . $existingBill['bill_number']
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }

    public function searchPatient() {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode([]); exit; }
        
        try {
            global $pdo; 
            if(!isset($pdo)){
                $db = require __DIR__ . '/../config/db.php';
                $pdo = isset($pdo) ? $pdo : $db;
            }

            $stmt = $pdo->prepare("
                SELECT id, mr_number, full_name as name, 
                       age, gender, phone, address, created_at
                FROM patients 
                WHERE full_name LIKE :q 
                   OR phone LIKE :q2 
                   OR mr_number LIKE :q3
                ORDER BY full_name ASC 
                LIMIT 10
            ");
            $like = '%' . $q . '%';
            $stmt->execute([':q' => $like, ':q2' => $like, ':q3' => $like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // blood_group append karo if column exists
            try {
                foreach ($rows as &$row) {
                    $bg = $pdo->prepare("SELECT blood_group FROM patients WHERE id = ?");
                    $bg->execute([$row['id']]);
                    $bgRow = $bg->fetch(PDO::FETCH_ASSOC);
                    $row['blood_group'] = $bgRow['blood_group'] ?? '';
                }
                unset($row);
            } catch (PDOException $e) { /* blood_group column not present, ignore */ }
            echo json_encode($rows);
        } catch (PDOException $e) {
            echo json_encode([]);
        }
        exit;
    }

    public function searchItem() {
        $term = $_GET['q'] ?? '';
        $items = $this->model->searchItems($term);
        echo json_encode($items);
        exit;
    }

    public function printBill($id) {
        $bill = $this->model->getBill($id);
        if (!$bill) {
            die("Bill not found.");
        }
        require_once __DIR__ . '/../views/billing/bill_print.php';
    }

    public function downloadPDF($id) {
        $bill = $this->model->getBill($id);
        if (!$bill) {
            die("Bill not found.");
        }
        
        ob_start();
        require_once __DIR__ . '/../views/billing/bill_print.php';
        $html = ob_get_clean();
        
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            if (class_exists('\Mpdf\Mpdf')) {
                $mpdf = new \Mpdf\Mpdf();
                $mpdf->WriteHTML($html);
                $filename = "Bill_" . $bill['bill_number'] . "_" . preg_replace('/[^A-Za-z0-9\-]/', '_', $bill['patient_name']) . ".pdf";
                $mpdf->Output($filename, 'D');
                exit;
            }
        }
        
        echo "mPDF is not installed. Please run 'composer require mpdf/mpdf' in your project root.";
    }

    public function settings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settings = [
                'hospital_name'        => $_POST['hospital_name'],
                'hospital_address'     => $_POST['hospital_address'],
                'hospital_phone'       => $_POST['hospital_phone'],
                'bill_prefix'          => $_POST['bill_prefix'],
                'enable_gst'           => $_POST['enable_gst'] ?? '0',
                'gst_number'           => $_POST['gst_number'],
                'default_payment_mode' => $_POST['default_payment_mode'],
                'print_size'           => $_POST['print_size'],
                'primary_color'        => $_POST['primary_color'],
                'secondary_color'      => $_POST['secondary_color'],
                'header_text'          => $_POST['header_text'],
                'footer_text'          => $_POST['footer_text'],
                'show_discount_col'    => $_POST['show_discount_col'] ?? '0',
                'show_tax_col'         => $_POST['show_tax_col'] ?? '0'
            ];
            
            // Handle logo upload
            if (isset($_FILES['hospital_logo']) && $_FILES['hospital_logo']['error'] == 0) {
                $uploadDir = __DIR__ . '/../assets/uploads/';
                if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $logoName = 'logo_' . time() . '.' . pathinfo($_FILES['hospital_logo']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['hospital_logo']['tmp_name'], $uploadDir . $logoName)) {
                    $settings['hospital_logo'] = $logoName;
                }
            }
            
            $this->model->updateSettings($settings);
            header("Location: index.php?page=billing_settings&success=1");
            exit;
        }
        
        $settings = $this->model->getSettings();
        require_once __DIR__ . '/../views/settings/billing_settings.php';
    }
    
    // Feature 4: All Bills Register — filters ke saath
    public function listBills() {
        $filters = [
            'search'      => trim($_GET['search'] ?? ''),
            'date_from'   => $_GET['date_from'] ?? '',
            'date_to'     => $_GET['date_to'] ?? '',
            'doctor_id'   => (int)($_GET['doctor_id'] ?? 0),
            'bill_type'   => $_GET['bill_type'] ?? ''
        ];

        global $pdo;
        // Doctors list for filter dropdown
        $stmt = $pdo->query("SELECT id, full_name as name FROM users WHERE role='Doctor' ORDER BY full_name");
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $bills = $this->model->getAllBills($filters);
        require_once __DIR__ . '/../views/billing/bill_list.php';
    }

    /**
     * getBillData() — used by bill_print.php
     * Returns ['bill' => [...], 'groups' => [partitionKey => ['items'=>[...],'subtotal'=>n]]]
     */
    public function getBillData($bill_id) {
        global $pdo;
        try {
            // Full bill with patient + doctor info
            $stmt = $pdo->prepare("
                SELECT b.*,
                       p.full_name, p.age, p.gender, p.phone, p.mr_number, p.address,
                       u.full_name as doctor_name
                FROM bills b
                LEFT JOIN patients p ON b.patient_id = p.id
                LEFT JOIN users u ON b.doctor_id = u.id
                WHERE b.id = ?
            ");
            $stmt->execute([$bill_id]);
            $bill = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$bill) return null;

            // Bill items
            $istmt = $pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ? ORDER BY id ASC");
            $istmt->execute([$bill_id]);
            $items = $istmt->fetchAll(PDO::FETCH_ASSOC);

            // Group items by item_type (partition key)
            $groups = [];
            foreach ($items as $item) {
                $key = strtoupper(trim($item['item_type'] ?? 'OTHER'));
                // Map common synonyms to standard keys
                if (in_array($key, ['LAB','PATHOL','DIAGNOSTIC','LABORATORY'])) $key = 'LAB';
                if (in_array($key, ['CONSULT','CONSULTATION','OPD'])) $key = 'CONSULTATION';
                if (in_array($key, ['PROC','PROCEDURE','SURGERY'])) $key = 'PROCEDURE';
                if (in_array($key, ['MED','MEDICINE','PHARMACY','DRUG'])) $key = 'MEDICINE';
                if (in_array($key, ['ROOM','BED','WARD'])) $key = 'ROOM';
                if (!isset($groups[$key])) $groups[$key] = ['items' => [], 'subtotal' => 0];
                $groups[$key]['items'][]  = $item;
                $groups[$key]['subtotal'] += (float)($item['amount'] ?? 0);
            }

            return ['bill' => $bill, 'groups' => $groups];
        } catch (PDOException $e) {
            error_log('getBillData error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * getPartitions() — used by bill_print.php
     * Returns sorted partition config from bill_partitions table (or defaults if table missing)
     */
    public function getPartitions() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT * FROM bill_partitions WHERE is_active = 1 ORDER BY sort_order ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rows ?: $this->defaultPartitions();
        } catch (PDOException $e) {
            return $this->defaultPartitions();
        }
    }

    private function defaultPartitions() {
        return [
            ['partition_key'=>'CONSULTATION','label'=>'Consultation Charges',       'sub_total_visible'=>1],
            ['partition_key'=>'LAB',         'label'=>'Laboratory / Investigations','sub_total_visible'=>1],
            ['partition_key'=>'PROCEDURE',   'label'=>'Procedures',                 'sub_total_visible'=>1],
            ['partition_key'=>'MEDICINE',    'label'=>'Medicines / Pharmacy',       'sub_total_visible'=>1],
            ['partition_key'=>'ROOM',        'label'=>'Room / Bed Charges',         'sub_total_visible'=>1],
            ['partition_key'=>'OTHER',       'label'=>'Other Charges',              'sub_total_visible'=>1],
        ];
    }

    /**
     * getBillById() — used by bill_modify.php
     * Returns complete bill + items for editing
     */
    public function getBillById($bill_id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                SELECT b.*,
                       p.full_name as patient_name, p.age, p.gender, p.phone, p.mr_number,
                       u.full_name as doctor_name
                FROM bills b
                LEFT JOIN patients p ON b.patient_id = p.id
                LEFT JOIN users u ON b.doctor_id = u.id
                WHERE b.id = ?
            ");
            $stmt->execute([$bill_id]);
            $bill = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$bill) return null;

            $istmt = $pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ? ORDER BY id ASC");
            $istmt->execute([$bill_id]);
            $bill['items'] = $istmt->fetchAll(PDO::FETCH_ASSOC);
            return $bill;
        } catch (PDOException $e) {
            error_log('getBillById error: ' . $e->getMessage());
            return null;
        }
    }
}
