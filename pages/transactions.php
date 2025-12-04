<?php

if (!defined('ABSPATH')) exit;

// Get the plugin assets URL
$PLUGIN_ASSETS = plugins_url('assets/', dirname(dirname(__FILE__)) . '/stock-vest.php');
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
                            $per_page = 20;
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


                                <!-- ========================= -->
                                <!-- DATA TABLE WRAPPER -->
                                <!-- ========================= -->
                                <div class="card adminuiux-card mt-4 mb-0">
                                    <div class="card-body">

                                        <table id="dataTable" class="table w-100 nowrap">
                                            <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Type</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                            </tr>
                                            </thead>

                                            <tbody>

                                            <?php if ($txs): ?>
                                                <?php foreach ($txs as $t): ?>

                                                    <?php
                                                    // Color status based on type
                                                    $badge = 'success';
                                                    if ($t->type === 'withdraw_request') $badge = 'warning';
                                                    if ($t->type === 'smart_farm_interest') $badge = 'info';
                                                    if ($t->type === 'reinvest') $badge = 'primary';
                                                    ?>

                                                    <tr>
                                                        <td>
                                                            <p class="mb-0"><?php echo esc_html($t->created_at); ?></p>
                                                        </td>

                                                        <td><h6>$<?php echo number_format($t->amount, 2); ?></h6></td>

                                                        <td>
                                                            <span class="badge badge-light rounded-pill text-bg-<?php echo $badge; ?>">
                                                                <?php echo esc_html($t->type); ?>
                                                            </span>
                                                        </td>

                                                        <td><?php echo esc_html($t->description); ?></td>

                                                        <td>
                                                            <a href="#" class="btn btn-square btn-link" data-bs-toggle="tooltip" title="View"><i class="bi bi-eye"></i></a>
                                                        </td>
                                                    </tr>

                                                <?php endforeach; ?>

                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No transactions found.</td>
                                                </tr>
                                            <?php endif; ?>

                                            </tbody>
                                        </table>

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