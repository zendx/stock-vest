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

// Use the same remaining principal and earnings balances as withdrawals.
$user_id = get_current_user_id();
$balances = wsi_get_withdrawal_balances($user_id);
$reinvest_total = round($balances['total_assets_unlocked_amount'] + $balances['available_balance'], 2);
$reinvest_disabled = $balances['balance_error'] || $reinvest_total <= 0;

?>
<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->

<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | Reinvest</title>
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
                                            <li class="breadcrumb-item active bi" aria-current="page">Reinvest</li>
                                        </ol>
                                    </nav>
                                    <h5>Reinvest</h5>
                                </div>
                                <div class="col-12 col-sm-auto text-end py-3 py-sm-0">

                                </div>
                            </div>
                        </div>

                        <!-- content -->
                        <div class="container mt-4" id="main-content" data-bs-spy="scroll" data-bs-target="#list-example" data-bs-smooth-scroll="true">
                            <div class="row" id="list-item-reinvest">
                                <div class="col-12">
                                    <div class="row">

                                        <div class="col-12 col-lg-8 mb-4">
                                            <div class="card adminuiux-card mb-4">
                                                <div class="card-header">
                                                    <h5>Reinvest</h5>
                                                    <p class="text-secondary mb-0">Choose how much you want to reinvest.</p>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row mb-4">
                                                        <div class="col">
                                                            <p class="text-secondary small mb-1">Total Available Balance to Reinvest</p>
                                                            <h1 class="mb-0" id="reinvest-balance">$<?php echo number_format($reinvest_total, 2); ?></h1>
                                                        </div>
                                                    </div>

                                                    <?php if ($balances['balance_error']): ?>
                                                        <p class="text-danger" role="alert">Balances are temporarily unavailable. Please refresh this page before reinvesting.</p>
                                                    <?php elseif ($reinvest_total <= 0): ?>
                                                        <p class="text-secondary">You have no unlocked deposits or Available Balance to reinvest yet.</p>
                                                    <?php endif; ?>

                                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wsi-reinvest-form" data-reinvest-form>
                                                        <input type="hidden" name="action" value="wsi_submit_reinvest">
                                                        <?php wp_nonce_field('wsi_reinvest_nonce'); ?>

                                                        <div class="btn-group mb-3" role="group" aria-label="Reinvest percentage">
                                                            <?php foreach ([10, 50, 100] as $percent): ?>
                                                                <button type="button" class="btn btn-outline-theme" data-reinvest-percent="<?php echo $percent; ?>" <?php disabled($reinvest_disabled); ?>><?php echo $percent; ?>%</button>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <div class="col-12 col-md-6">
                                                                <div class="form-floating">
                                                                    <input name="amount" type="number" step="0.01" min="0.01" max="<?php echo esc_attr(number_format($reinvest_total, 2, '.', '')); ?>" class="form-control" id="reinvest_amount" placeholder="Amount" required <?php disabled($reinvest_disabled); ?>>
                                                                    <label for="reinvest_amount">Amount ($)</label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row align-items-center">
                                                            <div class="col">
                                                                <p class="text-secondary small mb-0">Reinvest your unlocked deposits and available balance to keep earning.</p>
                                                            </div>
                                                            <div class="col-auto">
                                                                <button class="btn btn-theme" type="submit" <?php disabled($reinvest_disabled); ?>>Reinvest Now</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right-side card -->
                                        <div class="col-12 col-lg-4 mb-4">
                                            <div class="card adminuiux-card position-relative overflow-hidden bg-theme-1 h-100">
                                                <div class="position-absolute top-0 start-0 h-100 w-100 z-index-0 coverimg opacity-50">
                                                    <img src="<?php echo $PLUGIN_ASSETS; ?>img/modern-ai-image/flamingo-4.jpg" alt="">
                                                </div>
                                                <div class="card-body z-index-1">
                                                    <div class="avatar avatar-60 rounded bg-white-opacity text-white mb-4">
                                                        <i class="bi bi-arrow-repeat h4"></i>
                                                    </div>
                                                    <h2>Keep Earnings Working</h2>
                                                    <h4 class="fw-medium">Start a new investment period</h4>
                                                    <p class="mb-4">Put your chosen amount back to work and keep earning.</p>
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


<script src="<?php echo esc_url($PLUGIN_ASSETS . 'js/investment/reinvest-page.js?v=' . filemtime(__DIR__ . '/assets/js/investment/reinvest-page.js')); ?>"></script>
</body>
</html>
