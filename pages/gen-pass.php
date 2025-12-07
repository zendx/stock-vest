<?php
if (!defined('ABSPATH')) exit;

// Get the plugin assets URL
$plugin_dir = dirname(dirname(__FILE__)); // Go up from /pages to plugin root
$PLUGIN_ASSETS = plugins_url('pages/assets/', $plugin_dir . '/stock-vest.php');

// Cache-busting version for shared assets
$wsi_asset_ver = (defined('WSI_VER') ? WSI_VER : '1.0.0');
$wsi_asset_path = plugin_dir_path(__FILE__) . 'assets/js/app435e.js';
if (file_exists($wsi_asset_path)) {
    $wsi_asset_ver .= '-' . filemtime($wsi_asset_path);
}

$key   = sanitize_text_field($_GET['key'] ?? '');
$login = sanitize_text_field($_GET['login'] ?? '');
$has_link = !empty($key) && !empty($login);

if (is_user_logged_in()) {
    wp_redirect(home_url('wsi/dashboard'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COFCO CAPITAL | Reset Password</title>

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

    <script defer src="<?php echo plugin_dir_url(__FILE__) . 'assets/js/app435e.js?v=' . esc_attr($wsi_asset_ver); ?>"></script>
    <link href="<?php echo plugin_dir_url(__FILE__) . 'assets/css/app435e.css?v=' . esc_attr($wsi_asset_ver); ?>" rel="stylesheet">
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
                                        <h1 class="mb-2">Reset Password</h1>
                                        <p class="text-secondary">
                                            <?php if ($has_link) : ?>
                                                Enter a new password for <strong><?php echo esc_html($login); ?></strong>.
                                            <?php else : ?>
                                                Your reset link is invalid or missing. Request a new link below.
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <?php if ($has_link) : ?>
                                    <!-- RESET FORM -->
                                    <form id="wsi-reset-password-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">

                                        <?php wp_nonce_field('wsi_reset_password_nonce'); ?>
                                        <input type="hidden" name="action" value="wsi_reset_password">
                                        <input type="hidden" name="user_login" value="<?php echo esc_attr($login); ?>">
                                        <input type="hidden" name="reset_key" value="<?php echo esc_attr($key); ?>">

                                        <div class="form-floating mb-3">
                                            <input id="pass1" name="pass1" type="password"
                                                class="form-control" placeholder="New Password" required minlength="8">
                                            <label for="pass1">New Password</label>
                                        </div>

                                        <div class="form-floating mb-3">
                                            <input id="pass2" name="pass2" type="password"
                                                class="form-control" placeholder="Confirm Password" required minlength="8">
                                            <label for="pass2">Confirm Password</label>
                                        </div>

                                        <button type="submit" class="btn btn-lg btn-theme w-100 mb-3">
                                            Set New Password
                                        </button>

                                        <div class="text-center">
                                            <a href="<?php echo esc_url(home_url('/wsi/login/')); ?>">
                                                Back to login
                                            </a>
                                        </div>

                                    </form>
                                    <?php else : ?>
                                        <div class="alert alert-warning text-center">
                                            This reset link is invalid or expired. Please request a new one.
                                        </div>
                                        <div class="text-center">
                                            <a class="btn btn-theme" href="<?php echo esc_url(home_url('/wsi/forgot-password/')); ?>">Request new link</a>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>

                        </div>

                        <footer class="adminuiux-footer mt-auto">
                            <div class="container-fluid text-center">
                                <span class="small">&copy; <?php echo date('Y'); ?> 
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
    
    <?php if ($has_link) : ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('wsi-reset-password-form');
            if (!form) return;
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                
                fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Invalid response: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        const redirect = (data.data && data.data.redirect) ? data.data.redirect : '<?php echo esc_url(site_url('/wsi/login/')); ?>';
                        alert(data.data && data.data.message ? data.data.message : 'Password updated. You can now log in.');
                        window.location.href = redirect;
                    } else {
                        const msg = (data.data && data.data.message) ? data.data.message : 'Failed to reset password.';
                        alert('Error: ' + msg);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>
