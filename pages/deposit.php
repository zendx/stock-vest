<?php

if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    $redirect = function_exists('wsi_login_url') ? wsi_login_url() : wp_login_url();
    wp_safe_redirect($redirect);
    exit;
}

// Get the plugin assets URL
$PLUGIN_ASSETS = plugins_url('pages/assets/', dirname(dirname(__FILE__)) . '/stock-vest.php');
$wsi = $PLUGIN_ASSETS;

$opts = function_exists('wsi_get_opts') ? wsi_get_opts() : [];

// Cache-busting version for shared assets
$wsi_asset_ver = (defined('WSI_VER') ? WSI_VER : '1.0.0');
$wsi_asset_path = plugin_dir_path(__FILE__) . 'assets/js/app435e.js';
if (file_exists($wsi_asset_path)) {
    $wsi_asset_ver .= '-' . filemtime($wsi_asset_path);
}

// Normalize values with safe defaults
$exchange_rate       = floatval($opts['exchange_rate'] ?? 1000); // ₦ per $1
$min_invest          = floatval($opts['min_invest'] ?? 50);
$deposit_mode        = sanitize_text_field($opts['deposit_mode'] ?? 'manual');
$manual_payment_info = $opts['manual_payment_info'] ?? '';
$naira_payment_info  = $opts['naira_payment_info'] ?? '';

$usdt_trc_wallet      = trim($opts['usdt_trc_wallet'] ?? '');
$usdt_trc_instruction = $opts['usdt_trc_instruction'] ?? '';
$usdt_erc_wallet      = trim($opts['usdt_erc_wallet'] ?? '');
$usdt_erc_instruction = $opts['usdt_erc_instruction'] ?? '';
$sol_wallet           = trim($opts['sol_wallet'] ?? '');
$sol_instruction      = $opts['sol_instruction'] ?? '';
$eth_wallet           = trim($opts['eth_wallet'] ?? '');
$eth_instruction      = $opts['eth_instruction'] ?? '';

// ------------------------------
// Email templates (added)
// ------------------------------
$email_on_deposit         = $opts['email_on_deposit'] ?? '';        // user email when deposit is made
$email_on_withdraw        = $opts['email_on_withdraw'] ?? '';       // user email when withdrawal is made
$email_on_registration    = $opts['email_on_registration'] ?? '';   // welcome email
$email_on_stock_purchase  = $opts['email_on_stock_purchase'] ?? ''; // stock purchase email
$email_on_holding_sale    = $opts['email_on_holding_sale'] ?? '';   // sale notification
$email_admin_new_deposit  = $opts['email_admin_new_deposit'] ?? ''; // admin gets notified
$email_admin_new_withdraw = $opts['email_admin_new_withdraw'] ?? ''; // admin withdraw alert

// default naira amount shown = min_invest * exchange_rate
$default_naira = number_format($min_invest * max(1, $exchange_rate), 2, '.', '');

$requested_deposit = isset($_GET['deposit_id']) && is_scalar($_GET['deposit_id']) ? absint($_GET['deposit_id']) : 0;
$pending_deposit = wsi_get_deposit_status(get_current_user_id(), $requested_deposit);
$deposit_page_config = [
    'ajax_url' => admin_url('admin-ajax.php'),
    'dashboard_url' => wsi_get_dashboard_page_url(),
    'status_nonce' => wp_create_nonce('wsi_deposit_status_nonce'),
    'pending_deposit' => is_wp_error($pending_deposit) ? null : $pending_deposit,
    'exchange_rate' => max(1, $exchange_rate),
    'min_invest' => $min_invest,
    'wallets' => [
        'usdt_trc' => ['label' => 'USDT (TRC20)', 'address' => $usdt_trc_wallet, 'instruction' => $usdt_trc_instruction],
        'usdt_erc' => ['label' => 'USDT (ERC20)', 'address' => $usdt_erc_wallet, 'instruction' => $usdt_erc_instruction],
        'sol' => ['label' => 'Solana (SOL)', 'address' => $sol_wallet, 'instruction' => $sol_instruction],
        'eth' => ['label' => 'ETH', 'address' => $eth_wallet, 'instruction' => $eth_instruction],
    ],
];
/** Get Wallet Details **/
?>

<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->

<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | User Deposit</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">

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
<link rel="stylesheet" href="<?php echo esc_url($PLUGIN_ASSETS . 'css/deposit-page.css?v=' . filemtime(__DIR__ . '/assets/css/deposit-page.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/css/ui-polish.css?v=' . filemtime(__DIR__ . '/assets/css/ui-polish.css')); ?>">
</head>

<body class="wsi-ui main-bg main-bg-opac main-bg-blur adminuiux-sidebar-fill-white adminuiux-sidebar-boxed  theme-blue roundedui" data-theme="theme-blue" data-sidebarfill="adminuiux-sidebar-fill-white" data-bs-spy="scroll" data-bs-target="#list-example" data-bs-smooth-scroll="true" tabindex="0">
    <!-- Pageloader -->
<?php include_once "assets/inc/header.php" ?>

                    <main class="adminuiux-content has-sidebar" onclick="contentClick()">
                        <!-- body content of pages -->

                        <!-- breadcrumb -->
                        <div class="container-fluid mt-4">
                            <div class="row gx-3 align-items-center">
                                <div class="col-12 col-sm">
                                    <nav aria-label="breadcrumb" class="mb-2">
                                        <ol class="breadcrumb mb-0">
                                            <li class="breadcrumb-item bi"><a href="#"><i class="bi bi-house-door me-1 fs-14"></i> Dashboard</a></li>
                                            <li class="breadcrumb-item active bi" aria-current="page">Deposit</li>
                                        </ol>
                                    </nav>
                                    <h5>Deposit</h5>
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

                            <div class="row" id="list-item-1">

                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col-12 col-lg-8 mb-4">

                                                <div class="card adminuiux-card">
                                                    <div class="card-header">
                                                        <h5>Create Deposit</h5>
                                                        <p class="text-secondary">Start growing your wealth through advanced DeFi-agriculture and smart investment solutions.</p>
                                                    </div>

                                                    <div class="card-body">

                                                        <p id="wsi-deposit-feedback" class="text-danger small" role="alert" hidden></p>
                                                        <form data-wsi-deposit-page id="wsi-deposit-form" method="post" action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>">

                                                            <input type="hidden" name="action" value="wsi_submit_deposit">
                                                            <?php wp_nonce_field('wsi_deposit_nonce'); ?>
                                                            <input type="hidden" name="amount" id="amount_usd" value="">

                                                            <!-- Payment Type -->
                                                            <div class="mb-3">
                                                                <label><input type="radio" name="payment_type" value="naira" checked> Bank Payment</label>
                                                                <label class="ms-3"><input type="radio" name="payment_type" value="crypto"> Crypto Payment</label>
                                                            </div>

                                                            <!-- NAIRA SECTION -->
                                                            <div id="naira_section">
                                                                <div class="row mb-2">
                                                                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                        <div class="form-floating">
                                                                            <input placeholder=" " name="amount_naira" id="amount_naira" type="number" min="0" step="0.01"
                                                                                   class="form-control"
                                                                                   value="<?php echo esc_attr($opts['min_invest'] ?? 50) * ($opts['exchange_rate'] ?? 1000); ?>">
                                                                            <label for="amount_naira">Enter Amount (₦)</label>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                        <div class="form-floating">
                                                                            <input type="text" id="amount_usd_display" readonly class="form-control" placeholder=" ">
                                                                            <label for="amount_usd_display">Equivalent ($)</label>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div id="rate_info" class="text-secondary small mb-2">
                                                                    Exchange Rate: $1 = ₦<?php echo esc_html(number_format($opts['exchange_rate'] ?? 1000,2)); ?>
                                                                </div>

                                                                <div id="naira_instructions" class="mb-3">
                                                                    <?php echo nl2br(esc_html($opts['naira_payment_info'] ?? $opts['manual_payment_info'] ?? '')); ?>
                                                                </div>
                                                            </div>

                                                            <!-- CRYPTO SECTION -->
                                                            <div id="crypto_section" style="display:none;">
                                                                <div class="row mb-2">
                                                                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                        <div class="form-floating">
                                                                            <input name="crypto_amount" id="crypto_amount" type="number" step="0.01"
                                                                                   class="form-control"
                                                                                   min="0"
                                                                                   placeholder="<?php echo esc_attr($opts['min_invest'] ?? 50); ?>">
                                                                            <label for="crypto_amount">Enter Amount ($)</label>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-12 col-md-6 col-xl-4 mb-3" id="crypto_wallet_select" style="display:none;">
                                                                        <div class="form-floating">
                                                                            <select id="crypto_wallet" name="crypto_wallet" class="form-select">
                                                                                <option value="">-- choose --</option>
                                                                                <?php if(!empty($opts['usdt_trc_wallet'])): ?><option value="usdt_trc">USDT (TRC20)</option><?php endif; ?>
                                                                                <?php if(!empty($opts['usdt_erc_wallet'])): ?><option value="usdt_erc">USDT (ERC20)</option><?php endif; ?>
                                                                                <?php if(!empty($opts['sol_wallet'])): ?><option value="sol">Solana (SOL)</option><?php endif; ?>
                                                                                <?php if(!empty($opts['eth_wallet'])): ?><option value="eth">ETH</option><?php endif; ?>
                                                                            </select>
                                                                            <label for="crypto_wallet">Select wallet</label>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div id="crypto_wallet_info" style="display:none;">
                                                                    <div id="wallet_address" class="fw-bold"></div>
                                                                    <div id="wallet_instruction" class="mt-2 small"></div>
                                                                </div>
                                                            </div>

                                                            <div class="row align-items-center mt-4">
                                                                <div class="col">
                                                                    <p class="text-secondary small">Amount will be processed after admin confirmation</p>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <button type="submit" id="wsi_deposit_submit" class="btn btn-theme">
                                                                        Submit Deposit
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </form>

                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Right Offer Card (unchanged) -->
                                            <div class="col-12 col-lg-4 mb-4">
                                                <div class="card adminuiux-card position-relative overflow-hidden bg-theme-1 h-100">
                                                    <div class="position-absolute top-0 start-0 h-100 w-100 z-index-0 coverimg opacity-50">
                                                        <img src="assets/img/modern-ai-image/flamingo-4.jpg" alt="">
                                                    </div>
                                                    <div class="card-body z-index-1">
                                                        <div class="avatar avatar-60 rounded bg-white-opacity text-white mb-4">
                                                            <i class="bi bi-tags h4"></i>
                                                        </div>
                                                        <h2>Crypto Deposit</h2>
                                                        <h4 class="fw-medium">Seamlessly fund your <b>COFCO Capital</b> account by selecting from our range of supported <b>cryptocurrencies </b></h4>
                                                        <p class="mb-4">for a secure and efficient deposit experience</p>
                                                        <button class="btn btn-light my-1">Apply Now</button>
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
            if (file_exists(__DIR__ . '/assets/inc/footer.php')) {
                include_once __DIR__ . '/assets/inc/footer.php';
            }
            ?>

            <dialog id="wsi-deposit-wait" class="wsi-deposit-wait" aria-labelledby="wsi-deposit-wait-title" aria-describedby="wsi-deposit-wait-message" data-state="pending">
                <div class="wsi-deposit-wait__body">
                    <div class="wsi-deposit-wait__eyebrow">Deposit confirmation</div>
                    <div class="wsi-deposit-wait__orbit" aria-hidden="true"><div class="wsi-deposit-wait__core"><i class="bi bi-hourglass-split"></i></div></div>
                    <h2 id="wsi-deposit-wait-title">Your deposit is on its way</h2>
                    <p id="wsi-deposit-wait-message" class="wsi-deposit-wait__message" aria-live="polite"></p>
                    <p id="wsi-deposit-wait-reference" class="wsi-deposit-wait__reference"></p>
                    <div class="wsi-deposit-wait__time">
                        <span id="wsi-deposit-wait-clock" class="wsi-deposit-wait__clock">30:00</span>
                        <span id="wsi-deposit-wait-estimate" class="wsi-deposit-wait__caption">Estimated review time remaining</span>
                        <p class="wsi-deposit-wait__note">About 30 minutes is an estimate. Approval times may vary.</p>
                    </div>
                    <p id="wsi-deposit-wait-connection" class="wsi-deposit-wait__connection" role="status"></p>
                    <button type="button" id="wsi-deposit-wait-close" class="wsi-deposit-wait__button">Continue to dashboard</button>
                </div>
            </dialog>
            <script type="application/json" id="wsi-deposit-config"><?php echo wp_json_encode($deposit_page_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
            <script src="<?php echo esc_url($PLUGIN_ASSETS . 'js/investment/deposit-page.js?v=' . filemtime(__DIR__ . '/assets/js/investment/deposit-page.js')); ?>" defer></script>
        </body>

</html>
