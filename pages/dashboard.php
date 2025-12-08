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

// Get current user
$user_id = get_current_user_id();

// Cache-busting version for shared assets
$wsi_asset_ver = (defined('WSI_VER') ? WSI_VER : '1.0.0');
$wsi_asset_path = plugin_dir_path(__FILE__) . 'assets/js/app435e.js';
if (file_exists($wsi_asset_path)) {
    $wsi_asset_ver .= '-' . filemtime($wsi_asset_path);
}

// --- USE THE CORRECT FUNCTIONS ---

// Total assets
$assets = wsi_get_main($user_id);

// Profit income
$profit_income = wsi_get_profit($user_id);

// Net margin = assets + profit
$net_margin = $assets + $profit_income;

// Compute available balance (profit + unlocked deposits)
global $wpdb;
$t_dep = $wpdb->prefix . 'wsi_deposits';

$deposits = $wpdb->get_results(
    $wpdb->prepare("SELECT amount, created_at FROM $t_dep WHERE user_id=%d AND status='approved'", $user_id)
);

$now = current_time('timestamp');
$unlock_seconds = 60 * 24 * 60 * 60; // 60 days

$unlocked_assets = 0;
foreach ($deposits as $d) {
    if (($now - strtotime($d->created_at)) >= $unlock_seconds) {
        $unlocked_assets += floatval($d->amount);
    }
}

// Available balance = profit + unlocked deposits
$available_balance = $profit_income + $unlocked_assets;

// Format for display
$assets = number_format($assets, 2);
$profit_income = number_format($profit_income, 2);
$net_margin = number_format($net_margin, 2);
$available_balance = number_format($available_balance, 2);
?>

<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->
<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | User Dashboard</title>
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
</head>

<body class="main-bg main-bg-opac main-bg-blur adminuiux-sidebar-fill-white adminuiux-sidebar-boxed  theme-blue roundedui" data-theme="theme-blue" data-sidebarfill="adminuiux-sidebar-fill-white" data-bs-spy="scroll" data-bs-target="#list-example" data-bs-smooth-scroll="true" tabindex="0">
    <!-- Pageloader -->
    <?php
    include_once plugin_dir_path(__FILE__) . 'assets/inc/header.php';
    ?>


                    <main class="adminuiux-content has-sidebar" onclick="contentClick()">
                        <!-- body content of pages -->

                        <!-- Content  -->
                        <div class="container mt-4" id="main-content">

                            <!-- Welcome box -->
                            <div class="row align-items-center">
                                <div class="col-12 col-lg mb-4">
                                    <h3 class="fw-normal mb-0 text-secondary">Welcome,</h3>
                                    <h1><?php echo esc_html(wp_get_current_user()->display_name); ?></h1>
                                </div>
                            </div>
                        <div class="container mt-4" id="main-content">
                            <div class="row">
                                <!-- balance -->
                                <div class="col-12 col-md-6 col-lg-4 mb-4">
                                    <div class="card adminuiux-card bg-theme-1">
                                        <div class="card-body z-index-1">
                                            <div class="row gx-2 align-items-center mb-4">
                                                <div class="col-auto py-1">
                                                    <div class="avatar avatar-60 bg-white-opacity rounded"><i class="bi bi-wallet h2"></i></div>
                                                </div>
                                            </div>
                                            <h1 id="wsi-amt-assets">$<?php echo $assets; ?></h1>
                                            <h5 class="opacity-75 fw-normal mb-1">Total Assets</h5>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Assets -->
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="row">
                                        <div class="col-12 col-sm-6 col-md-12">
                                            <div class="card adminuiux-card mb-4">
                                                <div class="card-body z-index-1">
                                                    <div class="row">
                                                        <div class="col-auto">
                                                            <div class="avatar avatar-60 bg-success-subtle text-success rounded"><i class="bi bi-graph-down-arrow h4"></i></div>
                                                        </div>
                                                        <div class="col">
                                                            <h4 class="fw-medium" id="wsi-amt-profit">$<?php echo $profit_income; ?></h4>
                                                            <p class="text-secondary">Profit Income<span class="text-success fs-14"></i> </span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-12">
                                            <div class="card adminuiux-card mb-4">
                                                <div class="card-body z-index-1">
                                                    <div class="row">
                                                        <div class="col-auto">
                                                            <div class="avatar avatar-60 bg-danger-subtle text-danger rounded"><i class="bi bi-graph-up-arrow h4"></i></div>
                                                        </div>
                                                        <div class="col">
                                                            <h4 class="fw-medium" id="wsi-amt-available">$<?php echo $available_balance; ?></h4>
                                                            <p class="text-secondary">Available Balance <span class="text-success fs-14"></i> </span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- investment -->
                                <div class="col-12 col-md-12 col-lg-4">
                                    <div class="row">
                                        <div class="col-12 col-sm-6 col-lg-12">
                                            <div class="card adminuiux-card mb-4">
                                                <div class="card-body z-index-1">
                                                    <div class="row">
                                                        <div class="col-auto">
                                                            <div class="avatar avatar-60 bg-theme-1-subtle text-theme-1 rounded"><i class="bi bi-bank h4"></i></div>
                                                        </div>
                                                        <div class="col">
                                                            <h4 class="fw-medium wsi-amt-assets">$<?php echo $assets; ?></h4>
                                                            <p class="text-secondary">Total Assets <span class="text-success fs-14"></i> </span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-6 col-lg-12">
                                            <div class="card adminuiux-card mb-4">
                                                <div class="card-body z-index-1">
                                                    <div class="row">
                                                        <div class="col-auto">
                                                            <div class="avatar avatar-60 bg-theme-1-subtle text-theme-1 rounded"><i class="bi bi-cash-coin h4"></i></div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                                <h4 class="fw-medium mb-0" id="wsi-amt-net">$<?php echo $net_margin; ?></h4>
                                                            </div>
                                                            <p class="text-secondary">Net Margin <span class="text-success fs-14"></i> </span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- chart -->
                                <!--div class="col-12 col-md-12 col-lg-8 mb-4">
                                    <div class="card adminuiux-card">
                                        <div class="card-header">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <h6>Cash Flow</h6>
                                                </div>
                                                <div class="col-auto px-0">
                                                    <select class="form-select form-select-sm">
                                                        <option>USD</option>
                                                        <option>CAD</option>
                                                        <option>AUD</option>
                                                    </select>
                                                </div>
                                                <div class="col-auto">
                                                    <button class="btn btn-sm btn-square btn-link"><i class="bi bi-arrow-clockwise"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="height-250 mb-3">
                                                <canvas id="areachartblue1"></canvas>
                                            </div>
                                            <div class="row align-items-center">
                                                <div class="col-6 col-md-4 col-lg-3">
                                                    <div class="card adminuiux-card bg-theme-1">
                                                        <div class="card-body z-index-1">
                                                            <h4 class="fw-medium text">$5560.50</h4>
                                                            <p class="opacity-75">Income <span class="fs-14"></i> </span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-4 col-lg-3">
                                                    <div class="card adminuiux-card bg-theme-1-subtle">
                                                        <div class="card-body z-index-1">
                                                            <h4 class="fw-medium">$5560.50</h4>
                                                            <p class="text-secondary">Expense <span class="text-success fs-14"></i> </span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div-->

                                <!-- quick exchange -->
                                <!--div class="col-12 col-md-6 col-lg-4">
                                    <div class="card adminuiux-card">
                                        <div class="card-header">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <h6>Quick Exchange</h6>
                                                </div>
                                                <div class="col-auto px-0">

                                                </div>
                                                <div class="col-auto">
                                                    <span class="mx-1 text-secondary small">12s ago</span>
                                                    <button class="btn btn-sm btn-square btn-link"><i class="bi bi-arrow-clockwise"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <input type="number" class="form-control form-control-lg text-center mb-4" id="ihave" placeholder="Convert Amount..." value="100.00">
                                            <div class="row mb-3">
                                                <div class="col">
                                                    <div class="form-floating mb-1">
                                                        <select class="form-select" id="ihavecurrency">
                                                            <option>USD</option>
                                                            <option>CAD</option>
                                                            <option>AUD</option>
                                                        </select>
                                                        <label for="ihavecurrency">I have...</label>
                                                    </div>
                                                    <p class="small text-secondary text-center">1.00 USD</p>
                                                </div>
                                                <div class="col-auto">
                                                    <button class="btn btn-square btn-theme mt-2"><i class="bi bi-arrow-left-right"></i></button>
                                                </div>
                                                <div class="col">
                                                    <div class="form-floating mb-1">
                                                        <select class="form-select" id="ihavecurrency2">
                                                            <option>USD</option>
                                                            <option selected>CAD</option>
                                                            <option>AUD</option>
                                                        </select>
                                                        <label for="ihavecurrency2">I want...</label>
                                                    </div>
                                                    <p class="small text-secondary text-center">1.38 CAD</p>
                                                </div>
                                            </div>
                                            <br>
                                            <div class="text-center">
                                                <h5 class="fw-normal"><b class="fw-bold">Great!</b> You will get</h5>
                                                <h1 class="mb-0 text-theme-1">132.00</h1>
                                                <p class="text-secondary small mb-4">in Canadian Dollar</p>
                                                <button class="btn btn-outline-theme">Exchange now</button>
                                            </div>
                                        </div>
                                    </div>
                                </div-->
                    </main>

            </div>

            <!-- page footer -->
            <?php
            include_once plugin_dir_path(__FILE__) . 'assets/inc/footer.php';
            ?>
                <!-- Page Level js -->
                    <script src="assets/js/investment/investment-dashboard.js"></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const apiRoot = "<?php echo esc_url_raw(rest_url('wsi/v1')); ?>";
                        const nonce = "<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>";
                        fetch(`${apiRoot}/dashboard`, {
                            headers: { 'X-WP-Nonce': nonce },
                            credentials: 'same-origin'
                        })
                        .then(res => res.ok ? res.json() : Promise.reject(res))
                        .then(data => {
                            updateText('#wsi-amt-assets', data.assets);
                            document.querySelectorAll('.wsi-amt-assets').forEach(el => updateEl(el, data.assets));
                            updateText('#wsi-amt-profit', data.profit_income);
                            updateText('#wsi-amt-available', data.available_balance);
                            updateText('#wsi-amt-net', data.net_margin);
                        })
                        .catch(err => console.warn('Dashboard refresh failed', err));

                        function formatCurrency(value) {
                            const num = Number(value || 0);
                            return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                        function updateText(selector, value) {
                            const el = document.querySelector(selector);
                            if (el) updateEl(el, value);
                        }
                        function updateEl(el, value) {
                            el.textContent = '$' + formatCurrency(value);
                        }
                    });
                    </script>

                    </body>

</html>
