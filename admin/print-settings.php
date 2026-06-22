<?php
// file: /admin/print-settings.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Template Customizer</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8f9fa;
            --border-color: #dee2e6;
        }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            height: 100vh;
            overflow: hidden;
            background: var(--bg-color);
        }
        .left-panel {
            width: 40%;
            min-width: 400px;
            padding: 20px;
            overflow-y: auto;
            background: #fff;
            border-right: 1px solid var(--border-color);
            box-sizing: border-box;
        }
        .right-panel {
            flex-grow: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            background: #e9ecef;
        }
        .control-group {
            margin-bottom: 20px;
        }
        .control-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .color-picker-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        input[type="color"] {
            width: 40px;
            height: 40px;
            padding: 0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .quill-editor {
            height: 150px;
            background: #fff;
        }
        .drag-drop-zone {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            border-radius: 6px;
            background: #fafafa;
        }
        .drag-drop-zone.dragover {
            border-color: #007bff;
            background: #e9f5ff;
        }
        .cropper-container-wrapper {
            max-width: 100%;
            max-height: 400px;
            margin-top: 10px;
            display: none;
        }
        #previewIframe {
            width: 100%;
            flex-grow: 1;
            border: 1px solid var(--border-color);
            background: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary {
            background: #007bff;
            color: #fff;
        }
        .btn-success {
            background: #28a745;
            color: #fff;
            width: 100%;
            font-size: 16px;
            margin-top: 20px;
        }
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            color: white;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        .toast.success { background: #28a745; opacity: 1; }
        .toast.error { background: #dc3545; opacity: 1; }
    </style>
</head>
<body>

    <div class="left-panel">
        <h2 style="margin-top:0;">Bill Template Settings</h2>
        
        <form id="templateForm">
            <!-- Logo Upload -->
            <div class="control-group">
                <label>Hospital Logo (Max 2MB)</label>
                <div id="logoDropZone" class="drag-drop-zone">
                    Drag & Drop Logo Here or Click to Upload
                    <input type="file" id="logoInput" accept="image/*" style="display:none;">
                </div>
                <div class="cropper-container-wrapper" id="cropperWrapper">
                    <img id="cropperImage" src="" alt="To Crop">
                    <div style="margin-top: 10px; text-align: right;">
                        <button type="button" class="btn btn-primary" id="cropLogoBtn">Crop & Save Logo</button>
                    </div>
                </div>
                <!-- Hidden input to store cropped base64 or existing path -->
                <input type="hidden" id="logoPathData" name="logo_path">
                <img id="logoPreview" style="max-height: 80px; margin-top: 10px; display: none;">
            </div>

            <!-- Typography -->
            <div style="display: flex; gap: 15px;">
                <div class="control-group" style="flex:1;">
                    <label>Font Family</label>
                    <select class="form-control" name="font_family" id="fontFamily">
                        <option value="Arial, sans-serif">Arial</option>
                        <option value="Calibri, sans-serif">Calibri</option>
                        <option value="'Times New Roman', serif">Times New Roman</option>
                        <option value="Roboto, sans-serif">Roboto</option>
                    </select>
                </div>
                <div class="control-group" style="flex:1;">
                    <label>Page Size</label>
                    <select class="form-control" name="page_size" id="pageSize">
                        <option value="A4">A4 (210 × 297 mm)</option>
                        <option value="A5">A5 (148 × 210 mm)</option>
                        <option value="thermal">Thermal 80mm</option>
                    </select>
                </div>
            </div>

            <!-- Colors -->
            <div style="display: flex; gap: 15px;">
                <div class="control-group" style="flex:1;">
                    <label>Primary Color</label>
                    <div class="color-picker-wrap">
                        <input type="color" id="primaryColorPick" value="#0056b3">
                        <input type="text" class="form-control" name="primary_color" id="primaryColorText" value="#0056b3">
                    </div>
                </div>
                <div class="control-group" style="flex:1;">
                    <label>Secondary Color</label>
                    <div class="color-picker-wrap">
                        <input type="color" id="secondaryColorPick" value="#6c757d">
                        <input type="text" class="form-control" name="secondary_color" id="secondaryColorText" value="#6c757d">
                    </div>
                </div>
            </div>

            <!-- Rich Text Editors -->
            <div class="control-group">
                <label>Header Text</label>
                <div id="headerEditor" class="quill-editor"></div>
                <input type="hidden" name="header_text" id="headerTextData">
            </div>

            <div class="control-group">
                <label>Footer Text</label>
                <div id="footerEditor" class="quill-editor"></div>
                <input type="hidden" name="footer_text" id="footerTextData">
            </div>

            <!-- Watermark -->
            <div class="control-group">
                <label style="display:flex; align-items:center; gap: 10px;">
                    <input type="checkbox" name="show_watermark" id="showWatermark" value="1">
                    Show Watermark
                </label>
                <div id="watermarkUploadDiv" style="display:none; margin-top:10px;">
                    <label>Watermark Image</label>
                    <input type="file" class="form-control" id="watermarkInput" accept="image/*">
                    <input type="hidden" name="watermark_path" id="watermarkPathData">
                </div>
            </div>

            <button type="button" class="btn btn-success" id="saveTemplateBtn">Save Settings</button>
        </form>
    </div>

    <div class="right-panel">
        <h3 style="margin-top:0;">Live Preview</h3>
        <iframe id="previewIframe"></iframe>
    </div>

    <div id="toast" class="toast"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
    <script src="/hms/assets/js/template-customizer.js"></script>
</body>
</html>
