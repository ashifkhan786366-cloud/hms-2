// file: /assets/js/template-customizer.js

document.addEventListener('DOMContentLoaded', () => {

    // 1. Initialize Quill Editors
    const quillOptions = {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ 'align': [] }],
                ['clean']
            ]
        }
    };
    const headerQuill = new Quill('#headerEditor', quillOptions);
    const footerQuill = new Quill('#footerEditor', quillOptions);

    // 2. Elements Mapping
    const elements = {
        form: document.getElementById('templateForm'),
        logoDropZone: document.getElementById('logoDropZone'),
        logoInput: document.getElementById('logoInput'),
        cropperWrapper: document.getElementById('cropperWrapper'),
        cropperImage: document.getElementById('cropperImage'),
        cropLogoBtn: document.getElementById('cropLogoBtn'),
        logoPathData: document.getElementById('logoPathData'),
        logoPreview: document.getElementById('logoPreview'),
        
        primaryColorPick: document.getElementById('primaryColorPick'),
        primaryColorText: document.getElementById('primaryColorText'),
        secondaryColorPick: document.getElementById('secondaryColorPick'),
        secondaryColorText: document.getElementById('secondaryColorText'),
        
        fontFamily: document.getElementById('fontFamily'),
        pageSize: document.getElementById('pageSize'),
        showWatermark: document.getElementById('showWatermark'),
        watermarkUploadDiv: document.getElementById('watermarkUploadDiv'),
        watermarkInput: document.getElementById('watermarkInput'),
        watermarkPathData: document.getElementById('watermarkPathData'),
        
        headerTextData: document.getElementById('headerTextData'),
        footerTextData: document.getElementById('footerTextData'),
        
        previewIframe: document.getElementById('previewIframe'),
        saveBtn: document.getElementById('saveTemplateBtn'),
        toast: document.getElementById('toast')
    };

    let cropper = null;
    let debounceTimer;

    // 3. Sync Colors
    elements.primaryColorPick.addEventListener('input', (e) => { elements.primaryColorText.value = e.target.value; triggerPreview(); });
    elements.primaryColorText.addEventListener('input', (e) => { elements.primaryColorPick.value = e.target.value; triggerPreview(); });
    elements.secondaryColorPick.addEventListener('input', (e) => { elements.secondaryColorText.value = e.target.value; triggerPreview(); });
    elements.secondaryColorText.addEventListener('input', (e) => { elements.secondaryColorPick.value = e.target.value; triggerPreview(); });

    // 4. Other Triggers
    elements.fontFamily.addEventListener('change', triggerPreview);
    elements.pageSize.addEventListener('change', triggerPreview);
    elements.showWatermark.addEventListener('change', (e) => {
        elements.watermarkUploadDiv.style.display = e.target.checked ? 'block' : 'none';
        triggerPreview();
    });

    headerQuill.on('text-change', () => { elements.headerTextData.value = headerQuill.root.innerHTML; triggerPreview(); });
    footerQuill.on('text-change', () => { elements.footerTextData.value = footerQuill.root.innerHTML; triggerPreview(); });

    // 5. Logo Drag & Drop + Crop logic
    elements.logoDropZone.addEventListener('click', () => elements.logoInput.click());
    elements.logoDropZone.addEventListener('dragover', (e) => { e.preventDefault(); elements.logoDropZone.classList.add('dragover'); });
    elements.logoDropZone.addEventListener('dragleave', () => elements.logoDropZone.classList.remove('dragover'));
    elements.logoDropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        elements.logoDropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) handleLogoSelect(e.dataTransfer.files[0]);
    });
    elements.logoInput.addEventListener('change', (e) => {
        if (e.target.files.length) handleLogoSelect(e.target.files[0]);
    });

    function handleLogoSelect(file) {
        if (!file.type.startsWith('image/')) return showToast('Please upload an image file.', 'error');
        if (file.size > 2 * 1024 * 1024) return showToast('Image exceeds 2MB limit.', 'error');

        const reader = new FileReader();
        reader.onload = (e) => {
            elements.cropperImage.src = e.target.result;
            elements.cropperWrapper.style.display = 'block';
            elements.logoDropZone.style.display = 'none';
            if (cropper) cropper.destroy();
            cropper = new Cropper(elements.cropperImage, { aspectRatio: NaN, viewMode: 1 });
        };
        reader.readAsDataURL(file);
    }

    elements.cropLogoBtn.addEventListener('click', () => {
        if (!cropper) return;
        const base64Data = cropper.getCroppedCanvas().toDataURL('image/png');
        elements.logoPathData.value = base64Data;
        elements.logoPreview.src = base64Data;
        elements.logoPreview.style.display = 'block';
        elements.cropperWrapper.style.display = 'none';
        elements.logoDropZone.style.display = 'block';
        cropper.destroy();
        cropper = null;
        triggerPreview();
    });

    // Watermark base64 fast handling (simplified for settings preview)
    elements.watermarkInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if(!file) return;
        if(file.size > 2 * 1024 * 1024) return showToast('Image exceeds 2MB limit.', 'error');
        const reader = new FileReader();
        reader.onload = (ev) => {
            elements.watermarkPathData.value = ev.target.result;
            triggerPreview();
        };
        reader.readAsDataURL(file);
    });

    // 6. Fetch init data on load
    fetch('/hms/save_template.php?action=load')
        .then(res => res.json())
        .then(data => {
            if (data && data.success !== false) {
                if(data.logo_path) {
                    elements.logoPathData.value = data.logo_path;
                    elements.logoPreview.src = data.logo_path;
                    elements.logoPreview.style.display = 'block';
                }
                elements.primaryColorPick.value = data.primary_color || '#0056b3';
                elements.primaryColorText.value = data.primary_color || '#0056b3';
                elements.secondaryColorPick.value = data.secondary_color || '#6c757d';
                elements.secondaryColorText.value = data.secondary_color || '#6c757d';
                elements.fontFamily.value = data.font_family || 'Arial, sans-serif';
                elements.pageSize.value = data.page_size || 'A4';
                
                headerQuill.root.innerHTML = data.header_text || '';
                elements.headerTextData.value = data.header_text || '';
                
                footerQuill.root.innerHTML = data.footer_text || '';
                elements.footerTextData.value = data.footer_text || '';
                
                if (parseInt(data.show_watermark) === 1) {
                    elements.showWatermark.checked = true;
                    elements.watermarkUploadDiv.style.display = 'block';
                }
                if(data.watermark_path) elements.watermarkPathData.value = data.watermark_path;
            }
            triggerPreview(); // initial iframe load
        });

    // 7. Debounced iframe Update
    function triggerPreview() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            generateIframeContent();
        }, 300);
    }

    function generateIframeContent() {
        // Build mock layout directly mimicking the real bill render for live visual feedback
        const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { 
                    font-family: ${elements.fontFamily.value}; 
                    color: #333; 
                    margin: 0; padding: 20px; 
                    position: relative;
                }
                .bill-header { border-bottom: 2px solid ${elements.primaryColorPick.value}; padding-bottom: 10px; margin-bottom: 20px; display:flex; align-items:center; gap: 20px; }
                .bill-header img { max-width: 150px; max-height: 80px; }
                .bill-footer { border-top: 1px dashed ${elements.secondaryColorPick.value}; padding-top: 10px; margin-top: 50px; text-align:center; }
                table { width: 100%; border-collapse: collapse; }
                th { background: ${elements.primaryColorPick.value}; color: #fff; padding: 8px; text-align:left; }
                td { padding: 8px; border-bottom: 1px solid #ddd; }
                .partition-heading td { background: ${elements.secondaryColorPick.value}; color: #fff; font-weight: bold; }
                
                ${elements.showWatermark.checked && elements.watermarkPathData.value ? `
                body::before {
                    content: "";
                    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
                    background-image: url('${elements.watermarkPathData.value}');
                    background-repeat: no-repeat; background-position: center; background-size: 50%;
                    opacity: 0.1; z-index: -1; pointer-events: none;
                }` : ''}
                
                /* Page Size Limits purely for visual preview */
                body {
                    max-width: ${elements.pageSize.value === 'A4' ? '210mm' : (elements.pageSize.value === 'A5' ? '148mm' : '80mm')};
                    margin: 0 auto;
                }
            </style>
        </head>
        <body>
            <div class="bill-header">
                ${elements.logoPathData.value ? `<img src="${elements.logoPathData.value}">` : ''}
                <div style="flex-grow:1;">${elements.headerTextData.value || '<h1>Hospital Name</h1>'}</div>
            </div>
            
            <table>
                <thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>
                <tbody>
                    <tr class="partition-heading"><td colspan="4">ROOM CHARGES</td></tr>
                    <tr><td>General Ward</td><td>1</td><td>1000</td><td>1000</td></tr>
                    <tr style="font-weight:bold; text-align:right;"><td colspan="3">Subtotal</td><td>1000</td></tr>
                    
                    <tr class="partition-heading"><td colspan="4">LABORATORY</td></tr>
                    <tr><td>CBC Test</td><td>1</td><td>500</td><td>500</td></tr>
                    <tr style="font-weight:bold; text-align:right;"><td colspan="3">Subtotal</td><td>500</td></tr>
                </tbody>
            </table>
            
            <div class="bill-footer">
                ${elements.footerTextData.value || '<p>Thank you for choosing us.</p>'}
            </div>
        </body>
        </html>
        `;
        const iframeDoc = elements.previewIframe.contentDocument || elements.previewIframe.contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(html);
        iframeDoc.close();
    }

    // 8. Save Data
    elements.saveBtn.addEventListener('click', () => {
        const formData = new FormData(elements.form);
        formData.append('show_watermark', elements.showWatermark.checked ? 1 : 0);
        
        // Disable button during req
        elements.saveBtn.disabled = true;
        elements.saveBtn.textContent = 'Saving...';

        fetch('/hms/save_template.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.success) showToast('Template saved successfully!', 'success');
                else showToast(res.message || 'Error saving template', 'error');
            })
            .catch(err => showToast('Server connection error.', 'error'))
            .finally(() => {
                elements.saveBtn.disabled = false;
                elements.saveBtn.textContent = 'Save Settings';
            });
    });

    function showToast(msg, type) {
        elements.toast.textContent = msg;
        elements.toast.className = `toast ${type}`;
        setTimeout(() => elements.toast.className = 'toast', 3000);
    }
});
