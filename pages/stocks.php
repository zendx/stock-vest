    <?php
    if (!defined('ABSPATH')) exit;


    //Load Stocks Table
    global $wpdb;

    $t_stocks = $wpdb->prefix . 'wsi_stocks';

    $stocks = $wpdb->get_results("
        SELECT * FROM $t_stocks 
        WHERE active = 1 
        ORDER BY id DESC
    ");

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
                                                <li class="breadcrumb-item bi"><a href="investment-dashboard.html"><i class="bi bi-house-door me-1 fs-14"></i> Dashboard</a></li>
                                                <li class="breadcrumb-item active bi" aria-current="page">Stocks</li>
                                            </ol>
                                        </nav>
                                        <h5>Stocks</h5>
                                    </div>
                                    <div class="col-12 col-sm-auto text-end py-3 py-sm-0">

                                    </div>
                                </div>
                            </div>

                            <!-- Content  -->
                            <div class="container mt-4" id="main-content">

                                <div class="row">
                                    <div class="col-12 col-md-12 col-xl-9">

                                        <h5 class="mb-4 text-center">Available Stocks</h5>

                                        <?php if (empty($stocks)) : ?>

                                            <p class="text-center">No stocks available yet.</p>

                                        <?php else : ?>

                                            <?php foreach ($stocks as $s) : ?>

                                                <div class="card adminuiux-card mb-3">
                                                    <div class="card-body">
                                                        <div class="row align-items-center">

                                                            <!-- IMAGE + NAME + BADGES -->
                                                            <div class="col-12 col-sm-9 col-xxl mb-3 mb-xxl-0">
                                                                <div class="row align-items-center">
                                                                    <div class="col-auto">
                                                                        <div class="avatar avatar-60 rounded coverimg">
                                                                            <?php if (!empty($s->image)) : ?>
                                                                                <img src="<?php echo esc_url($s->image); ?>" alt="">
                                                                            <?php else : ?>
                                                                                <img src="https://via.placeholder.com/60" alt="">
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col">
                                                                        <h6><?php echo esc_html($s->name); ?></h6>

                                                                        <span class="badge badge-sm badge-light text-bg-theme-1">
                                                                            <?php echo esc_html($s->rate_period ?? "N/A"); ?>
                                                                        </span>

                                                                        <span class="badge badge-sm badge-light text-bg-success mx-1">
                                                                            Rate: <?php echo esc_html($s->rate_percent ?? "0"); ?>%
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- "PRICE: YOU WILL GIVE" -->
                                                            <div class="col-12 col-sm-3 col-xxl-auto mb-3 mb-sm-0">
                                                                <h6>$<?php echo number_format($s->price, 2); ?></h6>
                                                                <p class="text-secondary small">Price</p>
                                                            </div>

                                                            <!-- EXPECTED RETURN BOX -->
                                                            <div class="col-12 col-md-9 col-xxl-4 mb-3 mb-md-0">
                                                                <div class="card">
                                                                    <div class="card-body">
                                                                        <div class="row align-items-center justify-content-between">

                                                                            <div class="col-auto text-start">
                                                                                <h6 class="mb-1">
                                                                                    <?php echo esc_html($s->rate_percent ?? "0"); ?>%
                                                                                    <small>
                                                                                        <span class="badge badge-sm badge-light text-bg-success mx-1 fw-normal">
                                                                                            ROE
                                                                                        </span>
                                                                                    </small>
                                                                                </h6>
                                                                                <p class="text-secondary small">Expected Return</p>
                                                                            </div>

                                                                            <div class="col-auto text-end">
                                                                                <h6>
                                                                                    $<?php echo number_format(($s->price * ($s->rate_percent / 100)), 2); ?>
                                                                                </h6>
                                                                                <p class="text-secondary small">Est.</p>
                                                                            </div>

                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- BUY BUTTON -->
                                                            <div class="col-auto">
                                                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                                                    <input type="hidden" name="action" value="wsi_buy_stock">
                                                                    <?php wp_nonce_field('wsi_buy_stock_nonce'); ?>
                                                                    <input type="hidden" name="stock_id" value="<?php echo intval($s->id); ?>">
                                                                    <button class="btn btn-outline-theme" type="submit">Buy</button>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>

                                            <?php endforeach; ?>

                                        <?php endif; ?>

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