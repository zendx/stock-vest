<?php

if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    $redirect = function_exists('wsi_login_url') ? wsi_login_url() : wp_login_url();
    wp_safe_redirect($redirect);
    exit;
}

// Get the plugin assets URL and directory
$PLUGIN_ASSETS = plugins_url('pages/assets/', dirname(dirname(__FILE__)) . '/stock-vest.php');
$PLUGIN_DIR = dirname(dirname(__FILE__));
$wsi = $PLUGIN_ASSETS;

// Cache-busting version for shared assets
$wsi_asset_ver = (defined('WSI_VER') ? WSI_VER : '1.0.0');
$wsi_asset_path = plugin_dir_path(__FILE__) . 'assets/js/app435e.js';
if (file_exists($wsi_asset_path)) {
    $wsi_asset_ver .= '-' . filemtime($wsi_asset_path);
}

// Authentication check moved to plugin template_redirect hook
// If user is here, they're already authenticated

?>
<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->

<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | User Withdrawal</title>
    <link rel="icon" type="image/png" href="<?php echo $PLUGIN_ASSETS; ?>img/favicon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&amp;family=Open+Sans:ital,wght@0,300..800;1,300..800&amp;display=swap" rel="stylesheet">
    <style>
        :root {
            --adminuiux-content-font: "Open Sans", sans-serif;
            --adminuiux-content-font-weight: 400;
            --adminuiux-title-font: "Lexend", sans-serif;
            --adminuiux-title-font-weight: 600;
        }
    </style>

    <script defer src="<?php echo plugin_dir_url(__FILE__) . 'assets/js/app435e.js?v=' . esc_attr($wsi_asset_ver); ?>"></script><link href="<?php echo plugin_dir_url(__FILE__) . 'assets/css/app435e.css?v=' . esc_attr($wsi_asset_ver); ?>" rel="stylesheet">
</head>

<body class="main-bg main-bg-opac main-bg-blur adminuiux-sidebar-fill-white adminuiux-sidebar-boxed  theme-blue roundedui" data-theme="theme-blue" data-sidebarfill="adminuiux-sidebar-fill-white" data-bs-spy="scroll" data-bs-target="#list-example" data-bs-smooth-scroll="true" tabindex="0">
    <!-- Pageloader -->
     <?php
    include_once plugin_dir_path(__FILE__) . 'assets/inc/header.php';
    ?>

                    <main class="adminuiux-content has-sidebar" onclick="contentClick()">
                        <!-- body content of pages -->

                        <!-- breadcrumb -->
                        <div class="container-fluid mt-4">
                            <div class="row gx-3 align-items-center">
                                <div class="col-12 col-sm">
                                    <nav aria-label="breadcrumb" class="mb-2">
                                        <ol class="breadcrumb mb-0">
                                            <li class="breadcrumb-item bi"><a href="#"><i class="bi bi-house-door me-1 fs-14"></i> Dashboard</a></li>
                                            <li class="breadcrumb-item active bi" aria-current="page">Withdrawal</li>
                                        </ol>
                                    </nav>
                                    <h5>Withdrawal</h5>
                                </div>
                                <div class="col-12 col-sm-auto text-end py-3 py-sm-0">

                                </div>
                            </div>
                        </div>

                        <!-- content -->
                        <div class="container mt-4" id="main-content" data-bs-spy="scroll" data-bs-target="#list-example" data-bs-smooth-scroll="true">

                            <!--div class="position-sticky z-index-5 mb-4 adminuiux-header" style="top: 5rem;">
                                <nav class="navbar rounded p-1">
                                    <ul id="list-example" class="nav nav-pills bg-none">
                                        <li class="nav-item"><a class="nav-link" href="#list-item-1">My Deposit</a></li>
                                        <li class="nav-item mx-1"><a class="nav-link" href="#list-item-2">100% Guaranteed</a></li>
                                        <li class="nav-item"><a class="nav-link" href="#list-item-3">Market Linked</a></li>
                                    </ul>
                                </nav>
                            </div-->

                            <div class="row" id="list-item-withdraw">
                                <div class="col-12">
                                    <div class="row">

                                        <div class="col-12 col-lg-8 mb-4">

                                            <div class="card adminuiux-card">
                                                <div class="card-header">
                                                    <h5>Withdraw</h5>
                                                    <p class="text-secondary">Withdraw funds to your crypto wallet</p>
                                                </div>

                                                <div class="card-body">

                                                    <form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" id="wsi-withdrawal-form">
                                                        <input type="hidden" name="action" value="wsi_submit_withdraw">
                                                        <?php wp_nonce_field('wsi_withdraw_nonce'); ?>

                                                        <div class="row mb-2">

                                                            <!-- Amount -->
                                                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                <div class="form-floating">
                                                                    <input name="amount" type="number" step="0.01" class="form-control" id="withdraw_amount" placeholder="Amount" required>
                                                                    <label for="withdraw_amount">Amount ($)</label>
                                                                </div>
                                                            </div>

                                                            <!-- Crypto Type -->
                                                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                <div class="form-floating">
                                                                    <select name="crypto_type" class="form-select" id="withdraw_crypto_type" required>
                                                                        <option value="">Select Network</option>
                                                                        <option value="BTC">Bitcoin (BTC)</option>
                                                                        <option value="ETH">Ethereum (ETH)</option>
                                                                        <option value="USDT-TRC20">USDT (TRC20)</option>
                                                                        <option value="USDT-ERC20">USDT (ERC20)</option>
                                                                        <option value="BNB">BNB</option>
                                                                        <option value="TRX">TRON (TRX)</option>
                                                                    </select>
                                                                    <label for="withdraw_crypto_type">Crypto Network</label>
                                                                </div>
                                                            </div>

                                                            <!-- Wallet Address -->
                                                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                <div class="form-floating">
                                                                    <input type="text" name="account_details" class="form-control" id="withdraw_wallet" placeholder="Wallet Address" required>
                                                                    <label for="withdraw_wallet">Wallet Address</label>
                                                                </div>
                                                            </div>

                                                        </div>

                                                        <div class="row align-items-center">
                                                            <div class="col">
                                                                <p class="text-secondary small">Ensure your wallet address is correct before submitting</p>
                                                            </div>
                                                            <div class="col-auto">
                                                                <button class="btn btn-theme" type="submit">Request Withdrawal</button>
                                                            </div>
                                                        </div>

                                                    </form>

                                                </div>
                                            </div>

                                        </div>

                                        <!-- Right-side Offer Card (copied structure) -->
                                        <div class="col-12 col-lg-4 mb-4">
                                            <div class="card adminuiux-card position-relative overflow-hidden bg-theme-1 h-100">
                                                <div class="position-absolute top-0 start-0 h-100 w-100 z-index-0 coverimg opacity-50">
                                                    <img src="<?php echo $PLUGIN_ASSETS; ?>img/modern-ai-image/flamingo-4.jpg" alt="">
                                                </div>
                                                <div class="card-body z-index-1">
                                                    <div class="avatar avatar-60 rounded bg-white-opacity text-white mb-4">
                                                        <i class="bi bi-cash-coin h4"></i>
                                                    </div>

                                                    <h2>Crypto Withdrawals</h2>
                                                    <h4 class="fw-medium">Fast & secure blockchain withdrawals</h4>

                                                    <p class="mb-4">Your request will be processed instantly for supported networks.</p>

                                                    <button class="btn btn-light my-1">View Withdrawal History</button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </main>

            </div>

            <!-- page footer -->
            <?php
            include_once plugin_dir_path(__FILE__) . 'assets/inc/footer.php';
            ?>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('wsi-withdrawal-form');
                if (!form) return;
                
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    
                    fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(text => {
                        try {
                            const data = JSON.parse(text);
                            if (data.success) {
                                alert(data.data.message || 'Withdrawal request submitted successfully');
                                window.location.href = '<?php echo home_url('/wsi/dashboard/'); ?>';
                            } else {
                                alert('Error: ' + (data.data.message || 'Failed to process request'));
                            }
                        } catch(e) {
                            console.error('JSON parse error:', e);
                            console.error('Response text:', text);
                            alert('Error: Failed to process request');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert('Error: ' + error.message);
                    });
                });
            });
            </script>
                    </body>

</html>
