<?php 
session_start();
require_once __DIR__ . '/security_headers.php'; 

// Security Gate: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/live_warning.css">
    <link rel="icon" type="image/png" href="pebble.png">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#800000">
    <link rel="apple-touch-icon" href="pebble.png">
</head>
<body>

<div id="notification-container" class="notification-container"></div>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button id="sidebar-toggle" class="sidebar-toggle-btn">
            <img src="icons/menu.png" class="ui-icon" alt="menu">
        </button>
        <div class="brand" id="store-brand">
            Pebble
        </div>
    </div>
    
    <ul class="nav-links">
        <li>
            <button class="nav-btn active" id="nav-pos-btn" data-view="pos">
                <img src="icons/register.png" class="ui-icon" alt="register"> <span>Register</span>
            </button>
        </li>
        <li>
            <button class="nav-btn" id="nav-sales-btn" data-view="sales">
                <img src="icons/sales-history.png" class="ui-icon" alt="sales history"> <span>Sales History</span>
            </button>
        </li>
        <li>
            <button class="nav-btn" id="nav-settings-btn" data-view="settings">
                <img src="icons/settings.png" class="ui-icon" alt="settings"> <span>Settings</span>
            </button>
        </li>
    </ul>

    <div class="user-profile">
        <div class="user-info">
            <h4 id="user-display-name">Loading...</h4>
            <small id="user-role-display">Staff</small>
        </div>
        <button id="logout-btn">
            <img src="icons/logout.png" class="ui-icon" alt="logout">
        </button>
    </div>
</nav>

<div class="main-content">

    <section id="pos-view" class="view-section active">
        <div class="top-header">
            <button class="mobile-profile-btn" data-action="open-modal" data-modal="profile-modal">
                <span class="user-initial">U</span>
            </button>
            <div class="page-title">Register</div>
            <div class="search-wrapper">
                <img src="icons/search.png" class="ui-icon" alt="search">
                <input type="text" id="search-input" placeholder="Search items">
            </div>
            <button id="open-add-item-btn" class="btn-primary">
                <img src="icons/add-item.png" class="ui-icon" alt="add item"> Add Item
            </button>
        </div>

        <div class="pos-container">
            <div class="product-area">
                <div class="type-toggle-container">
                    <button class="type-toggle-btn active" data-action="switch-type" data-type="product" id="btn-type-product">Products</button>
                    <button class="type-toggle-btn" data-action="switch-type" data-type="service" id="btn-type-service">Services</button>
                </div>

                <div class="categories" id="category-list"></div>

                <div class="product-grid" id="product-grid">
                    <div class="loading">Loading Products...</div>
                </div>
                <button class="mobile-cart-fab" data-action="toggle-mobile-cart">
                    <img src="icons/cart.png" class="ui-icon" alt="cart">
                    <span id="mobile-cart-count" class="cart-badge hidden">0</span>
                </button>
            </div>

            <div class="cart-area">
                <div class="cart-header">
                    <button class="mobile-close-cart" data-action="toggle-mobile-cart"><img src="icons/home.png" class="ui-icon" alt="back"></button>
                    <h3>Current Order</h3>
                    <div class="flex-row gap-5">
                        <button class="btn-primary bg-warning btn-pad-compact" data-action="park-order" title="Park Order"><img src="icons/park-order.png" class="ui-icon" alt="park order"></button>
                        <button class="btn-primary bg-info btn-pad-compact" data-action="open-recall" title="Recall Order"><img src="icons/park-list.png" class="ui-icon" alt="recall order"></button>
                        <button class="btn-danger" id="clear-cart" title="Clear Cart"><img src="icons/delete-item.png" class="ui-icon" alt="clear cart"></button>
                    </div>
                </div>
                
                <div class="cart-items" id="cart-items">
                    </div>
                
                <div class="cart-footer">
                    <div class="summary-row">
                        <span>Subtotal</span> 
                        <span id="subtotal">PHP 0.00</span>
                    </div>
                    <div class="summary-row hidden text-gray" id="vat-row">
                        <span>VAT (<span id="vat-rate-disp">12</span>%)</span> 
                        <span id="vat-amount">PHP 0.00</span>
                    </div>
                    <div class="summary-row hidden text-success" id="disc-row">
                        <span>Discount</span> 
                        <span id="disc-amount">-PHP 0.00</span>
                    </div>
                    
                    <div class="total">
                        <span>Total</span> 
                        <span id="total">PHP 0.00</span>
                    </div>
                    
                    <button id="checkout-btn-el" class="checkout-btn">Pay Now</button>
                    <button id="cancel-edit-btn" class="btn-danger hidden w-100 mt-10 p-12 rounded-12 fw-700">Cancel Edit</button>
                </div>
            </div>
        </div>
    </section>

    <section id="sales-view" class="view-section">
        <div class="top-header">
            <div class="page-title">Sales History</div>
            
            <div class="search-wrapper search-wrapper-sales">
                <img src="icons/search.png" class="ui-icon" alt="search">
                <input type="text" id="sales-search-input" placeholder="Search Transaction #...">
            </div>

            <select id="sales-date-filter" class="select-pill">
                <option value="today" selected>Today</option>
                <option value="all">All Time</option>
            </select>

            <select id="sales-cashier-filter" class="select-pill hidden">
                <option value="all">All Cashiers</option>
            </select>

            <button class="btn-primary" id="refresh-sales-btn">
                <img src="icons/refresh.png" class="ui-icon" alt="refresh"> Refresh
            </button>
        </div>
        
        <div class="sales-view-content">
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon"><img src="icons/revenue.png" class="ui-icon" alt="revenue"></div>
                    <div class="stat-info">
                        <h3><span id="report-total-sales">PHP 0.00</span></h3>
                        <p>Total Revenue <span id="revenue-label" class="fs-07 text-muted">(All Time)</span></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><img src="icons/sales-history.png" class="ui-icon" alt="transactions"></div>
                    <div class="stat-info">
                        <h3><span id="report-total-orders">0</span></h3>
                        <p>Transactions</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-green-soft text-green-700">
                        <img src="icons/cash.png" class="ui-icon" alt="net sales">
                    </div>
                    <div class="stat-info">
                        <h3><span id="report-net-sales">PHP 0.00</span></h3>
                        <p>Net Sales <small class="text-muted fs-07">(Excl. VAT)</small></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-red-soft text-danger">
                        <img src="icons/vat.png" class="ui-icon" alt="vat">
                    </div>
                    <div class="stat-info">
                        <h3><span id="report-total-vat">PHP 0.00</span></h3>
                        <p>Total VAT</p>
                    </div>
                </div>
            </div>

            <div class="sales-tabs">
                <button class="sales-tab-btn active" id="tab-completed" data-action="switch-sales-tab" data-tab="completed">
                    <img src="icons/add-item.png" class="ui-icon" alt="completed"> Completed
                </button>
                <button class="sales-tab-btn" id="tab-hold" data-action="switch-sales-tab" data-tab="hold">
                    <img src="icons/hold.png" class="ui-icon" alt="on hold"> On Hold
                </button>
            </div>

            <div class="table-container">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Transaction #</th>
                            <th>Time</th>
                            <th>Type / Customer</th>
                            <th>Cashier</th>
                            <th>Items</th>
                            <th>Discount</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                        <tbody id="sales-table-body">
                        </tbody>
                </table>
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
                        <button class="btn-primary" id="save-store-settings-btn">
                            <img src="icons/edit-hold.png" class="ui-icon" alt="update branding"> Update Branding
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
                        <small class="note-small">Calculated on sales.</small>
                    </div>
                    <div class="settings-footer">
                        <button class="btn-primary btn-tax-muted" id="save-tax-settings-btn">
                            <img src="icons/edit-hold.png" class="ui-icon" alt="update tax"> Update Tax
                        </button>
                    </div>
                </div>

                <div class="settings-card settings-card-wide">
                    <div class="settings-header settings-header-row">
                        <h3>Custom Discounts</h3>
                        <button class="btn-primary" data-action="password-open-modal" data-modal="add-discount-modal">
                            <img src="icons/add-item.png" class="ui-icon" alt="add discount"> Add Discount
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
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="discounts-table-body">
                                <tr><td colspan="6" class="text-center">Loading discounts...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div><br>

            </div> <div class="settings-card settings-card-block">
                <div class="settings-header settings-header-row">
                    <h3>Cashier Accounts</h3>
                    <button class="btn-primary" data-action="password-open-modal" data-modal="add-cashier-modal">
                        <img src="icons/add-item.png" class="ui-icon" alt="add cashier"> Add Cashier
                    </button>
                </div>
                <div class="table-container">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Online Status</th>
                                <th>Account Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cashier-list-body">
                            <tr><td colspan="6" class="text-center">Loading cashiers...</td></tr>
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
            <button class="close-btn" data-action="close-modal" data-modal="add-modal">&times;</button>
        </div>
        
        <form id="add-product-form">
            <input type="hidden" id="edit-product-id">
            
            <div class="image-upload-wrapper">
                <label class="image-upload-box" for="new-image">
                    <img id="image-preview" src="" class="hidden" alt="Preview">
                    <div class="upload-placeholder" id="upload-placeholder">
                        <img src="icons/upload-image.png" class="ui-icon" alt="upload image">
                        <span></span>
                    </div>
                    <input type="file" id="new-image" accept="image/*" class="hidden">
                </label>
            </div>

            <div class="form-group">
                <label>Product Name</label>
                <input type="text" id="new-name" required placeholder="Enter product or service name">
            </div>

            <div class="form-group">
                <label>Product Code / Barcode (Optional)</label>
                <input type="text" id="new-code" placeholder="Scan or Type Code">
            </div>

            <div class="form-group">
                <label>Item Type</label>
                <select id="new-item-type" class="w-100 p-10 rounded-10 border">
                    <option value="product">Product</option>
                    <option value="service">Service</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <input type="text" id="new-category" list="cat-suggestions" required placeholder="Select or Type Category">
                <datalist id="cat-suggestions"></datalist>
            </div>
            
            <div class="variant-toggle-box">
                <input type="checkbox" id="has-variants-toggle" class="variant-toggle-checkbox">
                <label for="has-variants-toggle" class="variant-toggle-label">This product has variants</label>
            </div>

            <div id="simple-inputs" class="flex-row gap-10">
                <div class="form-group flex-1">
                    <label>Price</label>
                    <input type="number" id="new-price" step="0.01" placeholder="0.00">
                </div>
            </div>

            <div id="variant-inputs" class="hidden">
                <div id="variant-list" class="variant-list-scroll"></div>
                <button type="button" class="btn-primary w-100 bg-blue-soft text-primary" id="add-variant-row-btn">
                    <img src="icons/add-item.png" class="ui-icon" alt="add variant"> Add Variant
                </button>
            </div>

            <button type="submit" class="btn-primary w-100 mt-20">Save Item</button>
        </form>
    </div>
</div>

<div id="select-variant-modal" class="modal">
    <div class="modal-content modal-w-400">
        <div class="modal-header">
            <h2 id="variant-modal-title">Select Option</h2>
            <button class="close-btn" data-action="close-modal" data-modal="select-variant-modal">&times;</button>
        </div>
        <div id="variant-options-container" class="d-grid gap-10 pb-10"></div>
    </div>
</div>

<div id="payment-modal" class="modal">
    <div class="modal-content modal-w-650">
        <div class="modal-header">
            <h2 id="payment-modal-title">Checkout</h2>
            <button class="close-btn" data-action="close-modal" data-modal="payment-modal">&times;</button>
        </div>
        
        <div class="modal-body">

            <div id="hold-payment-info" class="hidden mb-15"></div>
            
            <div id="edit-difference-display" class="hidden bg-yellow-soft border-yellow-300 p-15 rounded-10 mb-15 text-center">
            </div>

            <div id="edit-status-section" class="hidden mb-20">
                <label class="fw-600 fs-085">Update Action</label>
                <select id="edit-order-status" class="w-100 p-10 rounded-10 border bg-blue-soft text-primary fw-700">
                    <option value="hold">Save Changes & Keep on Hold</option>
                    <option value="completed">Save Changes & Fulfill Order</option>
                </select>
            </div>
            
            <div class="flex-row justify-between mb-20 items-end">
                <div class="form-group flex-1 mr-20 mb-0">
                    <label>Service Type</label>
                    <select id="service-type-select" class="w-100 p-10 rounded-10 border fs-09">
                        </select>
                </div>
                <div class="text-right">
                    <small class="text-gray">Total Amount</small>
                    <h1 id="pay-total" class="text-primary mb-0">PHP 0.00</h1>
                </div>
            </div>

            <div id="discount-selection-area" class="hidden bg-soft p-15 rounded-10 mb-15 border">
                <div class="flex-row justify-between items-center mb-10">
                    <label class="fw-600 fs-085 mb-0">Select Discount</label>
                    <button type="button" class="bg-transparent border-0 fs-15 cursor-pointer text-gray" data-action="toggle-discount-section">&times;</button>
                </div>
                
                <div id="dynamic-discount-container" class="d-flex gap-10 flex-wrap">
                    <small class="text-gray">Loading discounts...</small>
                </div>
            </div>

            <div id="discount-details-section" class="hidden bg-blue-lighter p-15 rounded-10 mb-15 border-blue-200">
                <h4 class="mb-10 fs-09">Discount Requirements</h4>
                <div class="form-group mb-0">
                    <input type="text" id="discount-id-number" placeholder="ID Number (Required for this discount)">
                </div>
            </div>

            <div class="form-group mb-15">
                <label class="fw-600 fs-085">Customer Name (Optional)</label>
                <input type="text" id="global-cust-name" placeholder="Enter customer name for the record" class="w-100 p-10 rounded-10 border fs-09">
            </div>

            <div class="mb-15 mt-15">
                <button id="toggle-discount-btn" type="button" class="btn-primary btn-discount-toggle" data-action="toggle-discount-section">
                    <img src="icons/settings.png" class="ui-icon" alt="add discount"> Add Discount
                </button>
            </div>

            <div id="discount-selection-area" class="hidden bg-soft p-15 rounded-10 mb-15 border">
                <div class="flex-row justify-between items-center mb-10">
                    <label class="fw-600 fs-085 mb-0">Select Discount</label>
                    <button type="button" class="bg-transparent border-0 fs-15 cursor-pointer text-gray" data-action="toggle-discount-section">&times;</button>
                </div>
                
                <div id="dynamic-discount-container" class="d-flex gap-10 flex-wrap">
                    <small class="text-gray">Loading discounts...</small>
                </div>
            </div>

            <div id="discount-details-section" class="hidden bg-blue-lighter p-15 rounded-10 mb-15 border-blue-200">
                <h4 class="mb-10 fs-09">Discount Requirements</h4>
                <div class="form-group mb-0">
                    <input type="text" id="discount-id-number" placeholder="ID Number (Required for this discount)">
                </div>
            </div>

            <div id="hold-payment-info" class="hidden mb-15"></div>
            
            <label class="d-block mb-8 fw-600 fs-085">Payment Method</label>

            <div class="payment-methods">
                <button class="pay-btn active" data-action="select-payment" data-method="cash">
                    <img src="icons/cash.png" class="ui-icon" alt="cash">
                    <span>Cash</span>
                </button>
                <button class="pay-btn" data-action="select-payment" data-method="GCash">
                    <img src="icons/gcash.png" class="ui-icon" alt="gcash">
                    <span>GCash</span>
                </button>
                <button class="pay-btn" data-action="select-payment" data-method="Maya">
                    <img src="icons/maya.png" class="ui-icon" alt="maya">
                    <span>Maya</span>
                </button>
                <button class="pay-btn" data-action="select-payment" data-method="Card">
                    <img src="icons/card.png" class="ui-icon" alt="card">
                    <span>Card</span>
                </button>
            </div>

            <div id="cash-section" class="payment-input-area">
                <label>Amount Tendered</label>
                <input type="number" id="amount-tendered" placeholder="0.00">
                <div class="mt-10 fw-600">
                    Change: <span id="change-amount">PHP 0.00</span>
                </div>
            </div>
            
            <div id="ref-section" class="payment-input-area hidden">
                <label>Reference Number (External)</label>
                <input type="text" id="ref-number" placeholder="Enter Payment Ref #">
            </div>

            <button class="checkout-btn" id="confirm-payment-btn">
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
        <div id="receipt-content" class="p-20 bg-soft rounded-10 border border-dashed mb-20"></div>
        <div class="flex-row gap-10">
            <button class="btn-primary flex-1" id="print-receipt-btn">
                <img src="icons/sales-history.png" class="ui-icon" alt="print receipt"> Print Receipt
            </button>
            <button class="btn-primary flex-1 btn-neutral" data-action="close-modal" data-modal="receipt-modal">
                New Order
            </button>
        </div>
    </div>
</div>

<div id="security-modal" class="modal z-9000">
    <div class="modal-content modal-w-350 text-center">
        <div class="modal-header">
            <h2>Authorize</h2>
            <button class="close-btn" data-action="close-modal" data-modal="security-modal">&times;</button>
        </div>
        <div class="p-20">
            <div class="mb-20">
                <img src="icons/restricted.png" class="ui-icon ui-icon-large" alt="authorize">
            </div>
            <p class="mb-15">Enter admin password to proceed</p>
            <input type="password" id="security-password" class="password-input text-center" placeholder="••••">
            <button class="btn-primary w-100 mt-20" id="verify-security-btn">Verify</button>
        </div>
    </div>
</div>

<div id="confirm-modal" class="modal z-9000">
    <div class="modal-content modal-w-350 text-center">
        <div class="modal-header">
            <h2>Confirmation</h2>
            <button class="close-btn" data-action="close-modal" data-modal="confirm-modal">&times;</button>
        </div>
        <p id="confirm-message" class="mt-20 mb-20">Are you sure?</p>
        <div class="flex-row gap-10 justify-center">
            <button id="confirm-yes-btn" class="btn-danger" >Yes</button>
            <button class="btn-primary btn-neutral" data-action="close-modal" data-modal="confirm-modal">No</button>
        </div>
    </div>
</div>

<div id="add-cashier-modal" class="modal">
    <div class="modal-content modal-w-400">
        <div class="modal-header"><h2>Add Cashier</h2><button class="close-btn" data-action="close-modal" data-modal="add-cashier-modal">&times;</button></div>
        <div class="form-group"><label>First Name</label><input type="text" id="cashier-fname" placeholder="John"></div>
        <div class="form-group"><label>Last Name</label><input type="text" id="cashier-lname" placeholder="Doe"></div>
        <div class="form-group"><label>Username</label><input type="text" id="cashier-uname" placeholder="cashier1"></div>
        <div class="form-group"><label>Password</label><input type="text" id="cashier-pass" placeholder="SecretPass123"></div>
        <button class="btn-primary w-100 mt-15" id="save-new-cashier-btn">Create Account</button>
    </div>
</div>

<div id="edit-cashier-modal" class="modal">
    <div class="modal-content modal-w-400">
        <div class="modal-header"><h2>Edit Cashier</h2><button class="close-btn" data-action="close-modal" data-modal="edit-cashier-modal">&times;</button></div>
        <input type="hidden" id="edit-cashier-id">
        <div class="form-group"><label>First Name</label><input type="text" id="edit-cashier-fname"></div>
        <div class="form-group"><label>Last Name</label><input type="text" id="edit-cashier-lname"></div>
        <button class="btn-primary w-100 mt-15" id="update-cashier-info-btn">Update Name</button>
    </div>
</div>

<div id="parked-modal" class="modal">
    <div class="modal-content modal-w-500">
        <div class="modal-header">
            <h2>Parked Orders</h2>
            <button class="close-btn" data-action="close-modal" data-modal="parked-modal">&times;</button>
        </div>
        <div id="parked-list" class="d-flex flex-col gap-10">
            </div>
    </div>
</div>

<div id="add-discount-modal" class="modal">
    <div class="modal-content modal-w-450">
        <div class="modal-header"><h2>Create Discount</h2><button class="close-btn" data-action="close-modal" data-modal="add-discount-modal">&times;</button></div>
        
        <div class="form-group"><label>Discount Name (e.g., Summer Promo)</label><input type="text" id="disc-name" placeholder="Name"></div>
        
        <div class="flex-row gap-10">
            <div class="form-group flex-1"><label>Type</label><select id="disc-type"><option value="percentage">Percentage (%)</option><option value="fixed">Fixed Amount (PHP)</option></select></div>
            <div class="form-group flex-1"><label>Value</label><input type="number" id="disc-value" placeholder="20"></div>
        </div>

        <div class="flex-row gap-10">
            <div class="form-group flex-1"><label>Min Spend (PHP)</label><input type="number" id="disc-min" value="0"></div>
            <div class="form-group flex-1"><label>Cap Amount (PHP)</label><input type="number" id="disc-cap" value="0"><small class="text-gray">0 = No limit</small></div>
        </div>

        <div class="mt-10 d-flex gap-15 items-center">
            <label class="d-flex items-center gap-5"><input type="checkbox" id="disc-vat"> Voids VAT</label>
            <label class="d-flex items-center gap-5"><input type="checkbox" id="disc-id"> Requires Customer ID</label>
        </div>

        <button class="btn-primary w-100 mt-20" id="save-new-discount-btn">Save Discount</button>
    </div>
</div>

<div id="edit-discount-modal" class="modal">
    <div class="modal-content modal-w-450">
        <div class="modal-header"><h2>Edit Discount</h2><button class="close-btn" data-action="close-modal" data-modal="edit-discount-modal">&times;</button></div>
        
        <input type="hidden" id="edit-disc-id">
        <div class="form-group"><label>Discount Name</label><input type="text" id="edit-disc-name"></div>
        
        <div class="flex-row gap-10">
            <div class="form-group flex-1"><label>Type</label><select id="edit-disc-type"><option value="percentage">Percentage (%)</option><option value="fixed">Fixed Amount (PHP)</option></select></div>
            <div class="form-group flex-1"><label>Value</label><input type="number" id="edit-disc-value"></div>
        </div>

        <div class="flex-row gap-10">
            <div class="form-group flex-1"><label>Min Spend (PHP)</label><input type="number" id="edit-disc-min"></div>
            <div class="form-group flex-1"><label>Cap Amount (PHP)</label><input type="number" id="edit-disc-cap"><small class="text-gray">0 = No limit</small></div>
        </div>

        <div class="mt-10 d-flex gap-15 items-center">
            <label class="d-flex items-center gap-5"><input type="checkbox" id="edit-disc-vat"> Voids VAT</label>
            <label class="d-flex items-center gap-5"><input type="checkbox" id="edit-disc-id-req"> Requires Customer ID</label>
        </div>

        <button class="btn-primary w-100 mt-20" id="update-discount-btn">Save Changes</button>
    </div>
</div>

<div id="profile-modal" class="modal z-99999">
    <div class="modal-content modal-w-320 text-center p-20">
        <div class="modal-header justify-end mb-0">
            <button class="close-btn" data-action="close-modal" data-modal="profile-modal">&times;</button>
        </div>
        
        <div class="profile-avatar">
            <span id="modal-user-initial">U</span>
        </div>
        
        <h3 id="modal-user-name" class="mt-15 mb-5">Loading...</h3>
        <p id="modal-user-role" class="text-gray fs-09 mb-25 fw-600">Staff</p>
        
        <button class="btn-danger w-100 p-14 fs-105 fw-700 rounded-12" id="profile-logout-btn">
            <img src="icons/logout.png" class="ui-icon" alt="logout"> Log Out
        </button>
    </div>
</div>

<div id="reset-password-modal" class="modal">
    <div class="modal-content modal-w-400">
        <div class="modal-header">
            <h2>Reset Password</h2>
            <button class="close-btn" data-action="close-modal" data-modal="reset-password-modal">&times;</button>
        </div>
        
        <input type="hidden" id="reset-cashier-id">
        
        <div class="form-group mt-15">
            <label>New Password</label>
            <div class="input-icon-wrapper">
                <img src="icons/password.png" class="ui-icon" alt="password">
                <input type="password" id="new-cashier-password" placeholder="Enter new password">
            </div>
        </div>
        
        <button class="btn-primary w-100 mt-20" id="confirm-reset-password-btn">Save New Password</button>
    </div>
</div>

<script src="app.js"></script>

<div id="live-warning-modal" class="live-modal-overlay">
    <div class="live-modal-box">
        <div class="live-modal-icon"><img src="icons/restricted.png" class="ui-icon" alt="warning"></div>
        <div class="live-modal-title">Important Notice</div>
        <div id="live-warning-text" class="live-modal-message"></div>
        <button id="live-warning-dismiss" class="live-btn-understand">I Understand</button>
    </div>
</div>
<script src="assets/js/live_status.js"></script>
</body>
</html>