<?php
require_once 'config/db.php';

try {
    $pdo->exec("ALTER TABLE bills ADD COLUMN doctor_id INT(11) DEFAULT NULL AFTER patient_id");
    echo "bills table updated (doctor_id).<br>";
} catch (PDOException $e) {
    echo "bills table may already have doctor_id: " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE bills ADD CONSTRAINT bills_ibfk_doctor FOREIGN KEY (doctor_id) REFERENCES users(id)");
    echo "bills table foreign key added.<br>";
} catch (PDOException $e) {
    echo "bills table fk may already exist: " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE bill_items ADD COLUMN report_status ENUM('Pending', 'Completed', 'N/A') DEFAULT 'N/A' AFTER amount");
    echo "bill_items table updated (report_status).<br>";
} catch (PDOException $e) {
    echo "bill_items table may already have report_status: " . $e->getMessage() . "<br>";
}
?>
