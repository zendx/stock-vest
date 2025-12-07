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

// Cache-busting version for shared assets
$wsi_asset_ver = (defined('WSI_VER') ? WSI_VER : '1.0.0');
$wsi_asset_path = plugin_dir_path(__FILE__) . 'assets/js/app435e.js';
if (file_exists($wsi_asset_path)) {
    $wsi_asset_ver .= '-' . filemtime($wsi_asset_path);
}

// Load Stocks Table
global $wpdb;
$t_stocks = $wpdb->prefix . 'wsi_stocks';

$per_page = 10;
$page     = max(1, intval($_GET['pg'] ?? 1));
$offset   = ($page - 1) * $per_page;
$total_stocks = 0;

// Only query if the table exists
if ($wpdb->get_var("SHOW TABLES LIKE '$t_stocks'") === $t_stocks) {
    $total_stocks = intval($wpdb->get_var("SELECT COUNT(*) FROM $t_stocks WHERE active = 1"));
    $stocks = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $t_stocks 
        WHERE active = 1 
        ORDER BY id DESC
        LIMIT %d OFFSET %d
    ", $per_page, $offset));
} else {
    $stocks = []; // table not ready
}
$total_pages = ($per_page > 0) ? max(1, ceil($total_stocks / $per_page)) : 1;
?>

<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->

<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | User Stocks</title>
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
        .wsi-stock-list {
            max-width: 720px;
            margin: 0 auto;
            padding: 8px 0 32px;
        }
        .wsi-stock-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: #fff;
            border-radius: 18px;
            padding: 14px 16px;
            box-shadow: 0 8px 18px rgba(0,0,0,0.04);
            border: 1px solid #f1f1f1;
            margin-bottom: 12px;
        }
        .wsi-stock-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }
        .wsi-stock-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f5f5f7, #e9ecf3);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #e5e7ed;
        }
        .wsi-stock-icon img {
            width: 48px;
            height: 48px;
            object-fit: cover;
        }
        .wsi-stock-fallback {
            font-weight: 700;
            color: #4a5568;
            font-size: 18px;
        }
        .wsi-stock-meta {
            min-width: 0;
        }
        .wsi-stock-name {
            font-weight: 700;
            font-size: 16px;
            color: #111827;
            line-height: 1.2;
        }
        .wsi-stock-ticker {
            font-size: 12px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #6b7280;
            margin-top: 2px;
        }
        .wsi-stock-period {
            font-size: 11px;
            color: #9ca3af;
        }
        .wsi-stock-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .wsi-stock-price {
            font-weight: 800;
            font-size: 17px;
            color: #0f172a;
            white-space: nowrap;
        }
        .wsi-stock-change {
            font-size: 12px;
            font-weight: 700;
            padding: 6px 10px;
            border-radius: 10px;
            white-space: nowrap;
        }
        .wsi-change-up {
            color: #0a8f3e;
            background: #e6f7ed;
        }
        .wsi-change-down {
            color: #c0392b;
            background: #fdecea;
        }
        .wsi-buy-btn {
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            color: #111827;
            border-radius: 10px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .wsi-buy-btn:hover {
            background: #eef2f7;
        }
        @media (max-width: 640px) {
            .wsi-stock-card { flex-wrap: wrap; }
            .wsi-stock-right { width: 100%; justify-content: space-between; }
        }
        .wsi-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 20px 0 10px;
        }
        .wsi-pagination a,
        .wsi-pagination span {
            padding: 6px 12px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #0f172a;
            text-decoration: none;
            font-weight: 700;
        }
        .wsi-pagination .disabled {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>

    <script defer src="<?php echo plugin_dir_url(__FILE__) . 'assets/js/app435e.js?v=' . esc_attr($wsi_asset_ver); ?>"></script><link href="<?php echo plugin_dir_url(__FILE__) . 'assets/css/app435e.css?v=' . esc_attr($wsi_asset_ver); ?>" rel="stylesheet">
</head>

<body class="main-bg main-bg-opac main-bg-blur adminuiux-sidebar-fill-white adminuiux-sidebar-boxed  theme-blue roundedui" data-theme="theme-blue" data-sidebarfill="adminuiux-sidebar-fill-white" data-bs-spy="scroll" data-bs-target="#list-example" data-bs-smooth-scroll="true" tabindex="0">
    <!-- Pageloader -->
    <?php include_once "assets/inc/header.php" ?>

        <main class="adminuiux-content has-sidebar" onclick="contentClick()">
            <!-- breadcrumb -->
            <div class="container-fluid mt-4">
                <div class="row gx-3 align-items-center">
                    <div class="col-12 col-sm">
                        <nav aria-label="breadcrumb" class="mb-2">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item bi"><a href="investment-dashboard.html"><i class="bi bi-house-door me-1 fs-14"></i> Dashboard</a></li>
                                <li class="breadcrumb-item active bi" aria-current="page">Stocks</li>
                            </ol>
                        </nav>
                        <h5>Stocks</h5>
                    </div>
                    <div class="col-12 col-sm-auto text-end py-3 py-sm-0"></div>
                </div>
            </div>

            <!-- Content -->
            <div class="container mt-3" id="main-content">
                <div class="wsi-stock-list">
                    <h5 class="mb-3 text-center">Available Stocks</h5>

                    <?php if (empty($stocks)) : ?>
                        <p class="text-center">No stocks available yet.</p>
                    <?php else : ?>
                        <?php foreach ($stocks as $s) :
                            $change = floatval($s->rate_percent ?? 0);
                            $change_class = ($change >= 0) ? 'wsi-change-up' : 'wsi-change-down';
                            $change_label = ($change >= 0 ? '+' : '') . number_format($change, 2) . '%';
                            $ticker = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $s->name), 0, 4)) ?: 'STK' . intval($s->id);
                        ?>
                        <div class="wsi-stock-card">
                            <div class="wsi-stock-left">
                                <div class="wsi-stock-icon">
                                    <?php if (!empty($s->image)) : ?>
                                        <img src="<?php echo esc_url($s->image); ?>" alt="">
                                    <?php else : ?>
                                        <span class="wsi-stock-fallback"><?php echo esc_html(substr($s->name, 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="wsi-stock-meta">
                                    <div class="wsi-stock-name"><?php echo esc_html($s->name); ?></div>
                                    <div class="wsi-stock-ticker"><?php echo esc_html($ticker); ?></div>
                                    <div class="wsi-stock-period"><?php echo esc_html(ucfirst($s->rate_period ?? '')); ?></div>
                                </div>
                            </div>

                            <div class="wsi-stock-right">
                                <div class="wsi-stock-price">$<?php echo number_format($s->price, 2); ?></div>
                                <div class="wsi-stock-change <?php echo esc_attr($change_class); ?>"><?php echo esc_html($change_label); ?></div>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="wsi_buy_stock">
                                    <?php wp_nonce_field('wsi_buy_stock_nonce'); ?>
                                    <input type="hidden" name="stock_id" value="<?php echo intval($s->id); ?>">
                                    <button class="wsi-buy-btn" type="submit">Buy</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($total_pages > 1): 
                            $base_url = remove_query_arg('pg');
                            $prev_page = $page - 1;
                            $next_page = $page + 1;
                        ?>
                        <div class="wsi-pagination">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo esc_url(add_query_arg('pg', $prev_page, $base_url)); ?>">&laquo; Prev</a>
                            <?php else: ?>
                                <span class="disabled">&laquo; Prev</span>
                            <?php endif; ?>
                            <span>Page <?php echo intval($page); ?> of <?php echo intval($total_pages); ?></span>
                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo esc_url(add_query_arg('pg', $next_page, $base_url)); ?>">Next &raquo;</a>
                            <?php else: ?>
                                <span class="disabled">Next &raquo;</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>

    </div>

    <!-- page footer -->
    <?php include_once "assets/inc/footer.php" ?>

</body>

</html>
