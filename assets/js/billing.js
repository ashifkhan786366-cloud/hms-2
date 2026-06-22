document.addEventListener('DOMContentLoaded', () => {
    
    // --- Global Configurations & State ---
    const settings = window.billSettings || { show_discount_col: '1', show_tax_col: '1', enable_gst: '1' };
    const elements = {
        patientSearch: document.getElementById('patient_search_input'),
        patientId: document.getElementById('hpid'),
        patientName: document.getElementById('hpname'),
        patientAge: document.getElementById('hpage'),
        patientGender: document.getElementById('hpgen'),
        resultsContainer: document.getElementById('patient_dropdown'),
        
        itemsBody: document.getElementById('itemsBody'),
        btnAddRow: document.getElementById('btnAddRow'),
        
        subtotal: document.getElementById('subtotal'),
        totalDiscount: document.getElementById('total_discount'),
        totalTax: document.getElementById('total_tax'),
        grandTotal: document.getElementById('grand_total'),
        paidAmount: document.getElementById('paid_amount'),
        balanceDue: document.getElementById('balance_due'),
        
        form: document.getElementById('billingForm'),
        btnSave: document.getElementById('btnSave'),
        btnSavePrint: document.getElementById('btnSavePrint'),
        btnSavePdf: document.getElementById('btnSavePdf'),
        btnCancel: document.getElementById('btnCancel')
    };

    let rowCount = 0;

    // --- Feature 1: Discount Type State ---
    // 'percent' ya 'amount' — default percent
    window.currentDiscountType = 'percent';

    // --- Initialize ---
    addNewRow(); // Start with empty row
    
    // --- Pre-fill Patient from Dashboard ---
    if (window.prefillPatient && typeof window.prefillPatient === 'object') {
        setTimeout(() => {
            selectPatient(window.prefillPatient);
            const firstItemInput = document.querySelector('.item-search-input');
            if (firstItemInput) firstItemInput.focus();
        }, 100);
    }

    // --- Feature 2: Edit Mode — purani bill pre-fill karo ---
    if (window.editBillData && typeof window.editBillData === 'object') {
        setTimeout(() => {
            loadEditBillData(window.editBillData);
        }, 150);
    }
    
    // --- Utility: Show Toast Notification ---
    window.showToast = function(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    };

    // ────────────────────────────────────────────────────────────
    // FEATURE 1: Discount Toggle Logic (% aur ₹)
    // ────────────────────────────────────────────────────────────

    // Global function — HTML me onclick use karta hai
    window.setDiscountType = function(type) {
        window.currentDiscountType = (type === '%') ? 'percent' : 'amount';
        document.getElementById('discount_type_hidden').value = window.currentDiscountType;

        // Active button style update karo
        const btnPct = document.getElementById('discTypePercent');
        const btnAmt = document.getElementById('discTypeAmount');
        const suffix = document.getElementById('discSuffix');
        const modeLabel = document.getElementById('discModeLabelSmall');

        if (window.currentDiscountType === 'percent') {
            btnPct.classList.add('active-disc');
            btnAmt.classList.remove('active-disc');
            suffix.textContent = '%';
            suffix.style.color = '#007bff';
            modeLabel.textContent = 'Percent mode';
        } else {
            btnAmt.classList.add('active-disc');
            btnPct.classList.remove('active-disc');
            suffix.textContent = '₹';
            suffix.style.color = '#28a745';
            modeLabel.textContent = 'Amount mode';
        }

        // Recalculate discount after toggle
        applyGlobalDiscount();
    };

    // Global discount apply karo — items ka subtotal le ke discount calculate karo
    window.applyGlobalDiscount = function() {
        const discVal = parseFloat(document.getElementById('global_discount_val').value) || 0;
        const subtotalVal = parseFloat(elements.subtotal.value) || 0;

        let discountAmt = 0;
        if (window.currentDiscountType === 'percent') {
            // Percent discount: subtotal ka % nikalo
            discountAmt = (subtotalVal * discVal) / 100;
        } else {
            // Direct ₹ amount discount
            discountAmt = discVal;
        }

        // Max discount subtotal se zyada nahi ho sakta
        if (discountAmt > subtotalVal) discountAmt = subtotalVal;

        elements.totalDiscount.value = discountAmt.toFixed(2);
        const grand = subtotalVal - discountAmt;
        elements.grandTotal.value = Math.round(grand).toFixed(2);
        updateBalance();
        validateSplitPayment(); // Split payment validation bhi update karo
    };

    // ────────────────────────────────────────────────────────────
    // FEATURE 3: Split Payment (Cash + UPI) Logic
    // ────────────────────────────────────────────────────────────

    // Global function — HTML me onchange use karta hai
    window.handlePaymentModeChange = function() {
        const mode = document.getElementById('payment_mode').value;
        const splitSection = document.getElementById('split_payment_section');
        if (mode === 'Split') {
            splitSection.style.display = 'block';
            validateSplitPayment();
        } else {
            splitSection.style.display = 'none';
            document.getElementById('split_error_msg').style.display = 'none';
        }
    };

    // Split payment validation
    window.validateSplitPayment = function() {
        const mode = document.getElementById('payment_mode').value;
        if (mode !== 'Split') return true;

        const grandTotal = parseFloat(elements.grandTotal.value) || 0;
        const cashAmt = parseFloat(document.getElementById('split_cash').value) || 0;
        const upiAmt = parseFloat(document.getElementById('split_upi').value) || 0;
        const splitTotal = cashAmt + upiAmt;

        const errMsg = document.getElementById('split_error_msg');
        const balInfo = document.getElementById('split_balance_info');

        const diff = grandTotal - splitTotal;

        if (Math.abs(diff) < 0.01) {
            // Perfect match
            errMsg.style.display = 'none';
            balInfo.textContent = `✓ Total match: ₹${splitTotal.toFixed(2)}`;
            balInfo.style.color = '#2e7d32';
        } else if (diff > 0) {
            // Kam aaya — balance baki hai
            errMsg.style.display = 'none';
            balInfo.textContent = `Balance remaining: ₹${diff.toFixed(2)}`;
            balInfo.style.color = '#e65100';
        } else {
            // Zyada enter kiya — error
            errMsg.style.display = 'block';
            balInfo.textContent = `Excess: ₹${Math.abs(diff).toFixed(2)}`;
            balInfo.style.color = 'red';
        }

        // paid_amount bhi split total se auto-update karo
        elements.paidAmount.value = splitTotal.toFixed(2);
        updateBalance();
        return Math.abs(diff) < 0.01;
    };

    // ────────────────────────────────────────────────────────────
    // FEATURE 2: Load Edit Bill Data (pre-fill sab kuch)
    // ────────────────────────────────────────────────────────────

    function loadEditBillData(billData) {
        // Edit mode banner show karo
        const banner = document.getElementById('edit_mode_banner');
        if (banner) {
            banner.style.display = 'block';
            const billNoDisplay = document.getElementById('edit_bill_no_display');
            if (billNoDisplay) billNoDisplay.textContent = billData.bill_number;
        }

        // Bill ID store karo for update
        const editIdField = document.getElementById('edit_bill_id');
        if (editIdField) editIdField.value = billData.id;

        // Bill number, date, type set karo
        const billNumField = document.getElementById('bill_number');
        if (billNumField) billNumField.value = billData.bill_number;

        const billTypeField = document.getElementById('bill_type');
        if (billTypeField && billData.bill_type) billTypeField.value = billData.bill_type;

        // Patient info set karo
        if (billData.patient_id) {
            const patientObj = {
                id: billData.patient_id,
                name: billData.patient_name,
                age: billData.age,
                gender: billData.gender,
                phone: billData.phone,
                mr_number: billData.mr_number,
                address: billData.patient_address,
                blood_group: ''
            };
            selectPatient(patientObj);
        }

        // Doctor set karo
        if (billData.doctor_id) {
            const docSelect = document.getElementById('doctor_id');
            if (docSelect) docSelect.value = billData.doctor_id;
        }

        // Payment mode set karo
        if (billData.payment_method) {
            const pmSelect = document.getElementById('payment_mode');
            if (pmSelect) {
                pmSelect.value = billData.payment_method;
                handlePaymentModeChange();
            }
        }

        // Split payment data set karo (agar tha)
        if (billData.payment_mode_cash > 0 || billData.payment_mode_upi > 0) {
            const pmSelect = document.getElementById('payment_mode');
            if (pmSelect) pmSelect.value = 'Split';
            document.getElementById('split_payment_section').style.display = 'block';
            document.getElementById('split_cash').value = billData.payment_mode_cash || 0;
            document.getElementById('split_upi').value = billData.payment_mode_upi || 0;
        }

        // Discount set karo
        if (billData.discount > 0) {
            const discType = billData.discount_type || 'amount';
            window.setDiscountType(discType === 'percent' ? '%' : '₹');
            if (discType === 'percent') {
                document.getElementById('global_discount_val').value = billData.discount_percent || 0;
            } else {
                document.getElementById('global_discount_val').value = billData.discount || 0;
            }
        }

        // Existing items clear karo aur naye items load karo
        const tbody = elements.itemsBody;
        tbody.innerHTML = '';
        rowCount = 0;

        if (billData.items && billData.items.length > 0) {
            billData.items.forEach(item => {
                addNewRow();
                const lastRow = tbody.lastElementChild;
                const rowId = rowCount;

                // Item name set karo
                const searchInput = lastRow.querySelector('.item-search-input');
                if (searchInput) searchInput.value = item.service_name;

                // Item type set karo
                const itemTypeInput = document.getElementById(`item_type_${rowId}`);
                if (itemTypeInput) itemTypeInput.value = item.item_type || 'General';

                // Qty, rate, discount set karo
                const qtyInput = document.getElementById(`qty_${rowId}`);
                if (qtyInput) qtyInput.value = item.quantity || 1;

                const rateInput = document.getElementById(`rate_${rowId}`);
                if (rateInput) rateInput.value = parseFloat(item.cost || 0).toFixed(2);

                const discInput = document.getElementById(`disc_${rowId}`);
                if (discInput) discInput.value = item.discount_percent || 0;

                const amtInput = document.getElementById(`amt_${rowId}`);
                if (amtInput) amtInput.value = parseFloat(item.amount || 0).toFixed(2);
            });
        } else {
            addNewRow(); // Khaali row add karo agar koi item nahi
        }

        // Totals recalculate karo
        calculateTotals();
        applyGlobalDiscount();
    }

    // ---- Patient Search ----
    const patientInput = document.getElementById('patient_search_input');
    const patientDropdown = document.getElementById('patient_dropdown');
    let patientTimer = null;

    patientInput.addEventListener('input', function(){
        clearTimeout(patientTimer);
        const q = this.value.trim();
        if(q.length < 2){ patientDropdown.style.display='none'; return; }
        patientTimer = setTimeout(()=> fetchPatients(q), 250);
    });

    function fetchPatients(q){
        fetch('billing_app.php?action=search_patient&q='+encodeURIComponent(q))
        .then(r=>r.json())
        .then(data=>{
            patientDropdown.innerHTML='';
            if(!data.length){
                patientDropdown.innerHTML=
                '<div class="dd-item dd-empty">No patient found</div>';
            } else {
                data.forEach((p,i)=>{
                    const d = document.createElement('div');
                    d.className='dd-item';
                    d.dataset.idx=i;
                    d.innerHTML=`
                      <strong>${p.name}</strong>
                      <span style="float:right;color:#888;font-size:11px">
                      ${p.mr_number}</span><br>
                      <small>${p.age}Y/${p.gender} | 📞${p.phone}</small>`;
                    d.addEventListener('click',()=>selectPatient(p));
                    patientDropdown.appendChild(d);
                });
                patientDropdown._data = data;
            }
            patientDropdown.style.display='block';
            patientDropdown._active = -1;
        })
        .catch(e=>console.error('Patient search error:',e));
    }

    // Arrow key navigation in patient dropdown
    patientInput.addEventListener('keydown', function(e){
        const items = patientDropdown.querySelectorAll('.dd-item:not(.dd-empty)');
        if(!items.length) return;
        if(e.key==='ArrowDown'){
            e.preventDefault();
            patientDropdown._active = 
            Math.min((patientDropdown._active||-1)+1, items.length-1);
            highlightPatientItem(items);
        } else if(e.key==='ArrowUp'){
            e.preventDefault();
            patientDropdown._active = 
            Math.max((patientDropdown._active||0)-1, 0);
            highlightPatientItem(items);
        } else if(e.key==='Enter'){
            e.preventDefault();
            const idx = patientDropdown._active;
            if(idx>=0 && patientDropdown._data?.[idx]){
                selectPatient(patientDropdown._data[idx]);
            }
        } else if(e.key==='Escape'){
            patientDropdown.style.display='none';
        }
    });

    function highlightPatientItem(items){
        items.forEach(i=>i.classList.remove('active'));
        const idx = patientDropdown._active;
        if(idx>=0 && items[idx]){
            items[idx].classList.add('active');
            items[idx].scrollIntoView({block:'nearest'});
        }
    }

    function selectPatient(p){
        document.getElementById('hpid').value   = p.id;
        document.getElementById('hpname').value = p.name;
        document.getElementById('hpage').value  = p.age;
        document.getElementById('hpgen').value  = p.gender;
        document.getElementById('hpph').value   = p.phone;
        document.getElementById('hpmr').value   = p.mr_number;
        document.getElementById('hpbg').value   = p.blood_group  || '';
        document.getElementById('hpaddr').value = p.address || '';

        document.getElementById('patient_card').style.display='block';
        document.getElementById('patient_card').innerHTML=`
            <table style="width:100%;font-size:12px;line-height:1.8;border:none;">
            <tr><td style="width:22px;border:none;">👤</td><td style="border:none;"><b style="font-size:14px;color:#0056b3">${p.name}</b></td></tr>
            <tr><td style="border:none;">🪪</td><td style="border:none;"><b>${p.mr_number || '—'}</b></td></tr>
            <tr><td style="border:none;">📅</td><td style="border:none;">${p.age}Y / ${p.gender}</td></tr>
            <tr><td style="border:none;">📞</td><td style="border:none;">${p.phone || '—'}</td></tr>
            <tr><td style="border:none;">🏠</td><td style="font-size:11px;border:none;">${p.address||'—'}</td></tr>
            <tr><td style="border:none;">🩸</td><td style="border:none;">${p.blood_group||'—'}</td></tr>
            ${p.created_at ? `<tr><td style="border:none;">🗓️</td><td style="font-size:11px;border:none;">Reg: ${p.created_at.substring(0,10)}</td></tr>` : ''}
            </table>`;
            
        // --- AUTO-FILL FOR DOCTOR, WARD & TYPE ---
        const docSelect = document.getElementById('doctor_id');
        if (docSelect && p.doctor_id) docSelect.value = p.doctor_id;
        
        const wardInput = document.getElementById('ward_room');
        if (wardInput && p.ward_room) wardInput.value = p.ward_room;
        
        const billType = document.getElementById('bill_type');
        if (billType && p.patient_type) billType.value = p.patient_type;

        document.getElementById('change_patient_btn').style.display='inline-block';
        patientInput.style.display='none';
        patientDropdown.style.display='none';

        // Focus first item row
        setTimeout(()=>{
            const f = document.querySelector('.item-search-input');
            if(f) f.focus();
        },100);
    }

    document.getElementById('change_patient_btn')
    .addEventListener('click',function(e){
        e.preventDefault();
        document.getElementById('hpid').value='';
        document.getElementById('patient_card').style.display='none';
        this.style.display='none';
        patientInput.style.display='block';
        patientInput.value='';
        patientInput.focus();
    });

    // Click outside closes dropdown
    document.addEventListener('click',function(e){
        if(!e.target.closest('#patient_search_input') &&
           !e.target.closest('#patient_dropdown')){
            patientDropdown.style.display='none';
        }
    });

    // --- Grid System & Calculations ---
    function addNewRow() {
        rowCount++;
        const tr = document.createElement('tr');
        tr.id = `row_${rowCount}`;
        
        let html = `
            <td class="text-center current-sr">${rowCount}</td>
            <td class="autocomplete-container item-autocomplete-wrapper">
                <input type="text" class="item-search-input" data-row="${rowCount}" placeholder="Search Item..." required autocomplete="off">
                <input type="hidden" class="item-id" id="item_id_${rowCount}">
                <input type="hidden" class="item-type" id="item_type_${rowCount}">
                <div class="autocomplete-list" id="item_results_${rowCount}"></div>
            </td>
            <td><input type="number" class="num-input calc-input qty-input" id="qty_${rowCount}" value="1" step="any" min="0" required data-row="${rowCount}"></td>
            <td><input type="number" class="num-input calc-input rate-input" id="rate_${rowCount}" value="0.00" step="any" required tabindex="-1"></td>
        `;
        
        if (settings.show_discount_col === '1') {
            html += `<td><input type="number" class="num-input calc-input disc-input" id="disc_${rowCount}" value="0.00" step="any"></td>`;
        } else {
            html += `<input type="hidden" class="disc-input" id="disc_${rowCount}" value="0">`;
        }
        
        html += `
            <td><input type="text" class="num-input amt-input readonly" id="amt_${rowCount}" value="0.00" readonly tabindex="-1"></td>
            <td class="text-center"><button type="button" class="btn-remove-row" data-row="${rowCount}" tabindex="-1" title="Alt+Del"><i class="fas fa-trash"></i></button></td>
        `;
        
        tr.innerHTML = html;
        elements.itemsBody.appendChild(tr);
        bindRowEvents(tr, rowCount);
        updateSerialNumbers();
    }

    // Prefill logic
    if (window.prefillPatient) {
        setTimeout(focusLastRow, 100);
    }

    function focusLastRow() {
        const inputs = elements.itemsBody.querySelectorAll('tr:last-child .item-search-input');
        if(inputs.length > 0) inputs[0].focus();
    }

    elements.itemsBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-remove-row');
        if (btn) {
            const row = btn.closest('tr');
            if (elements.itemsBody.children.length > 1) {
                row.remove();
                updateSerialNumbers();
                calculateTotals();
            } else {
                showToast("Cannot remove last row. Clear contents instead.", "error");
            }
        }
    });

    function updateSerialNumbers() {
        const rows = elements.itemsBody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.querySelector('.current-sr').textContent = index + 1;
        });
    }

    // Bind events for row inputs Marg ERP Style
    function bindRowEvents(row, rId) {
        const searchInput = row.querySelector('.item-search-input');
        const resultsBox = row.querySelector('.autocomplete-list');
        const calcs = row.querySelectorAll('.calc-input');
        const qtyInput = row.querySelector('.qty-input');
        
        // Item Autocomplete search
        let itemTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(itemTimeout);
            const query = this.value.trim();
            if(query.length === 0) { resultsBox.style.display = 'none'; return; }
            
            itemTimeout = setTimeout(() => {
                fetch(`billing_app.php?action=search_item&q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        resultsBox.innerHTML = '';
                        if(data.length === 0) {
                            resultsBox.innerHTML = '<div class="dd-item dd-empty">No matches. Type Custom to add free-text.</div>';
                        } else {
                            data.forEach((item, i) => {
                                const div = document.createElement('div');
                                div.className = 'dd-item';
                                div.dataset.idx = i;
                                div.innerHTML = `<span>${item.item_name} <small class="text-muted">(${item.item_type})</small></span> <span class="bold" style="float:right;">₹${parseFloat(item.price).toFixed(2)}</span>`;
                                div.addEventListener('click', () => {
                                    selectItem(row, item);
                                    resultsBox.style.display = 'none';
                                });
                                resultsBox.appendChild(div);
                            });
                            resultsBox._data = data;
                        }
                        resultsBox.style.display = 'block';
                        attachItemKeyNav(searchInput, resultsBox, row); // Attach nav
                    });
            }, 300);
        });
        
        // Tab navigation across row inputs
        calcs.forEach((input, index) => {
            // Mouse wheel scroll to inc/dec on number inputs
            if (input.type === 'number') {
                input.addEventListener('wheel', function(e) {
                    if (document.activeElement === this) {
                        e.preventDefault();
                        const step = parseFloat(this.step || 1) || 1;
                        let val = parseFloat(this.value) || 0;
                        val += e.deltaY < 0 ? step : -step;
                        if(this.min && val < this.min) val = this.min;
                        this.value = val.toFixed(this.hasAttribute('step') && this.step === 'any' ? 2 : 0);
                        calculateTotals();
                    }
                });
            }

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Tab' || e.key === 'Enter') {
                    if (index < calcs.length - 1) {
                        if (e.key === 'Enter') {
                           e.preventDefault();
                           calcs[index + 1].focus();
                           calcs[index + 1].select();
                        }
                    } else {
                        if(searchInput.value.trim() !== '') {
                            const isLastRow = (row.nextElementSibling === null);
                            if (isLastRow) {
                                e.preventDefault();
                                addNewRow();
                                setTimeout(() => {
                                    const nextInput = document.getElementById(`row_${rowCount}`).querySelector('.item-search-input');
                                    if(nextInput) { nextInput.focus(); }
                                }, 50);
                            } else {
                                if (e.key === 'Enter') {
                                   e.preventDefault();
                                   const nextRow = row.nextElementSibling;
                                   nextRow.querySelector('.item-search-input').focus();
                                }
                            }
                        }
                    }
                }
            });
            input.addEventListener('input', calculateTotals);
            input.addEventListener('change', function() {
                if(this.value && !isNaN(this.value)) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });
        });
    }

    function selectItem(row, itemObj) {
        const rowId = row.cells[1].querySelector('.item-search-input').dataset.row;
        row.querySelector('.item-search-input').value = itemObj.item_name;
        document.getElementById(`item_id_${rowId}`).value = itemObj.id;
        document.getElementById(`item_type_${rowId}`).value = itemObj.item_type;
        document.getElementById(`rate_${rowId}`).value = parseFloat(itemObj.price).toFixed(2);
        
        calculateTotals();
        row.querySelector('.qty-input').focus();
        row.querySelector('.qty-input').select();
    }

    function calculateTotals() {
        let subSum = 0;
        let itemDiscSum = 0; // Items ke andar row-wise discount
        
        const rows = elements.itemsBody.querySelectorAll('tr');
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const rate = parseFloat(row.querySelector('.rate-input').value) || 0;
            const discP = parseFloat(row.querySelector('.disc-input').value) || 0;
            
            const baseAmount = qty * rate;
            const discAmt = baseAmount * (discP / 100);
            const finalAmt = baseAmount - discAmt;
            
            row.querySelector('.amt-input').value = finalAmt.toFixed(2);
            
            subSum += baseAmount;
            itemDiscSum += discAmt;
        });
        
        elements.subtotal.value = subSum.toFixed(2);

        // Global discount apply karo (Feature 1)
        applyGlobalDiscount();
        // Note: applyGlobalDiscount internally calls updateBalance()
    }

    function updateBalance() {
        const grand = parseFloat(elements.grandTotal.value) || 0;
        const paid = parseFloat(elements.paidAmount.value) || 0;
        elements.balanceDue.value = (grand - paid).toFixed(2);
    }

    elements.paidAmount.addEventListener('input', updateBalance);
    // Double click paid amount to auto-fill grand total
    elements.paidAmount.addEventListener('dblclick', function() {
        this.value = elements.grandTotal.value;
        updateBalance();
    });

    // --- Save Actions via AJAX ---
    function prepareBillingData() {
        if (!elements.patientId.value && !elements.patientSearch.value.trim()) {
            showToast("Please select or enter patient information", "error");
            if(elements.patientSearch) elements.patientSearch.focus();
            return false;
        }
        
        // Split payment validation
        const paymentMode = document.getElementById('payment_mode').value;
        if (paymentMode === 'Split') {
            if (!validateSplitPayment()) {
                showToast("Cash + UPI total Grand Total se match nahi kar raha! Please check.", "error");
                return false;
            }
        }

        let items = [];
        let valid = true;
        const rows = elements.itemsBody.querySelectorAll('tr');
        
        rows.forEach(row => {
            const itemName = row.querySelector('.item-search-input').value.trim();
            if (itemName === '') return; // skip empty rows
            
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            if (qty <= 0) valid = false;
            
            items.push({
                item_name: itemName,
                item_type: row.querySelector('.item-type').value || 'Unknown',
                qty: qty,
                rate: parseFloat(row.querySelector('.rate-input').value) || 0,
                discount_percent: parseFloat(row.querySelector('.disc-input').value) || 0,
                tax_percent: 0,
                amount: parseFloat(row.querySelector('.amt-input').value) || 0
            });
        });
        
        if (items.length === 0) {
            showToast("Bill must have at least one valid item", "error");
            valid = false;
        }
        
        if (!valid) return false;
        
        return items;
    }

    function submitBill(action) {
        if (!validatePatient()) return;
        
        const items = prepareBillingData();
        if (!items) return;
        
        const fd = new FormData(elements.form);
        fd.append('items', JSON.stringify(items));
        fd.append('global_discount_type', document.getElementById('discount_type_hidden').value);
        fd.append('global_discount_val', document.getElementById('global_discount_val').value);

        // Split payment data
        const paymentMode = document.getElementById('payment_mode').value;
        if (paymentMode === 'Split') {
            fd.set('payment_mode', 'Split');
            fd.append('payment_mode_cash', document.getElementById('split_cash').value || '0');
            fd.append('payment_mode_upi', document.getElementById('split_upi').value || '0');
        }

        // Edit mode check — agar edit hai to update action use karo
        const editBillId = document.getElementById('edit_bill_id')?.value;
        let saveAction = 'save';
        if (editBillId) {
            saveAction = 'update';
            fd.append('edit_bill_id', editBillId);
        }
        
        // Show loading state
        const originalBtnText = elements.btnSave.innerHTML;
        elements.btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        elements.btnSave.disabled = true;
        
        fetch(`billing_app.php?action=${saveAction}`, {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            elements.btnSave.innerHTML = originalBtnText;
            elements.btnSave.disabled = false;
            
            if (data.success) {
                const msg = data?.message || data?.error || 'Action completed successfully';
                showToast(msg);
                
                if (action === 'print') {
                    const printWindow = window.open(`billing_app.php?action=print&id=${data.bill_id}`, '_blank');
                    printWindow.onload = function() {
                        setTimeout(() => location.reload(), 500);
                    };
                } else if (action === 'pdf') {
                    window.location.href = `billing_app.php?action=pdf&id=${data.bill_id}`;
                    setTimeout(() => location.reload(), 2000);
                } else {
                    setTimeout(() => location.reload(), 1500);
                }
            } else {
                showToast(data.error || data.message || 'Save failed. Please try again.', "error");
            }
        })
        .catch(err => {
            console.error(err);
            elements.btnSave.innerHTML = originalBtnText;
            elements.btnSave.disabled = false;
            showToast("Server error saving bill", "error");
        });
    }

    elements.btnSave.addEventListener('click', () => submitBill('save'));
    elements.btnSavePrint.addEventListener('click', () => submitBill('print'));
    elements.btnSavePdf.addEventListener('click', () => submitBill('pdf'));
    
    elements.btnCancel.addEventListener('click', () => {
        if (elements.itemsBody.children.length > 1 || elements.grandTotal.value > 0) {
            if (confirm("Bill contains data. Are you sure you want to cancel and clear all?")) {
                location.reload();
            }
        } else {
            location.reload();
        }
    });

    window.clearPatient = function() {
        if(elements.patientId) elements.patientId.value = '';
        if(elements.patientName) elements.patientName.value = '';
        if(elements.patientAge) elements.patientAge.value = '';
        if(elements.patientGender) elements.patientGender.value = '';
        if(elements.patientSearch) elements.patientSearch.value = '';
        
        document.getElementById('patient_card').style.display = 'none';
        document.getElementById('change_patient_btn').style.display = 'none';
        
        if(elements.patientSearch) {
            elements.patientSearch.style.display = 'block';
            elements.patientSearch.focus();
        }
    };

    // Validate patient selected before save
    function validatePatient() {
        const pid = document.getElementById('hpid')?.value;
        if (!pid) {
            showToast('Please select a patient first!', 'error');
            const searchInput = document.getElementById('patient_search_input');
            if(searchInput) searchInput.focus();
            return false;
        }
        return true;
    }

    // --- Global Keyboard Shortcuts (Marg ERP style) ---
    document.addEventListener('keydown', function(e) {
        // F2: Save
        if (e.key === 'F2') {
            e.preventDefault();
            elements.btnSave.click();
        }
        // F5: Save & Print
        else if (e.key === 'F5') {
            e.preventDefault();
            elements.btnSavePrint.click();
        }
        // F6: Save & PDF
        else if (e.key === 'F6') {
            e.preventDefault();
            elements.btnSavePdf.click();
        }
        // Alt+P: Search Patient
        else if (e.altKey && e.key.toLowerCase() === 'p') {
            e.preventDefault();
            if (document.getElementById('patient_search_input')?.style.display !== 'none') {
                elements.patientSearch.focus();
            }
        }
        // Alt+D: Focus Doctor
        else if (e.altKey && e.key.toLowerCase() === 'd') {
            e.preventDefault();
            document.getElementById('doctor_id').focus();
        }
        // Esc: Cancel/Close autocomplete
        else if (e.key === 'Escape') {
            if (elements.resultsContainer) elements.resultsContainer.style.display = 'none';
        }
    });

    // --- Item Dropdown Keyboard Nav ---
    function attachItemKeyNav(input, dropdown) {
        dropdown._active = -1;
        input.addEventListener('keydown', function(e) {
            const items = dropdown.querySelectorAll('.dd-item:not(.dd-empty)');
            if(!items.length && e.key!=='Tab') return;

            if(e.key==='ArrowDown'){
                e.preventDefault();
                dropdown._active = Math.min(dropdown._active+1, items.length-1);
                highlightItem(items, dropdown._active);
            } else if(e.key==='ArrowUp'){
                e.preventDefault();
                dropdown._active = Math.max(dropdown._active-1, 0);
                highlightItem(items, dropdown._active);
            } else if(e.key==='Enter'){
                e.preventDefault();
                if(dropdown._active>=0 && dropdown._data?.[dropdown._active]){
                    const row = input.closest('tr');
                    selectItem(row, dropdown._data[dropdown._active]);
                    dropdown.style.display='none';
                } else if(dropdown.innerHTML === '' || dropdown.style.display === 'none') {
                    const row = input.closest('tr');
                    const qtyInput = row.querySelector('.qty-input');
                    if(qtyInput) {
                        qtyInput.focus();
                        qtyInput.select();
                    }
                }
            } else if(e.key==='Escape'){
                dropdown.style.display='none';
            }
        });
    }

    function highlightItem(items, idx){
        items.forEach(i=>i.classList.remove('active'));
        if(items[idx]){
            items[idx].classList.add('active');
            items[idx].scrollIntoView({block:'nearest'});
        }
    }
});
