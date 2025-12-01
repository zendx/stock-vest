<?php

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/wsi/login/') );
    exit();
}

if (!defined('ABSPATH')) exit;
$wsi = plugins_url('assets/', __FILE__);

global $wpdb;

$t_hold   = $wpdb->prefix . 'wsi_holdings';
$t_stocks = $wpdb->prefix . 'wsi_stocks';
$uid      = get_current_user_id();

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
    ", $uid)
);
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

        /* Ensure dropdown stays aligned inside table */
        .no-caret { text-decoration: none; }
    </style>

    <script defer src="<?php echo plugin_dir_url(__FILE__) . 'assets/js/app435e.js?1096aad991449c8654b2'; ?>"></script>
    <link href="<?php echo plugin_dir_url(__FILE__) . 'assets/css/app435e.css?1096aad991449c8654b2'; ?>" rel="stylesheet">
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

        <!-- updates -->
        <div class="container-fluid mt-4 ">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="mb-0">Updates:</h6>
                    <p class="small text-secondary">Today <span class="text-danger">Live</span></p>
                </div>
                <div class="col-12 col-sm-10 col-xxl-11 py-2">
                    <div class="swiper swipernav">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide width-200">
                                <h6 class="mb-0 text-success">24,806.00</h6>
                                <p class="small"><span class="text-secondary">GIFTS NIFTYS:</span> <span class="text-success"><i class="bi bi-caret-up-fill"></i> 1.40%</span> </p>
                            </div>
                            <div class="swiper-slide width-200">
                                <h6 class="mb-0 text-success">41,118.13</h6>
                                <p class="small"><span class="text-secondary">Nikkies 2250:</span> <span class="text-success"><i class="bi bi-caret-up-fill"></i> 0.40%</span> </p>
                            </div>
                            <div class="swiper-slide width-200">
                                <h6 class="mb-0 text-danger">30,006.00</h6>
                                <p class="small"><span class="text-secondary">JOHN DOUES:</span> <span class="text-danger"><i class="bi bi-caret-down-fill"></i> 0.40%</span> </p>
                            </div>
                            <div class="swiper-slide width-200">
                                <h6 class="mb-0 text-success">90,105.00</h6>
                                <p class="small"><span class="text-secondary">Adminuiux Love:</span> <span class="text-success"><i class="bi bi-caret-up-fill"></i> 1.40%</span> </p>
                            </div>
                            <!-- repeat items as needed -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- content -->
        <div class="container mt-4" id="main-content">
            <div class="row">

                <!-- portfolio chart -->
                <div class="col-12 col-lg-6 col-xl-4 mb-4">
                    <!-- summary account -->
                    <div class="card adminuiux-card">
                        <div class="card-body pb-0">
                            <div class="avatar avatar-60 bg-theme-1-subtle text-theme-1 rounded mb-4">
                                <i class="bi bi-bar-chart-line h4"></i>
                            </div>
                            <h5 class="fw-medium">Your portfolio value is</h5>
                            <h1 class="fw-medium">$ 65.52k <span class="text-success fs-14"><i class="bi bi-arrow-up-short me-1"></i> 18.5%</span></h1>
                            <p class="text-secondary mb-4">Your portfolio has been grown to<br>$ 152.00 at 7% last week.</p>

                            <div class="row">
                                <div class="col">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <p class="text-secondary mb-2">Total Assets</p>
                                            <h4 class="fw-medium">$ 15.51k </h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <p class="text-secondary mb-2">Available Balance</p>
                                            <h4 class="fw-medium">$ 45.00k</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-12 col-lg-6 col-xl-8 mb-4">
                    <div class="card adminuiux-card">
                        <!-- chart section -->
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <nav aria-label="Page navigation example">
                                        <ul class="pagination pagination-sm justify-content-end mb-0">
                                            <li class="page-item"><a class="page-link" href="#">1D</a></li>
                                            <li class="page-item"><a class="page-link active" href="#">1W</a></li>
                                            <li class="page-item"><a class="page-link" href="#">1M</a></li>
                                            <li class="page-item"><a class="page-link" href="#">1Y</a></li>
                                            <li class="page-item"><a class="page-link" href="#">All</a></li>
                                        </ul>
                                    </nav>
                                </div>
                                <div class="col position-relative text-end">
                                    <input type="text" class="form-control d-inline-block w-auto align-middle mx-3" id="daterangepicker">
                                    <button class="btn btn-square btn-theme d-inline-block align-middle" onclick="$(this).prev().click()">
                                        <i data-feather="calendar"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="w-100 height-270">
                                <canvas id="summarychart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assets funds and shares -->
                <div class="col-12">
                    <div class="card adminuiux-card mb-4">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6>My Available Stocks Holdings</h6>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">

                            <?php if (empty($holdings)) { ?>
                                <p>No holdings.</p>
                            <?php } else { ?>

                                <table class="table mb-0" data-show-toggle="true" id="dataTable">
                                    <thead>
                                        <tr>
                                            <th>Stock</th>
                                            <th>Price</th>
                                            <th data-breakpoints="xs">Holding</th>
                                            <th data-breakpoints="xs sm">Profit/Loss</th>
                                            <th data-breakpoints="xs">Today's Trend</th>
                                            <th>% Change</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($holdings as $h): ?>

                                            <?php
                                                // Safe fallbacks (prevent undefined property warnings)
                                                $h->image = $h->image ?? '';
                                                $h->current_price = isset($h->current_price) ? (float)$h->current_price : (float)($h->price ?? 0);
                                                $h->last_price = isset($h->last_price) ? (float)$h->last_price : $h->current_price;
                                                $h->shares = $h->shares ?? 0;
                                                $h->invested_amount = $h->invested_amount ?? 0;
                                                $h->accumulated_profit = $h->accumulated_profit ?? 0;
                                                $h->profit_percent = $h->profit_percent ?? 0;
                                                $h->today_change = $h->today_change ?? 0;
                                                $h->previous_price = $h->previous_price ?? $h->last_price; // prev value for animation
                                                $h->previous_profit = $h->previous_profit ?? $h->accumulated_profit;
                                                $h->yesterday_change = $h->yesterday_change ?? ($h->today_change - 0);
                                            ?>

                                            <tr>
                                                <!-- STOCK + IMAGE -->
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-40 rounded coverimg me-2">
                                                            <?php if (!empty($h->image)) : ?>
                                                                <img src="<?php echo esc_url($h->image); ?>" alt="<?php echo esc_attr($h->name); ?>">
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <p class="mb-0"><?php echo esc_html($h->name); ?></p>
                                                        </div>
                                                    </div>
                                                </td>



                                                <!-- PRICE -->
                                                <td>
                                                    <p class="mb-0">
                                                        $<span class="price-animate number-fade animate-number"
                                                                data-prev="<?php echo esc_attr(number_format((float)$h->previous_price, 2, '.', '')); ?>"
                                                                data-start="<?php echo esc_attr(number_format((float)$h->previous_price, 2, '.', '')); ?>"
                                                                data-end="<?php echo esc_attr(number_format((float)$h->current_price, 2, '.', '')); ?>"
                                                                data-duration="700">
                                                            <?php echo number_format((float)$h->current_price, 2); ?>
                                                        </span>
                                                    </p>
                                                    <p class="small">
                                                        <span class="text-secondary">LTP:</span>
                                                        <span class="price-animate number-fade animate-number"
                                                              data-prev="<?php echo esc_attr(number_format((float)$h->last_price, 2, '.', '')); ?>"
                                                              data-start="<?php echo esc_attr(number_format((float)$h->last_price, 2, '.', '')); ?>"
                                                              data-end="<?php echo esc_attr(number_format((float)$h->last_price, 2, '.', '')); ?>"
                                                              data-duration="600">
                                                            <?php echo number_format((float)$h->last_price, 2); ?>
                                                        </span>
                                                    </p>
                                                </td>

                                                <!-- HOLDING -->
                                                <td>
                                                    <p class="mb-0"><?php echo esc_html($h->shares); ?> units</p>
                                                    <p class="small">
                                                        <span class="text-secondary">Invested:</span>
                                                        $<?php echo number_format((float)$h->invested_amount, 2); ?>
                                                    </p>
                                                </td>

                                                <!-- PROFIT / LOSS -->
                                                <td>
                                                    <?php
                                                        $profit = (float)$h->accumulated_profit;
                                                        $profit_pct = (float)$h->profit_percent;
                                                        $profit_class = $profit >= 0 ? "text-success" : "text-danger";
                                                        $profit_icon  = $profit >= 0 ? "bi-caret-up-fill" : "bi-caret-down-fill";
                                                    ?>
                                                    <p class="mb-0 <?php echo $profit_class; ?>">
                                                        <i class="bi <?php echo $profit_icon; ?>"></i>
                                                        <span class="price-animate number-fade animate-number"
                                                              data-prev="<?php echo esc_attr(number_format((float)$h->previous_profit, 2, '.', '')); ?>"
                                                              data-start="<?php echo esc_attr(number_format((float)$h->previous_profit, 2, '.', '')); ?>"
                                                              data-end="<?php echo esc_attr(number_format($profit_pct, 2, '.', '')); ?>"
                                                              data-duration="700">
                                                            <?php echo number_format($profit_pct, 2); ?>%
                                                        </span>
                                                    </p>
                                                    <p class="small">
                                                        <span class="text-secondary">Profit:</span>
                                                        <span class="price-animate number-fade animate-number"
                                                              data-prev="<?php echo esc_attr(number_format((float)$h->previous_profit, 2, '.', '')); ?>"
                                                              data-start="<?php echo esc_attr(number_format((float)$h->previous_profit, 2, '.', '')); ?>"
                                                              data-end="<?php echo esc_attr(number_format($profit, 2, '.', '')); ?>"
                                                              data-duration="800">
                                                            $<?php echo number_format($profit, 2); ?>
                                                        </span>
                                                    </p>
                                                </td>

                                                <!-- TREND -->
                                                <td>
                                                    <?php
                                                        $trend = $h->trend ?? 'Neutral';
                                                        $trend_class = ($trend === 'Bullish') ? "text-success" : (($trend === 'Bearish') ? "text-danger" : "text-secondary");
                                                        $trend_icon  = ($trend === 'Bullish') ? "bi-graph-up-arrow" : (($trend === 'Bearish') ? "bi-graph-down-arrow" : "bi-dash");
                                                    ?>
                                                    <p class="mb-0 <?php echo $trend_class; ?>">
                                                        <i class="bi <?php echo $trend_icon; ?>"></i>
                                                        <?php echo esc_html($trend); ?>
                                                    </p>
                                                </td>

                                                <!-- TODAY % CHANGE -->
                                                <td>
                                                    <p class="mb-0 <?php echo $profit_class; ?>">
                                                        <i class="bi <?php echo $profit_icon; ?>"></i>
                                                        <span class="price-animate number-fade animate-number"
                                                              data-prev="<?php echo esc_attr(number_format((float)$h->yesterday_change, 2, '.', '')); ?>"
                                                              data-start="<?php echo esc_attr(number_format((float)$h->yesterday_change, 2, '.', '')); ?>"
                                                              data-end="<?php echo esc_attr(number_format((float)$h->today_change, 2, '.', '')); ?>"
                                                              data-duration="700">
                                                            <?php echo number_format((float)$h->today_change, 2); ?>%
                                                        </span>
                                                    </p>
                                                </td>

                                                <!-- ACTIONS -->
                                                <td>
                                                    <!-- Invest button -->
                                                    <button class="btn btn-sm btn-outline-success">Invest</button>

                                                    <!-- Sell button -->
                                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="d-inline">
                                                        <input type="hidden" name="action" value="wsi_sell_holding">
                                                        <?php wp_nonce_field('wsi_sell_holding_nonce'); ?>
                                                        <input type="hidden" name="holding_id" value="<?php echo intval($h->id); ?>">
                                                        <button class="btn btn-sm btn-outline-danger">Sell</button>
                                                    </form>

                                                    <!-- Extra dropdown -->
                                                    <div class="dropdown d-inline-block">
                                                        <a class="btn btn-link btn-square no-caret" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Favorite</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">View Chart</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Company Events</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>
                                </table>

                            <?php } ?>

                        </div>
                    </div>
                </div>


            </div>
        </div>
    </main>

    </div>

    <!-- page footer -->
    <?php include_once "assets/inc/footer.php" ?>

    <!-- Page Level js -->
    <script src="assets/js/investment/investment-company-shares.js"></script>

    <!-- --- Animation JS: number count + flash highlight --- -->
    <script>
    (function () {
        'use strict';

        // Smooth numeric animator: from start -> end (numbers as floats), duration in ms
        function animateNumberElem(elem, start, end, duration) {
            start = parseFloat(start);
            end = parseFloat(end);
            duration = parseInt(duration, 10) || 600;

            if (isNaN(start) || isNaN(end) || start === end) {
                // Still apply flash logic based on dataset-prev if available
                return;
            }

            var startTime = null;
            function step(timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = Math.min((timestamp - startTime) / duration, 1);
                var current = start + (end - start) * easeOutCubic(progress);
                // format with 2 decimals
                elem.textContent = current.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            }
            window.requestAnimationFrame(step);
        }

        // Easing (nice feel)
        function easeOutCubic(t) {
            return (--t) * t * t + 1;
        }

        // Add flash class (price-up / price-down), remove after timeout
        function flashIfChanged(elem, prev, curr) {
            prev = parseFloat(prev);
            curr = parseFloat(curr);

            if (isNaN(prev) || isNaN(curr) || prev === curr) return;

            if (curr > prev) {
                elem.classList.add('price-up');
                setTimeout(function () { elem.classList.remove('price-up'); }, 900);
            } else if (curr < prev) {
                elem.classList.add('price-down');
                setTimeout(function () { elem.classList.remove('price-down'); }, 900);
            }
        }

        // Initialize all animate-number elements
        function runAnimations() {
            var elems = document.querySelectorAll('.animate-number');

            elems.forEach(function (el) {
                // data attributes: data-prev, data-start, data-end, data-duration
                var prev = el.getAttribute('data-prev');
                var start = el.getAttribute('data-start') ?? prev ?? el.textContent;
                var end = el.getAttribute('data-end') ?? el.textContent;
                var duration = el.getAttribute('data-duration') ?? 700;

                // remove any thousands separators before parsing
                var prevNum = parseFloat(String(prev || '').replace(/[^0-9.\-]/g, ''));
                var startNum = parseFloat(String(start || '').replace(/[^0-9.\-]/g, ''));
                var endNum = parseFloat(String(end || '').replace(/[^0-9.\-]/g, ''));

                // run numeric animation
                if (!isNaN(startNum) && !isNaN(endNum) && startNum !== endNum) {
                    // temporarily set to start value to animate from
                    el.textContent = startNum.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    animateNumberElem(el, startNum, endNum, Number(duration));
                }

                // flash highlight based on prev vs end
                if (!isNaN(prevNum) && !isNaN(endNum)) {
                    flashIfChanged(el, prevNum, endNum);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            runAnimations();

            // OPTIONAL: if you later fetch live updates via AJAX, call runAnimations() afterwards on updated DOM
            // Example: after updating the innerText and data-prev of elements, call runAnimations() again to animate change.
        });
    })();
    </script>

</body>

</html>
