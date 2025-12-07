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

global $wpdb;

$t_hold   = $wpdb->prefix . 'wsi_holdings';
$t_stocks = $wpdb->prefix . 'wsi_stocks';
$uid      = get_current_user_id();

$per_page = 10;
$page     = max(1, intval($_GET['pg'] ?? 1));
$offset   = ($page - 1) * $per_page;

$total_holdings = intval($wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*) FROM $t_hold WHERE user_id = %d AND status = 'open'
", $uid)));

$holdings = $wpdb->get_results(
    $wpdb->prepare("
        SELECT 
            h.*, 
            s.name, 
            s.price, 
            s.rate_percent, 
            s.rate_period
        FROM $t_hold h
        LEFT JOIN $t_stocks s ON s.id = h.stock_id
        WHERE h.user_id = %d
          AND h.status = 'open'
        ORDER BY h.created_at DESC
        LIMIT %d OFFSET %d
    ", $uid, $per_page, $offset)
);

$total_pages = ($per_page > 0) ? max(1, ceil($total_holdings / $per_page)) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->

<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | User Stocks Holdings</title>
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

        /* --- Animation & styling for price changes --- */
        .price-animate {
            transition: background-color 0.45s ease, color 0.45s ease;
            border-radius: 4px;
            padding: 2px 4px;
            display: inline-block;
            min-width: 1.5em;
        }

        .price-up {
            background-color: rgba(0, 200, 0, 0.22) !important;
            color: #0a8a0a !important;
        }

        .price-down {
            background-color: rgba(200, 0, 0, 0.22) !important;
            color: #a80a0a !important;
        }

        /* small smoothing for numeric transitions */
        .number-fade {
            transition: opacity 0.25s ease;
        }

        /* Avatar small helper */
        .avatar.avatar-40 {
            width: 40px;
            height: 40px;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .avatar.avatar-40 img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* New card layout for holdings */
        .wsi-holdings-list {
            max-width: 760px;
            margin: 0 auto 40px;
        }
        .wsi-holding-card {
            background: #fff;
            border-radius: 18px;
            padding: 16px;
            margin-bottom: 14px;
            box-shadow: 0 10px 22px rgba(0,0,0,0.04);
            border: 1px solid #f1f1f1;
        }
        .wsi-holding-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .wsi-holding-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .wsi-holding-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f5f5f7, #e9ecf3);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #e5e7ed;
        }
        .wsi-holding-icon img {
            width: 52px;
            height: 52px;
            object-fit: cover;
        }
        .wsi-holding-fallback {
            font-weight: 700;
            color: #4a5568;
            font-size: 18px;
        }
        .wsi-holding-name {
            font-weight: 800;
            font-size: 17px;
            color: #0f172a;
        }
        .wsi-holding-sub {
            font-size: 13px;
            color: #6b7280;
        }
        .wsi-holding-change {
            font-weight: 800;
            font-size: 13px;
            padding: 8px 12px;
            border-radius: 12px;
            min-width: 70px;
            text-align: center;
        }
        .wsi-badge-up {
            background: #e6f7ed;
            color: #0a8f3e;
        }
        .wsi-badge-down {
            background: #fdecea;
            color: #c0392b;
        }
        .wsi-holding-body {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #f0f2f5;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .wsi-holding-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 4px;
        }
        .wsi-holding-value {
            font-weight: 800;
            font-size: 16px;
            color: #111827;
        }
        .wsi-text-up { color: #0a8f3e; }
        .wsi-text-down { color: #c0392b; }
        .wsi-holding-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 14px;
        }
        .wsi-sell-btn {
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #c0392b;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .wsi-sell-btn:hover { background: #fff6f4; }
        @media (max-width: 640px) {
            .wsi-holding-body { grid-template-columns: 1fr; }
            .wsi-holding-header { flex-wrap: wrap; }
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

    <script defer src="<?php echo plugin_dir_url(__FILE__) . 'assets/js/app435e.js?v=' . esc_attr($wsi_asset_ver); ?>"></script>
    <link href="<?php echo plugin_dir_url(__FILE__) . 'assets/css/app435e.css?v=' . esc_attr($wsi_asset_ver); ?>" rel="stylesheet">
</head>

<body class="main-bg main-bg-opac main-bg-blur adminuiux-sidebar-fill-white adminuiux-sidebar-boxed  theme-blue roundedui" data-theme="theme-blue" data-sidebarfill="adminuiux-sidebar-fill-white" data-bs-spy="scroll" data-bs-target="#list-example" data-bs-smooth-scroll="true" tabindex="0">
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
                            <li class="breadcrumb-item bi"><a href="investment-dashboard.html"><i class="bi bi-house-door me-1 fs-14"></i> Dashboard</a></li>
                            <li class="breadcrumb-item active bi" aria-current="page">Stocks Holdings</li>
                        </ol>
                    </nav>
                    <h5>Stocks Holdings</h5>
                </div>
                <div class="col-12 col-sm-auto text-end py-3 py-sm-0">

                </div>
            </div>
        </div>

        <!-- content -->
        <div class="container mt-4" id="main-content">
            <div class="row">
                <div class="col-12">
                    <h5 class="mb-3">Your Holdings</h5>

                    <div id="wsi-holdings-mount">
                        <?php if (empty($holdings)) { ?>
                            <p>No holdings.</p>
                        <?php } else { ?>
                            <div class="wsi-holdings-list">
                                <?php foreach ($holdings as $h): 
                                    $shares = floatval($h->shares ?? 0);
                                    $unit_price = floatval($h->price ?? 0);
                                    $invested = floatval($h->invested_amount ?? 0);
                                    $current_value = $shares * $unit_price;
                                    $profit = $current_value - $invested + floatval($h->accumulated_profit ?? 0);
                                    $profit_pct = ($invested > 0) ? ($profit / $invested * 100) : 0;
                                    $change_class = ($profit >= 0) ? 'wsi-badge-up' : 'wsi-badge-down';
                                    $profit_class = ($profit >= 0) ? 'wsi-text-up' : 'wsi-text-down';
                                ?>
                                <div class="wsi-holding-card">
                                    <div class="wsi-holding-header">
                                        <div class="wsi-holding-left">
                                            <div class="wsi-holding-icon">
                                                <?php if (!empty($h->image)) : ?>
                                                    <img src="<?php echo esc_url($h->image); ?>" alt="">
                                                <?php else : ?>
                                                    <span class="wsi-holding-fallback"><?php echo esc_html(substr($h->name ?? 'S', 0, 1)); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="wsi-holding-name"><?php echo esc_html($h->name ?? 'Unknown'); ?></div>
                                                <div class="wsi-holding-sub"><?php echo number_format($shares, 2); ?> units @ $<?php echo number_format($unit_price, 2); ?></div>
                                            </div>
                                        </div>
                                        <div class="wsi-holding-change <?php echo esc_attr($change_class); ?>">
                                            <?php echo ($profit_pct >= 0 ? '+' : '') . number_format($profit_pct, 1); ?>%
                                        </div>
                                    </div>

                                    <div class="wsi-holding-body">
                                        <div>
                                            <div class="wsi-holding-label">Invested</div>
                                            <div class="wsi-holding-value">$<?php echo number_format($invested, 2); ?></div>
                                        </div>
                                        <div>
                                            <div class="wsi-holding-label">Current Value</div>
                                            <div class="wsi-holding-value">$<?php echo number_format($current_value, 2); ?></div>
                                        </div>
                                        <div>
                                            <div class="wsi-holding-label">Profit/Loss</div>
                                            <div class="wsi-holding-value <?php echo esc_attr($profit_class); ?>">
                                                <?php echo ($profit >= 0 ? '+' : '') . '$' . number_format($profit, 2); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="wsi-holding-actions">
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                            <input type="hidden" name="action" value="wsi_sell_holding">
                                            <?php wp_nonce_field('wsi_sell_holding_nonce'); ?>
                                            <input type="hidden" name="holding_id" value="<?php echo intval($h->id); ?>">
                                            <button class="wsi-sell-btn" type="submit">Sell</button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="wsi-pagination" id="wsi-holdings-pagination">
                        <?php if ($total_pages > 1): 
                            $base_url = remove_query_arg('pg');
                            $prev_page = $page - 1;
                            $next_page = $page + 1;
                        ?>
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    </div>

    <!-- page footer -->
    <?php include_once "assets/inc/footer.php" ?>

    <!-- Page Level js (removed heavy charts; layout is static list) -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const apiRoot = "<?php echo esc_url_raw(rest_url('wsi/v1')); ?>";
        const nonce = "<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>";
        const holdingsMount = document.getElementById('wsi-holdings-mount');
        const paginationMount = document.getElementById('wsi-holdings-pagination');
        const urlParams = new URLSearchParams(window.location.search);
        const page = Math.max(1, parseInt(urlParams.get('pg') || '1', 10));

        if (!holdingsMount) return;

        fetch(`${apiRoot}/holdings?page=${page}`, {
            headers: {
                'X-WP-Nonce': nonce
            },
            credentials: 'same-origin'
        })
        .then(res => res.ok ? res.json() : Promise.reject(res))
        .then(data => {
            if (data.items_html) {
                holdingsMount.innerHTML = data.items_html;
            }
            if (paginationMount) {
                paginationMount.innerHTML = buildPagination(data.page || page, data.total_pages || 1);
            }
        })
        .catch(err => console.warn('Holdings refresh failed', err));

        function buildPagination(current, total) {
            if (!total || total <= 1) return '';
            const parts = [];
            const baseUrl = new URL(window.location.href);
            baseUrl.searchParams.delete('pg');

            const prevUrl = new URL(baseUrl);
            prevUrl.searchParams.set('pg', Math.max(1, current - 1));
            const nextUrl = new URL(baseUrl);
            nextUrl.searchParams.set('pg', Math.min(total, current + 1));

            if (current > 1) {
                parts.push(`<a href="${prevUrl.toString()}">&laquo; Prev</a>`);
            } else {
                parts.push('<span class="disabled">&laquo; Prev</span>');
            }

            parts.push(`<span>Page ${current} of ${total}</span>`);

            if (current < total) {
                parts.push(`<a href="${nextUrl.toString()}">Next &raquo;</a>`);
            } else {
                parts.push('<span class="disabled">Next &raquo;</span>');
            }

            return parts.join('');
        }
    });
    </script>
</body>

</html>
