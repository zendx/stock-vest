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
$balance_snapshot = wsi_get_withdrawal_balances(get_current_user_id());
$total_assets = $balance_snapshot['total_assets'];
$unlocked_assets = $balance_snapshot['total_assets_unlocked_amount'];
$available_balance = $balance_snapshot['available_balance'];
$total_assets_locked = $balance_snapshot['total_assets_locked'];
$selected_source = !$total_assets_locked && ($_GET['withdrawal_source'] ?? '') === 'total_assets' ? 'total_assets' : 'available_balance';

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
    <link rel="stylesheet" href="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/css/ui-polish.css?v=' . filemtime(__DIR__ . '/assets/css/ui-polish.css')); ?>">
</head>

<body class="wsi-ui main-bg main-bg-opac main-bg-blur adminuiux-sidebar-fill-white adminuiux-sidebar-boxed  theme-blue roundedui" data-theme="theme-blue" data-sidebarfill="adminuiux-sidebar-fill-white" data-bs-spy="scroll" data-bs-target="#list-example" data-bs-smooth-scroll="true" tabindex="0">
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
                                                    <p class="text-secondary">Withdraw funds to your bank account or crypto wallet</p>
                                                </div>

                                                <div class="card-body">

                                                    <div class="row mb-3">
                                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                                            <div class="border rounded p-3 h-100">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <span class="text-secondary small">Total Assets</span>
                                                                    <i id="wsi-withdraw-lock" class="bi <?php echo $total_assets_locked ? 'bi-lock-fill text-danger' : 'bi-unlock-fill text-success'; ?>" title="<?php echo esc_attr($total_assets_locked ? 'Locked during the investment period' : 'Available for withdrawal'); ?>" aria-label="<?php echo esc_attr($total_assets_locked ? 'Total assets locked' : 'Total assets unlocked'); ?>"></i>
                                                                </div>
                                                                <strong id="wsi-withdraw-assets" class="d-block fs-5 mt-1">$<?php echo number_format($total_assets, 2); ?></strong>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <div class="border rounded p-3 h-100">
                                                                <span class="text-secondary small">Available Balance</span>
                                                                <strong id="wsi-withdraw-available" class="d-block fs-5 mt-1">$<?php echo number_format($available_balance, 2); ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" id="wsi-withdrawal-form">
                                                        <input type="hidden" name="action" value="wsi_submit_withdraw">
                                                        <?php wp_nonce_field('wsi_withdraw_nonce'); ?>

                                                        <div class="row mb-2">

                                                            <!-- Withdrawal Source -->
                                                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                <div class="form-floating">
                                                                    <select name="withdrawal_source" class="form-select" id="withdrawal_source" required>
                                                                        <option value="available_balance" <?php selected($selected_source, 'available_balance'); ?>>Available Balance ($<?php echo number_format($available_balance, 2); ?>)</option>
                                                                        <option value="total_assets" <?php selected($selected_source, 'total_assets'); ?> <?php disabled($total_assets_locked, true); ?>>Total Assets ($<?php echo number_format($unlocked_assets, 2); ?> withdrawable)<?php echo $total_assets_locked ? ' - Locked' : ''; ?></option>
                                                                    </select>
                                                                    <label for="withdrawal_source">Withdraw From</label>
                                                                </div>
                                                                <p id="wsi-withdraw-lock-message" class="text-secondary small mt-1 mb-0">$<?php echo number_format($unlocked_assets, 2); ?> withdrawable ? $<?php echo number_format($balance_snapshot['total_assets_locked_amount'], 2); ?> locked</p>
                                                            </div>

                                                            <!-- Amount -->
                                                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                <div class="form-floating">
                                                                    <input name="amount" type="number" min="0.01" step="0.01" class="form-control" id="withdraw_amount" placeholder="Amount" required>
                                                                    <label for="withdraw_amount">Amount ($)</label>
                                                                </div>
                                                            </div>

                                                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                <div class="form-floating">
                                                                    <select name="payout_method" id="withdraw_payout_method" class="form-select" required>
                                                                        <option value="crypto">Crypto</option>
                                                                        <option value="bank">Bank Account</option>
                                                                    </select>
                                                                    <label for="withdraw_payout_method">Payout Method</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-md-6 mb-3" data-bank-field hidden>
                                                                <div class="form-floating">
                                                                    <input type="text" name="bank_name" id="withdraw_bank_name" class="form-control" placeholder="Bank Name" maxlength="150" disabled>
                                                                    <label for="withdraw_bank_name">Bank Name</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-md-6 mb-3" data-bank-field hidden>
                                                                <div class="form-floating">
                                                                    <input type="text" name="account_number" id="withdraw_account_number" class="form-control" placeholder="Account Number" maxlength="64" disabled>
                                                                    <label for="withdraw_account_number">Account Number</label>
                                                                </div>
                                                            </div>

                                                            <!-- Crypto Type -->
                                                            <div class="col-12 col-md-6 col-xl-4 mb-3" data-crypto-field>
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
                                                            <div class="col-12 col-md-6 col-xl-4 mb-3" data-crypto-field>
                                                                <div class="form-floating">
                                                                    <input type="text" name="account_details" class="form-control" id="withdraw_wallet" placeholder="Wallet Address" required>
                                                                    <label for="withdraw_wallet">Wallet Address</label>
                                                                </div>
                                                            </div>

                                                        </div>

                                                        <div class="row align-items-center">
                                                            <div class="col">
                                                                <p class="text-secondary small">Check your bank or wallet details carefully before submitting</p>
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

                                                    <h2>Withdraw Your Funds</h2>
                                                    <h4 class="fw-medium">Bank and crypto payout options</h4>

                                                    <p class="mb-4">Your request will be reviewed before payment is sent.</p>

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
                const source = document.getElementById('withdrawal_source');
                const amount = document.getElementById('withdraw_amount');
                const balances = {
                    available_balance: <?php echo wp_json_encode($available_balance); ?>,
                    total_assets: <?php echo wp_json_encode($unlocked_assets); ?>
                };

                const payout = document.getElementById('withdraw_payout_method');
                function updatePayoutFields() {
                    const bank = payout.value === 'bank';
                    form.querySelectorAll('[data-bank-field], [data-crypto-field]').forEach(group => {
                        const active = group.hasAttribute('data-bank-field') === bank;
                        group.hidden = !active;
                        group.querySelectorAll('input, select').forEach(input => {
                            input.disabled = !active;
                            input.required = active;
                        });
                    });
                }
                payout.addEventListener('change', updatePayoutFields);
                updatePayoutFields();
                const submitButton = form.querySelector('button[type="submit"]');
                let submitting = false;
                function refreshBalances() {
                    if (submitting) return;
                    fetch('<?php echo esc_url_raw(rest_url('wsi/v1/dashboard')); ?>', {
                        headers: {'X-WP-Nonce': '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>'},
                        credentials: 'same-origin', cache: 'no-store'
                    })
                    .then(response => response.ok ? response.json() : Promise.reject(response))
                    .then(data => {
                        balances.total_assets = Number(data.totalAssetsUnlockedAmount);
                        balances.available_balance = Number(data.available);
                        const locked = data.totalAssetsLocked !== false;
                        const assetsOption = source.querySelector('option[value="total_assets"]');
                        assetsOption.disabled = locked;
                        const money = value => '$' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        assetsOption.textContent = 'Total Assets (' + money(balances.total_assets) + ' withdrawable)' + (locked ? ' - Locked' : '');
                        source.querySelector('option[value="available_balance"]').textContent = 'Available Balance (' + money(balances.available_balance) + ')';
                        if (locked && source.value === 'total_assets') source.value = 'available_balance';
                        document.getElementById('wsi-withdraw-assets').textContent = money(Number(data.totalAssets));
                        document.getElementById('wsi-withdraw-available').textContent = money(balances.available_balance);
                        const icon = document.getElementById('wsi-withdraw-lock');
                        icon.className = 'bi ' + (locked ? 'bi-lock-fill text-danger' : 'bi-unlock-fill text-success');
                        icon.title = locked ? 'Total assets locked' : 'Total assets unlocked';
                        icon.setAttribute('aria-label', icon.title);
                        const message = document.getElementById('wsi-withdraw-lock-message');
                        message.className = 'text-secondary small mt-1 mb-0';
                        message.textContent = money(balances.total_assets) + ' withdrawable ? ' + money(Number(data.totalAssetsLockedAmount)) + ' locked';
                        updateAmountLimit();

                    })
                    .catch(error => console.warn('Withdrawal balance refresh failed', error));
                }
                refreshBalances();
                setInterval(function() { if (!document.hidden) refreshBalances(); }, 30000);
                document.addEventListener('visibilitychange', function() { if (!document.hidden) refreshBalances(); });

                function updateAmountLimit() {
                    if (!source || !amount) return;
                    amount.max = balances[source.value].toFixed(2);
                }

                if (source) {
                    source.addEventListener('change', updateAmountLimit);
                    updateAmountLimit();
                }

                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (submitting || !form.reportValidity()) return;
                    submitting = true;
                    submitButton.disabled = true;

                    const formData = new FormData(form);

                    fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(async response => {
                        const text = (await response.text()).replace(/^\uFEFF/, '').trim();
                        if (text === '0' || text === '-1') throw new Error('Your session may have expired. Refresh the page and sign in again.');
                        let data;
                        try { data = JSON.parse(text); }
                        catch (error) {
                            if (response.status === 401 || response.status === 403 || text === '-1' || text === '0') {
                                throw new Error('Your session may have expired. Refresh the page and sign in again.');
                            }
                            throw new Error('The server returned an unexpected response (HTTP ' + response.status + '). Check your transaction history before trying again, as the request may have been saved.');
                        }
                        if (!response.ok || !data || data.success !== true) {
                            throw new Error(data?.data?.message || data?.message || 'The withdrawal could not be submitted. Please refresh and try again.');
                        }
                        alert(data.data?.message || 'Withdrawal request submitted successfully');
                        window.location.href = '<?php echo esc_js(home_url('/wsi/dashboard/')); ?>';
                    })
                    .catch(error => {
                        alert('Error: ' + (error.message || 'Connection interrupted. Check your transaction history before submitting again.'));
                    })
                    .finally(() => {
                        submitting = false;
                        submitButton.disabled = false;
                        refreshBalances();
                    });
                });
            });
            </script>
                    </body>

</html>
