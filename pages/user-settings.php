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

$uid = get_current_user_id();   // <-- ADD THIS
$sf = get_user_meta($uid, 'wsi_smart_farming', true);
$sf_checked = ($sf === 'yes') ? 'checked' : '';

//Form Data Processor
$user_id = get_current_user_id();

$first_name = get_user_meta($user_id, 'first_name', true);
$last_name  = get_user_meta($user_id, 'last_name', true);
$birth_date = get_user_meta($user_id, 'birth_date', true);
$phone = get_user_meta($user_id, 'phone', true);

$address1   = get_user_meta($user_id, 'address1', true);
$address2   = get_user_meta($user_id, 'address2', true);
$landmark   = get_user_meta($user_id, 'landmark', true);
$street     = get_user_meta($user_id, 'street', true);
$country    = get_user_meta($user_id, 'country', true);
$zip        = get_user_meta($user_id, 'zip', true);
$state      = get_user_meta($user_id, 'state', true);
$city       = get_user_meta($user_id, 'city', true);


?>
<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->

<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | User Settings</title>
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
                                            <li class="breadcrumb-item bi"><a href="#">Home</a></li>
                                            <li class="breadcrumb-item active bi" aria-current="page">Settings</li>
                                        </ol>
                                    </nav>
                                    <h5>Settings Page</h5>
                                </div>
                            </div>
                        </div>

                        <!-- content -->
                        <div class="container mt-4" id="main-content">

                            <!-- cover -->
                            <!--div class="card adminuiux-card overflow-hidden mb-4 pt-5">
                                <figure class="coverimg start-0 top-0 w-100 h-100 z-index-0 position-absolute overlay-gradiant">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <button class="btn btn-theme" onclick="$(this).next().click()">
                                            <i class="bi bi-camera"></i> Change Cover
                                        </button>
                                        <input type="file" class="d-none">
                                    </div>
                                    <img src="assets/img/modern-ai-image/flamingo-3.jpg" class="mw-100" alt="">
                                </figure>

                                <div class="card-body text-center text-white z-index-1">
                                    <div class="d-inline-block position-relative w-auto mx-auto my-3">
                                        <figure class="avatar avatar-150 coverimg rounded-circle">
                                            <img src="assets/img/modern-ai-image/user-6.jpg" alt="">
                                        </figure>
                                        <div class="position-absolute bottom-0 end-0 z-index-1 h-auto">
                                            <button class="btn btn-lg btn-theme btn-square" onclick="$(this).next().click()">
                                                <i class="bi bi-camera"></i>
                                            </button>
                                            <input type="file" class="d-none">
                                        </div>
                                    </div>
                                    <h4>AdminUIUX</h4>
                                    <p class="opacity-75 mb-3">guest@adminuiux.com</p>
                                </div>
                            </div-->

                            <!-- settings -->
                            <div class="row">
                                <div class="col-12 col-md-4 col-lg-4 col-xl-3">
                                    <div class="position-sticky" style="top:5.5rem">
                                        <div class="card adminuiux-card mb-4">
                                            <div class="card-body">
                                                <ul class="nav nav-pills adminuiux-nav-pills flex-column">
                                                    <li class="nav-item">
                                                        <a class="nav-link active" aria-current="page" href="../user-settings">
                                                            <div class="avatar avatar-28 icon"><i data-feather="user"></i></div>
                                                            <div class="col">
                                                                <p class="h6 mb-0">My Profile</p>
                                                                <p class="small opacity-75">Basic Details</p>
                                                            </div>
                                                        </a>
                                                    </li>
                                                    <!--li class="nav-item">
                                                        <a class="nav-link" aria-current="page" href="investment-settings-users.html">
                                                            <div class="avatar avatar-28 icon"><i class="bi bi-people fs-4"></i></div>
                                                            <div class="col">
                                                                <p class="h6 mb-0">Users</p>
                                                                <p class="small opacity-75">Roles, Permission, Access</p>
                                                            </div>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link" aria-current="page" href="investment-settings-timing.html">
                                                            <div class="avatar avatar-28 icon"><i data-feather="clock"></i></div>
                                                            <div class="col">
                                                                <p class="h6 mb-0">Timing</p>
                                                                <p class="small opacity-75">Business hours, Emergency</p>
                                                            </div>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link" aria-current="page" href="investment-settings-payment.html">
                                                            <div class="avatar avatar-28 icon"><i class="bi bi-cash-stack fs-4"></i></div>
                                                            <div class="col">
                                                                <p class="h6 mb-0">Payment</p>
                                                                <p class="small opacity-75">Online, Devices, Cash</p>
                                                            </div>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link" aria-current="page" href="investment-settings-contact.html">
                                                            <div class="avatar avatar-28 icon"><i class="bi bi-life-preserver"></i></div>
                                                            <div class="col">
                                                                <p class="h6 mb-0">Contact</p>
                                                                <p class="small opacity-75">Support, Call, Chat, email</p>
                                                            </div>
                                                        </a>
                                                    </li-->
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                                        <?php wp_nonce_field( 'save_user_form_action', 'save_user_form_nonce' ); ?>

                                        <div class="card adminuiux-card overflow-hidden mb-4">
                                            <div class="card-body">

                                                <h6 class="mb-3">Basic Details</h6>

                                                <div class="row mb-2">

                                                    <!-- Username (readonly) -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="text" 
                                                                   placeholder="Username" 
                                                                   value="<?php echo esc_attr(wp_get_current_user()->user_login); ?>" 
                                                                   class="form-control is-valid" 
                                                                   readonly>
                                                            <label>Username</label>
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid input</div>
                                                    </div>

                                                    <!-- First Name -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="text" 
                                                                   name="first_name"
                                                                   placeholder="First Name"
                                                                   value="<?php echo esc_attr($first_name); ?>"
                                                                   required 
                                                                   class="form-control">
                                                            <label>First Name</label>
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid input</div>
                                                    </div>

                                                    <!-- Last Name -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="text" 
                                                                   name="last_name"
                                                                   placeholder="Last Name"
                                                                   value="<?php echo esc_attr($last_name); ?>"
                                                                   required 
                                                                   class="form-control">
                                                            <label>Last Name</label>
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid input</div>
                                                    </div>

                                                    <!-- Email (disabled) -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating">
                                                            <input type="email" 
                                                                   placeholder="Email Address" 
                                                                   value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" 
                                                                   disabled 
                                                                   required 
                                                                   class="form-control">
                                                            <label>Email Address</label>
                                                        </div>
                                                        <div class="invalid-feedback mb-3">Add .com at last to insert valid data</div>
                                                    </div>

                                                    <!-- Telephone -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="tel" 
                                                                   name="phone"
                                                                   placeholder="Telephone"
                                                                   value="<?php echo esc_attr($phone); ?>"
                                                                   required 
                                                                   class="form-control">
                                                            <label>Enter Phone Number</label>
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid input</div>
                                                    </div>

                                                    <!-- Birth date -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="date" 
                                                                   name="birth_date"
                                                                   placeholder="Birth Date"
                                                                   value="<?php echo esc_attr($birth_date); ?>"
                                                                   required 
                                                                   class="form-control datepicker">
                                                            <label>Birth date</label>
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid input</div>
                                                    </div>

                                                </div>

                                                <h6 class="mb-3">Address Details</h6>

                                                <div class="row mb-2">

                                                    <!-- Address Line 1 -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="text" 
                                                                   name="address1"
                                                                   placeholder="Address Line 1"
                                                                   value="<?php echo esc_attr($address1); ?>"
                                                                   required 
                                                                   class="form-control">
                                                            <label>Address Line 1</label>
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid input</div>
                                                    </div>

                                                    <!-- Address Line 2 -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="text" 
                                                                   name="address2"
                                                                   placeholder="Address Line 2"
                                                                   value="<?php echo esc_attr($address2); ?>"
                                                                   class="form-control">
                                                            <label>Address Line 2</label>
                                                        </div>
                                                        <div class="invalid-feedback mb-3">Please insert valid data</div>
                                                    </div>

                                                    <!-- Landmark -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="text" 
                                                                   name="landmark"
                                                                   placeholder="Landmark"
                                                                   value="<?php echo esc_attr($landmark); ?>"
                                                                   class="form-control">
                                                            <label>Landmark</label>
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid input</div>
                                                    </div>

                                                    <!-- Street -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="text" 
                                                                   name="street"
                                                                   placeholder="Street"
                                                                   value="<?php echo esc_attr($street); ?>"
                                                                   required 
                                                                   class="form-control">
                                                            <label>Street</label>
                                                        </div>
                                                        <div class="invalid-feedback mb-3">Please insert valid data</div>
                                                    </div>

                                                    <!-- Country -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="text" 
                                                                   name="country"
                                                                   placeholder="Country"
                                                                   value="<?php echo esc_attr($country); ?>"
                                                                   required 
                                                                   class="form-control">
                                                            <label>Country</label>
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid input</div>
                                                    </div>

                                                    <!-- Zip Code -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="text" 
                                                                   name="zip"
                                                                   placeholder="Zip Code"
                                                                   value="<?php echo esc_attr($zip); ?>"
                                                                   required 
                                                                   class="form-control">
                                                            <label>Zip Code</label>
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid input</div>
                                                    </div>

                                                    <!-- State -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="text" 
                                                                   name="state"
                                                                   placeholder="State"
                                                                   value="<?php echo esc_attr($state); ?>"
                                                                   required 
                                                                   class="form-control">
                                                            <label>State</label>
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid input</div>
                                                    </div>

                                                    <!-- City -->
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-floating mb-3">
                                                            <input type="text" 
                                                                   name="city"
                                                                   placeholder="City"
                                                                   value="<?php echo esc_attr($city); ?>"
                                                                   required 
                                                                   class="form-control <?php echo empty($city) ? 'is-invalid' : ''; ?>">
                                                            <label>City</label>
                                                            <input type="hidden" name="action" value="save_user_form">
                                                        </div>
                                                        <div class="invalid-feedback">Please enter valid input</div>
                                                    </div>

                                                </div>

                                            </div>

                                            <div class="card-footer">
                                                <div class="row">
                                                    <div class="col">
                                                        <button type="submit" class="btn btn-theme" name="save_user_form">Save</button>
                                                    </div>
                                                    <div class="col-auto">
                                                        <button type="reset" class="btn btn-link">Cancel</button>
                                                    </div>
                                                </div>
                                                &nbsp;


                                                <?php
                                                $sf = get_user_meta($uid, 'wsi_smart_farming', true);
                                                $sf_checked = ($sf === 'yes') ? 'checked' : '';
                                                ?>

                                                <h6 class="mb-3">Activate Smart Farming</h6>

                                                <div class="row">
                                                    <div class="col-12 col-md-6 col-lg-4 col-xl-3">

                                                <div class="form-check form-switch">
                                                    <input 
                                                        class="form-check-input" 
                                                        type="checkbox" 
                                                        id="wsi_smart_farming_toggle"
                                                        <?php echo $sf_checked; ?>
                                                    >

                                                    <label class="form-check-label" for="wsi_smart_farming_toggle">
                                                        <span id="wsi_smart_farming_status">
                                                            <?php echo $sf_checked ? 'Smart Farming Activated' : 'Smart Farming Disabled'; ?>
                                                        </span>
                                                    </label>
                                                </div>

                                                    </div>
                                                </div>


                                                <script>
                                                document.addEventListener('DOMContentLoaded', function () {

                                                    const toggle = document.getElementById('wsi_smart_farming_toggle');
                                                    const status = document.getElementById('wsi_smart_farming_status');

                                                    if (!toggle) return;

                                                    toggle.addEventListener('change', function () {

                                                        let enabled = toggle.checked ? 'yes' : 'no';

                                                        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                                                            method: 'POST',
                                                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                                            body: 'action=wsi_toggle_farming&state=' + enabled,
                                                        })
                                                        .then(res => res.text())
                                                        .then(result => {
                                                            const trimmed = (result || '').trim();
                                                            if (trimmed === 'ok') {
                                                                status.textContent = enabled === 'yes' ? 'Smart Farming Activated' : 'Smart Farming Disabled';
                                                            } else if (trimmed === 'noauth') {
                                                                alert('Please log in again to change Smart Farming.');
                                                                toggle.checked = !toggle.checked; // revert toggle to previous state
                                                                status.textContent = toggle.checked ? 'Smart Farming Activated' : 'Smart Farming Disabled';
                                                            } else {
                                                                alert('We could not update Smart Farming right now. Please try again shortly.');
                                                                toggle.checked = !toggle.checked; // revert toggle to previous state
                                                                status.textContent = toggle.checked ? 'Smart Farming Activated' : 'Smart Farming Disabled';
                                                            }
                                                        })
                                                        .catch(() => {
                                                            alert('Network error updating Smart Farming. Please check your connection and try again.');
                                                            toggle.checked = !toggle.checked; // revert toggle to previous state
                                                            status.textContent = toggle.checked ? 'Smart Farming Activated' : 'Smart Farming Disabled';
                                                        });
                                                    });

                                                });
                                                </script>
                                            </div>

                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </main>

            </div>

            <!-- page footer -->
            <?php
            include_once plugin_dir_path(__FILE__) . 'assets/inc/footer.php';
            ?>

                    <!-- Page Level js -->
                    </body>

</html>
