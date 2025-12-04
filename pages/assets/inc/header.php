<?php
$user_id = get_current_user_id();
$current_user = wp_get_current_user();
$first_name = trim(get_user_meta($user_id, 'first_name', true));
$last_name = trim(get_user_meta($user_id, 'last_name', true));
$full_name = trim($first_name . ' ' . $last_name);

if ($full_name === '') {
    $full_name = trim($current_user->display_name);
}

if ($full_name === '') {
    $full_name = trim($current_user->user_login);
}

$display_name = $first_name !== '' ? $first_name : $full_name;

if (!function_exists('wsi_get_user_initials')) {
    function wsi_get_user_initials($name)
    {
        $name = trim($name);
        $initials = '';

        foreach (preg_split('/\s+/', $name) as $part) {
            if ($part === '') {
                continue;
            }
            $initials .= strtoupper(substr($part, 0, 1));
            if (strlen($initials) >= 2) {
                break;
            }
        }

        if ($initials === '' && $name !== '') {
            $initials = strtoupper(substr($name, 0, 2));
        }

        return $initials !== '' ? $initials : '?';
    }
}

$user_initials = wsi_get_user_initials($full_name);
$assets = wsi_get_main($user_id);
if (empty($assets)) {
    $assets = 0;
}
?>

<!-- Page Loader -->
<div class="pageloader">
    <div class="container h-100">
        <div class="row justify-content-center align-items-center text-center h-100">
            <div class="col-12 mb-auto pt-4"></div>
            <div class="col-auto">
                <img src="<?php echo $wsi; ?>img/logo-main.png" alt="" class="height-60 mb-3">
                <p class="h6 mb-0">COFCO CAPITAL</p>
                <p class="h3 mb-4">LOADING</p>
                <div class="loader10 mb-2 mx-auto"></div>
            </div>
            <div class="col-12 mt-auto pb-4">
                <p class="text-secondary">Please wait we are preparing awesome things to preview...</p>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<header class="adminuiux-header">
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">

            <!-- Sidebar toggle -->
            <button class="btn btn-link btn-square sidebar-toggler" type="button" onclick="initSidebar()">
                <i class="sidebar-svg" data-feather="menu"></i>
            </button>

            <!-- Logo -->
            <a class="navbar-brand" href="../dashboard">
                <img data-bs-img="light" src="<?php echo $wsi; ?>img/logo-main.png" alt="">
                <img data-bs-img="dark" src="<?php echo $wsi; ?>img/logo-white.png" alt="">
            </a>

            <!-- Right section -->
            <div class="ms-auto">

                <!-- Dark mode toggle -->
                <button class="btn btn-link btn-square btnsunmoon btn-link-header" id="btn-layout-modes-dark-page">
                    <i class="sun mx-auto" data-feather="sun"></i>
                    <i class="moon mx-auto" data-feather="moon"></i>
                </button>

                <!-- Notifications -->
                <!--div class="dropdown d-inline-block">
                    <button class="btn btn-link btn-square btn-icon btn-link-header dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown">
                        <i data-feather="bell"></i>
                            <span class="position-absolute top-0 end-0 badge rounded-pill bg-danger p-1">
                            <small>9+</small>
                            </span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end notification-dd sm-mi-95px">
                        <li>
                            <a class="dropdown-item p-2" href="#">
                                            <div class="row gx-3">
                                                <div class="col-auto">
                                        <figure class="avatar avatar-40 rounded-circle bg-pink">
                                            <i class="bi bi-gift text-white"></i>
                                                    </figure>
                                                </div>
                                                <div class="col">
                                        <p class="mb-2 small">
                                            Congratulations! Your property <span class="fw-bold">#H10215</span> has reached 1000 views.
                                        </p>
                                        <span class="row">
                                            <span class="col">
                                                <span class="badge badge-light rounded-pill text-bg-warning small">Directory</span>
                                            </span>
                                            <span class="col-auto small opacity-75">1:00 am</span>
                                        </span>
                                            </div>
                                        </div>
                            </a>
                        </li>

                        <li>
                            <button class="btn btn-link text-center" onclick="notifcationAll()">
                                View all <i class="bi bi-arrow-right fs-14"></i>
                            </button>
                        </li>
                    </ul>
                </div-->

                <!-- Profile dropdown -->
                <div class="dropdown d-inline-block">
                    <a class="dropdown-toggle btn btn-link btn-square btn-link-header style-none no-caret px-0" id="userprofiledd" data-bs-toggle="dropdown">
                        <div class="row gx-0 d-inline-flex">
                            <div class="col-auto align-self-center">
                                <figure class="avatar avatar-28 rounded-circle d-inline-flex align-items-center justify-content-center bg-primary text-white fw-bold">
                                    <span><?php echo esc_html($user_initials); ?></span>
                                </figure>
                            </div>
                        </div>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end width-300 pt-0 px-0 sm-mi-45px">

                        <div class="bg-theme-1-space rounded py-3 mb-3 dropdown-dontclose">
                            <div class="row gx-0">
                                <div class="col-auto px-3">
                                    <figure class="avatar avatar-50 rounded-circle d-inline-flex align-items-center justify-content-center bg-primary text-white fw-bold">
                                        <span><?php echo esc_html($user_initials); ?></span>
                                    </figure>
                                </div>

                                <div class="col align-self-center">
                                    <p class="mb-1 fw-medium">
                                        <?php echo esc_html($display_name); ?>
                                    </p>
                                    <p>
                                        <i class="bi bi-wallet2 me-2"></i>
                                        $<?php echo number_format((float)$assets, 2); ?>
                                        <small class="opacity-50">Total Assets</small>
                                    </p>
                                </div>

                            </div>
                        </div>

                        <div class="px-2">
                            <!--a class="dropdown-item" href="investment-myprofile.html">
                                <i data-feather="user" class="avatar avatar-18 me-1"></i> My Profile
                            </a>

                            <a class="dropdown-item" href="investment-dashboard.html">
                                <i data-feather="layout" class="avatar avatar-18 me-1"></i> My Dashboard
                            </a>

                            <a class="dropdown-item" href="investment-earning.html">
                                <i data-feather="dollar-sign" class="avatar avatar-18 me-1"></i> Earning
                            </a-->

                            <a class="dropdown-item" href="../user-settings">
                                <i data-feather="settings" class="avatar avatar-18 me-1"></i> Account Settings
                            </a>

                            <a class="dropdown-item theme-red" href="<?php echo esc_url( wp_logout_url( home_url('/wsi/login/') ) ); ?>">
                                <i data-feather="power" class="avatar avatar-18 me-1"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mobile nav toggle -->
                <!--button class="navbar-toggler btn btn-link btn-link-header btn-square btn-icon collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#header-navbar">
                    <i data-feather="more-vertical" class="openbtn"></i>
                    <i data-feather="x" class="closebtn"></i>
                </button-->
            </div>
        </div>
    </nav>
</header>

<!-- Page wrapper -->
<div class="adminuiux-wrap">

    <!-- Sidebar -->
    <div class="adminuiux-sidebar">
        <div class="adminuiux-sidebar-inner">

            <div class="px-3 not-iconic mt-3">
                <h6 class="fw-medium">Main Menu</h6>
            </div>

            <ul class="nav flex-column menu-active-line">

                <li class="nav-item">
                    <a href="../dashboard" class="nav-link">
                        <i class="menu-icon bi bi-columns-gap"></i>
                        <span class="menu-name">Dashboard</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="../deposit" class="nav-link">
                        <i class="menu-icon bi bi-wallet"></i>
                        <span class="menu-name">Deposit</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="../holdings" class="nav-link">
                        <i class="menu-icon bi bi-bullseye"></i>
                        <span class="menu-name">Stock Holdings</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="../withdrawal" class="nav-link">
                        <i class="menu-icon bi bi-bank"></i>
                        <span class="menu-name">Withdrawals</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="../stocks" class="nav-link">
                        <i data-feather="settings" class="menu-icon"></i>
                        <span class="menu-name">Stocks</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="../transactions" class="nav-link">
                        <i class="menu-icon bi bi-bar-chart-line"></i>
                        <span class="menu-name">Transactions</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="../referral" class="nav-link">
                        <i data-feather="users" class="menu-icon"></i>
                        <span class="menu-name">Referral</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>
