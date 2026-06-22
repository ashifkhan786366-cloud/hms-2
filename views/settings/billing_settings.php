<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing Settings</title>
    <link rel="stylesheet" href="assets/css/billing.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .settings-container { max-width: 800px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .settings-header { border-bottom: 2px solid #007bff; padding-bottom: 15px; margin-bottom: 25px; }
        .settings-header h2 { margin: 0; color: #333; }
        .form-section { margin-bottom: 30px; }
        .form-section h3 { margin-bottom: 15px; color: #555; background: #f8f9fa; padding: 10px; border-radius: 5px; }
        .grid-2-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .settings-form label { font-weight: bold; margin-bottom: 5px; display: block; color: #444; }
        .settings-form input[type="text"], .settings-form input[type="number"], .settings-form select, .settings-form textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .settings-form input[type="file"] { border: none; padding: 10px 0; }
        .settings-form input[type="color"] { height: 40px; padding: 0; cursor: pointer; border: none; }
        .settings-form .checkbox-label { display: flex; align-items: center; gap: 10px; font-weight: normal; cursor: pointer; }
        .settings-form .checkbox-label input { width: 18px; height: 18px; margin: 0; cursor: pointer; }
        .btn-save-settings { background: #007bff; color: wite; padding: 12px 25px; font-size: 16px; border: none; border-radius: 5px; cursor: pointer; width: 100%; transition: background 0.3s; color:#fff; }
        .btn-save-settings:hover { background: #0056b3; }
        .alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body style="background: #f0f2f5;">

<div class="settings-container">
    <div class="settings-header">
        <h2><i class="fas fa-cogs"></i> Billing Settings</h2>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i> Settings saved successfully!
        </div>
    <?php endif; ?>

    <form action="billing_app.php?action=settings" method="POST" enctype="multipart/form-data" class="settings-form">
        
        <div class="form-section">
            <h3><i class="far fa-hospital"></i> Hospital Information</h3>
            <div class="grid-2-col">
                <div class="form-group">
                    <label>Hospital Name</label>
                    <input type="text" name="hospital_name" value="<?= htmlspecialchars($settings['hospital_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Hospital Logo</label>
                    <input type="file" name="hospital_logo" accept="image/*">
                    <?php if(!empty($settings['hospital_logo'])): ?>
                        <small>Current Logo: <?= htmlspecialchars($settings['hospital_logo']) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Hospital Address</label>
                    <textarea name="hospital_address" rows="2"><?= htmlspecialchars($settings['hospital_address'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Phone Number(s)</label>
                    <input type="text" name="hospital_phone" value="<?= htmlspecialchars($settings['hospital_phone'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-file-invoice-dollar"></i> Billing Preferences</h3>
            <div class="grid-2-col">
                <div class="form-group">
                    <label>Bill Prefix</label>
                    <input type="text" name="bill_prefix" value="<?= htmlspecialchars($settings['bill_prefix'] ?? 'BILL') ?>" placeholder="e.g. BILL, INV, RCP">
                </div>
                <div class="form-group">
                    <label>Default Print Size</label>
                    <select name="print_size">
                        <option value="A4" <?= ($settings['print_size'] ?? '') == 'A4' ? 'selected' : '' ?>>A4 Format</option>
                        <option value="Thermal" <?= ($settings['print_size'] ?? '') == 'Thermal' ? 'selected' : '' ?>>Thermal (80mm)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Default Payment Mode</label>
                    <select name="default_payment_mode">
                        <option value="Cash" <?= ($settings['default_payment_mode'] ?? '') == 'Cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="Card" <?= ($settings['default_payment_mode'] ?? '') == 'Card' ? 'selected' : '' ?>>Card</option>
                        <option value="UPI" <?= ($settings['default_payment_mode'] ?? '') == 'UPI' ? 'selected' : '' ?>>UPI</option>
                        <option value="Insurance" <?= ($settings['default_payment_mode'] ?? '') == 'Insurance' ? 'selected' : '' ?>>Insurance</option>
                    </select>
                </div>
            </div>
            <div class="grid-2-col" style="margin-top: 20px;">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="show_discount_col" value="1" <?= ($settings['show_discount_col'] ?? '1') == '1' ? 'checked' : '' ?>>
                        Show Discount Column in Bill Grid
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="show_tax_col" value="1" <?= ($settings['show_tax_col'] ?? '1') == '1' ? 'checked' : '' ?>>
                        Show Tax Column in Bill Grid
                    </label>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-percent"></i> Tax / GST Settings</h3>
            <div class="grid-2-col">
                <div class="form-group" style="display: flex; align-items: center;">
                    <label class="checkbox-label" style="margin-top:0;">
                        <input type="checkbox" name="enable_gst" id="enable_gst" value="1" <?= ($settings['enable_gst'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <strong>Enable GST / Tax Calculation</strong>
                    </label>
                </div>
                <div class="form-group">
                    <label>GSTIN / Tax Number</label>
                    <input type="text" name="gst_number" id="gst_number" value="<?= htmlspecialchars($settings['gst_number'] ?? '') ?>" <?= ($settings['enable_gst'] ?? '1') != '1' ? 'disabled' : '' ?>>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-palette"></i> Theme & Customization</h3>
            <div class="grid-2-col">
                <div class="form-group">
                    <label>Primary Brand Color</label>
                    <input type="color" name="primary_color" value="<?= htmlspecialchars($settings['primary_color'] ?? '#007bff') ?>">
                </div>
                <div class="form-group">
                    <label>Secondary Brand Color</label>
                    <input type="color" name="secondary_color" value="<?= htmlspecialchars($settings['secondary_color'] ?? '#6c757d') ?>">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Print Header Text</label>
                    <input type="text" name="header_text" value="<?= htmlspecialchars($settings['header_text'] ?? 'Thank you for choosing Sankhla Hospital') ?>">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Terms & Conditions (Footer Text)</label>
                    <textarea name="footer_text" rows="3"><?= htmlspecialchars($settings['footer_text'] ?? '1. No refund on billed items.\n2. Subject to local jurisdiction.') ?></textarea>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn-save-settings"><i class="fas fa-save"></i> Save Settings</button>

    </form>
</div>

<script>
    document.getElementById('enable_gst').addEventListener('change', function() {
        document.getElementById('gst_number').disabled = !this.checked;
    });
</script>

</body>
</html>
