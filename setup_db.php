<?php
require __DIR__ . '/config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `print_settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `template_type` varchar(20) NOT NULL,
      `header_text` text,
      `footer_text` text,
      `font_family` varchar(100) DEFAULT 'Roboto',
      `font_size` int(11) DEFAULT 14,
      `primary_color` varchar(20) DEFAULT '#0066CC',
      `show_logo` tinyint(1) DEFAULT 1,
      `margin_top` int(11) DEFAULT 20,
      `margin_bottom` int(11) DEFAULT 20,
      `margin_left` int(11) DEFAULT 20,
      `margin_right` int(11) DEFAULT 20,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `template_type` (`template_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    
    // Insert default rows if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM print_settings WHERE template_type IN ('bill', 'opd')");
    if ($stmt->fetchColumn() == 0) {
        $default_header = "<h3>" . APP_NAME . "</h3><p>" . APP_ADDRESS . "</p><p>Phone: " . APP_PHONE . " | Email: " . APP_EMAIL . "</p>";
        $default_footer = "<p>Thank you for choosing " . APP_SHORT_NAME . ". This is a computer-generated document.</p>";
        
        $insert = $pdo->prepare("INSERT INTO print_settings (template_type, header_text, footer_text) VALUES (?, ?, ?)");
        $insert->execute(['bill', $default_header, $default_footer]);
        $insert->execute(['opd', $default_header, $default_footer]);
    }
    
    echo "SUCCESS: Table print_settings created and populated.";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
