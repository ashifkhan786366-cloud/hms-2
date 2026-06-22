<?php
require_once 'config/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
check_role(['Admin']); // Only Admin can edit print settings

// Handle AJAX Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $type = clean_input($_POST['template_type']);
    $font_family = clean_input($_POST['font_family']);
    $font_size = (int)$_POST['font_size'];
    $primary_color = clean_input($_POST['primary_color']);
    $margin_top = (int)$_POST['margin_top'];
    $margin_bottom = (int)$_POST['margin_bottom'];
    $margin_left = (int)$_POST['margin_left'];
    $margin_right = (int)$_POST['margin_right'];
    $header_text = $_POST['header_text']; // Allow HTML
    $footer_text = $_POST['footer_text']; // Allow HTML

    try {
        $stmt = $pdo->prepare("UPDATE print_settings SET 
            font_family = ?, font_size = ?, primary_color = ?, 
            margin_top = ?, margin_bottom = ?, margin_left = ?, margin_right = ?,
            header_text = ?, footer_text = ? 
            WHERE template_type = ?");
        $stmt->execute([$font_family, $font_size, $primary_color, $margin_top, $margin_bottom, $margin_left, $margin_right, $header_text, $footer_text, $type]);
        
        // If row didn't update (meaning it might not exist yet), try to insert
        if ($stmt->rowCount() == 0) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM print_settings WHERE template_type = ?");
            $check->execute([$type]);
            if ($check->fetchColumn() == 0) {
                $ins = $pdo->prepare("INSERT INTO print_settings 
                (template_type, font_family, font_size, primary_color, margin_top, margin_bottom, margin_left, margin_right, header_text, footer_text) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$type, $font_family, $font_size, $primary_color, $margin_top, $margin_bottom, $margin_left, $margin_right, $header_text, $footer_text]);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Fetch Settings for all 3 templates for JS
$opd_bill_settings = get_print_settings($pdo, 'opd_bill');
$opd_slip_settings = get_print_settings($pdo, 'opd_slip');
$general_bill_settings = get_print_settings($pdo, 'general_bill');

include 'includes/header.php';
?>

<div class="content-wrapper p-3">
    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h2>Visual Print Template Designer</h2>
            <p class="text-muted">Customize fonts, sizes, colors and margins in real-time.</p>
        </div>
        <div class="col-md-6 text-end">
            <select id="templateType" class="form-select d-inline-block w-auto me-2">
                <option value="opd_bill">OPD Bill Format</option>
                <option value="opd_slip">OPD Slip Format</option>
                <option value="general_bill">General Bill Format</option>
            </select>
            <button class="btn btn-primary" onclick="saveSettings()">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Editor Panel -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-sliders-h"></i> Customization Box</h5>
                </div>
                <div class="card-body">
                    <form id="printSettingsForm">
                        <input type="hidden" id="template_type" name="template_type" value="opd_bill">
                        
                        <!-- Typography -->
                        <h6 class="border-bottom pb-2">Typography</h6>
                        <div class="mb-3">
                            <label class="form-label">Font Family</label>
                            <select class="form-select" id="font_family" name="font_family" onchange="updatePreview()">
                                <option value="Arial, sans-serif">Arial</option>
                                <option value="'Courier New', Courier, monospace">Courier New</option>
                                <option value="'Times New Roman', Times, serif">Times New Roman</option>
                                <option value="'Trebuchet MS', sans-serif">Trebuchet MS</option>
                                <option value="'Verdana', sans-serif">Verdana</option>
                                <option value="'Roboto', sans-serif">Roboto</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Base Font Size (px)</label>
                            <input type="range" class="form-range" id="font_size_range" min="10" max="24" oninput="document.getElementById('font_size').value=this.value; updatePreview()">
                            <input type="number" class="form-control form-control-sm mt-1" id="font_size" name="font_size" min="10" max="24" onchange="document.getElementById('font_size_range').value=this.value; updatePreview()">
                        </div>

                        <!-- Brand Colors -->
                        <h6 class="border-bottom pb-2 mt-4">Colors</h6>
                        <div class="mb-3">
                            <label class="form-label">Primary Theme Color</label>
                            <input type="color" class="form-control form-control-color w-100" id="primary_color" name="primary_color" onchange="updatePreview()">
                        </div>

                        <!-- Margins Page Layout -->
                        <h6 class="border-bottom pb-2 mt-4">Page Margins (px)</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label mb-0 small">Top</label>
                                <input type="number" class="form-control form-control-sm" id="margin_top" name="margin_top" onchange="updatePreview()">
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-0 small">Bottom</label>
                                <input type="number" class="form-control form-control-sm" id="margin_bottom" name="margin_bottom" onchange="updatePreview()">
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-0 small">Left</label>
                                <input type="number" class="form-control form-control-sm" id="margin_left" name="margin_left" onchange="updatePreview()">
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-0 small">Right</label>
                                <input type="number" class="form-control form-control-sm" id="margin_right" name="margin_right" onchange="updatePreview()">
                            </div>
                        </div>

                        <!-- Text Sections -->
                        <h6 class="border-bottom pb-2 mt-4">Header & Footer Text (HTML Allowed)</h6>
                        <div class="mb-3">
                            <label class="form-label small">Header Content</label>
                            <textarea class="form-control font-monospace" id="header_text" name="header_text" rows="4" onkeyup="updatePreview()"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Footer Content</label>
                            <textarea class="form-control font-monospace" id="footer_text" name="footer_text" rows="3" onkeyup="updatePreview()"></textarea>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Live Preview Panel -->
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 text-primary"><i class="far fa-eye"></i> Live Preview</h5>
                    <span class="badge bg-secondary">A4 Size Simulated</span>
                </div>
                <div class="card-body bg-secondary" style="overflow-y: auto; max-height: calc(100vh - 150px);">
                    <!-- Simulated A4 Paper -->
                    <div id="previewPaper" style="background: white; width: 210mm; min-height: 297mm; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.5); transition: all 0.3s; position: relative; box-sizing: border-box;">
                        
                        <!-- Header Area -->
                        <div id="previewHeader" style="text-align: center; border-bottom: 2px solid var(--primary-color, #006); padding-bottom: 10px; margin-bottom: 15px;">
                            <!-- Injected dynamically -->
                        </div>

                        <!-- Specific Body Templates (Mock Data) -->
                        
                        <!-- 1. OPD BILL format -->
                        <div id="previewBodyOpdBill" style="display: none; padding-top: 5mm;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                <tr>
                                    <td colspan="10" style="text-align: center; font-size: 24px; font-weight: bold; padding: 10px 0; border: 2px solid #000; border-bottom: 0;">SANKHLA HOSPITAL</td>
                                </tr>
                                <tr>
                                    <td colspan="5" style="border-top: 1px solid #000; border-left: 2px solid #000;">
                                        <div>Powered by Vitaid Health Care Foundation</div>
                                        <div>Reg. No. U86100RJ2023NPL086879</div>
                                    </td>
                                    <td colspan="5" style="text-align: right; border-top: 1px solid #000; border-right: 2px solid #000;">
                                        <div>Phone: 9829208462</div>
                                        <div>Email: bksankhlahospital@gmail.com</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="10" style="border-bottom: 2px solid #000; border-left: 2px solid #000; border-right: 2px solid #000; font-weight: bold;">
                                        TAX INVOICE / BILL OF SUPPLY / CASH MEMO
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="1" style="border: 1px solid #000; border-right: 0; border-left: 2px solid #000;">Invoice No.</td>
                                    <td colspan="4" style="border: 1px solid #000; border-left: 0;">REV-20261010-001</td>
                                    <td colspan="2" style="text-align: right; border: 1px solid #000; border-right: 0; font-weight:bold; font-size: 16px;">Date</td>
                                    <td colspan="3" style="text-align: center; border: 1px solid #000; border-left: 0; border-right: 2px solid #000; font-weight:bold; font-size: 22px;">18-Feb-26</td>
                                </tr>
                                <tr><td colspan="10" style="border-left: 2px solid #000; border-right: 2px solid #000; height: 10px;"></td></tr>
                                <tr>
                                    <td colspan="10" style="padding: 0; border-left: 2px solid #000; border-right: 2px solid #000;">
                                        <table style="width:100%; font-size: 13px; border-collapse: collapse;">
                                            <tr><td style="width: 25%; padding: 2px 6px;">Patient Name</td><td style="width: 75%; padding: 2px 6px;">JOHN DOE</td></tr>
                                            <tr><td style="padding: 2px 6px;">Age / Gender</td><td style="padding: 2px 6px;">35/M</td></tr>
                                            <tr><td style="padding: 2px 6px;">Contact No.</td><td style="padding: 2px 6px;">9876543210</td></tr>
                                            <tr><td style="padding: 2px 6px;">UHID / OPD No.</td><td style="padding: 2px 6px;">MR-001</td></tr>
                                            <tr><td style="padding: 2px 6px;">Doctor</td><td style="padding: 2px 6px;">DR. DOCTOR</td></tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr><td colspan="10" style="border-left: 2px solid #000; border-right: 2px solid #000; height: 10px;"></td></tr>
                                <tr style="background-color: var(--primary-color, #9dc3e6); color: white; -webkit-print-color-adjust: exact;">
                                    <td style="border: 1px solid #000; width: 5%;">Sr</td>
                                    <td colspan="3" style="border: 1px solid #000; width: 45%;">Service / Item Description</td>
                                    <td style="border: 1px solid #000; width: 5%;">Qty</td>
                                    <td style="border: 1px solid #000; width: 10%;">Rate</td>
                                    <td style="border: 1px solid #000; width: 12%;">Amount</td>
                                    <td style="border: 1px solid #000; width: 10%;">Discount</td>
                                    <td colspan="2" style="border: 1px solid #000; width: 13%;">Net Amount</td>
                                </tr>
                                <tr>
                                    <td style="border: 1px solid #000;">1</td>
                                    <td colspan="3" style="border: 1px solid #000;">OPD CONSULTATION</td>
                                    <td style="border: 1px solid #000; text-align: right;">1</td>
                                    <td style="border: 1px solid #000; text-align: right;">500</td>
                                    <td style="border: 1px solid #000; text-align: right;">500</td>
                                    <td style="border: 1px solid #000;"></td>
                                    <td colspan="2" style="border: 1px solid #000;"></td>
                                </tr>
                                <tr>
                                    <td style="border: 1px solid #000;"></td><td colspan="3" style="border: 1px solid #000;"></td>
                                    <td style="border: 1px solid #000; text-align: right;">1</td>
                                    <td style="border: 1px solid #000;"></td><td style="border: 1px solid #000; text-align: right;">0</td>
                                    <td style="border: 1px solid #000;"></td><td colspan="2" style="border: 1px solid #000;"></td>
                                </tr>
                                <tr style="background-color: var(--primary-color, #9dc3e6); color: white; -webkit-print-color-adjust: exact;">
                                    <td colspan="6" style="border: 1px solid #000; text-align: center;">Total Amount</td>
                                    <td style="border: 1px solid #000; text-align: right;">500</td>
                                    <td style="border: 1px solid #000;"></td><td colspan="2" style="border: 1px solid #000;"></td>
                                </tr>
                                <tr>
                                    <td colspan="6" style="border: 1px solid #000; text-align: center;">Discount</td>
                                    <td style="border: 1px solid #000; text-align: right; background-color: #fce4d6; color: #000;">0</td>
                                    <td style="border: 1px solid #000;"></td><td colspan="2" style="border: 1px solid #000;"></td>
                                </tr>
                                <tr style="background-color: var(--primary-color, #9dc3e6); color: white; -webkit-print-color-adjust: exact;">
                                    <td colspan="6" style="border: 1px solid #000; text-align: center;">NET AMOUNT PAYABLE</td>
                                    <td style="border: 1px solid #000; text-align: right;">500</td>
                                    <td style="border: 1px solid #000;"></td><td colspan="2" style="border: 1px solid #000;"></td>
                                </tr>
                                <tr>
                                    <td colspan="8" style="border: 1px solid #000; border-right: 0; border-bottom: 0;">
                                        <div style="margin-top: 10px;">Terms & Conditions :</div>
                                        <div>Goods once sold will not be taken back.<br>Subject to local jurisdiction.</div>
                                    </td>
                                    <td colspan="2" style="border: 1px solid #000; border-left: 0; border-bottom: 0; text-align: center; vertical-align: bottom;">
                                        <div style="width: 80px; height: 80px; border: 1px dashed #ccc; display: inline-block; text-align: center; line-height: 80px; color: #999; font-size: 10px;">QR OFF</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7" style="border: 1px solid #000; border-right: 0; border-top: 0; border-bottom: 0; padding-top: 10px;">Thank You for Choosing Sankhla Hospital</td>
                                    <td colspan="3" style="border: 1px solid #000; border-left: 0; border-top: 0; border-bottom: 0; text-align: right; padding-top: 10px;">SCAN FOR RATE US ON GMB</td>
                                </tr>
                                <tr>
                                    <td colspan="10" style="border: 1px solid #000; text-align: center; font-weight: bold; padding: 5px;">
                                        Govt. Dispensary Near Kanji Petrol Pump, Niwaru Road, Jhotwara, Jaipur - 302012
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- 2. OPD SLIP format -->
                        <div id="previewBodyOpdSlip" style="display:none; padding-top: 1.8in; padding-left: 40px; padding-right: 15mm; font-size: 17px; box-sizing: border-box;">
                            <div style="display: grid; grid-template-columns: 32% 16% 28% 24%; row-gap: 30px; margin-bottom: 50px; padding-bottom: 15px; border-bottom: 2px solid #000; text-transform: uppercase; align-items: baseline;">
                                <div style="white-space: nowrap;">PATIENT ID _ <strong>MR-001</strong></div>
                                <div style="white-space: nowrap;">TOKEN No. : <strong>12</strong></div>
                                <div style="white-space: nowrap;">NAME : JOHN DOE</div>
                                <div style="white-space: nowrap;">GEN. MALE &nbsp;&nbsp; AGE.35Y</div>
                                <div style="grid-column: 1 / span 2; white-space: nowrap;">MOBILE NO : 9876543210</div>
                                <div></div>
                                <div style="white-space: nowrap;">DATE _ <strong>18\02\26</strong></div>
                                <div style="grid-column: 1 / span 2; white-space: nowrap;">REF. BY - <strong>DR. DOCTOR</strong></div>
                                <div style="white-space: nowrap;">ADDRESS : JAIPUR, RA...</div>
                                <div style="white-space: nowrap;">TIMING : 10:30 AM</div>
                            </div>
                            <div style="display: flex; margin-bottom: 30px; font-weight: bold; font-size: 14px; text-transform: uppercase;">
                                <div style="margin-right: 25px;">BP 120/80</div>
                                <div style="margin-right: 25px;">SPO2 98%</div>
                                <div style="margin-right: 25px;">PLUS 72</div>
                                <div style="margin-right: 25px;">HIGHT ........</div>
                                <div style="margin-right: 25px;">WAIGHT 70KG</div>
                                <div style="margin-right: 0;">BLOOD GROUP O+</div>
                            </div>
                            <div>
                                <div style="font-size: 38px; font-weight: bold; font-family: Arial, sans-serif; margin-left: 130px; margin-top: 15px;">Rx</div>
                            </div>
                            <div style="position: absolute; bottom: 1in; right: 40px; font-weight: bold; text-transform: uppercase; font-size: 15px;">
                                OPD VALID FOR 3 DAYS
                            </div>
                        </div>

                        <!-- 3. GENERAL BILL format -->
                        <div id="previewBodyGeneralBill" style="display:none; padding-top: 5mm;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                <tr>
                                    <td colspan="10" style="border-top: 0; border-bottom: 2px solid #000; font-weight: bold; padding: 4px 6px;">
                                        TAX INVOICE / BILL OF SUPPLY / CASH MEMO
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="1" style="border: 1px solid #000; border-right: 0; border-bottom: 0;">Invoice No.</td>
                                    <td colspan="4" style="border: 1px solid #000; border-left: 0; border-bottom: 0;">GEN-20261010-001</td>
                                    <td colspan="2" style="text-align: right; border: 1px solid #000; border-right: 0; border-bottom: 0; font-weight: bold; font-size: 16px;">Date</td>
                                    <td colspan="3" style="text-align: center; border: 1px solid #000; border-left: 0; border-bottom: 0; font-weight: bold; font-size: 22px;">18-02-26</td>
                                </tr>
                                <tr><td colspan="10" style="border-top: 0; border-bottom: 0; height: 10px;"></td></tr>
                                <tr>
                                    <td colspan="10" style="padding: 0; border-top: 0; border-bottom: 0;">
                                        <table style="width: 100%; border-collapse: collapse;">
                                            <tr><td style="width: 25%; padding: 2px 6px;">Patient Name</td><td style="width: 75%; padding: 2px 6px;">JANE DOE</td></tr>
                                            <tr><td style="padding: 2px 6px;">Age / Gender</td><td style="padding: 2px 6px;">28/F</td></tr>
                                            <tr><td style="padding: 2px 6px;">Contact No.</td><td style="padding: 2px 6px;">9876543211</td></tr>
                                            <tr><td style="padding: 2px 6px;">UHID / OPD No. / IPD No.</td><td style="padding: 2px 6px;">MR-002</td></tr>
                                            <tr><td style="padding: 2px 6px;">Doctor</td><td style="padding: 2px 6px;">DR. DOCTOR</td></tr>
                                            <tr><td style="padding: 2px 6px;">Admission Date (if IPD)</td><td style="padding: 2px 6px;">15-02-2026</td></tr>
                                            <tr><td style="padding: 2px 6px;">Discharge Date (if IPD)</td><td style="padding: 2px 6px;">18-02-2026</td></tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr><td colspan="10" style="border-top: 0; height: 10px;"></td></tr>
                                <tr style="background-color: var(--primary-color, #0066cc); color: white; -webkit-print-color-adjust: exact; font-weight: bold;">
                                    <td style="border: 1px solid #000; width: 5%;">Sr</td>
                                    <td colspan="3" style="border: 1px solid #000; width: 45%;">Particulars / Service / Item Description</td>
                                    <td style="border: 1px solid #000; width: 5%;">Qty</td>
                                    <td style="border: 1px solid #000; width: 10%;">Rate</td>
                                    <td style="border: 1px solid #000; width: 12%;">Amount</td>
                                    <td style="border: 1px solid #000; width: 10%;">Discount</td>
                                    <td colspan="2" style="border: 1px solid #000; width: 13%;">Net Amount</td>
                                </tr>
                                <tr>
                                    <td style="border: 1px solid #000;">1</td>
                                    <td colspan="3" style="border: 1px solid #000;">ICU BED CHARGE</td>
                                    <td style="border: 1px solid #000; text-align: right;">3</td>
                                    <td style="border: 1px solid #000; text-align: right;">3000</td>
                                    <td style="border: 1px solid #000; text-align: right;">9000</td>
                                    <td style="border: 1px solid #000;"></td>
                                    <td colspan="2" style="border: 1px solid #000;"></td>
                                </tr>
                                <tr>
                                    <td style="border: 1px solid #000;"></td><td colspan="3" style="border: 1px solid #000;"></td>
                                    <td style="border: 1px solid #000; text-align: right;">3</td>
                                    <td style="border: 1px solid #000;"></td><td style="border: 1px solid #000; text-align: right;">0</td>
                                    <td style="border: 1px solid #000;"></td><td colspan="2" style="border: 1px solid #000;"></td>
                                </tr>
                                <tr style="background-color: var(--primary-color, #0066cc); color: white; -webkit-print-color-adjust: exact;">
                                    <td colspan="6" style="border: 1px solid #000; text-align: center;">Total Amount</td>
                                    <td style="border: 1px solid #000; text-align: right;">9000</td>
                                    <td style="border: 1px solid #000;"></td><td colspan="2" style="border: 1px solid #000;"></td>
                                </tr>
                                <tr>
                                    <td colspan="6" style="border: 1px solid #000; text-align: center;">Discount</td>
                                    <td style="border: 1px solid #000; text-align: right; background-color: #fce4d6; color: #000;">-500</td>
                                    <td style="border: 1px solid #000;"></td><td colspan="2" style="border: 1px solid #000;"></td>
                                </tr>
                                <tr style="background-color: var(--primary-color, #0066cc); color: white; -webkit-print-color-adjust: exact;">
                                    <td colspan="6" style="border: 1px solid #000; text-align: center;">NET AMOUNT PAYABLE</td>
                                    <td style="border: 1px solid #000; text-align: right;">8500</td>
                                    <td style="border: 1px solid #000;"></td><td colspan="2" style="border: 1px solid #000;"></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Footer Area (Position absolute at bottom for A4 effect) -->
                        <div id="previewFooter" style="position: absolute; bottom: var(--margin-bottom, 20px); left: var(--margin-left, 20px); right: var(--margin-right, 20px); text-align: center; border-top: 1px dashed #ccc; padding-top: 10px; font-size: 0.85em;">
                            <!-- Injected dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const settingsData = {
        opd_bill: <?php echo json_encode($opd_bill_settings); ?>,
        opd_slip: <?php echo json_encode($opd_slip_settings); ?>,
        general_bill: <?php echo json_encode($general_bill_settings); ?>
    };

    function loadSettingsToForm(type) {
        // Fallback for missing settings if created freshly
        let s = settingsData[type];
        if (!s || Array.isArray(s)) {
            s = { font_family: 'Arial, sans-serif', font_size: 14, primary_color: '#0066cc', margin_top: 20, margin_bottom: 20, margin_left: 20, margin_right: 20, header_text: '', footer_text: '' };
        }

        document.getElementById('template_type').value = type;
        document.getElementById('font_family').value = s.font_family || 'Arial, sans-serif';
        document.getElementById('font_size').value = s.font_size || 14;
        document.getElementById('font_size_range').value = s.font_size || 14;
        document.getElementById('primary_color').value = s.primary_color || '#0066cc';
        document.getElementById('margin_top').value = s.margin_top || 20;
        document.getElementById('margin_bottom').value = s.margin_bottom || 20;
        document.getElementById('margin_left').value = s.margin_left || 20;
        document.getElementById('margin_right').value = s.margin_right || 20;
        document.getElementById('header_text').value = s.header_text || '';
        document.getElementById('footer_text').value = s.footer_text || '';

        // Toggle Mock Content
        document.getElementById('previewBodyOpdBill').style.display = type === 'opd_bill' ? 'block' : 'none';
        document.getElementById('previewBodyOpdSlip').style.display = type === 'opd_slip' ? 'block' : 'none';
        document.getElementById('previewBodyGeneralBill').style.display = type === 'general_bill' ? 'block' : 'none';

        updatePreview();
    }

    function updatePreview() {
        const paper = document.getElementById('previewPaper');
        
        // Typography & Colors via CSS Variables
        paper.style.setProperty('--font-family', document.getElementById('font_family').value);
        paper.style.setProperty('--font-size', document.getElementById('font_size').value + 'px');
        paper.style.setProperty('--primary-color', document.getElementById('primary_color').value);
        
        // Set standard styles
        paper.style.fontFamily = 'var(--font-family)';
        
        const type = document.getElementById('template_type').value;
        if (type !== 'opd_slip') {
            paper.style.fontSize = 'var(--font-size)';
        } else {
            // OPD Slip has custom layout sizing but we can still apply font
            paper.style.fontSize = 'var(--font-size)';
        }
        
        paper.style.color = '#000';

        // Margins
        const mt = document.getElementById('margin_top').value + 'px';
        const mb = document.getElementById('margin_bottom').value + 'px';
        const ml = document.getElementById('margin_left').value + 'px';
        const mr = document.getElementById('margin_right').value + 'px';

        paper.style.paddingTop = mt;
        paper.style.paddingLeft = ml;
        paper.style.paddingRight = mr;
        paper.style.paddingBottom = mb;
        
        // Update CSS vars for footer absolute positioning
        paper.style.setProperty('--margin-bottom', mb);
        paper.style.setProperty('--margin-left', ml);
        paper.style.setProperty('--margin-right', mr);

        // Header and Footer Text
        document.getElementById('previewHeader').innerHTML = document.getElementById('header_text').value;
        
        // Dont show default header in preview if it's empty, and hide if it's opd_bill or opd_slip since they have their own inline header in sankhla hospital
        if (document.getElementById('header_text').value.trim() === '') {
            document.getElementById('previewHeader').style.display = 'none';
        } else {
            document.getElementById('previewHeader').style.display = 'block';
        }

        document.getElementById('previewFooter').innerHTML = document.getElementById('footer_text').value;
        
        if (document.getElementById('footer_text').value.trim() === '') {
            document.getElementById('previewFooter').style.display = 'none';
        } else {
            document.getElementById('previewFooter').style.display = 'block';
        }

        // Update border color
        document.getElementById('previewHeader').style.borderColor = document.getElementById('primary_color').value;
    }

    document.getElementById('templateType').addEventListener('change', function() {
        loadSettingsToForm(this.value);
    });

    function saveSettings() {
        const formData = new FormData(document.getElementById('printSettingsForm'));
        formData.append('action', 'save_settings');

        fetch('setup_print.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Update local js cache
                const type = document.getElementById('template_type').value;
                settingsData[type] = Object.fromEntries(formData.entries());
                alert('Success: Settings Saved Successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            alert('Error saving settings');
            console.error(err);
        });
    }

    // Init
    window.onload = () => loadSettingsToForm('opd_bill');
</script>

<?php include 'includes/footer.php'; ?>
