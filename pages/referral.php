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

?>
<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->

<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | User Referal</title>
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
<div class="pageloader">
    <div class="container h-100">
        <div class="row justify-content-center align-items-center text-center h-100">
            <div class="col-12 mb-auto pt-4"></div>
            <div class="col-auto">
                <img src="assets/img/logo.svg" alt="" class="height-60 mb-3">
                <p class="h6 mb-0">AdminUIUX</p>
                <p class="h3 mb-4">Investment</p>
                <div class="loader10 mb-2 mx-auto"></div>
            </div>
            <div class="col-12 mt-auto pb-4">
                <p class="text-secondary">Please wait we are preparing awesome things to preview...</p>
            </div>
        </div>
    </div>
</div>
<!-- standard header -->
<?php include_once "assets/inc/header.php" ?>

                    <main class="adminuiux-content has-sidebar" onclick="contentClick()">
                        <!-- body content of pages -->

                        <!-- breadcrumb -->
                        <div class="container-fluid mt-4">
                            <div class="row gx-3 align-items-center">
                                <div class="col-12 col-sm">
                                    <nav aria-label="breadcrumb" class="mb-2">
                                        <ol class="breadcrumb mb-0">
                                            <li class="breadcrumb-item bi"><a href="#">Home</a></li>
                                            <li class="breadcrumb-item active bi" aria-current="page">Referral</li>
                                        </ol>
                                    </nav>
                                    <h5>Referral</h5>
                                </div>
                            </div>
                        </div>

                        <!-- Content  -->
                        <div class="container mt-4" id="main-content">

                            <div class="row align-items-center">
                                <!-- Welcome box -->
                                <div class="col-12 col-md-10 col-lg-8 mb-4">
                                    <h3 class="fw-normal mb-0 text-secondary">You can earn up to $1000.00 per day</h3>
                                    <h1>based solely on the number and activity of users you successfully refer.</h1>
                                </div>
                                <div class="col-12 py-2"></div>
                                <!-- copy code-->
                                <div class="col-12 col-md-8 col-lg-6 col-xxl-5 mb-4">
                                    <p>Copy and Share your referral link with your network</p>
                                    <div class="input-group mb-3">
                                        <input 
                                            type="text" 
                                            class="form-control form-control-lg border-theme-1" 
                                            id="referralLink"
                                            placeholder="Referral Code"
                                            aria-describedby="button-addon2" 
                                            value="<?php echo esc_attr(wsi_get_invite_link()); ?>" 
                                            readonly
                                        >
                                        <button class="btn btn-lg btn-outline-theme" type="button" id="copyReferral">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>

                                    <script>
                                    document.getElementById('copyReferral').addEventListener('click', function () {
                                        const input = document.getElementById('referralLink');
                                        input.select();
                                        input.setSelectionRange(0, 99999); // for mobile devices

                                        navigator.clipboard.writeText(input.value).then(function () {
                                            // Optional: give user feedback
                                            alert("Referral link copied!");
                                        });
                                    });
                                    </script>

                                </div>
                                <div class="col-12 py-2"></div>
                            </div>
                            <br>
                            <div class="row align-items-center jsutify-content-center">
                                <div class="col-12 mb-4">
                                    <h5>Learn how it works!</h5>
                                </div>
                                <!-- step 1 -->
                                <div class="col-12 col-sm-6 col-lg-3 mb-4">
                                    <i class="bi bi-link avatar avatar-60 bg-theme-1-subtle text-theme-1 rounded h4 mb-3"></i>
                                    <br>
                                    <h6>1. Invite</h6>
                                    <p class="text-secondary">Invite unlimited network members by sharing referral link</p>
                                </div>
                                <!-- step 2 -->
                                <div class="col-12 col-sm-6 col-lg-3 mb-4">
                                    <i class="bi bi-person avatar avatar-60 bg-theme-1-subtle text-theme-1 rounded h4 mb-3"></i>
                                    <br>
                                    <h6>2. Registration</h6>
                                    <p class="text-secondary">Let your network member join our platform and track earning</p>
                                </div>
                                <!-- step 3 -->
                                <div class="col-12 col-sm-6 col-lg-3 mb-4">
                                    <i class="bi bi-coin avatar avatar-60 bg-theme-1-subtle text-theme-1 rounded h4 mb-3"></i>
                                    <br>
                                    <h6>3. Process Overview</h6>
                                    <p class="text-secondary">Remain at the top of the COFCO Tree and earn continuous and steady profits from both your direct referrals and the growing branches they create.</p>
                                </div>
                                <!-- step 4 -->
                                <!--div class="col-12 col-sm-6 col-lg-3 mb-4">
                                    <i class="bi bi-cash-stack avatar avatar-60 bg-theme-1-subtle text-theme-1 rounded h4 mb-3"></i>
                                    <br>
                                    <h6>4. Purchase Membership</h6>
                                    <p class="text-secondary">Earn 10% on each purchase made by your referral members in lifetime</p>
                                </div-->
                            </div>
                        </div>
                    </main>

            </div>

            <!-- page footer -->
            <!-- standard footer -->
            <?php include_once "assets/inc/footer.php" ?>
                    <!-- Page Level js -->
                    </body>

</html>
