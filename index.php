<?php
session_start();
// Security Gate: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page immediately
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/png" href="pebble.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div id="notification-container" class="notification-container"></div>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button id="sidebar-toggle" class="sidebar-toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="brand" id="store-brand">
            Pebble
        </div>
    </div>
    
    <ul class="nav-links">
        <li>
            <button class="nav-btn active" onclick="showView('pos', this)">
                <i class="fas fa-cash-register"></i> <span>Register</span>
            </button>
        </li>
        <li>
            <button class="nav-btn" onclick="showView('sales', this)">
                <i class="fas fa-chart-pie"></i> <span>Sales History</span>
            </button>
        </li>
        <li>
            <button class="nav-btn" onclick="showView('settings', this)">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </button>
        </li>
    </ul>

    <div class="user-profile">
        <div class="user-info">
            <h4 id="user-display-name">Loading...</h4>
            <small id="user-role-display">Staff</small>
        </div>
        <button class="logout-btn" onclick="logout()" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </div>
</nav>

<div class="main-content">

    <section id="pos-view" class="view-section active">
        <div class="top-header">
            <button class="mobile-profile-btn" onclick="openModal('profile-modal')">
                <span class="user-initial">U</span>
            </button>
            <div class="page-title">Register</div>
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" placeholder="Search items">
            </div>
            <button onclick="openAddModal()" class="btn-primary">
                <i class="fas fa-plus"></i> Add Item
            </button>
        </div>

        <div class="pos-container">
            <div class="product-area">
                <div class="categories" id="category-list">
                    </div>
                <div class="product-grid" id="product-grid">
                    <div class="loading">Loading Products...</div>
                </div>
                <button class="mobile-cart-fab" onclick="toggleMobileCart()">
                    
                    <i class="fas fa-shopping-basket"></i>
                    <span id="mobile-cart-count" class="cart-badge" style="display:none;">0</span>
                </button>
            </div>

            <div class="cart-area">
                <div class="cart-header">
                    <button class="mobile-close-cart" onclick="toggleMobileCart()"><i class="fas fa-arrow-left"></i></button>
                    <h3>Current Order</h3>
                    <div style="display:flex; gap:5px;">
                        <button class="btn-primary" style="background:#f59e0b; padding:6px 10px;" onclick="parkCurrentOrder()" title="Park Order"><i class="fas fa-pause"></i></button>
                        <button class="btn-primary" style="background:#3b82f6; padding:6px 10px;" onclick="openRecallModal()" title="Recall Order"><i class="fas fa-list-ul"></i></button>
                        <button class="btn-danger" id="clear-cart" title="Clear Cart"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                
                <div class="cart-items" id="cart-items">
                    </div>
                
                <div class="cart-footer">
                    <div class="summary-row">
                        <span>Subtotal</span> 
                        <span id="subtotal">PHP 0.00</span>
                    </div>
                    <div class="summary-row" id="vat-row" style="display:none; color:#6b7280;">
                        <span>VAT (<span id="vat-rate-disp">12</span>%)</span> 
                        <span id="vat-amount">PHP 0.00</span>
                    </div>
                    <div class="summary-row" id="disc-row" style="display:none; color:#10b981;">
                        <span>Discount</span> 
                        <span id="disc-amount">-PHP 0.00</span>
                    </div>
                    
                    <div class="total">
                        <span>Total</span> 
                        <span id="total">PHP 0.00</span>
                    </div>
                    
                    <button class="checkout-btn" onclick="processCheckout()">Pay Now</button>
                </div>
            </div>
        </div>
    </section>

    <section id="sales-view" class="view-section">
        <div class="top-header">
            <div class="page-title">Sales History</div>
            
            <div class="search-wrapper" style="margin-right: 10px; margin-left: auto;">
                <i class="fas fa-search"></i>
                <input type="text" id="sales-search-input" placeholder="Search Transaction #...">
            </div>

            <select id="sales-date-filter" onchange="filterSalesByDate()" style="padding: 8px 15px; border-radius: 20px; border: 1px solid #e5e7eb; background: white; margin-right: 10px; cursor: pointer; outline: none; font-family: inherit; font-size: 0.9rem;">
                <option value="all">All Time</option>
                <option value="today">Today</option>
            </select>

            <select id="sales-cashier-filter" onchange="filterSalesByDate()" style="display:none; padding: 8px 15px; border-radius: 20px; border: 1px solid #e5e7eb; background: white; margin-right: 10px; cursor: pointer; outline: none; font-family: inherit; font-size: 0.9rem;">
                <option value="all">All Cashiers</option>
            </select>

            <button class="btn-primary" onclick="loadSalesHistory()">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
        
        <div class="sales-view-content">
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="stat-info">
                        <h3><span id="report-total-sales">PHP 0.00</span></h3>
                        <p>Total Revenue <span id="revenue-label" style="font-size: 0.7rem; color: #888;">(All Time)</span></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                    <div class="stat-info">
                        <h3><span id="report-total-orders">0</span></h3>
                        <p>Transactions</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background:#dcfce7; color:#16a34a;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <h3><span id="report-net-sales">PHP 0.00</span></h3>
                        <p>Net Sales <small style="color:#888; font-size:0.7rem;">(Excl. VAT)</small></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fee2e2; color:#ef4444;">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="stat-info">
                        <h3><span id="report-total-vat">PHP 0.00</span></h3>
                        <p>Total VAT</p>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Transaction #</th>
                            <th>Time</th>
                            <th>Type / Customer</th>
                            <th>Items</th>
                            <th>Discount</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="sales-table-body">
                        </tbody>
                </table>
            </div>
        </div>
    </section>

<section id="settings-view" class="view-section">
        <div class="top-header">
            <div class="page-title">Settings</div>
        </div>
        
        <div class="settings-view-content">
            
            <div class="settings-grid-row">
                
                <div class="settings-card">
                    <div class="settings-header">
                        <h3>Receipt Branding</h3>
                    </div>
                    <div class="form-group">
                        <label>Brand Name:</label>
                        <input type="text" id="setting-store-name" placeholder="e.g. My Coffee Shop">
                    </div>
                    <div class="form-group">
                        <label>Location:</label>
                        <input type="text" id="setting-store-address" placeholder="e.g. City, Country">
                    </div>
                    <div class="settings-footer">
                        <button class="btn-primary" onclick="saveStoreSettings()">
                            <i class="fas fa-save"></i> Update Branding
                        </button>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-header">
                        <h3>Financial Settings</h3>
                    </div>
                    <div class="form-group">
                        <label>VAT Rate (%)</label>
                        <input type="number" id="setting-vat-rate" value="12">
                        <small style="color:#6b7280; display:block; margin-top:5px;">Calculated on sales.</small>
                    </div>
                    <div class="settings-footer">
                        <button class="btn-primary" style="background:#4b5563;" onclick="saveTaxSettings()">
                            <i class="fas fa-percentage"></i> Update Tax
                        </button>
                    </div>
                </div>

                <div class="settings-card" style="grid-column: 1 / -1; margin-top: 20px;">
                    <div class="settings-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>Custom Discounts</h3>
                        <button class="btn-primary" onclick="requirePassword(() => openModal('add-discount-modal'))">
                            <i class="fas fa-plus"></i> Add Discount
                        </button>
                    </div>
                    <div class="table-container">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Discount Name</th>
                                    <th>Type & Value</th>
                                    <th>Min Spend</th>
                                    <th>Max Cap</th>
                                    <th>VAT Void</th>
                                    <th>Req. ID</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="discounts-table-body">
                                <tr><td colspan="6" style="text-align:center;">Loading discounts...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div> <div class="settings-card" style="margin-top: 20px; width: 100%;">
                <div class="settings-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Cashier Accounts</h3>
                    <button class="btn-primary" onclick="requirePassword(() => openModal('add-cashier-modal'))">
                        <i class="fas fa-plus"></i> Add Cashier
                    </button>
                </div>
                <div class="table-container">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Online Status</th>
                                <th>Account Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cashier-list-body">
                            <tr><td colspan="6" style="text-align:center;">Loading cashiers...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>

</div>

<div id="add-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Inventory Item</h2>
            <button class="close-btn" onclick="closeModal('add-modal')">&times;</button>
        </div>
        
        <form id="add-product-form">
            <input type="hidden" id="edit-product-id">
            
            <div class="image-upload-wrapper">
                <label class="image-upload-box" for="new-image">
                    <img id="image-preview" src="" style="display:none;" alt="Preview">
                    <div class="upload-placeholder" id="upload-placeholder">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Upload Image</span>
                    </div>
                    <input type="file" id="new-image" accept="image/*" style="display:none;" onchange="previewImage(event)">
                </label>
            </div>

            <div class="form-group">
                <label>Product Name</label>
                <input type="text" id="new-name" required placeholder="e.g. Iced Coffee">
            </div>

            <div class="form-group">
                <label>Product Code / Barcode (Optional)</label>
                <input type="text" id="new-code" placeholder="Scan or Type Code">
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <input type="text" id="new-category" list="cat-suggestions" required placeholder="Select or Type Category">
                <datalist id="cat-suggestions"></datalist>
            </div>
            
            <div style="background:#f9fafb; padding:10px; border-radius:8px; margin-bottom:15px; display:flex; align-items:center; gap:10px; border:1px solid #e5e7eb;">
                <input type="checkbox" id="has-variants-toggle" onchange="toggleVariantInputs()" style="width:18px; height:18px; cursor:pointer;">
                <label for="has-variants-toggle" style="margin:0; cursor:pointer; font-weight:600;">This product has variants</label>
            </div>

            <div id="simple-inputs" style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label>Price</label>
                    <input type="number" id="new-price" step="0.01" placeholder="0.00">
                </div>
            </div>

            <div id="variant-inputs" style="display:none;">
                <div id="variant-list" style="max-height:150px; overflow-y:auto; margin-bottom:10px; padding-right:5px;"></div>
                <button type="button" class="btn-primary" style="width:100%; background:#e0e7ff; color:var(--primary);" onclick="addVariantRow()">
                    <i class="fas fa-plus"></i> Add Variant
                </button>
            </div>

            <button type="submit" class="btn-primary" style="width:100%; margin-top:20px;">Save Item</button>
        </form>
    </div>
</div>

<div id="select-variant-modal" class="modal">
    <div class="modal-content" style="width:400px;">
        <div class="modal-header">
            <h2 id="variant-modal-title">Select Option</h2>
            <button class="close-btn" onclick="closeModal('select-variant-modal')">&times;</button>
        </div>
        <div id="variant-options-container" style="display:grid; gap:10px; padding-bottom:10px;"></div>
    </div>
</div>

<div id="payment-modal" class="modal">
    <div class="modal-content" style="width:650px;">
        <div class="modal-header">
            <h2>Checkout</h2>
            <button class="close-btn" onclick="closeModal('payment-modal')">&times;</button>
        </div>
        
        <div class="modal-body">
            
            <div style="display:flex; justify-content:space-between; margin-bottom:20px; align-items: flex-end;">
                <div class="form-group" style="flex:1; margin-right:20px; margin-bottom:0;">
                    <label>Service Type</label>
                    <select id="service-type-select" style="width:100%; padding:10px; border-radius:10px; border:2px solid #e5e7eb; font-family:inherit; font-size:0.95rem;">
                        </select>
                </div>
                <div style="text-align:right;">
                    <small style="color:var(--gray);">Total Amount</small>
                    <h1 id="pay-total" style="color:var(--primary); margin:0; font-size:2.2rem;">PHP 0.00</h1>
                </div>
            </div>

            <div style="margin-bottom: 15px; margin-top: 15px;">
                <button id="toggle-discount-btn" type="button" class="btn-primary" style="background:#f3f4f6; color:#374151; width:100%; border:1px dashed #cbd5e1; justify-content:center; padding: 12px;" onclick="toggleDiscountSection()">
                    <i class="fas fa-tags"></i> Add Discount
                </button>
            </div>

            <div id="discount-selection-area" style="display:none; background:#f9fafb; padding:15px; border-radius:10px; margin-bottom:15px; border:1px solid #e5e7eb;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <label style="font-weight:600; font-size:0.85rem; margin:0;">Select Discount</label>
                    <button type="button" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--gray); line-height: 1;" onclick="toggleDiscountSection()">&times;</button>
                </div>
                
                <div id="dynamic-discount-container" style="display:flex; gap:10px; flex-wrap:wrap;">
                    <small style="color:var(--gray);">Loading discounts...</small>
                </div>
            </div>

            <div id="discount-details-section" style="display:none; background:#eff6ff; padding:15px; border-radius:10px; margin-bottom:15px; border:1px solid #bfdbfe;">
                <h4 style="margin-bottom:10px; font-size:0.9rem; color:#1e40af;">Discount Requirements</h4>
                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <input type="text" id="discount-cust-name" placeholder="Customer Name">
                    </div>
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <input type="text" id="discount-id-number" placeholder="ID Number">
                    </div>
                </div>
            </div>

            <div id="checkout-breakdown" style="background:#f9fafb; padding:15px; border-radius:10px; margin-bottom:20px; font-size:0.9rem; border:1px dashed #cbd5e1;">
                <div style="display:flex; justify-content:space-between; margin-bottom:5px; color:var(--gray);">
                    <span>Subtotal:</span><span id="chk-subtotal">PHP 0.00</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:5px; color:var(--success);">
                    <span>Discount Applied: <small id="chk-disc-name"></small></span>
                    <span id="chk-discount">-PHP 0.00</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:0; color:var(--gray);">
                    <span>VAT <small id="chk-vat-status"></small>:</span>
                    <span id="chk-vat">PHP 0.00</span>
                </div>
            </div>

            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.85rem;">Payment Method</label>
            <div class="payment-methods">
                <button class="pay-btn active" onclick="selectPayment('cash')">
                    <i class="fas fa-money-bill-wave" style="color:#10b981;"></i>
                    <span>Cash</span>
                </button>
                <button class="pay-btn" onclick="selectPayment('GCash')">
                    <i class="fas fa-mobile-alt" style="color:#007dfe;"></i>
                    <span>GCash</span>
                </button>
                <button class="pay-btn" onclick="selectPayment('Maya')">
                    <i class="fas fa-wallet" style="color:#232531;"></i>
                    <span>Maya</span>
                </button>
                <button class="pay-btn" onclick="selectPayment('Card')">
                    <i class="fas fa-credit-card" style="color:#f59e0b;"></i>
                    <span>Card</span>
                </button>
            </div>

            <div id="cash-section" class="payment-input-area">
                <label>Amount Tendered</label>
                <input type="number" id="amount-tendered" placeholder="0.00" oninput="calculateChange()">
                <div style="margin-top:10px; font-weight:600; color:var(--text);">
                    Change: <span id="change-amount">PHP 0.00</span>
                </div>
            </div>
            
            <div id="ref-section" class="payment-input-area" style="display:none;">
                <label>Reference Number (External)</label>
                <input type="text" id="ref-number" placeholder="Enter Payment Ref #">
            </div>

            <button class="checkout-btn" onclick="confirmPayment()">
                COMPLETE PAYMENT
            </button>
        </div>
    </div>
</div>

<div id="receipt-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Payment Successful!</h2>
        </div>
        <div id="receipt-content" style="padding:20px; background:#f9fafb; border-radius:10px; border:1px dashed #e5e7eb; margin-bottom:20px;"></div>
        <div style="display:flex; gap:10px;">
            <button class="btn-primary" style="flex:1;" onclick="printReceipt()">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            <button class="btn-primary" style="flex:1; background:#e5e7eb; color:#374151;" onclick="closeModal('receipt-modal')">
                New Order
            </button>
        </div>
    </div>
</div>

<div id="security-modal" class="modal" style="z-index:9000;">
    <div class="modal-content" style="width:350px; text-align:center;">
        <div class="modal-header">
            <h2>Authorize</h2>
            <button class="close-btn" onclick="closeModal('security-modal')">&times;</button>
        </div>
        <div style="padding:20px;">
            <div style="margin-bottom:20px;">
                <i class="fas fa-lock" style="font-size:3rem; color:var(--primary);"></i>
            </div>
            <p style="margin-bottom:15px;">Enter admin password to proceed</p>
            <input type="password" id="security-password" class="password-input" placeholder="••••" style="text-align:center; letter-spacing:5px; font-size:1.5rem; padding:10px; width:80%; border-radius:10px; border:2px solid #e5e7eb;">
            <button class="btn-primary" style="width:100%; margin-top:20px;" onclick="verifyAndExecute()">Verify</button>
        </div>
    </div>
</div>

<div id="confirm-modal" class="modal" style="z-index:9000;">
    <div class="modal-content" style="width:350px; text-align:center;">
        <div class="modal-header">
            <h2>Confirmation</h2>
            <button class="close-btn" onclick="closeModal('confirm-modal')">&times;</button>
        </div>
        <p id="confirm-message" style="margin:20px 0; font-size:1.1rem; color:var(--text);">Are you sure?</p>
        <div style="display:flex; gap:10px; justify-content:center;">
            <button id="confirm-yes-btn" class="btn-danger" style="width:100px;">Yes</button>
            <button class="btn-primary" style="background:#e5e7eb; color:#374151; width:100px;" onclick="closeModal('confirm-modal')">No</button>
        </div>
    </div>
</div>

<div id="add-cashier-modal" class="modal">
    <div class="modal-content" style="width: 400px;">
        <div class="modal-header"><h2>Add Cashier</h2><button class="close-btn" onclick="closeModal('add-cashier-modal')">&times;</button></div>
        <div class="form-group"><label>First Name</label><input type="text" id="cashier-fname" placeholder="John"></div>
        <div class="form-group"><label>Last Name</label><input type="text" id="cashier-lname" placeholder="Doe"></div>
        <div class="form-group"><label>Username</label><input type="text" id="cashier-uname" placeholder="cashier1"></div>
        <div class="form-group"><label>Password</label><input type="text" id="cashier-pass" placeholder="SecretPass123"></div>
        <button class="btn-primary" style="width:100%; margin-top:15px;" onclick="saveNewCashier()">Create Account</button>
    </div>
</div>

<div id="edit-cashier-modal" class="modal">
    <div class="modal-content" style="width: 400px;">
        <div class="modal-header"><h2>Edit Cashier</h2><button class="close-btn" onclick="closeModal('edit-cashier-modal')">&times;</button></div>
        <input type="hidden" id="edit-cashier-id">
        <div class="form-group"><label>First Name</label><input type="text" id="edit-cashier-fname"></div>
        <div class="form-group"><label>Last Name</label><input type="text" id="edit-cashier-lname"></div>
        <button class="btn-primary" style="width:100%; margin-top:15px;" onclick="updateCashierInfo()">Update Name</button>
    </div>
</div>

<div id="parked-modal" class="modal">
    <div class="modal-content" style="width:500px;">
        <div class="modal-header">
            <h2>Parked Orders</h2>
            <button class="close-btn" onclick="closeModal('parked-modal')">&times;</button>
        </div>
        <div id="parked-list" style="display:flex; flex-direction:column; gap:10px;">
            </div>
    </div>
</div>

<div id="add-discount-modal" class="modal">
    <div class="modal-content" style="width: 450px;">
        <div class="modal-header"><h2>Create Discount</h2><button class="close-btn" onclick="closeModal('add-discount-modal')">&times;</button></div>
        
        <div class="form-group"><label>Discount Name (e.g., Summer Promo)</label><input type="text" id="disc-name" placeholder="Name"></div>
        
        <div style="display:flex; gap:10px;">
            <div class="form-group" style="flex:1;"><label>Type</label><select id="disc-type"><option value="percentage">Percentage (%)</option><option value="fixed">Fixed Amount (PHP)</option></select></div>
            <div class="form-group" style="flex:1;"><label>Value</label><input type="number" id="disc-value" placeholder="20"></div>
        </div>

        <div style="display:flex; gap:10px;">
            <div class="form-group" style="flex:1;"><label>Min Spend (PHP)</label><input type="number" id="disc-min" value="0"></div>
            <div class="form-group" style="flex:1;"><label>Cap Amount (PHP)</label><input type="number" id="disc-cap" value="0"><small style="color:gray">0 = No limit</small></div>
        </div>

        <div style="margin-top:10px; display:flex; gap:15px; align-items:center;">
            <label style="display:flex; align-items:center; gap:5px; cursor:pointer;"><input type="checkbox" id="disc-vat"> Voids VAT</label>
            <label style="display:flex; align-items:center; gap:5px; cursor:pointer;"><input type="checkbox" id="disc-id"> Requires Customer ID</label>
        </div>

        <button class="btn-primary" style="width:100%; margin-top:20px;" onclick="saveNewDiscount()">Save Discount</button>
    </div>
</div>

<div id="edit-discount-modal" class="modal">
    <div class="modal-content" style="width: 450px;">
        <div class="modal-header"><h2>Edit Discount</h2><button class="close-btn" onclick="closeModal('edit-discount-modal')">&times;</button></div>
        
        <input type="hidden" id="edit-disc-id">
        <div class="form-group"><label>Discount Name</label><input type="text" id="edit-disc-name"></div>
        
        <div style="display:flex; gap:10px;">
            <div class="form-group" style="flex:1;"><label>Type</label><select id="edit-disc-type"><option value="percentage">Percentage (%)</option><option value="fixed">Fixed Amount (PHP)</option></select></div>
            <div class="form-group" style="flex:1;"><label>Value</label><input type="number" id="edit-disc-value"></div>
        </div>

        <div style="display:flex; gap:10px;">
            <div class="form-group" style="flex:1;"><label>Min Spend (PHP)</label><input type="number" id="edit-disc-min"></div>
            <div class="form-group" style="flex:1;"><label>Cap Amount (PHP)</label><input type="number" id="edit-disc-cap"><small style="color:gray">0 = No limit</small></div>
        </div>

        <div style="margin-top:10px; display:flex; gap:15px; align-items:center;">
            <label style="display:flex; align-items:center; gap:5px; cursor:pointer;"><input type="checkbox" id="edit-disc-vat"> Voids VAT</label>
            <label style="display:flex; align-items:center; gap:5px; cursor:pointer;"><input type="checkbox" id="edit-disc-id-req"> Requires Customer ID</label>
        </div>

        <button class="btn-primary" style="width:100%; margin-top:20px;" onclick="updateDiscount()">Save Changes</button>
    </div>
</div>

<div id="profile-modal" class="modal" style="z-index: 99999;">
    <div class="modal-content" style="width: 320px; text-align: center; padding: 25px;">
        <div class="modal-header" style="justify-content: flex-end; margin-bottom: 0;">
            <button class="close-btn" onclick="closeModal('profile-modal')">&times;</button>
        </div>
        
        <div class="profile-avatar">
            <span id="modal-user-initial">U</span>
        </div>
        
        <h3 id="modal-user-name" style="margin: 15px 0 5px; color: var(--text);">Loading...</h3>
        <p id="modal-user-role" style="color: var(--gray); font-size: 0.9rem; text-transform: uppercase; margin-bottom: 25px; font-weight: 600;">Staff</p>
        
        <button class="btn-danger" style="width: 100%; padding: 14px; font-size: 1.05rem; font-weight: bold; border-radius: 12px;" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i> Log Out
        </button>
    </div>
</div>

<script src="app.js"></script>
</body>
</html>