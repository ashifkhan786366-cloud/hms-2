<?php
// Function to sanitize input
function clean_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to format currency
function format_currency($amount)
{
    return CURRENCY . ' ' . number_format($amount, 2);
}

// Function to set flash message
function set_flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Function to get and clear flash message
function get_flash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Function to check if user has permission (Role based)
function check_role($allowed_roles)
{
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: index.php");
        exit();
    }
}

// Function to fetch print template settings
function get_print_settings($pdo, $type)
{
    $stmt = $pdo->prepare("SELECT * FROM print_settings WHERE template_type = ?");
    $stmt->execute([$type]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Default fallback if not found
    if (!$settings) {
        $app_name = defined('APP_NAME') ? APP_NAME : 'Hospital';
        $app_address = defined('APP_ADDRESS') ? APP_ADDRESS : '';
        $app_phone = defined('APP_PHONE') ? APP_PHONE : '';
        $app_email = defined('APP_EMAIL') ? APP_EMAIL : '';
        $app_short_name = defined('APP_SHORT_NAME') ? APP_SHORT_NAME : 'Hospital';
        
        $settings = [
            'font_family' => 'Arial, sans-serif',
            'font_size' => 14,
            'primary_color' => '#0066CC',
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 20,
            'margin_right' => 20,
            'show_logo' => 1,
            'header_text' => "<h3>" . $app_name . "</h3><p>" . $app_address . "</p><p>Phone: " . $app_phone . " | Email: " . $app_email . "</p>",
            'footer_text' => "<p>Thank you for choosing " . $app_short_name . ".</p>"
        ];
    }
    return $settings;
}

?>