<?php
if (!defined('ABSPATH')) exit;

// Get the plugin assets URL
$PLUGIN_ASSETS = plugins_url('pages/assets/', dirname(dirname(__FILE__)) . '/stock-vest.php');

if (is_user_logged_in()) {
    wp_redirect( home_url('wsi/dashboard') );
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">



<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COFCO CAPITAL | Login</title>

    <link rel="icon" type="image/png" href="<?php echo $PLUGIN_ASSETS; ?>img/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&family=Open+Sans:wght@300..800&display=swap" rel="stylesheet">

    <style>
        :root {
            --adminuiux-content-font: "Open Sans", sans-serif;
            --adminuiux-content-font-weight: 400;
            --adminuiux-title-font: "Lexend", sans-serif;
            --adminuiux-title-font-weight: 600;
        }
    </style>

    <script defer src="<?php echo plugin_dir_url(__FILE__) . 'assets/js/app435e.js?1096aad991449c8654b2'; ?>"></script>
    <link href="<?php echo plugin_dir_url(__FILE__) . 'assets/css/app435e.css?1096aad991449c8654b2'; ?>" rel="stylesheet">
</head>

<body class="main-bg main-bg-opac main-bg-blur adminuiux-sidebar-fill-white adminuiux-sidebar-boxed theme-blue roundedui">

    <main class="flex-shrink-0 pt-0 h-100">
        <div class="container-fluid">
            <div class="auth-wrapper">

                <div class="row">
                    <div class="col-12 col-md-6 col-xl-4 minvheight-100 d-flex flex-column px-0">
                        <div class="h-100 py-4 px-3">

                            <div class="row h-100 align-items-center justify-content-center mt-md-4">
                                <div class="col-11 col-sm-8 col-md-11 col-xl-11 col-xxl-10 login-box">

                                    <div class="text-center mb-4">
                                        <h1 class="mb-2">Welcome 👋</h1>
                                        <p class="text-secondary">Enter your credentials to login</p>
                                    </div>

                                    <?php
                                    $wsi_login_error = '';

                                    // Prefer transient set by wp_login_failed so we can survive redirects
                                    if ($msg = get_transient('wsi_login_error')) {
                                        $wsi_login_error = $msg;
                                        delete_transient('wsi_login_error');
                                    }

                                    // Fallback to core ?login=failed flag if present
                                    if (empty($wsi_login_error) && isset($_GET['login']) && $_GET['login'] === 'failed') {
                                        $wsi_login_error = 'Invalid username or password';
                                    }
                                    ?>

                                    <?php if (!empty($wsi_login_error)) : ?>
                                        <div class="alert alert-danger"><?php echo esc_html($wsi_login_error); ?></div>
                                    <?php endif; ?>

                                    <!-- REAL WORDPRESS LOGIN FORM -->
                                    <form method="post" action="<?php echo esc_url( wp_login_url() ); ?>" >

                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" name="log" id="emailadd" placeholder="Email or Username" required>
                                            <label for="emailadd">Email Address</label>
                                        </div>

                                        <div class="position-relative">
                                            <div class="form-floating mb-3">
                                                <input type="password" class="form-control" name="pwd" id="passwd" placeholder="Enter your password" required>
                                                <label for="passwd">Password</label>
                                            </div>
                                            <button type="button" id="toggle-password" class="btn btn-square btn-link text-theme-1 position-absolute end-0 top-0 mt-2 me-2" aria-label="Show password">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                            </button>
                                        </div>

                                        <div class="row align-items-center mb-3">
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="rememberme" id="rememberme" value="forever">
                                                    <label class="form-check-label" for="rememberme">Remember me</label>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <a href="../forgot-password">Forgot Password?</a>
                                            </div>
                                        </div>

                                        <!-- REQUIRED FIX #1: Identify this as your custom login form -->
                                        <input type="hidden" name="form_id" value="wsi-loginform">

                                        <!-- REQUIRED FIX #2: Correct redirect destination after login -->
                                        <input type="hidden" name="redirect_to" value="<?php echo home_url('/wsi/dashboard/'); ?>">

                                        <button type="submit" class="btn btn-lg btn-theme w-100 mb-4">Login</button>

                                    </form>


                                </div>
                            </div>
                        </div>

                        <footer class="adminuiux-footer mt-auto">
                            <div class="container-fluid text-center">
                                <span class="small">&copy; <?php echo date('Y'); ?> COFCO CAPITAL. All rights reserved.
                                </span>
                            </div>
                        </footer>

                    </div>

                    <!-- RIGHT PANEL WITH SLIDER UNCHANGED -->
                    <div class="col-12 col-md-6 col-xl-8 p-4 d-none d-md-block">
                        <div class="card adminuiux-card bg-theme-1-space position-relative overflow-hidden h-100">
                            <div class="position-absolute start-0 top-0 h-100 w-100 coverimg opacity-75 z-index-0">
                                <img src="<?php echo $PLUGIN_ASSETS; ?>img/background-image/background-image-8.png" alt="">
                            </div>
                            <div class="card-body position-relative z-index-1">
                                <div class="row h-100 d-flex flex-column justify-content-center align-items-center text-center">

                                    <div class="swiper swipernavpagination pb-5">
                                        <div class="swiper-wrapper">

                                            <div class="swiper-slide">
                                                <img src="<?php echo $PLUGIN_ASSETS; ?>img/investment/slider.png" class="mw-100 mb-3">
                                                <h2 class="text-white mb-3">Manage your Investments easily</h2>
                                                <p class="lead opacity-75">Personalized space for your financial growth</p>
                                            </div>

                                            <div class="swiper-slide">
                                                <img src="<?php echo $PLUGIN_ASSETS; ?>img/investment/slider.png" class="mw-100 mb-3">
                                                <h2 class="text-white mb-3">Smart investment tools</h2>
                                                <p class="lead opacity-75">Designed to simplify your workflow</p>
                                            </div>

                                            <div class="swiper-slide">
                                                <img src="<?php echo $PLUGIN_ASSETS; ?>img/investment/slider.png" class="mw-100 mb-3">
                                                <h2 class="text-white mb-3">Easy monitoring</h2>
                                                <p class="lead opacity-75">Track everything in one place</p>
                                            </div>

                                        </div>
                                        <div class="swiper-pagination white bottom-0"></div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </main>

    <script src="<?php echo $PLUGIN_ASSETS; ?>js/investment/investment-auth.js"></script>
    <script>
        // Simple password visibility toggle for the login form
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('passwd');
            const toggleButton = document.getElementById('toggle-password');
            const icon = toggleButton?.querySelector('i');

            if (!passwordInput || !toggleButton) return;

            toggleButton.addEventListener('click', () => {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                if (icon) {
                    icon.classList.toggle('bi-eye', !isHidden);
                    icon.classList.toggle('bi-eye-slash', isHidden);
                }
            });
        });
    </script>
</body>
</html>
