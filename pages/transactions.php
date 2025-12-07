<?php

if (!defined('ABSPATH')) exit;

// Get the plugin assets URL
$PLUGIN_ASSETS = plugins_url('pages/assets/', dirname(dirname(__FILE__)) . '/stock-vest.php');
$wsi = $PLUGIN_ASSETS;

?>
<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->

<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | Transactions</title>
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

    <script defer src="<?php echo plugin_dir_url(__FILE__) . 'assets/js/app435e.js?1096aad991449c8654b2'; ?>"></script><link href="<?php echo plugin_dir_url(__FILE__) . 'assets/css/app435e.css?1096aad991449c8654b2'; ?>" rel="stylesheet">
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
                                            <li class="breadcrumb-item bi"><a href="investment-dashboard.html">Home</a></li>
                                            <li class="breadcrumb-item active bi" aria-current="page">Transactions</li>
                                        </ol>
                                    </nav>
                                    <h5>Transactions</h5>
                                </div>
                                <div class="col-auto py-1 ms-auto ms-sm-0">
                                    <button class="btn btn-link btn-square btn-icon" data-bs-toggle="collapse" data-bs-target="#filterschedule" aria-expanded="false" aria-controls="filterschedule">
                                        <i data-feather="filter"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="tab_transactions" class="wsi-tab-content">

                            <?php
                            global $wpdb;
                            $uid = get_current_user_id();

                            /* ---------- FILTERING ---------- */
                            $allowed_filters = [
                                '' => '',
                                'deposit' => 'deposit',
                                'withdrawal' => 'withdraw_request',
                                'smart' => 'smart_farm_interest',
                                'reinvest' => 'reinvest',
                                'stocks' => 'buy_stock'
                            ];

                            $filter_key  = isset($_GET['ftype']) ? sanitize_text_field($_GET['ftype']) : '';
                            $filter_type = isset($allowed_filters[$filter_key]) ? $allowed_filters[$filter_key] : '';

                            /* ---------- SORTING ---------- */
                            $allowed_sort = ['created_at','amount','type'];
                            $requested_orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
                            $orderby = in_array($requested_orderby, $allowed_sort, true) ? $requested_orderby : 'created_at';
                            $order   = (isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC') ? 'ASC' : 'DESC';

                            /* ---------- PAGINATION ---------- */
                            $per_page = 10;
                            $page = max(1, intval($_GET['pg'] ?? 1));
                            $offset = ($page - 1) * $per_page;

                            /* ---------- BUILD SQL ---------- */
                            $where = "WHERE user_id=%d";
                            $params = [$uid];

                            if ($filter_type !== '') {
                                $where .= " AND type=%s";
                                $params[] = $filter_type;
                            }

                            /* count */
                            $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}wsi_transactions $where";
                            $count = $wpdb->get_var($wpdb->prepare($count_sql, $params));
                            $total_pages = ($per_page > 0) ? max(1, ceil(intval($count) / $per_page)) : 1;

                            /* main */
                            $order_by_col = $orderby;
                            $main_sql = "SELECT * FROM {$wpdb->prefix}wsi_transactions
                                         $where
                                         ORDER BY {$order_by_col} {$order}
                                         LIMIT %d OFFSET %d";

                            $main_params = $params;
                            $main_params[] = $per_page;
                            $main_params[] = $offset;

                            $txs = $wpdb->get_results($wpdb->prepare($main_sql, $main_params));

                            /* ---------- CSV EXPORT ---------- */
                            if (isset($_GET['export']) && $_GET['export'] === 'csv') {
                                $export_sql = "SELECT * FROM {$wpdb->prefix}wsi_transactions $where ORDER BY {$order_by_col} {$order}";
                                $export_rows = $wpdb->get_results($wpdb->prepare($export_sql, $params), ARRAY_A);

                                header('Content-Type: text/csv; charset=utf-8');
                                header('Content-Disposition: attachment; filename=transactions-' . date('Ymd') . '.csv');
                                $out = fopen('php://output', 'w');
                                fputcsv($out, ['When','Amount','Type','Description','User ID']);

                                foreach ($export_rows as $r) {
                                    fputcsv($out, [$r['created_at'], $r['amount'], $r['type'], $r['description'], $r['user_id']]);
                                }
                                fclose($out);
                                exit;
                            }

                            /* ---------- URL builder ---------- */
                            $build_qs = function($overrides = []) {
                                $q = $_GET;
                                foreach ($overrides as $k => $v) {
                                    if ($v === null) unset($q[$k]);
                                    else $q[$k] = $v;
                                }
                                return esc_url(add_query_arg($q, remove_query_arg('paged')));
                            };
                            ?>
                            <style>
                                .wsi-tx-list { display: flex; flex-direction: column; gap: 10px; }
                                .wsi-tx-card {
                                    border: 1px solid #f1f1f1;
                                    border-radius: 14px;
                                    padding: 10px 12px;
                                    background: #fff;
                                    box-shadow: 0 6px 14px rgba(0,0,0,0.04);
                                }
                                .wsi-tx-card summary {
                                    list-style: none;
                                    cursor: pointer;
                                }
                                .wsi-tx-card summary::-webkit-details-marker { display: none; }
                                .wsi-tx-card summary > div {
                                    display: flex;
                                    align-items: center;
                                    justify-content: space-between;
                                    gap: 12px;
                                }
                                .wsi-tx-type { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
                                .wsi-tx-date { font-size: 12px; color: #6b7280; }
                                .wsi-tx-amount { font-weight: 800; color: #0f172a; }
                                .wsi-amount-up { color: #0a8f3e; }
                                .wsi-amount-down { color: #c0392b; }
                                .wsi-tx-body { margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f1f1; }
                                .wsi-tx-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
                                .wsi-tx-label { color: #9ca3af; font-size: 12px; }
                                .wsi-tx-text { font-size: 13px; color: #111827; }
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

                            <!-- ========================= -->
                            <!-- FILTER PANEL (COLLAPSIBLE) -->
                            <!-- ========================= -->
                            <div class="container" id="main-content">

                                <div class="collapse" id="filterschedule">
                                    <div class="card adminuiux-card mt-4">
                                        <div class="card-body pb-0">

                                            <div class="row">

                                                <!-- Search (optional) -->
                                                <div class="col-12 col-md-6 col-lg-3 mb-3">
                                                    <div class="form-floating">
                                                        <input type="text" class="form-control" placeholder="Search...">
                                                        <label>Search...</label>
                                                    </div>
                                                </div>

                                                <!-- TYPE FILTER mapped to WP filter -->
                                                <div class="col-12 col-md-6 col-lg-3 mb-3">
                                                    <div class="form-floating">
                                                        <select class="form-select" onchange="location.href=this.value;">
                                                            <option value="<?php echo $build_qs(['ftype'=>'', 'pg'=>1]); ?>" <?php if($filter_key=='') echo 'selected'; ?>>All</option>
                                                            <option value="<?php echo $build_qs(['ftype'=>'deposit','pg'=>1]); ?>" <?php if($filter_key=='deposit') echo 'selected'; ?>>Deposit</option>
                                                            <option value="<?php echo $build_qs(['ftype'=>'withdrawal','pg'=>1]); ?>" <?php if($filter_key=='withdrawal') echo 'selected'; ?>>Withdrawal</option>
                                                            <option value="<?php echo $build_qs(['ftype'=>'smart','pg'=>1]); ?>" <?php if($filter_key=='smart') echo 'selected'; ?>>Smart Farming</option>
                                                            <option value="<?php echo $build_qs(['ftype'=>'reinvest','pg'=>1]); ?>" <?php if($filter_key=='reinvest') echo 'selected'; ?>>Reinvest</option>
                                                            <option value="<?php echo $build_qs(['ftype'=>'stocks','pg'=>1]); ?>" <?php if($filter_key=='stocks') echo 'selected'; ?>>Stocks</option>
                                                        </select>
                                                        <label>Transaction Type</label>
                                                    </div>
                                                </div>

                                                <!-- CSV Export -->
                                                <div class="col-12 col-md-6 col-lg-3 mb-3">
                                                    <div class="form-floating">
                                                        <?php $export_qs = $build_qs(['export'=>'csv', '_wpnonce'=>wp_create_nonce('wsi_tx_export')]); ?>
                                                        <a class="btn btn-theme mt-2" href="<?php echo $export_qs; ?>">Export CSV</a>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                </div>


                                <div class="card adminuiux-card mt-4 mb-0">
                                    <div class="card-body">

                                        <p class="text-secondary small mb-3">Tap any transaction to view full details.</p>

                                        <?php if ($txs): ?>
                                            <div class="wsi-tx-list">
                                                <?php foreach ($txs as $t):
                                                    $badge = 'success';
                                                    if ($t->type === 'withdraw_request') $badge = 'warning';
                                                    if ($t->type === 'smart_farm_interest') $badge = 'info';
                                                    if ($t->type === 'reinvest') $badge = 'primary';

                                                    $amount_class = ($t->amount >= 0) ? 'wsi-amount-up' : 'wsi-amount-down';
                                                    $desc = trim($t->description) !== '' ? $t->description : 'No description';
                                                ?>
                                                <details class="wsi-tx-card">
                                                    <summary>
                                                        <div>
                                                            <div class="wsi-tx-type">
                                                                <span class="badge badge-light rounded-pill text-bg-<?php echo $badge; ?>">
                                                                    <?php echo esc_html($t->type); ?>
                                                                </span>
                                                                <span class="wsi-tx-date"><?php echo esc_html($t->created_at); ?></span>
                                                            </div>
                                                            <div class="wsi-tx-amount <?php echo $amount_class; ?>">
                                                                $<?php echo number_format($t->amount, 2); ?>
                                                            </div>
                                                        </div>
                                                    </summary>
                                                    <div class="wsi-tx-body">
                                                        <div class="wsi-tx-row">
                                                            <span class="wsi-tx-label">Description</span>
                                                            <span class="wsi-tx-text"><?php echo esc_html($desc); ?></span>
                                                        </div>
                                                        <div class="wsi-tx-row">
                                                            <span class="wsi-tx-label">ID</span>
                                                            <span class="wsi-tx-text"><?php echo intval($t->id); ?></span>
                                                        </div>
                                                        <div class="wsi-tx-row">
                                                            <span class="wsi-tx-label">Date</span>
                                                            <span class="wsi-tx-text"><?php echo esc_html($t->created_at); ?></span>
                                                        </div>
                                                        <div class="wsi-tx-row">
                                                            <span class="wsi-tx-label">Amount</span>
                                                            <span class="wsi-tx-text <?php echo $amount_class; ?>">$<?php echo number_format($t->amount, 2); ?></span>
                                                        </div>
                                                    </div>
                                                </details>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php if ($total_pages > 1): 
                                                $prev_page = $page - 1;
                                                $next_page = $page + 1;
                                            ?>
                                            <div class="wsi-pagination">
                                                <?php if ($page > 1): ?>
                                                    <a href="<?php echo $build_qs(['pg' => $prev_page]); ?>">&laquo; Prev</a>
                                                <?php else: ?>
                                                    <span class="disabled">&laquo; Prev</span>
                                                <?php endif; ?>
                                                <span>Page <?php echo intval($page); ?> of <?php echo intval($total_pages); ?></span>
                                                <?php if ($page < $total_pages): ?>
                                                    <a href="<?php echo $build_qs(['pg' => $next_page]); ?>">Next &raquo;</a>
                                                <?php else: ?>
                                                    <span class="disabled">Next &raquo;</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-center mb-0">No transactions found.</p>
                                        <?php endif; ?>

                                    </div>
                                </div>


                            </div>
                        </div>

                    </main>

            </div>

            <!-- page footer -->
            <?php include_once "assets/inc/footer.php" ?>

                    <!-- Page Level js -->
                    </body>

</html>
