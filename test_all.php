<?php
require 'config/db.php';
$stmt = $pdo->query('SHOW TABLES LIKE "%services%"');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($tables);
