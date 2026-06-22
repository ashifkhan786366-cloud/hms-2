<?php
// file: /save_template.php
header('Content-Type: application/json');

require_once 'config/db.php';
require_once __DIR__ . '/controllers/TemplateController.php';

try {
    $controller = new TemplateController($pdo);
    $hospital_id = 1; // Default for current instance

    // Handle GET for loading initial state
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'load') {
        $settings = $controller->getSettings($hospital_id);
        if ($settings) {
            echo json_encode($settings);
        } else {
            echo json_encode(['success' => false, 'message' => 'No settings found.']);
        }
        exit;
    }

    // Handle POST for saving state
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // File upload abstraction for Base64 (used by cropper and watermark in our JS)
        function processBase64Image($base64Data, $prefix) {
            if (empty($base64Data)) return null;
            if (strpos($base64Data, 'data:image/') !== 0) return $base64Data; // Already a path not a blob

            list($type, $base64Data) = explode(';', $base64Data);
            list(, $base64Data)      = explode(',', $base64Data);
            
            $extension = explode('/', $type)[1];
            $decodedData = base64_decode($base64Data);
            
            $filename = '/uploads/' . $prefix . '_' . time() . '.' . $extension;
            $filepath = __DIR__ . $filename;
            
            // Ensure dir exists
            if (!is_dir(__DIR__ . '/uploads/')) {
                mkdir(__DIR__ . '/uploads/', 0777, true);
            }
            
            if(file_put_contents($filepath, $decodedData)) {
                return $filename;
            }
            return null; // Silent fail falls back to null
        }

        $logo_path = processBase64Image($_POST['logo_path'] ?? '', 'logo');
        $watermark_path = processBase64Image($_POST['watermark_path'] ?? '', 'watermark');

        $data = [
            'logo_path'       => $logo_path ?: ($_POST['logo_path'] ?? null),
            'header_text'     => $_POST['header_text'] ?? '',
            'footer_text'     => $_POST['footer_text'] ?? '',
            'font_family'     => $_POST['font_family'] ?? 'Arial, sans-serif',
            'primary_color'   => $_POST['primary_color'] ?? '#0056b3',
            'secondary_color' => $_POST['secondary_color'] ?? '#6c757d',
            'show_watermark'  => isset($_POST['show_watermark']) ? (int)$_POST['show_watermark'] : 0,
            'watermark_path'  => $watermark_path ?: ($_POST['watermark_path'] ?? null),
            'page_size'       => $_POST['page_size'] ?? 'A4',
        ];

        $result = $controller->saveSettings($data, $hospital_id);
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save settings.']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
