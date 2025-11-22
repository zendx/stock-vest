<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->

<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | User Withdrawal</title>
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

<script defer src="assets/js/app435e.js?1096aad991449c8654b2"></script><link href="assets/css/app435e.css?1096aad991449c8654b2" rel="stylesheet"></head>

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
                                            <li class="breadcrumb-item active bi" aria-current="page">Withdrawal</li>
                                        </ol>
                                    </nav>
                                    <h5>Withdrawal</h5>
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

                                <div class="col-12 ">
                                    <div class="row">
                                        <div class="col-12 col-lg-8 mb-4">
                                            <!-- create fixed deposit -->
                                            <div class="card adminuiux-card">
                                                <div class="card-header">
                                                    <h5>Create Deposit @ 7.00% </h5>
                                                    <p class="text-secondary">Start your money growing with smart investment</p>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row mb-2">
                                                        <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                            <div class="form-floating">
                                                                <input type="text" class="form-control" id="amount" placeholder="Amount" value="100">
                                                                <label for="amount">Investment ($)</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                            <div class="form-floating">
                                                                <select class="form-select" id="typeinvestment">
                                                                    <option>Fixed Deposit</option>
                                                                    <option selected>Recurring Deposit</option>
                                                                </select>
                                                                <label for="typeinvestment">Type of Investment</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                            <div class="form-floating">
                                                                <select class="form-select" id="frequency1">
                                                                    <option selected>Monthly</option>
                                                                    <option>Quarterly</option>
                                                                    <option>Yearly</option>
                                                                </select>
                                                                <label for="frequency1">Deposit Frequency</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                            <div class="form-floating">
                                                                <select class="form-select" id="depositday">
                                                                    <option selected>1st</option>
                                                                    <option>2nd</option>
                                                                    <option>3rd</option>
                                                                </select>
                                                                <label for="depositday">Deposit on day</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                            <div class="form-floating">
                                                                <input type="text" class="form-control" id="duration" placeholder="Months" value="12">
                                                                <label for="duration">Duration (months)</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                            <div class="form-floating">
                                                                <select class="form-select" id="maturity">
                                                                    <option>Interest Monthly</option>
                                                                    <option>Interest Quarterly</option>
                                                                    <option>Interest Half Yearly</option>
                                                                    <option>Interest Yearly</option>
                                                                    <option selected>All on Maturity</option>
                                                                </select>
                                                                <label for="maturity">Withdraw</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-center">
                                                        <div class="col">
                                                            <h5>$ 1246.50</h5>
                                                            <p class="text-secondary small">Amount will be credited to wallet on maturity</p>
                                                        </div>
                                                        <div class="col-auto">
                                                            <button class="btn btn-theme">Create Deposit</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-4 mb-4">
                                            <!-- offer -->
                                            <div class="card adminuiux-card position-relative overflow-hidden bg-theme-1 h-100">
                                                <div class="position-absolute top-0 start-0 h-100 w-100 z-index-0 coverimg opacity-50">
                                                    <img src="assets/img/modern-ai-image/flamingo-4.jpg" alt="">
                                                </div>
                                                <div class="card-body z-index-1">
                                                    <div class="avatar avatar-60 rounded bg-white-opacity text-white mb-4">
                                                        <i class="bi bi-tags h4"></i>
                                                    </div>
                                                    <h2>Great Offer!</h2>
                                                    <h4 class="fw-medium">You have <b>LOAN</b> of <b>$ 800000.00</b> offer from HSBCD Bank</h4>
                                                    <p class="mb-4">No documentation required...</p>
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
            <?php include_once "assets/inc/footer.php" ?>
                    </body>

</html>