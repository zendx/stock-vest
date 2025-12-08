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

// Current user and total profit (stored profit + accumulated holdings)
$user_id = get_current_user_id();
$profit_balance_raw = floatval(wsi_get_profit($user_id));
$profit_balance = number_format($profit_balance_raw, 2);

?>
<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->

<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | Reinvest Profit</title>
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
                                                    <h5>Reinvest Profit</h5>
                                                    <p class="text-secondary mb-0">Move profit balance into your main balance to keep it working for you.</p>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row mb-4">
                                                        <div class="col">
                                                            <p class="text-secondary small mb-1">Available Profit Balance</p>
                                                            <h1 class="mb-0">$<?php echo $profit_balance; ?></h1>
                                                        </div>
                                                    </div>

                                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wsi-reinvest-form">
                                                        <input type="hidden" name="action" value="wsi_submit_reinvest">
                                                        <?php wp_nonce_field('wsi_reinvest_nonce'); ?>

                                                        <div class="row mb-3">
                                                            <div class="col-12 col-md-6 col-xl-4">
                                                                <div class="form-floating">
                                                                    <input name="amount" type="number" step="0.01" min="0" class="form-control" id="reinvest_amount" placeholder="Amount" required>
                                                                    <label for="reinvest_amount">Amount ($)</label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row align-items-center">
                                                            <div class="col">
                                                                <p class="text-secondary small mb-0">Reinvesting shifts funds from profit balance into your main balance.</p>
                                                            </div>
                                                            <div class="col-auto">
                                                                <button class="btn btn-theme" type="submit">Reinvest</button>
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
                                                    <h4 class="fw-medium">Roll profits back into your portfolio</h4>
                                                    <p class="mb-4">Use reinvest to grow your main balance without adding new deposits.</p>
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
                const form = document.getElementById('wsi-reinvest-form');
                if (!form) return;

                form.addEventListener('submit', function(e) {
                    // Let the form post normally; this hook is here if we later need to add client checks.
                });

            });
            </script>
</body>
</html>
