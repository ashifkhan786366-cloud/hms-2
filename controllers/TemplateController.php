<?php
// file: /controllers/TemplateController.php
class TemplateController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getSettings($hospital_id = 1) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM print_template_settings WHERE hospital_id = ?");
            $stmt->execute([$hospital_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Fetch Error: " . $e->getMessage());
            return false;
        }
    }

    public function saveSettings($data, $hospital_id = 1) {
        try {
            // Check if exists
            $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM print_template_settings WHERE hospital_id = ?");
            $stmtCheck->execute([$hospital_id]);
            $exists = $stmtCheck->fetchColumn() > 0;

            if ($exists) {
                $sql = "UPDATE print_template_settings SET 
                            logo_path = ?, header_text = ?, footer_text = ?, 
                            font_family = ?, primary_color = ?, secondary_color = ?, 
                            show_watermark = ?, watermark_path = ?, page_size = ?
                        WHERE hospital_id = ?";
            } else {
                $sql = "INSERT INTO print_template_settings 
                            (logo_path, header_text, footer_text, font_family, primary_color, secondary_color, show_watermark, watermark_path, page_size, hospital_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }

            $stmtSave = $this->pdo->prepare($sql);
            return $stmtSave->execute([
                $data['logo_path'], 
                $data['header_text'], 
                $data['footer_text'],
                $data['font_family'], 
                $data['primary_color'], 
                $data['secondary_color'],
                $data['show_watermark'], 
                $data['watermark_path'], 
                $data['page_size'],
                $hospital_id
            ]);
        } catch (PDOException $e) {
            error_log("DB Save Error: " . $e->getMessage());
            return false;
        }
    }
}
