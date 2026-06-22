<?php
class BillModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getSettings() {
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM bill_settings");
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return $settings;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function updateSettings($settings) {
        $stmt = $this->pdo->prepare("UPDATE bill_settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($settings as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        return true;
    }

    public function generateBillNumber() {
        $settings = $this->getSettings();
        $prefix = $settings['bill_prefix'] ?? 'BILL';
        $year = date('Y');
        
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM bills WHERE bill_number LIKE ?");
            $stmt->execute(["{$prefix}-{$year}-%"]);
            $count = $stmt->fetchColumn() + 1;
            return sprintf("%s-%s-%04d", $prefix, $year, $count);
        } catch (PDOException $e) {
            return sprintf("%s-%s-%04d", $prefix, $year, rand(1000, 9999));
        }
    }

    public function searchPatients($term) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, full_name as name, phone, age, gender, mr_number FROM patients WHERE full_name LIKE ? OR phone LIKE ? OR mr_number LIKE ? LIMIT 10");
            $likeTerm = "%$term%";
            $stmt->execute([$likeTerm, $likeTerm, $likeTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getPatientById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, full_name as name, phone, age, gender, address, mr_number, created_at
                FROM patients WHERE id = ?
            ");
            $stmt->execute([(int)$id]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($patient) {
                // blood_group column agar exist kare to fetch karo
                try {
                    $bg = $this->pdo->prepare("SELECT blood_group FROM patients WHERE id = ?");
                    $bg->execute([(int)$id]);
                    $bgRow = $bg->fetch(PDO::FETCH_ASSOC);
                    $patient['blood_group'] = $bgRow['blood_group'] ?? '';
                } catch (PDOException $e) {
                    $patient['blood_group'] = '';
                }
            }
            return $patient;
        } catch (PDOException $e) {
            error_log('getPatientById error: ' . $e->getMessage());
            return false;
        }
    }

    public function searchItems($term) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, service_name as item_name, type as item_type, cost as price, 0 as tax_percent FROM services WHERE service_name LIKE ? ORDER BY type, service_name LIMIT 10");
            $likeTerm = "%$term%";
            $stmt->execute([$likeTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    // Feature 1+3+5: Bill save — discount type, split payment, lab auto-link sab handle
    public function saveBill($data, $items) {
        $this->pdo->beginTransaction();
        
        try {
            // Payment method sanitize karo — ENUM ke hisaab se
            $paymentMethod = $data['payment_mode'] ?? 'Cash';
            $allowedMethods = ['Cash', 'Card', 'UPI', 'Insurance', 'Split', 'Other'];
            if (!in_array($paymentMethod, $allowedMethods)) {
                $paymentMethod = 'Other';
            }

            // Step 1: Basic insert (original schema ke columns) — bill_type bhi include karo
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO bills (
                        bill_number, patient_id, doctor_id, bill_type, total_amount, 
                        discount, tax, net_amount, paid_amount, payment_status, payment_method, generated_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['bill_number'],
                    $data['patient_id'],
                    $data['doctor_id'] ?? null,
                    $data['bill_type'] ?? 'OPD',
                    $data['subtotal'],
                    $data['discount'],
                    $data['tax'],
                    $data['grand_total'],
                    $data['paid_amount'],
                    $data['status'],
                    $paymentMethod,
                    $_SESSION['user_id'] ?? 1
                ]);
            } catch (PDOException $insertE) {
                // bill_type column nahi hai — bina uske insert karo
                error_log("saveBill with bill_type failed, retrying without: " . $insertE->getMessage());
                $stmt = $this->pdo->prepare("
                    INSERT INTO bills (
                        bill_number, patient_id, doctor_id, total_amount, 
                        discount, tax, net_amount, paid_amount, payment_status, payment_method, generated_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['bill_number'],
                    $data['patient_id'],
                    $data['doctor_id'] ?? null,
                    $data['subtotal'],
                    $data['discount'],
                    $data['tax'],
                    $data['grand_total'],
                    $data['paid_amount'],
                    $data['status'],
                    $paymentMethod,
                    $_SESSION['user_id'] ?? 1
                ]);
            }
            
            $billId = $this->pdo->lastInsertId();

            // Extra columns update karo agar columns exist hain (migration ke baad)
            try {
                $extraStmt = $this->pdo->prepare("
                    UPDATE bills SET 
                        discount_type      = ?,
                        discount_percent   = ?,
                        payment_mode_cash  = ?,
                        payment_mode_upi   = ?,
                        balance_due        = ?
                    WHERE id = ?
                ");
                $extraStmt->execute([
                    $data['discount_type']     ?? 'amount',
                    $data['discount_percent']  ?? 0,
                    $data['payment_mode_cash'] ?? 0,
                    $data['payment_mode_upi']  ?? 0,
                    $data['balance_due']       ?? 0,
                    $billId
                ]);
            } catch (PDOException $extraE) {
                // Columns exist nahi karte — migration pending hai, ignore karo
                error_log("Extra bill columns update failed (run migration): " . $extraE->getMessage());
            }
            
            // Bill items insert karo
            // Feature 5: Lab items ka report_status = 'Pending' set karo automatically
            foreach ($items as $item) {
                $itemType = $item['item_type'] ?? 'General';
                
                // Lab item identify karo — service type 'Lab' ho ya item_type mein 'Lab' word ho
                $isLabItem = (
                    strtolower($itemType) === 'lab' ||
                    stripos($itemType, 'lab') !== false ||
                    stripos($itemType, 'pathol') !== false ||
                    stripos($itemType, 'diagnostic') !== false
                );
                $reportStatus = $isLabItem ? 'Pending' : null;

                try {
                    // Naye columns ke saath insert try karo
                    $itemStmt = $this->pdo->prepare("
                        INSERT INTO bill_items (
                            bill_id, service_name, item_type, quantity, cost, discount_percent, amount, report_status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $itemStmt->execute([
                        $billId,
                        $item['item_name'],
                        $itemType,
                        $item['qty'],
                        $item['rate'],
                        $item['discount_percent'] ?? 0,
                        $item['amount'],
                        $reportStatus
                    ]);
                } catch (PDOException $itemE) {
                    // Fallback: column list se item_type/discount_percent/report_status remove karke try karo
                    $itemStmtFallback = $this->pdo->prepare("
                        INSERT INTO bill_items (
                            bill_id, service_name, quantity, cost, amount
                        ) VALUES (?, ?, ?, ?, ?)
                    ");
                    $itemStmtFallback->execute([
                        $billId,
                        $item['item_name'],
                        $item['qty'],
                        $item['rate'],
                        $item['amount']
                    ]);
                }
            }
            
            $this->pdo->commit();
            return $billId;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("DB Save Error in Bills: " . $e->getMessage());
            throw $e;
        }
    }

    // Feature 2: Existing bill update karo (edit save)
    public function updateBill($billId, $data, $items) {
        $this->pdo->beginTransaction();

        try {
            $paymentMethod = $data['payment_mode'] ?? 'Cash';
            $allowedMethods = ['Cash', 'Card', 'UPI', 'Insurance', 'Split', 'Other'];
            if (!in_array($paymentMethod, $allowedMethods)) $paymentMethod = 'Other';

            // Bill main record update karo
            $stmt = $this->pdo->prepare("
                UPDATE bills SET
                    doctor_id       = ?,
                    bill_type       = ?,
                    total_amount    = ?,
                    discount        = ?,
                    tax             = ?,
                    net_amount      = ?,
                    paid_amount     = ?,
                    payment_status  = ?,
                    payment_method  = ?,
                    last_edited_at  = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $data['doctor_id'] ?? null,
                $data['bill_type'],
                $data['subtotal'],
                $data['discount'],
                $data['tax'],
                $data['grand_total'],
                $data['paid_amount'],
                $data['status'],
                $paymentMethod,
                $billId
            ]);

            // Extra columns update karo
            try {
                $extraStmt = $this->pdo->prepare("
                    UPDATE bills SET
                        discount_type     = ?,
                        discount_percent  = ?,
                        payment_mode_cash = ?,
                        payment_mode_upi  = ?,
                        balance_due       = ?
                    WHERE id = ?
                ");
                $extraStmt->execute([
                    $data['discount_type']     ?? 'amount',
                    $data['discount_percent']  ?? 0,
                    $data['payment_mode_cash'] ?? 0,
                    $data['payment_mode_upi']  ?? 0,
                    $data['balance_due']       ?? 0,
                    $billId
                ]);
            } catch (PDOException $e) {
                error_log("Extra columns update skip (migration pending): " . $e->getMessage());
            }

            // Purane items delete karo — naye insert karo
            $this->pdo->prepare("DELETE FROM bill_items WHERE bill_id = ?")->execute([$billId]);

            foreach ($items as $item) {
                $itemType = $item['item_type'] ?? 'General';
                $isLabItem = (
                    strtolower($itemType) === 'lab' ||
                    stripos($itemType, 'lab') !== false ||
                    stripos($itemType, 'pathol') !== false
                );
                $reportStatus = $isLabItem ? 'Pending' : null;

                try {
                    $itemStmt = $this->pdo->prepare("
                        INSERT INTO bill_items (
                            bill_id, service_name, item_type, quantity, cost, discount_percent, amount, report_status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $itemStmt->execute([
                        $billId,
                        $item['item_name'],
                        $itemType,
                        $item['qty'],
                        $item['rate'],
                        $item['discount_percent'] ?? 0,
                        $item['amount'],
                        $reportStatus
                    ]);
                } catch (PDOException $itemE) {
                    $itemStmtFallback = $this->pdo->prepare("
                        INSERT INTO bill_items (bill_id, service_name, quantity, cost, amount) VALUES (?, ?, ?, ?, ?)
                    ");
                    $itemStmtFallback->execute([
                        $billId, $item['item_name'], $item['qty'], $item['rate'], $item['amount']
                    ]);
                }
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("DB Update Error in Bills: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getBill($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT b.*, 
                       p.full_name as patient_name, p.age, p.gender, p.phone, p.mr_number, p.address as patient_address,
                       u.full_name as doctor_name
                FROM bills b 
                LEFT JOIN patients p ON b.patient_id = p.id
                LEFT JOIN users u ON b.doctor_id = u.id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            $bill = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($bill) {
                $stmt = $this->pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
                $stmt->execute([$id]);
                $bill['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $bill['settings'] = $this->getSettings();
            }
            
            return $bill;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Feature 4: getAllBills — filters ke saath, pura data
    public function getAllBills($filters = []) {
        try {
            $where = ['1=1'];
            $params = [];

            // Search filter — patient name ya MR number
            if (!empty($filters['search'])) {
                $where[] = "(p.full_name LIKE ? OR p.mr_number LIKE ? OR b.bill_number LIKE ?)";
                $like = '%' . $filters['search'] . '%';
                $params[] = $like; $params[] = $like; $params[] = $like;
            }

            // Date range filter — bill_date use karo (created_at bills table mein nahi hai)
            if (!empty($filters['date_from'])) {
                $where[] = "DATE(b.bill_date) >= ?";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = "DATE(b.bill_date) <= ?";
                $params[] = $filters['date_to'];
            }

            // Doctor filter
            if (!empty($filters['doctor_id'])) {
                $where[] = "b.doctor_id = ?";
                $params[] = (int)$filters['doctor_id'];
            }

            // Bill type filter — bill_type column check karo (ho sakta hai na ho)
            if (!empty($filters['bill_type'])) {
                $where[] = "b.bill_type = ?";
                $params[] = $filters['bill_type'];
            }

            $whereStr = implode(' AND ', $where);

            // bill_date column use karo, created_at nahi — actual schema ke hisaab se
            $stmt = $this->pdo->prepare("
                SELECT b.id, b.bill_number, b.bill_date as created_at,
                       b.total_amount, b.discount, b.net_amount as grand_total,
                       b.paid_amount, b.payment_status as status, b.payment_method,
                       (b.net_amount - b.paid_amount) as balance,
                       p.full_name as patient_name, p.mr_number,
                       u.full_name as doctor_name,
                       COALESCE(b.bill_type, 'OPD') as bill_type
                FROM bills b 
                LEFT JOIN patients p ON b.patient_id = p.id
                LEFT JOIN users u ON b.doctor_id = u.id
                WHERE {$whereStr}
                ORDER BY b.id DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getAllBills error: " . $e->getMessage());
            return [];
        }
    }
}
