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

                        <!-- content -->
                        <div class="container" id="main-content">
                            <!-- filter area -->
                            <div class="collapse" id="filterschedule">
                                <div class="card adminuiux-card mt-4">
                                    <div class="card-body pb-0">
                                        <div class="row">
                                            <div class="col-12 col-md-6 col-lg-3 mb-3">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" placeholder="Search...">
                                                    <label>Search...</label>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-3 mb-3">
                                                <div class="form-floating">
                                                    <select class="form-select">
                                                        <option selected>All</option>
                                                        <option value="1">My Self</option>
                                                        <option value="2">Agent</option>
                                                        <option value="2">Users</option>
                                                    </select>
                                                    <label>User Role</label>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-3 mb-3">
                                                <div class="form-floating">
                                                    <select class="form-select">
                                                        <option selected>All</option>
                                                        <option value="1">Completed</option>
                                                        <option value="2">Requested</option>
                                                        <option value="3">Pending</option>
                                                        <option value="4">Error</option>
                                                        <option value="5">Waiting</option>
                                                        <option value="6">Blocked</option>
                                                    </select>
                                                    <label>Status</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- appointment grid view list datatable-->
                            <div class="card adminuiux-card mt-4 mb-0">
                                <div class="card-body">
                                    <!-- data table -->
                                    <table id="dataTable" class="table w-100 nowrap">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th class="all">Date and Time</th>
                                                <th data-breakpoints="xs sm">User Name</th>
                                                <th data-breakpoints="xs sm md">Contact info</th>
                                                <th data-breakpoints="xs sm">Amount</th>
                                                <th data-breakpoints="xs sm">Status</th>
                                                <th class="all">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>2054ID</td>
                                                <td>
                                                    <p class="mb-0">25-12-2024</p>
                                                    <p class="text-secondary small">08:30 PM</p>
                                                </td>
                                                <td>
                                                    <div class="row align-items-center flex-nowrap">
                                                        <div class="col-auto">
                                                            <div class="avatar avatar-40 mb-0 rounded-circle bg-red text-white">
                                                                <span class="h6">MD</span>
                                                            </div>
                                                        </div>
                                                        <div class="col ps-0">
                                                            <p class="mb-0 fw-medium">Mc. Doweelds</p>
                                                            <p class="text-secondary small">Storefront, United Kingdom</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="mb-0">info@maxd..core.com</p>
                                                    <p class="text-secondary small">+44 846655****1154</p>
                                                </td>
                                                <td>
                                                    <h6>$ 110.00</h6>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light rounded-pill text-bg-success">Completed</span>
                                                </td>
                                                <td>
                                                    <a href="investment-myprofile.html" class="btn btn-square btn-link" data-bs-toggle="tooltip" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <div class="dropdown d-inline-block">
                                                        <a class="btn btn-link no-caret" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Edit</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Move</a></li>
                                                            <li><a class="dropdown-item theme-red" href="javascript:void(0)">Delete</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>105ID</td>
                                                <td>
                                                    <p class="mb-0">22-12-2024</p>
                                                    <p class="text-secondary small">9:00 PM</p>
                                                </td>
                                                <td>
                                                    <div class="row align-items-center flex-nowrap">
                                                        <div class="col-auto">
                                                            <figure class="avatar avatar-40 mb-0 coverimg rounded-circle">
                                                                <img src="assets/img/modern-ai-image/user-8.jpg" alt="">
                                                            </figure>
                                                        </div>
                                                        <div class="col ps-0">
                                                            <p class="mb-0 fw-medium">Winnie John</p>
                                                            <p class="text-secondary small">18 years, Australia</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="mb-0">winnie@sales..core.com</p>
                                                    <p class="text-secondary small">+44 8466585****1154</p>
                                                </td>
                                                <td>
                                                    <h6>$ 63.00</h6>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light rounded-pill text-bg-success">Completed</span>
                                                </td>
                                                <td><a href="investment-myprofile.html" class="btn btn-square btn-link" data-bs-toggle="tooltip" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <div class="dropdown d-inline-block">
                                                        <a class="btn btn-link no-caret" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Edit</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Move</a></li>
                                                            <li><a class="dropdown-item theme-red" href="javascript:void(0)">Delete</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>058ID</td>
                                                <td>
                                                    <p class="mb-0">22-12-2024</p>
                                                    <p class="text-secondary small">07:15 PM</p>
                                                </td>
                                                <td>
                                                    <div class="row align-items-center flex-nowrap">
                                                        <div class="col-auto">
                                                            <figure class="avatar avatar-40 mb-0 coverimg rounded-circle">
                                                                <img src="assets/img/modern-ai-image/user-1.jpg" alt="">
                                                            </figure>
                                                        </div>
                                                        <div class="col ps-0">
                                                            <p class="mb-0 fw-medium">Alicia Smith</p>
                                                            <p class="text-secondary small">30 years, United States</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="mb-0">alicia@sales..core.com</p>
                                                    <p class="text-secondary small">+44 8466585****1154</p>
                                                </td>
                                                <td>
                                                    <h6>$ 75.00</h6>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light rounded-pill text-bg-success">Completed</span>
                                                </td>
                                                <td><a href="investment-myprofile.html" class="btn btn-square btn-link" data-bs-toggle="tooltip" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <div class="dropdown d-inline-block">
                                                        <a class="btn btn-link no-caret" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Edit</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Move</a></li>
                                                            <li><a class="dropdown-item theme-red" href="javascript:void(0)">Delete</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>500ID</td>
                                                <td>
                                                    <p class="mb-0">21-12-2024</p>
                                                    <p class="text-secondary small">01:15 PM</p>
                                                </td>
                                                <td>
                                                    <div class="row align-items-center flex-nowrap">
                                                        <div class="col-auto">
                                                            <div class="avatar avatar-40 mb-0 rounded-circle bg-purple text-white">
                                                                <span class="h6">JG</span>
                                                            </div>
                                                        </div>
                                                        <div class="col ps-0">
                                                            <p class="mb-0 fw-medium">JJohnson Bags</p>
                                                            <p class="text-secondary small">eCommerce, United States</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="mb-0">sales@JJohnso..led.com</p>
                                                    <p class="text-secondary small">+44 8466585****1154</p>
                                                </td>
                                                <td>
                                                    <h6>$ 65.00</h6>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light rounded-pill text-bg-danger">Error</span>
                                                    <button class="btn btn-link btn-square" type="button" data-bs-toggle="tooltip" title="Retry">
                                                        <i class="bi bi-arrow-clockwise"></i>
                                                    </button>
                                                </td>
                                                <td><a href="investment-myprofile.html" class="btn btn-square btn-link" data-bs-toggle="tooltip" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <div class="dropdown d-inline-block">
                                                        <a class="btn btn-link no-caret" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Edit</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Retry</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Move</a></li>
                                                            <li><a class="dropdown-item theme-red" href="javascript:void(0)">Delete</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>2054ID</td>
                                                <td>
                                                    <p class="mb-0">20-12-2024</p>
                                                    <p class="text-secondary small">08:18 PM</p>
                                                </td>
                                                <td>
                                                    <div class="row align-items-center flex-nowrap">
                                                        <div class="col-auto">
                                                            <figure class="avatar avatar-40 mb-0 coverimg rounded-circle">
                                                                <img src="assets/img/modern-ai-image/user-3.jpg" alt="">
                                                            </figure>
                                                        </div>
                                                        <div class="col ps-0">
                                                            <p class="mb-0 fw-medium">David Warner</p>
                                                            <p class="text-secondary small">30 years, United States</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="mb-0">david@sales..core.com</p>
                                                    <p class="text-secondary small">+44 8466585****1154</p>
                                                </td>
                                                <td>
                                                    <h6>$ 84.00</h6>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light rounded-pill text-bg-warning">Pending</span>
                                                </td>
                                                <td><a href="investment-myprofile.html" class="btn btn-square btn-link" data-bs-toggle="tooltip" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <div class="dropdown d-inline-block">
                                                        <a class="btn btn-link no-caret" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Edit</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Move</a></li>
                                                            <li><a class="dropdown-item theme-red" href="javascript:void(0)">Delete</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>105ID</td>
                                                <td>
                                                    <p class="mb-0">20-12-2024</p>
                                                    <p class="text-secondary small">05:07 PM</p>
                                                </td>
                                                <td>
                                                    <div class="row align-items-center flex-nowrap">
                                                        <div class="col-auto">
                                                            <div class="avatar avatar-40 mb-0 rounded-circle bg-theme-1 text-white">
                                                                <span class="h6">WJ</span>
                                                            </div>
                                                        </div>
                                                        <div class="col ps-0">
                                                            <p class="mb-0 fw-medium">Winnie John</p>
                                                            <p class="text-secondary small">15 years, Australia</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="mb-0">winnie@sales..core.com</p>
                                                    <p class="text-secondary small">+44 8466585****1154</p>
                                                </td>
                                                <td>
                                                    <h6>$ 65.00</h6>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light rounded-pill text-bg-info">Pending</span>
                                                </td>
                                                <td><a href="investment-myprofile.html" class="btn btn-square btn-link" data-bs-toggle="tooltip" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <div class="dropdown d-inline-block">
                                                        <a class="btn btn-link no-caret" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Edit</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Move</a></li>
                                                            <li><a class="dropdown-item theme-red" href="javascript:void(0)">Delete</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>058ID</td>
                                                <td>
                                                    <p class="mb-0">19-12-2024</p>
                                                    <p class="text-secondary small">11:30 AM</p>
                                                </td>
                                                <td>
                                                    <div class="row align-items-center flex-nowrap">
                                                        <div class="col-auto">
                                                            <figure class="avatar avatar-40 mb-0 coverimg rounded-circle">
                                                                <img src="assets/img/modern-ai-image/user-5.jpg" alt="">
                                                            </figure>
                                                        </div>
                                                        <div class="col ps-0">
                                                            <p class="mb-0 fw-medium">Alicia Smith</p>
                                                            <p class="text-secondary small">21 years, United States</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="mb-0">alicia@sales..core.com</p>
                                                    <p class="text-secondary small">+44 8466585****1154</p>
                                                </td>
                                                <td>
                                                    <h6>$ 15.00</h6>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light rounded-pill text-bg-success">Completed</span>
                                                </td>
                                                <td><a href="investment-myprofile.html" class="btn btn-square btn-link" data-bs-toggle="tooltip" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <div class="dropdown d-inline-block">
                                                        <a class="btn btn-link no-caret" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Edit</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Move</a></li>
                                                            <li><a class="dropdown-item theme-red" href="javascript:void(0)">Delete</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>501ID</td>
                                                <td>
                                                    <p class="mb-0">19-12-2024</p>
                                                    <p class="text-secondary small">08:30 AM</p>
                                                </td>
                                                <td>
                                                    <div class="row align-items-center flex-nowrap">
                                                        <div class="col-auto">
                                                            <figure class="avatar avatar-40 mb-0 coverimg rounded-circle">
                                                                <img src="assets/img/modern-ai-image/user-6.jpg" alt="">
                                                            </figure>
                                                        </div>
                                                        <div class="col ps-0">
                                                            <p class="mb-0 fw-medium">Jr. Sham Co</p>
                                                            <p class="text-secondary small">45 years, United Kingdom</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="mb-0">cheli@sales..core.com</p>
                                                    <p class="text-secondary small">+44 8466585****1154</p>
                                                </td>
                                                <td>
                                                    <h6>$ 212.00</h6>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light rounded-pill text-bg-success">Completed</span>
                                                </td>
                                                <td><a href="investment-myprofile.html" class="btn btn-square btn-link" data-bs-toggle="tooltip" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <div class="dropdown d-inline-block">
                                                        <a class="btn btn-link no-caret" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Edit</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Move</a></li>
                                                            <li>
                                                                <a class="dropdown-item theme-red" href="javascript:void(0)">Delete</a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>3052ID</td>
                                                <td>
                                                    <p class="mb-0">19-12-2024</p>
                                                    <p class="text-secondary small">12:46 PM</p>
                                                </td>
                                                <td>
                                                    <div class="row align-items-center flex-nowrap">
                                                        <div class="col-auto">
                                                            <figure class="avatar avatar-40 mb-0 coverimg rounded-circle">
                                                                <img src="assets/img/modern-ai-image/user-7.jpg" alt="">
                                                            </figure>
                                                        </div>
                                                        <div class="col ps-0">
                                                            <p class="mb-0 fw-medium">David Warner</p>
                                                            <p class="text-secondary small">55 years, United Kingdom</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="mb-0">david@sales..core.com</p>
                                                    <p class="text-secondary small">+44 8466585****1154</p>
                                                </td>
                                                <td>
                                                    <h6>$ 180.00</h6>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light rounded-pill text-bg-success">Completed</span>
                                                </td>
                                                <td><a href="investment-view-patient.html" class="btn btn-square btn-link" data-bs-toggle="tooltip" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <div class="dropdown d-inline-block">
                                                        <a class="btn btn-link no-caret" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Edit</a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)">Move</a></li>
                                                            <li><a class="dropdown-item theme-red" href="javascript:void(0)">Delete</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
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