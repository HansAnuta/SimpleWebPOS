// Global State
let products = [];
let cart = [];
let allSales = []; 
let pendingAction = null; 
let confirmCallback = null;
let currentVatRate = 12; 
let currentDiscountType = null;
let selectedPaymentMethod = 'cash';

// Global Settings Object (Initialize with defaults)
let posSettings = {
    vat_rate: 12,
    store_name: 'SimplePOS',
    store_address: 'Philippines'
};

// Service Types
const serviceTypes = [
    {id: 1, name: 'Walk-in'},
    {id: 2, name: 'Take-out'},
    {id: 3, name: 'Delivery'}
];

// --- 1. INITIALIZATION ---
window.addEventListener('DOMContentLoaded', () => {
    initApp();
});

async function initApp() {
    const isAuthenticated = await checkAuth(); 
    if (!isAuthenticated) { 
        window.location.href = 'login.html'; 
        return; 
    }
    
    await fetchSettings();
    
    showView('pos'); 

    // Init Service Type Dropdown
    const svcSelect = document.getElementById('service-type-select');
    if(svcSelect) {
        svcSelect.innerHTML = serviceTypes.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
    }

    // Load Data
    try { await loadCategories(); } catch (e) { console.error("Cat Error", e); }
    try { await loadProducts(); } catch (e) { console.error("Prod Error", e); }
    
    // NEW: Restore Cart from LocalStorage
    restoreCartState();

    // Event Listeners
    const clearBtn = document.getElementById('clear-cart');
    if (clearBtn) clearBtn.addEventListener('click', clearCart);

    const addForm = document.getElementById('add-product-form');
    if(addForm) addForm.addEventListener('submit', handleSaveProduct);
    
    // Search Listeners
    const searchInput = document.getElementById('search-input');
    if(searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const filtered = products.filter(p => 
                p.name.toLowerCase().includes(term) || 
                (p.product_code && p.product_code.toLowerCase().includes(term))
            );
            renderProducts(filtered);
        });
    }

    const salesSearchInput = document.getElementById('sales-search-input');
    if(salesSearchInput) {
        salesSearchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const filtered = allSales.filter(sale => 
                (sale.transaction_number && sale.transaction_number.toLowerCase().includes(term)) ||
                (sale.reference_number && sale.reference_number.toLowerCase().includes(term)) ||
                (sale.customer_name && sale.customer_name.toLowerCase().includes(term))
            );
            renderSalesTable(filtered);
        });
    }
    
    const confirmYesBtn = document.getElementById('confirm-yes-btn');
    if(confirmYesBtn) {
        confirmYesBtn.addEventListener('click', () => {
            if (confirmCallback) confirmCallback();
            closeModal('confirm-modal');
        });
    }

    const passInput = document.getElementById('security-password');
    if(passInput) {
        passInput.addEventListener('keypress', function (e) { 
            if (e.key === 'Enter') verifyAndExecute(); 
        });
    }
}

// --- NEW: PERSISTENCE HELPERS ---
function saveCartState() {
    localStorage.setItem('persistent_cart', JSON.stringify(cart));
}

function restoreCartState() {
    const savedCart = localStorage.getItem('persistent_cart');
    if (savedCart) {
        try {
            cart = JSON.parse(savedCart);
            updateCartUI();
        } catch (e) {
            console.error("Failed to restore cart", e);
            cart = [];
        }
    }
}

function clearCartState() {
    localStorage.removeItem('persistent_cart');
}

// --- FETCH SETTINGS ---
async function fetchSettings() {
    try {
        const res = await fetch('api/get_settings.php?t=' + new Date().getTime());
        const text = await res.text(); 
        const cleanText = text.trim();
        
        try {
            const data = JSON.parse(cleanText);
            if (data) {
                posSettings = {
                    vat_rate: parseFloat(data.vat_rate || 12),
                    store_name: data.store_name || 'SimplePOS',
                    store_address: data.store_address || 'Philippines'
                };
                currentVatRate = posSettings.vat_rate;
                
                const brandEl = document.getElementById('store-brand');
                if(brandEl) brandEl.innerText = posSettings.store_name;
                
                const nameInput = document.getElementById('setting-store-name');
                if(nameInput) {
                    nameInput.value = posSettings.store_name;
                    document.getElementById('setting-store-address').value = posSettings.store_address;
                    document.getElementById('setting-vat-rate').value = posSettings.vat_rate;
                }
            }
        } catch (jsonErr) {
            console.error("JSON Error. Server sent:", text);
        }
    } catch (e) { 
        console.error("Network Error", e); 
    }
}

// --- 2. VIEW NAVIGATION ---
window.showView = function(viewName, btnElement) {
    const currentView = document.querySelector('.view-section.active');
    if (currentView && currentView.id === `${viewName}-view`) return;
    
    document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
    const targetView = document.getElementById(`${viewName}-view`);
    if(targetView) targetView.classList.add('active');
    
    if(btnElement) {
        document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');
    }
    
    if (viewName === 'sales') loadSalesHistory();
    if (viewName === 'settings') loadSettingsPageUI();
}

// --- 3. AUTHENTICATION & SECURITY ---
async function checkAuth() {
    try {
        const response = await fetch('api/check_auth.php');
        if (response.status === 200) {
            const data = await response.json();
            sessionStorage.setItem('user', JSON.stringify(data.user));
            updateUserDisplay(data.user);
            return true;
        }
        return false;
    } catch (error) { return false; }
}

function updateUserDisplay(user) {
    const nameEl = document.getElementById('user-display-name');
    const roleEl = document.getElementById('user-role-display');
    if(nameEl && user) nameEl.innerText = user.first_name + ' ' + user.last_name;
    if(roleEl && user) roleEl.innerText = user.role.toUpperCase();
}

window.logout = function() { window.location.href = 'api/logout.php'; }

function requirePassword(callback) {
    pendingAction = callback;
    document.getElementById('security-password').value = '';
    openModal('security-modal');
    setTimeout(() => document.getElementById('security-password').focus(), 100);
}

async function verifyAndExecute() {
    const password = document.getElementById('security-password').value;
    if (!password) return;
    try {
        const res = await fetch('api/verify_password.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ password: password }) 
        });
        const data = await res.json();
        if (data.success) {
            closeModal('security-modal');
            if (pendingAction) { pendingAction(); pendingAction = null; }
        } else {
            showNotification('Incorrect Password', 'error');
            document.getElementById('security-password').value = '';
        }
    } catch (err) { showNotification('Verification Error', 'error'); }
}

// --- 4. UI COMPONENTS ---
window.showNotification = function(message, type = 'success') {
    const container = document.getElementById('notification-container');
    const notif = document.createElement('div');
    notif.className = `notification ${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    notif.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;
    container.appendChild(notif);
    
    setTimeout(() => {
        notif.classList.add('hiding');
        notif.addEventListener('animationend', () => {
            if (notif.parentNode) notif.parentNode.removeChild(notif);
        });
    }, 3000);
}

window.showConfirm = function(message, callback) {
    document.getElementById('confirm-message').innerText = message;
    confirmCallback = callback;
    openModal('confirm-modal');
}
window.closeModal = function(id) { document.getElementById(id).classList.remove('active'); }
window.openModal = function(id) { document.getElementById(id).classList.add('active'); }
window.onclick = function(event) { if (event.target.classList.contains('modal')) { event.target.classList.remove('active'); } }

// --- 5. PRODUCT MANAGEMENT ---
window.openAddModal = function() {
    document.getElementById('add-product-form').reset();
    resetImagePreview();
    document.getElementById('edit-product-id').value = '';
    document.getElementById('modal-title').innerText = "Add Item";
    const codeInput = document.getElementById('new-code');
    if(codeInput) codeInput.value = '';
    
    document.getElementById('variant-list').innerHTML = '';
    document.getElementById('has-variants-toggle').checked = false;
    toggleVariantInputs();
    addVariantRow();
    
    const datalist = document.getElementById('cat-suggestions');
    if(datalist && products.length > 0) {
        datalist.innerHTML = '';
        const cats = [...new Set(products.map(p => p.category_name))].filter(Boolean);
        cats.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            datalist.appendChild(opt);
        });
    }
    openModal('add-modal');
}

window.promptEdit = function(id) { requirePassword(() => openEditModal(id)); }

function openEditModal(id) {
    const p = products.find(i => i.product_id == id);
    if (!p) return;
    document.getElementById('add-product-form').reset();
    resetImagePreview();
    if(p.image) {
        const preview = document.getElementById('image-preview');
        preview.src = p.image;
        preview.style.display = 'block';
        document.getElementById('upload-placeholder').style.display = 'none';
    }
    document.getElementById('variant-list').innerHTML = '';
    document.getElementById('modal-title').innerText = "Edit Item";
    document.getElementById('edit-product-id').value = p.product_id;
    document.getElementById('new-name').value = p.name;
    document.getElementById('new-category').value = p.category_name;
    const codeInput = document.getElementById('new-code');
    if(codeInput) codeInput.value = p.product_code || '';
    
    const hasVar = p.has_variants == 1;
    document.getElementById('has-variants-toggle').checked = hasVar;
    toggleVariantInputs();
    if (hasVar) { p.variants.forEach(v => addVariantRow(v.variant_name, v.price)); } 
    else { document.getElementById('new-price').value = p.price; }
    openModal('add-modal');
}

async function handleSaveProduct(e) {
    e.preventDefault();
    const formData = new FormData();
    const editId = document.getElementById('edit-product-id').value;
    const url = editId ? 'api/edit_product.php' : 'api/add_product.php';
    if(editId) formData.append('product_id', editId);
    
    formData.append('name', document.getElementById('new-name').value);
    formData.append('category_name', document.getElementById('new-category').value);
    const codeInput = document.getElementById('new-code');
    if(codeInput) formData.append('product_code', codeInput.value);
    
    const imageFile = document.getElementById('new-image').files[0];
    if (imageFile) formData.append('image', imageFile);
    
    const isVariant = document.getElementById('has-variants-toggle').checked;
    if (isVariant) {
        const rows = document.querySelectorAll('.variant-row');
        let variants = [];
        rows.forEach(row => {
            const vName = row.querySelector('.v-name').value;
            const vPrice = row.querySelector('.v-price').value;
            if(vName && vPrice) { variants.push({ name: vName, price: vPrice }); }
        });
        if(variants.length === 0) { showNotification("Add at least one variant.", "error"); return; }
        formData.append('variants', JSON.stringify(variants));
    } else {
        formData.append('price', document.getElementById('new-price').value);
    }
    
    try {
        const res = await fetch(url, { method: 'POST', body: formData });
        const data = await res.json();
        if (res.ok) {
            showNotification(editId ? 'Item Updated!' : 'Item Added!', 'success');
            closeModal('add-modal');
            await loadCategories();
            await loadProducts();
        } else { showNotification(data.message || 'Error saving product', 'error'); }
    } catch (err) { showNotification('System Error', 'error'); }
}

window.toggleVariantInputs = function() {
    const isChecked = document.getElementById('has-variants-toggle').checked;
    document.getElementById('simple-inputs').style.display = isChecked ? 'none' : 'flex';
    document.getElementById('variant-inputs').style.display = isChecked ? 'block' : 'none';
}

window.addVariantRow = function(name='', price='') {
    const container = document.getElementById('variant-list');
    const div = document.createElement('div');
    div.className = 'variant-row';
    div.innerHTML = `
        <input type="text" class="v-name" value="${name}" placeholder="Name">
        <input type="number" class="v-price" value="${price}" placeholder="Price" step="0.01">
        <button type="button" class="btn-remove-variant" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

window.previewImage = function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const preview = document.getElementById('image-preview');
            preview.src = evt.target.result;
            preview.style.display = 'block';
            document.getElementById('upload-placeholder').style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
}

function resetImagePreview() {
    const preview = document.getElementById('image-preview');
    const placeholder = document.getElementById('upload-placeholder');
    const input = document.getElementById('new-image');
    preview.src = ''; preview.style.display = 'none'; placeholder.style.display = 'flex'; input.value = '';
}

// --- 6. PRODUCT GRID ---
async function loadCategories() { 
    try {
        const response = await fetch('api/get_categories.php');
        const categories = await response.json();
        const container = document.getElementById('category-list');
        if (!container) return;
        container.innerHTML = ''; 
        
        const allBtn = document.createElement('button');
        allBtn.textContent = 'All Items';
        allBtn.className = 'active'; 
        allBtn.onclick = () => filterByCategory('all', allBtn);
        container.appendChild(allBtn);
        
        categories.forEach(cat => {
            const btn = document.createElement('button');
            btn.textContent = cat.name;
            btn.onclick = () => filterByCategory(cat.category_id, btn);
            container.appendChild(btn);
        });
    } catch (error) { console.error(error); }
}

async function loadProducts() {
    try {
        const response = await fetch('api/get_products.php'); 
        if (response.ok) {
            products = await response.json();
            renderProducts(products);
        }
    } catch (error) { console.error(error); }
}

function renderProducts(productList) {
    const grid = document.getElementById('product-grid');
    if(!grid) return;
    
    if (!productList || productList.length === 0) {
        grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #666;">No items found.</p>';
        return;
    }

    const loading = grid.querySelector('.loading');
    if(loading) loading.remove();

    grid.innerHTML = '';

    productList.forEach((product, index) => {
        const hasVariants = product.has_variants == 1;
        let metaHtml = '<div class="meta-spacer">&nbsp;</div>'; 
        let priceText = `PHP ${parseFloat(product.price).toFixed(2)}`;
        
        if (hasVariants && product.variants && product.variants.length > 0) {
             const prices = product.variants.map(v => parseFloat(v.price));
             metaHtml = `<div class="variant-badge">${product.variants.length} Options</div>`;
             priceText = `PHP ${Math.min(...prices).toFixed(2)}`; 
        }

        const imageHtml = product.image 
            ? `<div class="product-img-wrapper"><img src="${product.image}" class="product-img"></div>`
            : `<div class="product-img-wrapper"><i class="fas fa-box fa-3x" style="color:#cbd5e1;"></i></div>`;

        const card = document.createElement('div');
        card.className = 'product-card';
        card.dataset.id = product.product_id;
        card.style.animationDelay = `${index * 0.05}s`; 
        
        card.innerHTML = `
            <div class="card-actions">
                <button class="action-btn edit-btn" data-action="edit"><i class="fas fa-edit"></i></button>
                <button class="action-btn delete-btn" data-action="delete"><i class="fas fa-trash"></i></button>
            </div>
            ${imageHtml}
            <div class="product-card-content">
                <h3 class="product-name">${product.name}</h3>
                ${metaHtml}
                <div class="product-price">${priceText}</div>
            </div>
        `;
        
        card.addEventListener('click', (e) => {
            if (e.target.closest('.action-btn')) {
                const btn = e.target.closest('.action-btn');
                if(btn.dataset.action === 'edit') promptEdit(product.product_id);
                if(btn.dataset.action === 'delete') promptDelete(product.product_id);
                return;
            }
            checkVariantAndAdd(product);
        });
        
        grid.appendChild(card);
    });
}

function filterByCategory(categoryId, clickedBtn) {
    if (clickedBtn) {
        document.querySelectorAll('.categories button').forEach(btn => btn.classList.remove('active'));
        clickedBtn.classList.add('active');
    }
    let filtered = (categoryId === 'all') ? products : products.filter(p => p.category_id == categoryId);
    renderProducts(filtered); 
}

// --- 7. CART LOGIC ---
window.checkVariantAndAdd = function(p) {
    if (p.has_variants == 1 && p.variants.length > 0) {
        const container = document.getElementById('variant-options-container');
        container.innerHTML = '';
        p.variants.forEach(v => {
            const btn = document.createElement('button');
            btn.className = 'pay-btn';
            btn.style.width = '100%';
            btn.style.flexDirection = 'row';
            btn.style.justifyContent = 'space-between';
            btn.innerHTML = `<span>${v.variant_name}</span> <strong>PHP ${parseFloat(v.price).toFixed(2)}</strong>`;
            btn.onclick = () => { addToCart(p, v); closeModal('select-variant-modal'); };
            container.appendChild(btn);
        });
        openModal('select-variant-modal');
    } else { addToCart(p, null); }
}

// UPDATED: Save state after adding
function addToCart(p, v) {
    const id = v ? `${p.product_id}-${v.variant_id}` : `${p.product_id}`;
    const existingItem = cart.find(i => String(i.uniqueId) === String(id));
    
    if (existingItem) { 
        existingItem.qty++; 
    } else {
        cart.push({
            uniqueId: String(id),
            product_id: p.product_id,
            variant_id: v ? v.variant_id : null,
            variant_name: v ? v.variant_name : null,
            name: p.name,
            price: parseFloat(v ? v.price : p.price),
            image: p.image,
            qty: 1
        });
    }
    updateCartUI();
    saveCartState(); 
    showNotification("Added to cart", "success");
}

function updateCartUI() {
    const container = document.getElementById('cart-items');
    if (!container) return;

    container.innerHTML = '';

    if (cart.length === 0) {
        container.innerHTML = `<div class="empty-cart-state"><p>No items yet</p></div>`;
        calculateTotals();
        return;
    }

    const fragment = document.createDocumentFragment();

    cart.forEach((item, index) => {
        const itemDisplayName = item.variant_name ? `${item.name} (${item.variant_name})` : item.name;
        
        const div = document.createElement('div');
        div.className = 'cart-item';
        
        let imgHtml = '';
        if (item.image) {
            imgHtml = `<img src="${item.image}" class="cart-item-img" onerror="this.style.display='none'">`;
        } else {
            imgHtml = `<div class="cart-item-img" style="display:flex;align-items:center;justify-content:center;color:#ccc"><i class="fas fa-box"></i></div>`;
        }

        div.innerHTML = `
            ${imgHtml}
            <div class="item-info">
                <h4>${itemDisplayName}</h4>
                <div class="unit-price">PHP ${parseFloat(item.price).toFixed(2)} x ${item.qty}</div>
            </div>
            <div class="item-controls">
                <button class="qty-btn" data-action="decrease">-</button>
                <span class="qty-val">${item.qty}</span>
                <button class="qty-btn" data-action="increase">+</button>
            </div>
            <div class="item-total">PHP ${(item.price * item.qty).toFixed(2)}</div>
            <button class="btn-remove-item" data-action="remove"><i class="fas fa-times"></i></button>
        `;

        div.querySelector('[data-action="decrease"]').onclick = () => changeQty(index, -1);
        div.querySelector('[data-action="increase"]').onclick = () => changeQty(index, 1);
        div.querySelector('[data-action="remove"]').onclick = () => removeOneItem(index);

        fragment.appendChild(div);
    });

    container.appendChild(fragment);
    calculateTotals();
}

// UPDATED: Save state after removing
window.removeOneItem = function(index) {
    requirePassword(() => {
        cart.splice(index, 1);
        updateCartUI();
        saveCartState(); 
    });
}

// UPDATED: Save state after qty change
window.changeQty = function(index, change) {
    if (cart[index].qty + change <= 0) {
        removeOneItem(index);
    } else {
        cart[index].qty += change;
        updateCartUI();
        saveCartState(); 
    }
}

// UPDATED: Clear state on manual clear
window.clearCart = function() {
    if(cart.length === 0) return;
    requirePassword(() => { 
        cart = []; 
        updateCartUI(); 
        clearCartState(); 
        showNotification("Cart Cleared", "success"); 
    });
}

window.promptDelete = function(id) { requirePassword(() => deleteProduct(id)); }

async function deleteProduct(id) {
    try {
        const res = await fetch('api/delete_product.php', { method: 'POST', body: JSON.stringify({ product_id: id }) });
        const data = await res.json();
        if (res.ok) { showNotification('Product Deleted', 'success'); await loadProducts(); await loadCategories(); }
        else { showNotification(data.message, 'error'); }
    } catch (e) { showNotification('Error', 'error'); }
}

function calculateTotals() {
    let sub = cart.reduce((acc, i) => acc + (i.price * i.qty), 0);
    
    let vat = 0;
    let disc = 0;
    
    if (currentDiscountType === 'Senior' || currentDiscountType === 'PWD') {
        let vatableSales = sub / (1 + (currentVatRate / 100));
        vat = 0; 
        let calculatedDisc = vatableSales * 0.20;
        disc = (calculatedDisc > 50) ? 50 : calculatedDisc;
    } else {
        let netSales = sub / (1 + (currentVatRate / 100));
        vat = sub - netSales;
        disc = 0;
    }
    
    let total = (currentDiscountType) ? ((sub / (1 + (currentVatRate / 100))) - disc) : sub;
    if (total < 0) total = 0;

    const subEl = document.getElementById('subtotal');
    if(subEl) subEl.innerText = `PHP ${sub.toFixed(2)}`;
    
    const totalEl = document.getElementById('total');
    if(totalEl) totalEl.innerText = `PHP ${total.toFixed(2)}`;
    
    const vatRow = document.getElementById('vat-row');
    if(vatRow) {
        const vatLabel = document.getElementById('vat-rate-disp');
        if(vatLabel) vatLabel.innerText = currentVatRate; 

        if(currentDiscountType) {
            vatRow.style.display = 'flex';
            document.getElementById('vat-amount').innerText = `PHP 0.00 (Void)`;
        } else {
            vatRow.style.display = 'flex';
            document.getElementById('vat-amount').innerText = `PHP ${vat.toFixed(2)}`;
        }
    }

    const discRow = document.getElementById('disc-row');
    if(discRow) {
        if(currentDiscountType) { 
            discRow.style.display = 'flex'; 
            document.getElementById('disc-amount').innerText = `-PHP ${disc.toFixed(2)}`; 
        } else { 
            discRow.style.display = 'none'; 
        }
    }

    if(document.getElementById('pay-total')) document.getElementById('pay-total').innerText = `PHP ${total.toFixed(2)}`;
    return { total, subtotal: sub, vatAmount: vat, discountAmount: disc };
}

window.processCheckout = function() {
    if (cart.length === 0) { showNotification("Cart is empty", "error"); return; }
    calculateTotals();
    document.getElementById('amount-tendered').value = '';
    document.getElementById('ref-number').value = ''; 
    document.getElementById('discount-cust-name').value = '';
    document.getElementById('discount-id-number').value = '';
    selectPayment('cash');
    toggleDiscountInputs(); 
    openModal('payment-modal');
}

window.toggleDiscountType = function(type) {
    if (currentDiscountType === type) {
        currentDiscountType = null;
        const btn = document.getElementById(`btn-${type.toLowerCase()}`);
        if(btn) btn.classList.remove('active-disc');
    } else {
        currentDiscountType = type;
        document.querySelectorAll('.disc-btn').forEach(b => b.classList.remove('active-disc'));
        const btn = document.getElementById(`btn-${type.toLowerCase()}`);
        if(btn) btn.classList.add('active-disc');
    }
    toggleDiscountInputs();
    calculateTotals();
}

function toggleDiscountInputs() {
    const inputs = document.getElementById('discount-details-section');
    if (currentDiscountType) {
        inputs.style.display = 'block';
    } else {
        inputs.style.display = 'none';
    }
}

window.confirmPayment = function() { executePayment(); }

async function executePayment() {
    const totals = calculateTotals();
    const tendered = parseFloat(document.getElementById('amount-tendered').value) || 0;
    const refNumber = document.getElementById('ref-number').value;
    const serviceTypeId = document.getElementById('service-type-select').value;
    
    let custName = null;
    let custIdType = null;
    let custIdNum = null;
    
    if (currentDiscountType) {
        custName = document.getElementById('discount-cust-name').value;
        custIdNum = document.getElementById('discount-id-number').value;
        custIdType = currentDiscountType + " ID"; 
        
        if (!custName || !custIdNum) {
            showNotification("Name and ID Number required for Discount", "error");
            return;
        }
    }

    if(selectedPaymentMethod === 'cash' && tendered < totals.total) { 
        showNotification("Insufficient Amount", "error"); 
        return; 
    }
    
    if(selectedPaymentMethod !== 'cash' && !refNumber) {
        showNotification("Reference Number Required", "error");
        return;
    }

    const orderData = { 
        items: cart, 
        total: totals.total, 
        subtotal: totals.subtotal, 
        vat_amount: totals.vatAmount, 
        discount_amount: totals.discountAmount, 
        discount_type: currentDiscountType, 
        payment_method: selectedPaymentMethod, 
        amount_tendered: selectedPaymentMethod === 'cash' ? tendered : totals.total, 
        change_amount: selectedPaymentMethod === 'cash' ? (tendered - totals.total) : 0, 
        reference_number: refNumber, 
        service_type_id: serviceTypeId,
        customer_name: custName,
        customer_id_type: custIdType,
        customer_id_number: custIdNum
    };
    
    try {
        const res = await fetch('api/save_order.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(orderData) });
        const result = await res.json();
        
        if (res.ok) {
            closeModal('payment-modal');
            orderData.transaction_number = result.transaction_number;
            showReceipt(result.order_id, orderData);
            openModal('receipt-modal');
            
            // Clear cart and storage on success
            cart = []; 
            updateCartUI(); 
            clearCartState(); 
            
            currentDiscountType = null;
            document.querySelectorAll('.disc-btn').forEach(b => b.classList.remove('active-disc'));
        } else { 
            showNotification(result.error || result.message, "error"); 
        }
    } catch (e) { console.error(e); }
}

// --- UPDATED RECEIPT LOGIC (Compliance Update) ---
function showReceipt(id, data) {
    const storeName = posSettings.store_name || 'SimplePOS';
    const storeAddr = posSettings.store_address || 'Philippines';
    
    let total = (data.total !== undefined) ? data.total : data.total_amount;
    if (total === undefined || total === null) total = 0;
    
    const subtotal = data.subtotal || 0;
    const disc = data.discount_amount || 0;
    const vat = data.vat_amount || 0;
    const serviceName = serviceTypes.find(s => s.id == data.service_type_id)?.name || data.service_type || 'Walk-in';

    let itemsHtml = data.items.map(i => {
        const variantStr = i.variant || i.variant_name;
        const displayName = variantStr ? `${i.name} (${variantStr})` : i.name;
        return `<p style="display:flex;justify-content:space-between;margin-bottom:5px;"><span>${displayName} x${i.qty}</span><span>PHP ${(i.price*i.qty).toFixed(2)}</span></p>`;
    }).join('');

    // --- CUSTOMER INFO FOR DISCOUNTS ---
    let customerHtml = '';
    
    // Check if discount was applied (either by amount > 0 or type exists)
    if (parseFloat(data.discount_amount) > 0 || (data.discount_type && data.discount_type !== 'null')) {
        const idLabel = data.customer_id_type ? data.customer_id_type : (data.discount_type + ' ID');
        
        customerHtml = `
            <div style="margin-top:15px; border-top:1px dashed #9ca3af; padding-top:10px; font-size:0.8rem; text-align:left; line-height: 1.6; color: #000;">
                <p><strong>Customer Name:</strong> ${data.customer_name ? data.customer_name.toUpperCase() : ''}</p>
                <p><strong>${idLabel} Number:</strong> ${data.customer_id_number || 'N/A'}</p>
                <div style="margin-top: 20px; display: flex; align-items: flex-end;">
                    <span style="font-weight: bold; margin-right: 5px;">Signature:</span>
                    <div style="flex: 1; border-bottom: 1px solid #000; margin-bottom: 3px;"></div>
                </div>
            </div>
        `;
    }

    document.getElementById('receipt-content').innerHTML = `
        <div style="text-align:center; font-family: monospace; color: #000;">
            <h3 style="margin-bottom: 5px; font-size: 1.2rem;">${storeName}</h3>
            <p style="font-size:0.8rem; margin-bottom: 15px;">${storeAddr}</p>
            
            <div style="text-align: center; margin-bottom: 10px; font-size: 0.85rem;">
                <p style="font-weight:bold;">${data.transaction_number || 'TRX-0000'}</p>
                <p>${new Date().toLocaleString()}</p>
                <p>Type: ${serviceName}</p>
                ${data.reference_number ? `<p>Ref: ${data.reference_number}</p>` : ''}
            </div>
            
            <hr style="border:1px dashed #9ca3af; margin:10px 0;">
            <div style="text-align:left; font-size: 0.9rem;">${itemsHtml}</div>
            <hr style="border:1px dashed #9ca3af; margin:10px 0;">
            
            <div style="font-size: 0.9rem; line-height: 1.4;">
                <p style="display:flex;justify-content:space-between"><span>Subtotal:</span><span>PHP ${parseFloat(subtotal).toFixed(2)}</span></p>
                ${disc > 0 ? `<p style="display:flex;justify-content:space-between;"><span>Disc (${data.discount_type}):</span><span>-PHP ${parseFloat(disc).toFixed(2)}</span></p>` : ''}
                ${vat > 0 ? `<p style="display:flex;justify-content:space-between;font-size:0.8rem"><span>VAT (Incl.):</span><span>PHP ${parseFloat(vat).toFixed(2)}</span></p>` : ''}
                
                <h3 style="text-align:right; margin-top:10px; font-size: 1.3rem;">Total: PHP ${parseFloat(total).toFixed(2)}</h3>
                <p style="text-align:right; font-size:0.8rem;">Paid via ${data.payment_method}</p>
            </div>
            
            ${customerHtml}
            
            <p style="margin-top: 20px; font-size: 0.8rem; text-align: center;">Thank you, come again!</p>
        </div>`;
}

window.printReceipt = function() {
    const content = document.getElementById('receipt-content').innerHTML;
    const win = window.open('', '', 'height=600,width=400');
    win.document.write('<html><head><title>Receipt</title><style>body{font-family:sans-serif; text-align:center; padding:20px;} hr{border-top:1px dashed #000;}</style></head><body>' + content + '</body></html>');
    win.document.close(); win.print();
}

window.selectPayment = function(method) {
    selectedPaymentMethod = method;
    document.querySelectorAll('.pay-btn').forEach(btn => {
        const label = btn.querySelector('span').innerText;
        if(label.toLowerCase() === method.toLowerCase()) btn.classList.add('active');
        else btn.classList.remove('active');
    });
    if (method === 'cash') { document.getElementById('cash-section').style.display = 'block'; document.getElementById('ref-section').style.display = 'none'; } 
    else { document.getElementById('cash-section').style.display = 'none'; document.getElementById('ref-section').style.display = 'block'; }
}

window.calculateChange = function() {
    const totals = calculateTotals();
    const tendered = parseFloat(document.getElementById('amount-tendered').value) || 0;
    const change = tendered - totals.total;
    const changeEl = document.getElementById('change-amount');
    if(change >= 0) { changeEl.innerText = `PHP ${change.toFixed(2)}`; changeEl.style.color = 'var(--text)'; } 
    else { changeEl.innerText = "Insufficient"; changeEl.style.color = 'red'; }
}

// --- NEW: DATE FILTER LOGIC ---
window.filterSalesByDate = function() {
    const filterType = document.getElementById('sales-date-filter').value;
    const now = new Date();
    const todayStr = now.toDateString();
    
    let filteredData = [];
    let labelText = "(All Time)";

    if (filterType === 'all') {
        filteredData = allSales;
    } 
    else if (filterType === 'today') {
        filteredData = allSales.filter(sale => new Date(sale.created_at).toDateString() === todayStr);
        labelText = "(Today)";
    } 
    else {
        // Filter by Specific Month (Value is "YYYY-MM")
        filteredData = allSales.filter(sale => sale.created_at.startsWith(filterType));
        const [y, m] = filterType.split('-');
        const date = new Date(y, m - 1);
        labelText = `(${date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })})`;
    }

    // Update Revenue Label
    const labelEl = document.getElementById('revenue-label');
    if(labelEl) labelEl.innerText = labelText;

    renderSalesTable(filteredData);
}

// --- UPDATE: LOAD SALES HISTORY ---
window.loadSalesHistory = async function() {
    try {
        const res = await fetch('api/get_sales.php');
        allSales = await res.json();
        
        // 1. Populate the Dynamic Filter
        populateDateFilter(allSales);
        
        // 2. Render Default View (All Time)
        filterSalesByDate(); 
        
    } catch (e) { showNotification("Error loading sales", "error"); }
}

function populateDateFilter(sales) {
    const filterEl = document.getElementById('sales-date-filter');
    // Keep the first 2 options (All Time, Today) and clear the rest
    filterEl.innerHTML = `
        <option value="all">All Time</option>
        <option value="today">Today</option>
    `;
    
    // Extract Unique Months (YYYY-MM)
    const months = new Set();
    sales.forEach(sale => {
        const date = new Date(sale.created_at);
        const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        months.add(key);
    });
    
    // Sort Descending (Newest Month First)
    const sortedMonths = Array.from(months).sort().reverse();
    
    // Create Options
    sortedMonths.forEach(monthKey => {
        const [year, month] = monthKey.split('-');
        const date = new Date(year, month - 1);
        const label = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        
        const opt = document.createElement('option');
        opt.value = monthKey; // Value: "2026-02"
        opt.innerText = label; // Text: "February 2026"
        filterEl.appendChild(opt);
    });
}

// --- UPDATED SALES RENDERER (With Net & VAT Calc) ---
function renderSalesTable(salesData) {
    const tbody = document.getElementById('sales-table-body');
    if(!tbody) return;
    tbody.innerHTML = '';
    
    // Initialize Totals
    let totalSales = 0;
    let totalVat = 0; // New Variable
    
    // Date Grouping Helper
    let lastDateStr = null;
    const todayStr = new Date().toDateString();
    
    // Helper for "Yesterday"
    const yest = new Date();
    yest.setDate(yest.getDate() - 1);
    const yesterdayStr = yest.toDateString();
    
    if(!salesData || salesData.length === 0) { 
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px;">No matching records found.</td></tr>'; 
    } else {
        salesData.forEach(sale => {
            // Accumulate Totals
            const saleTotal = parseFloat(sale.total_amount);
            const saleVat = parseFloat(sale.vat_amount || 0);
            
            totalSales += saleTotal;
            totalVat += saleVat; // Accumulate VAT
            
            // --- SECTIONING LOGIC ---
            const saleDate = new Date(sale.created_at);
            const currentDateStr = saleDate.toDateString();
            
            if (currentDateStr !== lastDateStr) {
                let label = saleDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
                if (currentDateStr === todayStr) label = "Today";
                else if (currentDateStr === yesterdayStr) label = "Yesterday";
                
                const sepRow = document.createElement('tr');
                sepRow.className = 'date-separator';
                sepRow.innerHTML = `<td colspan="6">${label}</td>`;
                tbody.appendChild(sepRow);
                lastDateStr = currentDateStr;
            }
            // ------------------------

            const tr = document.createElement('tr');
            tr.className = 'clickable-row';
            
            const itemCount = sale.items.reduce((acc, i) => acc + parseInt(i.qty), 0);
            const discountDisplay = (parseFloat(sale.discount_amount) > 0) 
                ? `<span style="color:green; font-size:0.8rem;">${sale.discount_type} (-${parseFloat(sale.discount_amount).toFixed(2)})</span>` 
                : '-';
            
            const customerDisplay = sale.customer_name ? `<br><small style="color:#666">${sale.customer_name}</small>` : '';
            const timeStr = saleDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

            tr.innerHTML = `
                <td style="font-family:monospace; font-weight:600; color:var(--primary);">${sale.transaction_number || sale.formatted_ref}</td>
                <td>${timeStr}</td>
                <td><span class="badge-service">${sale.service_type || 'Walk-in'}</span>${customerDisplay}</td>
                <td>${itemCount}</td>
                <td>${discountDisplay}</td>
                <td><span class="text-success">PHP ${saleTotal.toFixed(2)}</span></td>
            `;
            tr.onclick = () => toggleOrderDetails(tr, sale);
            tbody.appendChild(tr);
        });
    }
    
    // Calculate Net Sales (Revenue - VAT)
    const netSales = totalSales - totalVat;

    // Update All 4 Cards
    if(document.getElementById('report-total-sales')) document.getElementById('report-total-sales').innerText = `PHP ${totalSales.toFixed(2)}`;
    if(document.getElementById('report-total-orders')) document.getElementById('report-total-orders').innerText = salesData.length;
    
    // NEW UPDATES
    if(document.getElementById('report-net-sales')) document.getElementById('report-net-sales').innerText = `PHP ${netSales.toFixed(2)}`;
    if(document.getElementById('report-total-vat')) document.getElementById('report-total-vat').innerText = `PHP ${totalVat.toFixed(2)}`;
}

function toggleOrderDetails(row, sale) {
    const nextRow = row.nextElementSibling;
    if (nextRow && nextRow.classList.contains('order-detail-row')) { nextRow.remove(); row.classList.remove('active-row'); return; }
    document.querySelectorAll('.order-detail-row').forEach(el => { el.previousElementSibling.classList.remove('active-row'); el.remove(); });
    row.classList.add('active-row');

    let itemsHtml = sale.items.map(item => `
        <tr>
            <td>${item.qty}x</td>
            <td>${item.name} ${item.variant ? `(${item.variant})` : ''}</td>
            <td>PHP ${parseFloat(item.price).toFixed(2)}</td>
            <td style="text-align:right;">PHP ${parseFloat(item.total).toFixed(2)}</td>
        </tr>
    `).join('');

    const detailRow = document.createElement('tr');
    detailRow.className = 'order-detail-row';
    detailRow.innerHTML = `
        <td colspan="6">
            <div class="detail-container">
                <table class="detail-table">
                    <thead><tr><th width="10%">Qty</th><th width="50%">Item</th><th width="20%">Price</th><th width="20%" style="text-align:right;">Total</th></tr></thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
                <div class="detail-footer">
                    <div class="breakdown">
                        <p><span>Subtotal:</span> <span>PHP ${parseFloat(sale.subtotal).toFixed(2)}</span></p>
                        ${parseFloat(sale.discount_amount) > 0 ? `<p style="color:green;"><span>Discount (${sale.discount_type}):</span> <span>-PHP ${parseFloat(sale.discount_amount).toFixed(2)}</span></p>` : ''}
                        <p><span>VAT:</span> <span>PHP ${parseFloat(sale.vat_amount).toFixed(2)}</span></p>
                        <h4 style="margin-top:5px; border-top:1px solid #ddd; padding-top:5px;"><span>Total:</span> <span>PHP ${parseFloat(sale.total_amount).toFixed(2)}</span></h4>
                    </div>
                    <div class="actions">
                        <p style="font-size:0.8rem; color:#666;">Ref: ${sale.reference_number || 'N/A'}</p>
                        <button class="btn-primary" onclick="showReceipt(${sale.order_id}, ${JSON.stringify(sale).replace(/"/g, '&quot;')}); openModal('receipt-modal');">
                            <i class="fas fa-print"></i> Reprint Receipt
                        </button>
                    </div>
                </div>
            </div>
        </td>
    `;
    row.parentNode.insertBefore(detailRow, row.nextSibling);
}

// --- 9. SAVE SETTINGS ---
window.saveGlobalSettings = function() {
    requirePassword(async () => {
        // 1. Get Elements safely
        const vatEl = document.getElementById('setting-vat-rate');
        const nameEl = document.getElementById('setting-store-name');
        const addrEl = document.getElementById('setting-store-address');

        // 2. Crash Prevention: Check if elements exist
        if (!vatEl || !nameEl || !addrEl) {
            showNotification("Error: Settings form incomplete. Refresh page.", "error");
            return;
        }

        const payload = { 
            vat_rate: vatEl.value, 
            store_name: nameEl.value, 
            store_address: addrEl.value 
        };
        
        try {
            const res = await fetch('api/save_settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            
            if(res.ok) {
                showNotification("Settings Saved Successfully!", "success");
                await fetchSettings(); // Reload global settings immediately
                // Update header manually just in case
                document.getElementById('store-brand').innerText = payload.store_name;
            } else {
                showNotification("Error saving settings", "error");
            }
        } catch(e) { console.error(e); }
    });
}

// --- 11. SETTINGS PAGE UI LOADER ---
function loadSettingsPageUI() {
    const vatInput = document.getElementById('setting-vat-rate');
    const nameInput = document.getElementById('setting-store-name');
    const addrInput = document.getElementById('setting-store-address');

    if (vatInput) vatInput.value = posSettings.vat_rate;
    // Handle potential null/undefined for text inputs
    if (nameInput) nameInput.value = posSettings.store_name || '';
    if (addrInput) addrInput.value = posSettings.store_address || '';
}

// --- NEW: SEPARATE SAVE FUNCTIONS ---

// 1. Save Store Info Only
window.saveStoreSettings = function() {
    const name = document.getElementById('setting-store-name').value;
    const addr = document.getElementById('setting-store-address').value;
    
    if(!name || !addr) { showNotification("Please fill in fields", "error"); return; }

    requirePassword(async () => {
        // INSTANTLY Update Global State (Client-Side)
        posSettings.store_name = name;
        posSettings.store_address = addr;
        
        // Update Sidebar
        document.getElementById('store-brand').innerText = name;

        // Send to Backend
        const payload = { 
            vat_rate: posSettings.vat_rate, // Keep existing VAT
            store_name: name, 
            store_address: addr 
        };
        await executeSaveSettings(payload, "Store Info Updated!");
    });
}

// 2. Save Tax Info Only
window.saveTaxSettings = function() {
    const vat = document.getElementById('setting-vat-rate').value;
    
    requirePassword(async () => {
        // INSTANTLY Update Global State
        posSettings.vat_rate = parseFloat(vat);
        currentVatRate = parseFloat(vat);
        
        // Send to Backend
        const payload = { 
            vat_rate: vat,
            store_name: posSettings.store_name, // Keep existing Name
            store_address: posSettings.store_address // Keep existing Address
        };
        await executeSaveSettings(payload, "Tax Rate Updated!");
    });
}

async function executeSaveSettings(payload, successMsg) {
    try {
        const res = await fetch('api/save_settings.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        
        if(res.ok) {
            showNotification(successMsg, "success");
            // Optional: Re-fetch to confirm sync
            fetchSettings();
        } else {
            showNotification("Save Failed: " + (data.message || "Unknown error"), "error");
        }
    } catch(e) { console.error(e); showNotification("Connection Error", "error"); }
}