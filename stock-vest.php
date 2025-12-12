    <?php
    /**
     * Plugin Name: WSI — Single-file Investment Plugin
     * Description: A self-contained “investment platform” WordPress plugin.
     * Version: 1.0.0
     * Author: HAPPY GILMORE
     * Text Domain: wsi
     */

    if (!defined('ABSPATH')) exit;

    /* -------------------------------------------------------------------------
       HARD PROTECTION — Prevent ANY ALTER TABLE or SHOW COLUMNS
       unless the deposits table actually exists.
    ------------------------------------------------------------------------- */
    if (!function_exists('wsi_table_exists')) {
        function wsi_table_exists() {
            global $wpdb;
            $t = $wpdb->prefix . 'wsi_deposits';
            return $wpdb->get_var("SHOW TABLES LIKE '{$t}'") === $t;
        }
    }

    /* -------------------------------------------------------------------------
       SAFE: Only run column repair AFTER tables exist
    ------------------------------------------------------------------------- */
    if (!function_exists('wsi_safe_migration')) {
        function wsi_safe_migration() {
            global $wpdb;

            // Guard: if DB is already at current plugin version, skip.
            if (get_option('wsi_db_version') === WSI_VER) return;

        // Deposits table checks (existing)
        $t_dep = $wpdb->prefix . 'wsi_deposits';
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t_dep));
        if ($table_exists === $t_dep) {
            $cols = $wpdb->get_col("SHOW COLUMNS FROM $t_dep", 0);
            if (!in_array('payment_type', (array)$cols, true)) {
                $wpdb->query("ALTER TABLE `$t_dep` ADD COLUMN `payment_type` VARCHAR(20) NULL DEFAULT NULL");
            }
            if (!in_array('wallet', (array)$cols, true)) {
                $wpdb->query("ALTER TABLE `$t_dep` ADD COLUMN `wallet` VARCHAR(100) NULL DEFAULT NULL");
            }
            if (!in_array('crypto_wallet', (array)$cols, true)) {
                // keep names safe: add if plugin expects crypto_wallet somewhere
                $wpdb->query("ALTER TABLE `$t_dep` ADD COLUMN `crypto_wallet` VARCHAR(255) NULL DEFAULT NULL");
            }

        }

        // Withdrawals table checks (NEW — fixes your 500s)
        $t_w = $wpdb->prefix . 'wsi_withdrawals';
        $table_exists_w = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t_w));
        if ($table_exists_w === $t_w) {
            $cols_w = $wpdb->get_col("SHOW COLUMNS FROM $t_w", 0);
            if (!in_array('admin_note', (array)$cols_w, true)) {
                $wpdb->query("ALTER TABLE `$t_w` ADD COLUMN `admin_note` TEXT NULL");
            }
            // add any other missing columns the code might write to:
            if (!in_array('account_details', (array)$cols_w, true)) {
                $wpdb->query("ALTER TABLE `$t_w` ADD COLUMN `account_details` TEXT NULL");
            }
        }
        }
    }



    /* -------------------------------------------------------------------------
       CONSTANTS
    ------------------------------------------------------------------------- */
    define('WSI_FILE', __FILE__);
    define('WSI_DIR', plugin_dir_path(__FILE__));
    define('WSI_VER', '1.0.3');

    /* -------------------------------------------------------------------------
       ACTIVATION / DEACTIVATION
    ------------------------------------------------------------------------- */
    register_activation_hook(WSI_FILE, 'wsi_activate');
    register_deactivation_hook(WSI_FILE, 'wsi_deactivate');

    function wsi_activate() {
        // Run any pending migrations (creates tables + applies fixes) once per version
        if (function_exists('wsi_run_pending_migrations')) {
            wsi_run_pending_migrations();
        } else {
            // fallback
            wsi_create_tables();
            update_option('wsi_db_version', WSI_VER);
        }

        if (!wp_next_scheduled('wsi_hourly_accrue')) {
            wp_schedule_event(time(), 'hourly', 'wsi_hourly_accrue');
        }

        if (!wp_next_scheduled('wsi_daily_accrue')) {
            wp_schedule_event(time(), 'daily', 'wsi_daily_accrue');
        }

    if (get_option('wsi_options') === false) {
        update_option('wsi_options', [
            'main_daily_percent' => 2.29,
            'min_invest' => 50.00,
            'deposit_mode' => 'manual',
            'manual_payment_info' => "Bank: Example Bank\nAccount: 0123456789\nName: WSI Investments",
            'email_notifications' => 1
        ]);
    }

    // Seed default email templates/subjects without overwriting existing content
    if (function_exists('wsi_seed_email_templates')) {
        wsi_seed_email_templates();
    }
}

    /**
     * Run pending DB migrations once per plugin version.
     * Creates tables (dbDelta) and applies column fixes.
     */
    function wsi_run_pending_migrations() {
        global $wpdb;

        $current = get_option('wsi_db_version');
        if ($current === WSI_VER) return; // nothing to do

        // Ensure base tables exist and dbDelta runs
        wsi_create_tables();

        // Apply safe repairs / migration helpers (they are guarded themselves)
        if (function_exists('wsi_fix_missing_deposit_columns')) wsi_fix_missing_deposit_columns();
        if (function_exists('wsi_safe_migration')) wsi_safe_migration();
        if (function_exists('wsi_deposits_auto_migrate')) wsi_deposits_auto_migrate();

        if (!empty($wpdb->last_error)) {
            error_log('WSI migration error: ' . $wpdb->last_error);
        }

        // mark version as applied
        update_option('wsi_db_version', WSI_VER);
    }

    /* -- Column Repair (AFTER activation) -- */
    function wsi_fix_missing_deposit_columns() {
        // Only run when migrations are pending for this plugin version
        if (get_option('wsi_db_version') === WSI_VER) return;

        if (!wsi_table_exists()) return; // STOP if table is missing

        global $wpdb;
        $t = $wpdb->prefix . 'wsi_deposits';

        $cols = $wpdb->get_col("SHOW COLUMNS FROM $t", 0);

        if (!in_array('amount_local', (array)$cols, true)) {
            $wpdb->query("ALTER TABLE `$t` ADD COLUMN amount_local DECIMAL(14,2) DEFAULT 0 AFTER amount");
        }

        if (!in_array('payment_type', (array)$cols, true)) {
            $wpdb->query("ALTER TABLE `$t` ADD COLUMN payment_type VARCHAR(80) DEFAULT '' AFTER amount_local");
        }

        if (!in_array('wallet', (array)$cols, true)) {
            $wpdb->query("ALTER TABLE `$t` ADD COLUMN wallet VARCHAR(255) DEFAULT '' AFTER payment_type");
        }

        if (!in_array('method', (array)$cols, true)) {
            $wpdb->query("ALTER TABLE `$t` ADD COLUMN method VARCHAR(80) DEFAULT '' AFTER wallet");
        }

        if (!in_array('admin_note', (array)$cols, true)) {
            $wpdb->query("ALTER TABLE `$t` ADD COLUMN admin_note TEXT AFTER token");
        }
    }





    function wsi_deactivate() {
        wp_clear_scheduled_hook('wsi_hourly_accrue');
        wp_clear_scheduled_hook('wsi_daily_accrue');
    }

    /* -------------------------------------------------------------------------
       DATABASE: create tables
    ------------------------------------------------------------------------- */
    function wsi_create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $tables = [];

        // deposits
        $tables[] = "CREATE TABLE {$wpdb->prefix}wsi_deposits (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          user_id BIGINT UNSIGNED NOT NULL,
          amount DECIMAL(14,2) NOT NULL,
          amount_local DECIMAL(14,2) NOT NULL DEFAULT 0,
          payment_type VARCHAR(80) DEFAULT '',
          wallet VARCHAR(255) DEFAULT '',
          crypto_wallet VARCHAR(255) DEFAULT '',
          method VARCHAR(80) DEFAULT '',
          status VARCHAR(32) DEFAULT 'pending',
          token VARCHAR(128) DEFAULT '',
          admin_note TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY user_id (user_id),
          KEY status (status)
        ) $charset_collate;";

        // withdrawals
        $tables[] = "CREATE TABLE {$wpdb->prefix}wsi_withdrawals (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          user_id BIGINT UNSIGNED NOT NULL,
          amount DECIMAL(14,2) NOT NULL,
          method VARCHAR(80) DEFAULT '',
          account_details TEXT,
          status VARCHAR(32) DEFAULT 'pending',
          admin_note TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY user_id (user_id),
          KEY status (status)
        ) $charset_collate;";

        // transactions
        $tables[] = "CREATE TABLE {$wpdb->prefix}wsi_transactions (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          user_id BIGINT UNSIGNED NOT NULL,
          amount DECIMAL(14,2) NOT NULL,
          type VARCHAR(60) NOT NULL,
          description TEXT,
          meta LONGTEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY user_id (user_id)
        ) $charset_collate;";

        // stocks
        $tables[] = "CREATE TABLE {$wpdb->prefix}wsi_stocks (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          name VARCHAR(255) NOT NULL,
          price DECIMAL(14,2) NOT NULL,
          rate_percent DECIMAL(8,4) NOT NULL,
          rate_period ENUM('daily','hourly') DEFAULT 'daily',
          active TINYINT(1) DEFAULT 1,
          image VARCHAR(255) DEFAULT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id)
        ) $charset_collate;";

        // holdings
        $tables[] = "CREATE TABLE {$wpdb->prefix}wsi_holdings (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          user_id BIGINT UNSIGNED NOT NULL,
          stock_id BIGINT UNSIGNED NOT NULL,
          invested_amount DECIMAL(14,2) NOT NULL,
          shares DECIMAL(20,8) NOT NULL,
          accumulated_profit DECIMAL(14,2) DEFAULT 0,
          status VARCHAR(32) DEFAULT 'open',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY user_id (user_id),
          KEY stock_id (stock_id),
          KEY status (status)
        ) $charset_collate;";

        // audit
        $tables[] = "CREATE TABLE {$wpdb->prefix}wsi_audit (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          actor_id BIGINT UNSIGNED,
          action VARCHAR(255),
          details TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id)
        ) $charset_collate;";

        // run dbDelta on each table
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }



    /**
     * Auto-migrate database columns for wsi_deposits table
     * Ensures missing columns (amount_local, payment_type, wallet) are created.
     */
    function wsi_deposits_auto_migrate() {
        global $wpdb;
        // Only run migrations when plugin version differs
        if (get_option('wsi_db_version') === WSI_VER) return;
        $table = $wpdb->prefix . 'wsi_deposits';

        // Get existing columns
        if ($wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", $table
        )) !== $table) {
            return;
        }

        $columns = $wpdb->get_col("SHOW COLUMNS FROM $table", 0);
        if (!$columns) return;

        // List of required columns for new logic
        $required = [
            'amount_local' => "ALTER TABLE $table ADD COLUMN amount_local DECIMAL(14,2) DEFAULT 0 AFTER amount",
            'payment_type' => "ALTER TABLE $table ADD COLUMN payment_type VARCHAR(80) NOT NULL AFTER amount_local",
            'wallet'       => "ALTER TABLE $table ADD COLUMN wallet VARCHAR(150) AFTER payment_type",
        ];

        // Add each missing column safely
        foreach ($required as $col => $sql) {
            if (!in_array($col, $columns)) {
                $wpdb->query($sql);
            }
        }
    }
    


    /* -------------------------------------------------------------------------
       OPTIONS helpers
    ------------------------------------------------------------------------- */
    function wsi_get_opts() {
        return get_option('wsi_options', []);
    }
    function wsi_update_opt($k, $v) {
        $opts = wsi_get_opts();
        $opts[$k] = $v;
        update_option('wsi_options', $opts);
    }

    /* -------------------------------------------------------------------------
       Admin access helper (role-based allowlist)
    ------------------------------------------------------------------------- */
    function wsi_get_admin_allowed_roles() {
        $opts = wsi_get_opts();
        $raw = $opts['admin_allowed_roles'] ?? ['administrator'];
        if (is_string($raw)) {
            $raw = array_filter(array_map('trim', explode(',', $raw)), 'strlen');
        }
        $roles = array_map('sanitize_key', (array) $raw);
        if (empty($roles)) {
            $roles = ['administrator']; // safety default to prevent lockout
        }
        return array_values(array_unique($roles));
    }

    function wsi_admin_can($user_id = 0) {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }
        // Super admins / admins retain access
        if (current_user_can('manage_options')) return true;
        if (current_user_can('wsi_admin_access')) return true;

        $user = get_userdata($user_id);
        if (!$user || empty($user->roles)) return false;

        $allowed = wsi_get_admin_allowed_roles();
        foreach ($user->roles as $role) {
            if (in_array($role, $allowed, true)) {
                return true;
            }
        }
        return false;
    }

    // Map a virtual capability so allowed roles see admin menus
    add_filter('user_has_cap', function($allcaps, $caps, $args) {
        // $args: [0] => capability being checked
        if (!isset($args[0]) || $args[0] !== 'wsi_admin_access') {
            return $allcaps;
        }
        // Always allow admins
        if (!empty($allcaps['manage_options'])) {
            $allcaps['wsi_admin_access'] = true;
            return $allcaps;
        }
        $user_id = isset($args[1]) ? intval($args[1]) : get_current_user_id();
        $user = get_userdata($user_id);
        if (!$user || empty($user->roles)) {
            return $allcaps;
        }
        $allowed = wsi_get_admin_allowed_roles();
        foreach ($user->roles as $role) {
            if (in_array($role, $allowed, true)) {
                $allcaps['wsi_admin_access'] = true;
                break;
            }
        }
        return $allcaps;
    }, 10, 3);

    /**
     * Allow-list for Settings page access (user IDs, not roles).
     * Store under wsi_options['settings_allowed_users'] as array or comma-separated list.
     */
    function wsi_get_settings_allowed_users() {
        $opts = wsi_get_opts();
        $raw = $opts['settings_allowed_users'] ?? [];
        if (is_string($raw)) {
            $raw = array_filter(array_map('trim', explode(',', $raw)), 'strlen');
        }
        $ids = array_map('intval', (array) $raw);
        return array_values(array_unique(array_filter($ids, function($v) { return $v > 0; })));
    }

    function wsi_user_can_access_settings($uid = 0) {
        if ($uid === 0) {
            $uid = get_current_user_id();
        }
        // Always allow site admins to avoid lockout
        if (current_user_can('manage_options')) {
            return true;
        }
        $allowed = wsi_get_settings_allowed_users();
        return in_array(intval($uid), $allowed, true);
    }

    /* -------------------------------------------------------------------------
       BALANCE helpers (usermeta)
    ------------------------------------------------------------------------- */
    function wsi_get_main($uid) { return floatval(get_user_meta($uid, 'wsi_main_balance', true)); }
    /*function wsi_get_profit($uid) { return floatval(get_user_meta($uid, 'wsi_profit_balance', true)); }*/
    function wsi_set_main($uid, $v) {
        $v = round(floatval($v), 2);
        if ($v < 0) $v = 0; // Prevent negative flip-bug
        update_user_meta($uid, 'wsi_main_balance', $v);
    }
    function wsi_get_profit($uid) {
        global $wpdb;

        // Daily profit stored in user meta
        $daily_profit = floatval(get_user_meta($uid, 'wsi_profit_balance', true));

        // Sum of accumulated holding profits
        $t_hold = $wpdb->prefix . 'wsi_holdings';
        $accumulated = floatval(
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(accumulated_profit) FROM $t_hold WHERE user_id = %d AND status = 'open'",
                    $uid
                )
            )
        );

        // Total combined profit
        return $daily_profit + $accumulated;
    }
    function wsi_inc_main($uid, $d) { wsi_set_main($uid, wsi_get_main($uid) + floatval($d)); }
    function wsi_inc_profit($uid, $d) { wsi_set_profit($uid, wsi_get_profit($uid) + floatval($d)); }

    /*-------------------------------------------------------------
        STock Dedct elper
    ------------------------------------------------------*/
    if (!function_exists('wsi_add_transaction')) {
        function wsi_add_transaction($user_id, $amount, $type, $note = '') {
            global $wpdb;
            $table = $wpdb->prefix . 'wsi_transactions';

            // Check if table exists before inserting
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table) {
                $wpdb->insert($table, [
                    'user_id'    => $user_id,
                    'amount'     => $amount,
                    'type'       => sanitize_text_field($type),
                    'note'       => sanitize_textarea_field($note),
                    'created_at' => current_time('mysql'),
                ]);
            
                $deposit_id = $wpdb->insert_id;

                // ---------- EMAIL TRIGGERS (insert right here) ----------
                if (!empty($deposit_id)) {
                    // $type is already sanitized earlier: identify event types by $type string
                    // You may use types like: 'deposit_received', 'deposit_approved', 'withdraw_received', 'withdraw_approved', 'withdraw_declined'
                    // If your code uses different $type strings, adapt the checks accordingly.

                    // deposit request submitted
                    if (strpos($type, 'deposit') !== false && strpos($type, 'approved') === false) {
                        wsi_send_email_template($user_id, 'email_deposit_received', ['amount' => $amount]);
                    }

                    // deposit approved (type contains 'deposit' and 'approved')
                    if (strpos($type, 'deposit') !== false && strpos($type, 'approved') !== false) {
                        wsi_send_email_template($user_id, 'email_deposit_approved', ['amount' => $amount]);
                    }

                    // withdrawal request submitted
                    if (strpos($type, 'withdraw') !== false && strpos($type, 'approved') === false && strpos($type, 'declined') === false) {
                        wsi_send_email_template($user_id, 'email_withdraw_received', ['amount' => $amount]);
                    }

                    // withdrawal approved
                    if (strpos($type, 'withdraw') !== false && strpos($type, 'approved') !== false) {
                        wsi_send_email_template($user_id, 'email_withdraw_approved', ['amount' => $amount]);
                    }

                    // withdrawal declined
                    if (strpos($type, 'withdraw') !== false && strpos($type, 'declined') !== false) {
                        wsi_send_email_template($user_id, 'email_withdraw_declined', ['amount' => $amount]);
                    }

                    // generic registration welcome (if you log registration via transactions with a type like 'registration_welcome')
                    if (strpos($type, 'registration') !== false) {
                        wsi_send_email_template($user_id, 'email_registration_welcome', $vars ?? []);
                    }
                }
                // ---------- END EMAIL TRIGGERS ----------

            }
        }
    }

    /* -------------------------------------------------------------------------
       Logging & notifications
    ------------------------------------------------------------------------- */
    function wsi_log_tx($uid, $amount, $type = 'info', $desc = '', $meta = []) {
        global $wpdb;
        $t = $wpdb->prefix . 'wsi_transactions';
        
        $wpdb->insert($t, [
            'user_id' => intval($uid),
            'amount' => round(floatval($amount), 2),
            'type' => sanitize_text_field($type),
            'description' => sanitize_text_field($desc),
            'meta' => maybe_serialize($meta),
            'created_at' => current_time('mysql')
        ]);
        
        // Send emails based on transaction type (only once, here)
        if ($wpdb->insert_id) {
            wsi_trigger_transaction_email($uid, $type, $amount);
        }
    }

    /**
     * Trigger emails based on transaction type
     * This prevents infinite loops by separating email logic from transaction logging
     */
function wsi_trigger_transaction_email($user_id, $type, $amount) {
    $is_ajax = defined('DOING_AJAX') && DOING_AJAX;

    // Map transaction types to email templates
    $email_map = [
        'deposit_pending'   => 'email_deposit_received',
        'deposit_approved'  => 'email_deposit_approved',
            'deposit_declined'  => 'email_deposit_declined',
            'withdraw_request'  => 'email_withdraw_received',
            'withdraw_approved' => 'email_withdraw_approved',
        'withdraw_declined' => 'email_withdraw_declined',
        'withdraw_refund'   => 'email_withdraw_declined',
    ];
    
    if (isset($email_map[$type])) {
        $vars = ['amount' => $amount];
        if ($is_ajax) {
            wsi_queue_email_template($user_id, $email_map[$type], $vars);
            wsi_email_log('queued', [
                'template' => $email_map[$type],
                'user_id'  => intval($user_id),
                'to'       => '',
                'subject'  => '',
                'reason'   => 'ajax_queue',
            ]);
        } else {
            wsi_send_email_template($user_id, $email_map[$type], $vars);
        }
    }
}



    function wsi_audit($actor, $action, $details = '') {
        global $wpdb;
        $t = $wpdb->prefix . 'wsi_audit';
        $wpdb->insert($t, [
            'actor_id' => intval($actor),
            'action' => sanitize_text_field($action),
            'details' => sanitize_textarea_field($details),
            'created_at' => current_time('mysql')
        ]);
    }
    function wsi_get_user_label($uid) {
        $user = get_userdata($uid);
        if ($user) {
            $name = trim($user->display_name);
            if ($name === '') {
                $name = $user->user_login;
            }
            if ($name !== '') {
                return $name;
            }
        }
        return "User #{$uid}";
    }
    function wsi_notify_admin($subject, $message) {
        $opts = wsi_get_opts();
        if (empty($opts['email_notifications'])) return;
        $admin = get_option('admin_email');
        if (is_email($admin)) wp_mail($admin, $subject, $message);
    }
    function wsi_notify_user($uid, $subject, $message) {
        $opts = wsi_get_opts();
        $u = get_userdata($uid);

        // Send email if enabled
        if (!empty($opts['email_notifications']) && $u && is_email($u->user_email)) {
            wp_mail($u->user_email, $subject, $message);
        }

        // Store in-app notification
        $notifications = get_user_meta($uid, 'wsi_notifications', true);
        if (!is_array($notifications)) {
            $notifications = [];
        }

        $notifications[] = [
            'title'   => $subject,
            'body'    => $message,
            'type'    => 'info',
            'time'    => current_time('timestamp'),
            'read'    => false,
        ];

        // Keep only the latest 30
        $notifications = array_slice($notifications, -30);

        update_user_meta($uid, 'wsi_notifications', $notifications);
    }

    /* -------------------------------------------------------------------------
       Invite code helpers and invite-only registration
    ------------------------------------------------------------------------- */
    function wsi_ensure_invite_code($uid) {
        $code = get_user_meta($uid, 'wsi_invite_code', true);
        if (empty($code)) {
            $u = get_userdata($uid);
            $code = sanitize_text_field($u->user_login . '-' . substr(wp_generate_password(6, false, false), 0, 6));
            update_user_meta($uid, 'wsi_invite_code', $code);
        }
        return $code;
    }

    add_action('init', function() {
        add_rewrite_rule(
            '^wsi/signup/([^/]+)/?$',
            'index.php?wsi_referral=$matches[1]',
            'top'
        );
    });

    add_filter('query_vars', function($vars) {
        $vars[] = 'wsi_referral';
        return $vars;
    });

    function wsi_get_invite_link($uid = 0) {
        if (!$uid) {
            $uid = get_current_user_id();
        }

        $code = wsi_ensure_invite_code($uid);

        // Return pretty URL: site.com/wsi/signup/CODE
        return site_url("wsi/signup/?ref=$code");
    }

    function wsi_get_register_page() {
        $id = get_option('wsi_register_page_id');
        if ($id) return get_permalink($id);
        $pages = get_posts(['post_type' => 'page', 'numberposts' => -1, 'post_status' => 'publish']);
        foreach ($pages as $p) {
            if (has_shortcode($p->post_content, 'wsi_register')) {
                update_option('wsi_register_page_id', $p->ID);
                return get_permalink($p->ID);
            }
        }
        return home_url('/');
    }

    add_action('init', 'wsi_capture_referrer');
    function wsi_capture_referrer() {
        if (!empty($_GET['ref'])) {
            $ref = sanitize_text_field($_GET['ref']);
            setcookie('wsi_ref', $ref, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
            $_COOKIE['wsi_ref'] = $ref;
        }
        // block register page without ref cookie
        if (!is_user_logged_in()) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($uri, '/register') !== false) {
                if (empty($_GET['ref']) && empty($_COOKIE['wsi_ref'])) {
                    wp_safe_redirect(home_url('/'));
                    exit;
                }
            }
        }
    }

    add_action('user_register', 'wsi_attach_inviter');
    function wsi_attach_inviter($user_id) {
        $ref = '';
        if (!empty($_GET['ref'])) $ref = sanitize_text_field($_GET['ref']);
        if (empty($ref) && !empty($_COOKIE['wsi_ref'])) $ref = sanitize_text_field($_COOKIE['wsi_ref']);
        if (!empty($ref)) {
            $found = get_users(['meta_key' => 'wsi_invite_code', 'meta_value' => $ref, 'number' => 1]);
            if (!empty($found)) {
                $inviter_id = intval($found[0]->ID);
                update_user_meta($user_id, 'wsi_inviter_id', $inviter_id);
                update_user_meta($user_id, 'wsi_referred_by', $inviter_id); // mirror for compatibility
            }
        }
        wsi_ensure_invite_code($user_id);
    }

// Send welcome email using admin template (if configured)
add_action('user_register', function($user_id) {
    // Queue instead of sending synchronously to avoid slow mail blocking signup
    if (function_exists('wsi_queue_email_template')) {
        wsi_queue_email_template($user_id, 'email_registration_welcome');
    } else {
        wsi_send_email_template($user_id, 'email_registration_welcome');
    }
}, 20, 1);

    add_action('template_redirect', function () {

        $ref = get_query_var('wsi_referral');

        // Stop if not a referral URL
        if (empty($ref)) return;

        // Set referral cookie
        $ref = sanitize_text_field($ref);
        setcookie('wsi_ref', $ref, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
        $_COOKIE['wsi_ref'] = $ref;

        // DO NOT REDIRECT
        // Just allow the page to load normally on /wsi/signup/<code>
    });



    /* -------------------------------------------------------------------------
       Admin menu (fixed slugs and callbacks)
    ------------------------------------------------------------------------- */
    add_action('admin_menu', 'wsi_admin_menu');
    function wsi_admin_menu() {
        $cap = 'wsi_admin_access';
        add_menu_page('WSI', 'WSI', $cap, 'wsi_main', 'wsi_admin_dashboard', 'dashicons-chart-area', 3);
        add_submenu_page('wsi_main', 'Users', 'Users', $cap, 'wsi_users', 'wsi_admin_users');
        add_submenu_page('wsi_main', 'Deposits', 'Deposits', $cap, 'wsi_deposits', 'wsi_admin_deposits');
        add_submenu_page('wsi_main', 'Withdrawals', 'Withdrawals', $cap, 'wsi_withdrawals', 'wsi_admin_withdrawals');
        add_submenu_page('wsi_main', 'Stocks', 'Stocks', $cap, 'wsi_stocks', 'wsi_admin_stocks');
        add_submenu_page('wsi_main', 'Transactions', 'Transactions', $cap, 'wsi_transactions', 'wsi_admin_transactions');
        add_submenu_page('wsi_main', 'Settings', 'Settings', $cap, 'wsi_settings', 'wsi_admin_settings');
    }

    function wsi_admin_dashboard() {
        if (!wsi_admin_can()) { wp_die(__('You do not have permission to access this page.')); }
        global $wpdb;
        $users_count = count_users();
        $opts = wsi_get_opts();
        $pending_deposits = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wsi_deposits WHERE status='pending'");
        $pending_withdrawals = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wsi_withdrawals WHERE status='pending'");
        ?>
        <div class="wrap">
          <h1>WSI Admin Dashboard</h1>
          <p>Users: <?php echo intval($users_count['total_users']); ?> | Pending deposits: <?php echo intval($pending_deposits); ?> | Pending withdrawals: <?php echo intval($pending_withdrawals); ?></p>
          <p>Daily main interest: <?php echo esc_html($opts['main_daily_percent'] ?? 2.29); ?>% | Min invest: $<?php echo esc_html($opts['min_invest'] ?? 50); ?></p>
        </div>
        <?php
    }


    /* -------------------------------------------------------------------------
       ADMIN: Users page
    ------------------------------------------------------------------------- */
    function wsi_admin_users() {
        if (!wsi_admin_can()) { wp_die(__('You do not have permission to access this page.')); }

        // Handle admin actions
        if (!empty($_POST['action_user']) && check_admin_referer('wsi_users_nonce')) {
            $act = sanitize_text_field($_POST['action_user']);
            $uid = intval($_POST['user_id']);
            $amt = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

            switch ($act) {
                case 'delete':
                    require_once(ABSPATH . 'wp-admin/includes/user.php');
                    wp_delete_user($uid);
                    echo '<div class="notice notice-success"><p>User deleted</p></div>';
                    wsi_audit(get_current_user_id(), 'delete_user', "Deleted {$uid}");
                    break;
                case 'suspend':
                    update_user_meta($uid, 'wsi_suspended', 1);
                    echo '<div class="notice notice-success"><p>User suspended</p></div>';
                    wsi_audit(get_current_user_id(), 'suspend_user', "Suspended {$uid}");
                    break;
                case 'unsuspend':
                    delete_user_meta($uid, 'wsi_suspended');
                    echo '<div class="notice notice-success"><p>User unsuspended</p></div>';
                    wsi_audit(get_current_user_id(), 'unsuspend_user', "Unsuspended {$uid}");
                    break;
                case 'credit':
                    wsi_inc_main($uid, $amt);
                    wsi_log_tx($uid, $amt, 'admin_credit', 'Admin credited');
                    echo '<div class="notice notice-success"><p>Credited $' . number_format($amt,2) . '</p></div>';
                    wsi_audit(get_current_user_id(), 'credit_user', "Credited {$amt} to {$uid}");
                    break;
                case 'debit':
                    wsi_inc_main($uid, -$amt);
                    wsi_log_tx($uid, $amt, 'admin_debit', 'Admin debited');
                    echo '<div class="notice notice-success"><p>Debited $' . number_format($amt,2) . '</p></div>';
                    wsi_audit(get_current_user_id(), 'debit_user', "Debited {$amt} from {$uid}");
                    break;
            }
        }

        $users = get_users(['number' => 200, 'orderby' => 'ID', 'order' => 'DESC']);
        ?>

        <div class="wrap">
            <h1>Users</h1>
            <table class="widefat striped" id="wsi-users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Login</th>
                        <th>Email</th>
                        <th>Main</th>
                        <th>Profit</th>
                        <th>Status</th>
                        <th>Actions</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u) :
                        $main   = number_format(wsi_get_main($u->ID), 2);
                        $profit = number_format(wsi_get_profit($u->ID), 2);
                        $status = get_user_meta($u->ID, 'wsi_suspended', true) ? 'Suspended' : 'Active';

                        // User meta
                        $first_name = get_user_meta($u->ID, 'first_name', true);
                        $last_name  = get_user_meta($u->ID, 'last_name', true);
                        $phone      = get_user_meta($u->ID, 'phone', true);
                        $birth      = get_user_meta($u->ID, 'birth_date', true);
                        $addr1      = get_user_meta($u->ID, 'address1', true);
                        $addr2      = get_user_meta($u->ID, 'address2', true);
                        $landmark   = get_user_meta($u->ID, 'landmark', true);
                        $street     = get_user_meta($u->ID, 'street', true);
                        $country    = get_user_meta($u->ID, 'country', true);
                        $zip        = get_user_meta($u->ID, 'zip', true);
                        $state      = get_user_meta($u->ID, 'state', true);
                        $city       = get_user_meta($u->ID, 'city', true);
                    ?>
                    <tr>
                        <td><?php echo intval($u->ID); ?></td>
                        <td><?php echo esc_html($u->user_login); ?></td>
                        <td><?php echo esc_html($u->user_email); ?></td>
                        <td>$<?php echo $main; ?></td>
                        <td>$<?php echo $profit; ?></td>
                        <td><?php echo esc_html($status); ?></td>
                        <td>
                            <!-- Admin actions -->
                            <form method="post" style="display:inline"><?php wp_nonce_field('wsi_users_nonce'); ?>
                                <input type="hidden" name="user_id" value="<?php echo intval($u->ID); ?>">
                                <button name="action_user" value="delete" class="button" onclick="return confirm('Delete?')">Delete</button>
                            </form>
                            <form method="post" style="display:inline"><?php wp_nonce_field('wsi_users_nonce'); ?>
                                <input type="hidden" name="user_id" value="<?php echo intval($u->ID); ?>">
                                <button name="action_user" value="suspend" class="button">Suspend</button>
                            </form>
                            <form method="post" style="display:inline">
                                <?php wp_nonce_field('wsi_users_nonce'); ?>
                                <input type="hidden" name="user_id" value="<?php echo intval($u->ID); ?>">
                                <input name="amount" type="number" step="0.01" placeholder="Amt">
                                <button name="action_user" value="credit" class="button">Credit</button>
                            </form>
                            <form method="post" style="display:inline">
                                <?php wp_nonce_field('wsi_users_nonce'); ?>
                                <input type="hidden" name="user_id" value="<?php echo intval($u->ID); ?>">
                                <input name="amount" type="number" step="0.01" placeholder="Amt">
                                <button name="action_user" value="debit" class="button">Debit</button>
                            </form>
                        </td>
                        <td>
                            <button class="button toggle-details">Show Details</button>
                        </td>
                    </tr>

                    <!-- Collapsible nested table -->
                    <tr class="user-details-row" style="display:none;">
                        <td colspan="8">
                            <table class="nested-user-table">
                                <tr><th>First Name</th><td><?php echo esc_html($first_name); ?></td></tr>
                                <tr><th>Last Name</th><td><?php echo esc_html($last_name); ?></td></tr>
                                <tr><th>Phone</th><td><?php echo esc_html($phone); ?></td></tr>
                                <tr><th>Birth Date</th><td><?php echo esc_html($birth); ?></td></tr>
                                <tr><th>Address Line 1</th><td><?php echo esc_html($addr1); ?></td></tr>
                                <tr><th>Address Line 2</th><td><?php echo esc_html($addr2); ?></td></tr>
                                <tr><th>Landmark</th><td><?php echo esc_html($landmark); ?></td></tr>
                                <tr><th>Street</th><td><?php echo esc_html($street); ?></td></tr>
                                <tr><th>Country</th><td><?php echo esc_html($country); ?></td></tr>
                                <tr><th>Zip</th><td><?php echo esc_html($zip); ?></td></tr>
                                <tr><th>State</th><td><?php echo esc_html($state); ?></td></tr>
                                <tr><th>City</th><td><?php echo esc_html($city); ?></td></tr>
                            </table>
                        </td>
                    </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <style>
            .nested-user-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 5px;
            }
            .nested-user-table th, .nested-user-table td {
                border: 1px solid #ddd;
                padding: 6px 10px;
                text-align: left;
            }
            .nested-user-table th {
                background-color: #f1f1f1;
                width: 150px;
            }
            .user-details-row td {
                padding: 0;
                background-color: #fafafa;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const buttons = document.querySelectorAll('.toggle-details');
                buttons.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const row = this.closest('tr').nextElementSibling;
                        if (row.style.display === 'none') {
                            row.style.display = 'table-row';
                            this.textContent = 'Hide Details';
                        } else {
                            row.style.display = 'none';
                            this.textContent = 'Show Details';
                        }
                    });
                });
            });
        </script>

    <?php
    }




    /* -------------------------------------------------------------------------
       ADMIN: Deposits page (pending)
    ------------------------------------------------------------------------- */
    function wsi_admin_deposits() {
        if (!wsi_admin_can()) { wp_die(__('You do not have permission to access this page.')); }
        
        global $wpdb;
        $t = $wpdb->prefix . 'wsi_deposits';
        
        // Handle Approve / Decline
        if (!empty($_POST['action_deposit']) && check_admin_referer('wsi_deposits_nonce')) {
            $id = intval($_POST['deposit_id']);
            $action = sanitize_text_field($_POST['action_deposit']);
            
            error_log("WSI Admin: Processing deposit #$id - Action: $action");
            
            $dep = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d", $id));
            
            if ($dep) {
                if ($action === 'approve') {
                    // Update status
                    $updated = $wpdb->update(
                        $t, 
                        ['status' => 'approved'], 
                        ['id' => $id],
                        ['%s'],
                        ['%d']
                    );
                    
                    if ($updated === false) {
                        error_log("WSI: Failed to update deposit status - " . $wpdb->last_error);
                        echo '<div class="notice notice-error"><p>Database error occurred.</p></div>';
                    } else {
                        // Credit user balance
                        wsi_inc_main($dep->user_id, floatval($dep->amount));
                        
                        // Log transaction (this will trigger email)
                        wsi_log_tx($dep->user_id, $dep->amount, 'deposit_approved', "Deposit #{$id} approved by admin");
                        
                        // Apply referral bonus
                        wsi_apply_referral($dep->user_id, floatval($dep->amount), $id);
                        
                        // Audit
                        wsi_audit(get_current_user_id(), 'approve_deposit', "Approved deposit #{$id}");
                        
                        echo '<div class="notice notice-success"><p>Deposit approved successfully.</p></div>';
                        
                        error_log("WSI Admin: Deposit #$id approved successfully");
                    }
                    
                } elseif ($action === 'decline') {
                    $updated = $wpdb->update(
                        $t, 
                        ['status' => 'declined'], 
                        ['id' => $id],
                        ['%s'],
                        ['%d']
                    );
                    
                    if ($updated !== false) {
                        // Log transaction (no email for declined deposits by default)
                        wsi_log_tx($dep->user_id, $dep->amount, 'deposit_declined', "Deposit #{$id} declined by admin");
                        
                        // Audit
                        wsi_audit(get_current_user_id(), 'decline_deposit', "Declined deposit #{$id}");
                        
                        echo '<div class="notice notice-success"><p>Deposit declined.</p></div>';
                        
                        error_log("WSI Admin: Deposit #$id declined");
                    }
                }
            } else {
                echo '<div class="notice notice-error"><p>Deposit not found.</p></div>';
                error_log("WSI Admin: Deposit #$id not found");
            }
        }
        
        // Get pending deposits with error handling
        $rows = $wpdb->get_results("SELECT * FROM $t WHERE TRIM(LOWER(status))='pending' ORDER BY created_at DESC LIMIT 100");
        
        if ($wpdb->last_error) {
            error_log("WSI: Error loading deposits - " . $wpdb->last_error);
            echo '<div class="notice notice-error"><p>Error loading deposits.</p></div>';
            return;
        }
        
        ?>
        <div class="wrap">
            <h1>Pending Deposits</h1>
            
            <?php if (empty($rows)) { ?>
                <p>No pending deposits.</p>
            <?php } else { ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Amount (USD)</th>
                            <th>Amount (Local)</th>
                            <th>Payment Type</th>
                            <th>Wallet</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r) { 
                        $u = get_userdata($r->user_id);
                        $walletLabels = [
                            'usdt_trc' => 'USDT (TRC20)',
                            'usdt_erc' => 'USDT (ERC20)',
                            'sol'      => 'Solana (SOL)',
                            'eth'      => 'ETH',
                            // Legacy fallbacks
                            'btc'      => 'BTC',
                            'usdt'     => 'USDT'
                        ];
                        $walletCode = $r->crypto_wallet ?? '';
                        $walletLabel = $walletLabels[$walletCode] ?? ($walletCode ?: '—');
                    ?>
                        <tr>
                            <td><?php echo intval($r->id); ?></td>
                            <td><?php echo esc_html($u ? $u->user_login : 'User #' . $r->user_id); ?></td>
                            <td><?php echo esc_html($u ? $u->user_email : 'N/A'); ?></td>
                            <td><strong>$<?php echo number_format($r->amount, 2); ?></strong></td>
                            <td>
                                <?php 
                                $local = !empty($r->amount_local) ? number_format($r->amount_local, 2) : '-';
                                echo esc_html($local);
                                ?>
                            </td>
                            <td><?php echo esc_html(ucfirst($r->payment_type ?? 'Naira')); ?></td>
                            <td>
                                <small><?php echo esc_html($walletLabel); ?></small>
                            </td>
                            <td><?php echo esc_html(ucfirst($r->status)); ?></td>
                            <td><?php echo esc_html(date('M j, g:i A', strtotime($r->created_at))); ?></td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Approve this deposit?');">
                                    <?php wp_nonce_field('wsi_deposits_nonce'); ?>
                                    <input type="hidden" name="deposit_id" value="<?php echo intval($r->id); ?>">
                                    <button name="action_deposit" value="approve" class="button button-primary">
                                        ✓ Approve
                                    </button>
                                </form>
                                <form method="post" style="display:inline;margin-left:5px;" onsubmit="return confirm('Decline this deposit?');">
                                    <?php wp_nonce_field('wsi_deposits_nonce'); ?>
                                    <input type="hidden" name="deposit_id" value="<?php echo intval($r->id); ?>">
                                    <button name="action_deposit" value="decline" class="button">
                                        ✗ Decline
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
        <?php
    }



    /* -------------------------------------------------------------------------
       ADMIN: Withdrawals page (pending)
    ------------------------------------------------------------------------- */
    function wsi_admin_withdrawals() {
        if (!wsi_admin_can()) { wp_die(__('You do not have permission to access this page.')); }
        
        global $wpdb;
        $t = $wpdb->prefix . 'wsi_withdrawals';
        
        // Handle admin actions
        if (!empty($_POST['action_withdraw']) && check_admin_referer('wsi_withdraws_nonce')) {
            $id = intval($_POST['withdraw_id']);
            $action = sanitize_text_field($_POST['action_withdraw']);
            
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d", $id));
            
            if ($row) {
                if ($action === 'approve') {
                    $updated = $wpdb->update(
                        $t, 
                        [
                            'status' => 'approved',
                            'admin_note' => 'Paid by Admin ID: ' . get_current_user_id() . ' on ' . current_time('mysql')
                        ], 
                        ['id' => $id],
                        ['%s', '%s'],
                        ['%d']
                    );
                    
                    if ($updated !== false) {
                        wsi_log_tx($row->user_id, $row->amount, 'withdraw_approved', "Withdrawal #{$id} approved");
                        wsi_notify_user($row->user_id, 'Withdrawal Approved', "Your withdrawal of $" . number_format($row->amount, 2) . " has been processed and sent.");
                        wsi_audit(get_current_user_id(), 'approve_withdraw', "Approved withdrawal #{$id} for user #{$row->user_id}");
                        echo '<div class="notice notice-success"><p>Withdrawal approved successfully.</p></div>';
                    } else {
                        error_log('WSI: Failed to approve withdrawal #' . $id . ' - ' . $wpdb->last_error);
                        echo '<div class="notice notice-error"><p>Failed to approve withdrawal. Check error logs.</p></div>';
                    }
                    
                } elseif ($action === 'decline') {
                    $updated = $wpdb->update(
                        $t, 
                        [
                            'status' => 'declined',
                            'admin_note' => 'Declined by Admin ID: ' . get_current_user_id() . ' on ' . current_time('mysql')
                        ], 
                        ['id' => $id],
                        ['%s', '%s'],
                        ['%d']
                    );
                    
                    if ($updated !== false) {
                        // Refund to profit balance
                        wsi_inc_profit($row->user_id, floatval($row->amount));
                        wsi_log_tx($row->user_id, $row->amount, 'withdraw_refund', "Withdrawal #{$id} declined, amount refunded to profit balance");
                        wsi_notify_user($row->user_id, 'Withdrawal Declined', 'Your withdrawal request was declined and the amount has been refunded to your profit balance.');
                        wsi_audit(get_current_user_id(), 'decline_withdraw', "Declined withdrawal #{$id} for user #{$row->user_id}");
                        echo '<div class="notice notice-success"><p>Withdrawal declined and refunded.</p></div>';
                    } else {
                        error_log('WSI: Failed to decline withdrawal #' . $id . ' - ' . $wpdb->last_error);
                        echo '<div class="notice notice-error"><p>Failed to decline withdrawal. Check error logs.</p></div>';
                    }
                }
            } else {
                echo '<div class="notice notice-error"><p>Withdrawal request not found.</p></div>';
            }
        }
        
        // Load pending withdrawals with error handling
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $t WHERE TRIM(LOWER(status))=%s ORDER BY created_at DESC",
            'pending'
        ));
        
        // Check for database errors
        if ($wpdb->last_error) {
            error_log('WSI: Error loading withdrawals - ' . $wpdb->last_error);
            echo '<div class="notice notice-error"><p>Error loading withdrawals. Please check database structure.</p></div>';
            return;
        }
        ?>
        <div class="wrap">
            <h1>Pending Withdrawals</h1>
            
            <?php if (empty($rows)) { ?>
                <p>No pending withdrawals.</p>
            <?php } else { ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Amount</th>
                            <th>Network</th>
                            <th>Wallet Address</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r) { 
                        $u = get_userdata($r->user_id);
                        $method = !empty($r->method) ? esc_html($r->method) : 'N/A';
                        $wallet = !empty($r->account_details) ? esc_html($r->account_details) : 'Not provided';
                    ?>
                        <tr>
                            <td><?php echo intval($r->id); ?></td>
                            <td>
                                <?php echo esc_html($u ? $u->user_login : 'User #' . $r->user_id); ?>
                                <?php if (!$u) { ?>
                                    <br><small style="color:red;">⚠ User not found</small>
                                <?php } ?>
                            </td>
                            <td><?php echo esc_html($u ? $u->user_email : 'N/A'); ?></td>
                            <td><strong>$<?php echo number_format($r->amount, 2); ?></strong></td>
                            <td><?php echo $method; ?></td>
                            <td>
                                <code style="word-break:break-all;display:block;max-width:250px;">
                                    <?php echo $wallet; ?>
                                </code>
                            </td>
                            <td><?php echo esc_html(date('M j, Y g:i A', strtotime($r->created_at))); ?></td>
                            <td>
                                <button class="button button-primary wsi-admin-approve-btn" data-id="<?php echo intval($r->id); ?>" data-nonce="<?php echo wp_create_nonce('wsi_withdraws_nonce'); ?>" onclick="wsiAdminApproveWithdrawal(this)">
                                    ✓ Approve
                                </button>
                                <button class="button wsi-admin-decline-btn" data-id="<?php echo intval($r->id); ?>" data-nonce="<?php echo wp_create_nonce('wsi_withdraws_nonce'); ?>" onclick="wsiAdminDeclineWithdrawal(this)" style="margin-left:5px;">
                                    ✗ Decline & Refund
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
        
        <style>
            .widefat td code {
                background: #f0f0f0;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
            }
            .widefat td small {
                font-size: 11px;
            }
        </style>
        
        <script>
        function wsiAdminApproveWithdrawal(btn) {
            if (!confirm('Approve this withdrawal? This action cannot be undone.')) return;
            
            const id = btn.dataset.id;
            const nonce = btn.dataset.nonce;
            const data = new FormData();
            data.append('action', 'wsi_admin_approve_withdrawal');
            data.append('withdraw_id', id);
            data.append('_wpnonce', nonce);
            
            btn.disabled = true;
            btn.textContent = 'Processing...';
            
            fetch(<?php echo json_encode(admin_url('admin-ajax.php')); ?>, {
                method: 'POST',
                body: data
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    alert('Withdrawal approved successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (d.data?.message || 'Unknown error'));
                    btn.disabled = false;
                    btn.textContent = '✓ Approve';
                }
            })
            .catch(e => {
                alert('Error: ' + e.message);
                btn.disabled = false;
                btn.textContent = '✓ Approve';
            });
        }
        
        function wsiAdminDeclineWithdrawal(btn) {
            if (!confirm('Decline and refund this withdrawal?')) return;
            
            const id = btn.dataset.id;
            const nonce = btn.dataset.nonce;
            const data = new FormData();
            data.append('action', 'wsi_admin_decline_withdrawal');
            data.append('withdraw_id', id);
            data.append('_wpnonce', nonce);
            
            btn.disabled = true;
            btn.textContent = 'Processing...';
            
            fetch(<?php echo json_encode(admin_url('admin-ajax.php')); ?>, {
                method: 'POST',
                body: data
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    alert('Withdrawal declined and refunded');
                    location.reload();
                } else {
                    alert('Error: ' + (d.data?.message || 'Unknown error'));
                    btn.disabled = false;
                    btn.textContent = '✗ Decline & Refund';
                }
            })
            .catch(e => {
                alert('Error: ' + e.message);
                btn.disabled = false;
                btn.textContent = '✗ Decline & Refund';
            });
        }
        </script>
        <?php
    }


    /* -------------------------------------------------------------------------
       ADMIN: Stocks page (complete)
    ------------------------------------------------------------------------- */
    function wsi_admin_stocks() {
        if (!wsi_admin_can()) { wp_die(__('You do not have permission to access this page.')); }
        global $wpdb;
        $table = $wpdb->prefix . 'wsi_stocks';

        // add new stock
        if (isset($_POST['wsi_add_stock']) && check_admin_referer('wsi_add_stock_nonce')) {

            $image_url = '';

            // handle image upload
            if (!empty($_FILES['stock_image']['name'])) {

                $file = $_FILES['stock_image'];

                // allowed types
                $allowed = ['image/png', 'image/jpeg', 'image/jpg'];

                if (in_array($file['type'], $allowed)) {

                    // check dimensions
                    $size = getimagesize($file['tmp_name']);
                    if ($size) {
                        $width  = $size[0];
                        $height = $size[1];

                        if ($width == 80 && $height == 80) {
                            require_once(ABSPATH . 'wp-admin/includes/file.php');
                            $upload = wp_handle_upload($file, ['test_form' => false]);

                            if (!isset($upload['error'])) {
                                $image_url = $upload['url'];
                            } else {
                                echo '<div class="error"><p>Image upload error: ' . esc_html($upload['error']) . '</p></div>';
                            }
                        } else {
                            echo '<div class="error"><p>Image must be exactly 80×80 pixels.</p></div>';
                        }
                    }
                } else {
                    echo '<div class="error"><p>Invalid image format. Only PNG and JPG/JPEG allowed.</p></div>';
                }
            }

            // insert stock
            $wpdb->insert($table, [
                'name'        => sanitize_text_field($_POST['stock_name']),
                'price'       => floatval($_POST['stock_price']),
                'rate_percent'     => floatval($_POST['stock_percent']),
                'rate_period'   => sanitize_text_field($_POST['stock_rate_type']),
                'image'       => $image_url, // NEW
                'created_at'  => current_time('mysql'),
            ]);

            echo '<div class="updated"><p>Stock added.</p></div>';
        }

        // delete
        if (!empty($_GET['del'])) {
            $wpdb->delete($table, ['id' => intval($_GET['del'])]);
            echo '<div class="updated"><p>Stock deleted.</p></div>';
        }

        $stocks = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
        ?>
        <div class="wrap">
            <h1>Manage Stocks</h1>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('wsi_add_stock_nonce'); ?>
                <table class="form-table">
                    <tr><th>Name</th><td><input type="text" name="stock_name" required></td></tr>
                    <tr><th>Price</th><td><input type="number" step="0.01" name="stock_price" required></td></tr>
                    <tr><th>Interest %</th><td><input type="number" step="0.01" name="stock_percent" required></td></tr>
                    <tr><th>Rate Type</th>
                        <td>
                            <select name="stock_rate_type">
                                <option value="daily">Daily</option>
                                <option value="hourly">Hourly</option>
                            </select>
                        </td>
                    </tr>
                    <tr><th>Stock Image<br><small>80×80 px</small></th>
                        <td><input type="file" name="stock_image" accept="image/png, image/jpeg"></td>
                    </tr>
                </table>
                <p><button class="button button-primary" name="wsi_add_stock">Add Stock</button></p>
            </form>
            <hr>
            <h2>Available Stocks</h2>
            <?php if ($stocks): ?>
            <table class="widefat fixed striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Icon</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>%</th>
                    <th>Rate</th>
                    <th>Date</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($stocks as $s): ?>
                    <tr>
                        <td><?php echo esc_html($s->id); ?></td>
                        <td>
                            <?php if (!empty($s->image)): ?>
                                <img src="<?php echo esc_url($s->image); ?>" width="40" height="40" style="border-radius:4px;">
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($s->name); ?></td>
                        <td><?php echo number_format($s->price,2); ?></td>
                        <td><?php echo esc_html($s->rate_percent); ?></td>
                        <td><?php echo esc_html(ucfirst($s->rate_period)); ?></td>
                        <td><?php echo esc_html($s->created_at); ?></td>
                        <td><a href="?page=wsi_stocks&del=<?php echo esc_attr($s->id); ?>" onclick="return confirm('Delete this stock?')">Delete</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No stocks yet.</p>
            <?php endif; ?>
        </div>
        <?php
    }



    /* -------------------------------------------------------------------------
       ADMIN: Transactions (show all)
    ------------------------------------------------------------------------- */

    function wsi_admin_transactions() {
        global $wpdb;
        $t = $wpdb->prefix . 'wsi_transactions';

        echo '<div class="wrap"><h1>All Transactions</h1>';

        /* ---------------- FILTERS ---------------- */
        $allowed_filters = [
            '' => '',
            'deposit'      => 'deposit',
            'withdrawal'   => 'withdraw_request',
            'smart'        => 'smart_farm_interest',
            'reinvest'     => 'reinvest',
            'stocks'       => 'buy_stock'
        ];

        $filter_key  = isset($_GET['ftype']) ? sanitize_text_field($_GET['ftype']) : '';
        $filter_type = isset($allowed_filters[$filter_key]) ? $allowed_filters[$filter_key] : '';

        /* ---------------- SORTING ---------------- */
        $allowed_sort = ['id','user_id','amount','type','created_at'];
        $requested_orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
        $orderby = in_array($requested_orderby, $allowed_sort, true) ? $requested_orderby : 'created_at';
        $order = (isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC') ? 'ASC' : 'DESC';

        /* ---------------- SEARCH (username / email) ---------------- */
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $searched_user_id = null;

        if ($search !== '') {
            $user = get_user_by('login', $search);
            if (!$user) $user = get_user_by('email', $search);
            if ($user) {
                $searched_user_id = $user->ID;
            } else {
                $searched_user_id = -1; // forces empty result
            }
        }

        /* ---------------- PAGINATION ---------------- */
        $per_page = 30;
        $page = max(1, intval($_GET['pg'] ?? 1));
        $offset = ($page - 1) * $per_page;

        /* ---------------- WHERE CLAUSE ---------------- */
        $where = "WHERE 1=1";
        $params = [];

        if ($filter_type !== '') {
            $where .= " AND type=%s";
            $params[] = $filter_type;
        }

        if ($search !== '') {
            $where .= " AND user_id=%d";
            $params[] = $searched_user_id;
        }

        /* ---------------- COUNT ---------------- */
        $count_sql = "SELECT COUNT(*) FROM {$t} $where";
        $count = (!empty($params))
            ? $wpdb->get_var($wpdb->prepare($count_sql, $params))
            : $wpdb->get_var($count_sql);

        /* ---------------- MAIN QUERY ---------------- */
        $main_sql = "SELECT * FROM {$t}
                     $where
                     ORDER BY {$orderby} {$order}
                     LIMIT %d OFFSET %d";

        $main_params = $params;
        $main_params[] = $per_page;
        $main_params[] = $offset;

        $rows = (!empty($params))
            ? $wpdb->get_results($wpdb->prepare($main_sql, $main_params))
            : $wpdb->get_results($wpdb->prepare($main_sql, $per_page, $offset));

        /* ---------------- CSV EXPORT ---------------- */
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $export_sql = "SELECT * FROM {$t} $where ORDER BY {$orderby} {$order}";
            $export_rows = (!empty($params))
                ? $wpdb->get_results($wpdb->prepare($export_sql, $params), ARRAY_A)
                : $wpdb->get_results($export_sql, ARRAY_A);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename=admin-transactions-' . date('Ymd') . '.csv');

            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','User','Amount','Type','Description','Date']);

            foreach ($export_rows as $r) {
                $user_info   = get_userdata($r['user_id']);
                $user_display = $user_info ? $user_info->user_login : $r['user_id'];

                fputcsv($out, [
                    $r['id'],
                    $user_display,
                    $r['amount'],
                    $r['type'],
                    $r['description'],
                    $r['created_at']
                ]);
            }
            fclose($out);
            exit;
        }

        /* ---------------- QUERY STRING BUILDER ---------------- */
        $build_qs = function($overrides = []) {
            $q = $_GET;
            foreach ($overrides as $k => $v) {
                if ($v === null) unset($q[$k]);
                else $q[$k] = $v;
            }
            return esc_url(add_query_arg($q));
        };

        /* ---------------- SEARCH, FILTER & EXPORT UI ---------------- */
        echo '<div style="margin-bottom:15px; display:flex; gap:20px; align-items:center;">';

        echo '<form method="get" action="" style="display:flex; gap:10px; align-items:center;">';
        echo '<input type="hidden" name="page" value="wsi_admin_transactions">';
        echo '<input type="text" name="search" value="' . esc_attr($search) . '" placeholder="Search username or email" />';
        echo '<button class="button">Search</button>';
        echo '</form>';

        echo '<div style="margin-left:20px;">
                <strong>Filter:</strong>
                <a href="'.$build_qs(['ftype'=>'','pg'=>1]).'">All</a> |
                <a href="'.$build_qs(['ftype'=>'deposit','pg'=>1]).'">Deposit</a> |
                <a href="'.$build_qs(['ftype'=>'withdrawal','pg'=>1]).'">Withdrawal</a> |
                <a href="'.$build_qs(['ftype'=>'smart','pg'=>1]).'">Smart Farming</a> |
                <a href="'.$build_qs(['ftype'=>'reinvest','pg'=>1]).'">Reinvest</a> |
                <a href="'.$build_qs(['ftype'=>'stocks','pg'=>1]).'">Stocks</a>
              </div>';

        echo '<div style="margin-left:auto;">';
        $export_qs = $build_qs(['export'=>'csv','pg'=>null,'_wpnonce'=>wp_create_nonce('wsi_admin_export')]);
        echo '<a class="button" href="'.$export_qs.'">Export CSV</a>';
        echo '</div>';

        echo '</div>';

        /* ---------------- TABLE ---------------- */
        echo '<table class="widefat fixed striped"><thead><tr>';

        echo '<th><a href="'.$build_qs(['orderby'=>'id','order'=>($orderby==='id'&&$order==='DESC')?'ASC':'DESC','pg'=>1]).'">ID</a></th>';
        echo '<th><a href="'.$build_qs(['orderby'=>'user_id','order'=>($orderby==='user_id'&&$order==='DESC')?'ASC':'DESC','pg'=>1]).'">User</a></th>';
        echo '<th><a href="'.$build_qs(['orderby'=>'amount','order'=>($orderby==='amount'&&$order==='DESC')?'ASC':'DESC','pg'=>1]).'">Amount</a></th>';
        echo '<th><a href="'.$build_qs(['orderby'=>'type','order'=>($orderby==='type'&&$order==='DESC')?'ASC':'DESC','pg'=>1]).'">Type</a></th>';
        echo '<th>Description</th>';
        echo '<th><a href="'.$build_qs(['orderby'=>'created_at','order'=>($orderby==='created_at'&&$order==='DESC')?'ASC':'DESC','pg'=>1]).'">Date</a></th>';

        echo '</tr></thead><tbody>';

        if ($rows) {
            foreach ($rows as $r) {
                $user_info = get_userdata($r->user_id);
                $user_display = $user_info ? esc_html($user_info->user_login) : esc_html($r->user_id);

                $row_style = ($r->type === 'smart_farm_interest') ? ' style="background:#e6ffe6;"' : '';

                echo "<tr{$row_style}>";
                echo "<td>{$r->id}</td>";
                echo "<td>{$user_display}</td>";
                echo "<td>$" . number_format((float)$r->amount,2) . "</td>";
                echo "<td>{$r->type}</td>";
                echo "<td>{$r->description}</td>";
                echo "<td>{$r->created_at}</td>";
                echo "</tr>";
            }
        } else {
            echo '<tr><td colspan="6">No transactions found.</td></tr>';
        }

        echo '</tbody></table>';

        /* ---------------- PAGINATION ---------------- */
        $pages = max(1, ceil(intval($count) / $per_page));

        if ($pages > 1) {
            echo '<div style="margin-top:12px;">';
            for ($i = 1; $i <= $pages; $i++) {
                if ($i == $page) echo "<strong>$i</strong> ";
                else echo '<a href="'.$build_qs(['pg'=>$i]).'">'.$i.'</a> ';
            }
            echo '</div>';
        }

        echo '</div>'; // wrap
    }

     /* -------------------------------------------------------------------------
         ADMIN: Settings
     ------------------------------------------------------------------------- */
     // Note: settings are saved inside `wsi_admin_settings()` via POST + nonce.
     // Avoid writing options at top-level which would overwrite admin values on every load.

    function wsi_admin_settings() {
        if (!wsi_admin_can()) { wp_die(__('You do not have permission to access this page.')); }

        $opts = wsi_get_opts();

        if (!empty($_POST['wsi_save_settings']) && check_admin_referer('wsi_settings_nonce')) {

            $daily = floatval($_POST['main_daily_percent']);
            $min = floatval($_POST['min_invest']);
            $mode = sanitize_text_field($_POST['deposit_mode']);
            $manual = sanitize_textarea_field($_POST['manual_payment_info'] ?? '');
            $email = !empty($_POST['email_notifications']) ? 1 : 0;
            $exchange_rate = floatval($_POST['exchange_rate'] ?? ($opts['exchange_rate'] ?? 1000));

            wsi_update_opt('main_daily_percent', $daily);
            wsi_update_opt('min_invest', $min);
            wsi_update_opt('deposit_mode', $mode);
            wsi_update_opt('manual_payment_info', $manual);
            wsi_update_opt('email_notifications', $email);
            wsi_update_opt('exchange_rate', $exchange_rate);

            $naira_info = sanitize_textarea_field($_POST['naira_payment_info'] ?? '');
            $usdt_trc_wallet = sanitize_text_field($_POST['usdt_trc_wallet'] ?? '');
            $usdt_trc_instruction = sanitize_textarea_field($_POST['usdt_trc_instruction'] ?? '');
            $usdt_erc_wallet = sanitize_text_field($_POST['usdt_erc_wallet'] ?? '');
            $usdt_erc_instruction = sanitize_textarea_field($_POST['usdt_erc_instruction'] ?? '');
            $sol_wallet = sanitize_text_field($_POST['sol_wallet'] ?? '');
            $sol_instruction = sanitize_textarea_field($_POST['sol_instruction'] ?? '');
            $eth_wallet = sanitize_text_field($_POST['eth_wallet'] ?? '');
            $eth_instruction = sanitize_textarea_field($_POST['eth_instruction'] ?? '');

            $allowed_roles = isset($_POST['wsi_admin_roles']) ? array_map('sanitize_key', (array) $_POST['wsi_admin_roles']) : [];
            if (empty($allowed_roles)) {
                $allowed_roles = ['administrator']; // prevent lockout
            }
            wsi_update_opt('admin_allowed_roles', $allowed_roles);

            wsi_update_opt('naira_payment_info', $naira_info);
            wsi_update_opt('usdt_trc_wallet', $usdt_trc_wallet);
            wsi_update_opt('usdt_trc_instruction', $usdt_trc_instruction);
            wsi_update_opt('usdt_erc_wallet', $usdt_erc_wallet);
            wsi_update_opt('usdt_erc_instruction', $usdt_erc_instruction);
            wsi_update_opt('sol_wallet', $sol_wallet);
            wsi_update_opt('sol_instruction', $sol_instruction);
            wsi_update_opt('eth_wallet', $eth_wallet);
            wsi_update_opt('eth_instruction', $eth_instruction);

            // NEW EMAIL TEMPLATES
            wsi_update_opt('email_deposit_received', sanitize_textarea_field($_POST['email_deposit_received']));
            wsi_update_opt('email_deposit_approved', sanitize_textarea_field($_POST['email_deposit_approved']));
            wsi_update_opt('email_withdraw_received', sanitize_textarea_field($_POST['email_withdraw_received']));
            wsi_update_opt('email_withdraw_approved', sanitize_textarea_field($_POST['email_withdraw_approved']));
            wsi_update_opt('email_withdraw_declined', sanitize_textarea_field($_POST['email_withdraw_declined']));
            wsi_update_opt('email_registration_welcome', sanitize_textarea_field($_POST['email_registration_welcome']));
            wsi_update_opt('email_smart_farming_on', sanitize_textarea_field($_POST['email_smart_farming_on'] ?? ''));
            wsi_update_opt('email_smart_farming_off', sanitize_textarea_field($_POST['email_smart_farming_off'] ?? ''));

            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }

        $opts = wsi_get_opts();
        ?>
        <div class="wrap"><h1>WSI Settings</h1>
        <form method="post"><?php wp_nonce_field('wsi_settings_nonce'); ?>
          <table class="form-table">

            <tr><th>Admin access roles</th>
                <td>
                    <?php
                    global $wp_roles;
                    $all_roles = $wp_roles ? $wp_roles->roles : [];
                    $allowed_roles = wsi_get_admin_allowed_roles();
                    foreach ($all_roles as $role_key => $role_data) {
                        $checked = in_array($role_key, $allowed_roles, true) ? 'checked' : '';
                        echo '<label style="display:inline-block;margin-right:12px;"><input type="checkbox" name="wsi_admin_roles[]" value="' . esc_attr($role_key) . '" ' . $checked . '> ' . esc_html($role_data['name']) . '</label>';
                    }
                    ?>
                    <p class="description">Users with any selected role can access WSI admin pages. Administrators always retain access.</p>
                </td>
            </tr>

            <tr><th>Main balance daily interest (%)</th>
                <td><input name="main_daily_percent" type="number" step="0.001" value="<?php echo esc_attr($opts['main_daily_percent'] ?? 2.29); ?>"></td></tr>

            <tr><th>Minimum investment $</th>
                <td><input name="min_invest" type="number" step="0.01" value="<?php echo esc_attr($opts['min_invest'] ?? 50); ?>"></td></tr>

            <tr><th>Exchange rate ($ per $1)</th>
                <td><input name="exchange_rate" type="number" step="0.01" value="<?php echo esc_attr($opts['exchange_rate'] ?? 1000); ?>"> 
                <small>How many Naira equals $1</small></td></tr>

            <tr><th>Deposit mode</th>
                <td><select name="deposit_mode">
                      <option value="manual" <?php selected(($opts['deposit_mode'] ?? 'manual'), 'manual'); ?>>Naira Payment</option>
                      <option value="auto" <?php selected(($opts['deposit_mode'] ?? 'manual'), 'auto'); ?>>Crypto Payment</option>
                    </select></td></tr>

            <!--tr><th>Manual payment info</th>
                <td><textarea name="manual_payment_info" rows="4"><?php echo esc_textarea($opts['manual_payment_info'] ?? ''); ?></textarea></td></tr-->

            <tr><th>Naira payment instructions</th>
                <td><textarea name="naira_payment_info" rows="4"><?php echo esc_textarea($opts['naira_payment_info'] ?? ''); ?></textarea></td></tr>

            <tr><th>USDT (TRC20) Wallet Address</th>
                <td><input name="usdt_trc_wallet" value="<?php echo esc_attr($opts['usdt_trc_wallet'] ?? ''); ?>" style="width:100%"></td></tr>

            <tr><th>USDT (TRC20) Payment Instruction</th>
                <td><textarea name="usdt_trc_instruction" rows="3"><?php echo esc_textarea($opts['usdt_trc_instruction'] ?? ''); ?></textarea></td></tr>

            <tr><th>USDT (ERC20) Wallet Address</th>
                <td><input name="usdt_erc_wallet" value="<?php echo esc_attr($opts['usdt_erc_wallet'] ?? ''); ?>" style="width:100%"></td></tr>

            <tr><th>USDT (ERC20) Payment Instruction</th>
                <td><textarea name="usdt_erc_instruction" rows="3"><?php echo esc_textarea($opts['usdt_erc_instruction'] ?? ''); ?></textarea></td></tr>

            <tr><th>Solana (SOL) Wallet Address</th>
                <td><input name="sol_wallet" value="<?php echo esc_attr($opts['sol_wallet'] ?? ''); ?>" style="width:100%"></td></tr>

            <tr><th>Solana Payment Instruction</th>
                <td><textarea name="sol_instruction" rows="3"><?php echo esc_textarea($opts['sol_instruction'] ?? ''); ?></textarea></td></tr>

            <tr><th>ETH Wallet Address</th>
                <td><input name="eth_wallet" value="<?php echo esc_attr($opts['eth_wallet'] ?? ''); ?>" style="width:100%"></td></tr>

            <tr><th>ETH Payment Instruction</th>
                <td><textarea name="eth_instruction" rows="3"><?php echo esc_textarea($opts['eth_instruction'] ?? ''); ?></textarea></td></tr>

            <tr><th>Email notifications</th>
                <td><label><input type="checkbox" name="email_notifications" value="1" <?php checked($opts['email_notifications'] ?? 1, 1); ?>> Enable</label></td></tr>

          </table>

          <h2>Email Templates</h2>
          <p>Use placeholders: <code>{name}</code> <code>{amount}</code> <code>{date}</code></p>

          <table class="form-table">

            <tr><th>Deposit Received (User)</th>
                <td><textarea name="email_deposit_received" rows="5" style="width:100%"><?php echo esc_textarea($opts['email_deposit_received'] ?? "We have received your deposit of {amount}. It will be reviewed shortly."); ?></textarea></td></tr>

            <tr><th>Deposit Approved</th>
                <td><textarea name="email_deposit_approved" rows="5" style="width:100%"><?php echo esc_textarea($opts['email_deposit_approved'] ?? "Your deposit of {amount} has been approved and added to your account."); ?></textarea></td></tr>

            <tr><th>Withdrawal Request Received</th>
                <td><textarea name="email_withdraw_received" rows="5" style="width:100%"><?php echo esc_textarea($opts['email_withdraw_received'] ?? "We received your withdrawal request of {amount}. Processing soon."); ?></textarea></td></tr>

            <tr><th>Withdrawal Approved</th>
                <td><textarea name="email_withdraw_approved" rows="5" style="width:100%"><?php echo esc_textarea($opts['email_withdraw_approved'] ?? "Your withdrawal of {amount} has been approved and sent."); ?></textarea></td></tr>

            <tr><th>Withdrawal Declined</th>
                <td><textarea name="email_withdraw_declined" rows="5" style="width:100%"><?php echo esc_textarea($opts['email_withdraw_declined'] ?? "Your withdrawal request was declined. Contact support for details."); ?></textarea></td></tr>

            <tr><th>Welcome Email (Registration)</th>
                <td><textarea name="email_registration_welcome" rows="5" style="width:100%"><?php echo esc_textarea($opts['email_registration_welcome'] ?? "Welcome {name}! Your account has been created successfully."); ?></textarea></td></tr>

            <tr><th>Smart Farming Activated</th>
                <td><textarea name="email_smart_farming_on" rows="4" style="width:100%"><?php echo esc_textarea($opts['email_smart_farming_on'] ?? "Your Smart Farming feature is now active. Enjoy the automated farming benefits!"); ?></textarea></td></tr>

            <tr><th>Smart Farming Deactivated</th>
                <td><textarea name="email_smart_farming_off" rows="4" style="width:100%"><?php echo esc_textarea($opts['email_smart_farming_off'] ?? "Smart Farming has been disabled on your account. You can reactivate it at any time."); ?></textarea></td></tr>




          </table>

          <button class="button button-primary" name="wsi_save_settings" value="1">Save Settings</button>
        </form>
        </div>
        <?php
    }


    /* -------------------------------------------------------------------------
       FRONTEND: enqueue assets (tiny CSS/JS)
    ------------------------------------------------------------------------- */
    add_action('wp_enqueue_scripts', 'wsi_front_assets');
    function wsi_front_assets() {
        wp_register_style('wsi_front_style', false);
        wp_enqueue_style('wsi_front_style');
        $css = "
        .wsi-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px; }
        .wsi-tab { padding:8px 12px; background:#f1f1f1; border-radius:6px; cursor:pointer; }
        .wsi-tab.active { background:#0073aa; color:#fff; }
        .wsi-tab-content { display:none; }
        .wsi-tab-content.active { display:block; }
        ";
        wp_add_inline_style('wsi_front_style', $css);

        wp_register_script('wsi_front_js', false);
        wp_enqueue_script('wsi_front_js');
        $js = "
        document.addEventListener('click', function(e){
          if(e.target && e.target.classList && e.target.classList.contains('wsi-tab')) {
            var parent = e.target.closest('.wsi-tabs-wrap');
            if(!parent) return;
            parent.querySelectorAll('.wsi-tab').forEach(t=>t.classList.remove('active'));
            e.target.classList.add('active');
            var target = e.target.getAttribute('data-target');
            parent.querySelectorAll('.wsi-tab-content').forEach(c=>c.classList.remove('active'));
            var content = parent.querySelector('#'+target);
            if(content) content.classList.add('active');
          }
        });
        ";
        wp_add_inline_script('wsi_front_js', $js);
    }

    /* -------------------------------------------------------------------------
       SHORTCODE: registration (invite-only)
    ------------------------------------------------------------------------- */
    //add_shortcode('wsi_register', 'wsi_shortcode_register');
    function wsi_shortcode_register() {
        if (is_user_logged_in()) return '<div class="notice">Already logged in.</div>';
        $ref = $_GET['ref'] ?? ($_COOKIE['wsi_ref'] ?? '');
        if (empty($ref)) return '<div class="notice notice-warning">Registration is invite-only. Use an invite link.</div>';

        $msg = '';
        if (!empty($_POST['wsi_register_submit'])) {
            if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'wsi_register_nonce')) return '<div class="notice notice-error">Invalid request.</div>';
            $first = sanitize_text_field($_POST['first_name'] ?? '');
            $last = sanitize_text_field($_POST['last_name'] ?? '');
            $email = sanitize_email($_POST['email'] ?? '');
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $pass = $_POST['password'] ?? '';
            $pass2 = $_POST['password2'] ?? '';
            if (!$first || !$last || !$email || !$phone || !$pass) $msg = '<div class="notice notice-error">All fields are required.</div>';
            elseif (!is_email($email)) $msg = '<div class="notice notice-error">Invalid email.</div>';
            elseif ($pass !== $pass2) $msg = '<div class="notice notice-error">Passwords do not match.</div>';
            elseif (email_exists($email)) $msg = '<div class="notice notice-error">Email already registered.</div>';
            else {
                $username = sanitize_user(current(explode('@', $email)));
                $base = $username;
                $i = 1;
                while (username_exists($username)) $username = $base . ($i++);
                $user_id = wp_create_user($username, $pass, $email);
                if (is_wp_error($user_id)) $msg = '<div class="notice notice-error">Registration error: ' . esc_html($user_id->get_error_message()) . '</div>';
                else {
                    wp_update_user(['ID' => $user_id, 'first_name' => $first, 'last_name' => $last, 'display_name' => $first . ' ' . $last]);
                    update_user_meta($user_id, 'wsi_phone', $phone);
                    // attach inviter
                    $refcode = sanitize_text_field($ref);
                    $found = get_users(['meta_key' => 'wsi_invite_code', 'meta_value' => $refcode, 'number' => 1]);
                    if (!empty($found)) update_user_meta($user_id, 'wsi_inviter_id', intval($found[0]->ID));
                    wsi_ensure_invite_code($user_id);
                    wp_set_current_user($user_id);
                    wp_set_auth_cookie($user_id);
                    wsi_audit($user_id, 'register', 'Registered via invite');
                    wp_safe_redirect(home_url('/?wsi_reg=ok'));
                    exit;
                }
            }
        }

        ob_start();
        if ($msg) echo $msg;
        ?>
        <div class="wsi-register">
          <h3>Create an account</h3>
          <form method="post">
            <?php wp_nonce_field('wsi_register_nonce'); ?>
            <p><input name="first_name" placeholder="First name" required></p>
            <p><input name="last_name" placeholder="Last name" required></p>
            <p><input name="email" type="email" placeholder="Email" required></p>
            <p><input name="phone" placeholder="Phone number" required></p>
            <p><input name="password" type="password" placeholder="Password" required></p>
            <p><input name="password2" type="password" placeholder="Confirm password" required></p>
            <p><button name="wsi_register_submit">Register</button></p>
          </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /* -------------------------------------------------------------------------
       SHORTCODE: dashboard (tabs) - deposit, withdraw, reinvest, buy/sell, invite, transactions
    ------------------------------------------------------------------------- */
    //add_shortcode('wsi_dashboard', 'wsi_shortcode_dashboard');
    function wsi_shortcode_dashboard() {
        if (!is_user_logged_in()) return '<div class="notice">Please <a href="' . wp_login_url() . '">log in</a> to access your dashboard.</div>';
        $uid = get_current_user_id();
        if (get_user_meta($uid, 'wsi_suspended', true)) return '<div class="notice notice-error">Account suspended. Contact admin.</div>';
        global $wpdb;
        $opts = wsi_get_opts();
        $t_stocks = $wpdb->prefix . 'wsi_stocks';
        $t_hold = $wpdb->prefix . 'wsi_holdings';
        $t_tx = $wpdb->prefix . 'wsi_transactions';
        $t_dep = $wpdb->prefix . 'wsi_deposits';
        $t_wd = $wpdb->prefix . 'wsi_withdrawals';

        // Original balances
        $assets = wsi_get_main($uid);
        $profit_income = wsi_get_profit($uid);

        // Net margin = assets + profit income
        $net_margin = $assets + $profit_income;

        // Compute available balance for 60+ day old deposits
        $deposits = $wpdb->get_results(
            $wpdb->prepare("SELECT amount, created_at FROM $t_dep WHERE user_id=%d AND status='approved'", $uid)
        );

        $now = current_time('timestamp');
        $unlock_seconds = 60 * 24 * 60 * 60;

        $unlocked_assets = 0;
        foreach ($deposits as $d) {
            $created = strtotime($d->created_at);
            if (($now - $created) >= $unlock_seconds) {
                $unlocked_assets += floatval($d->amount);
            }
        }

        // Available = profit + unlocked deposits
        $available_balance = $profit_income + $unlocked_assets;

        $assets = number_format($assets, 2);
        $profit_income = number_format($profit_income, 2);
        $net_margin = number_format($net_margin, 2);
        $available_balance = number_format($available_balance, 2);

        $invite_link = wsi_get_invite_link($uid);
        $stocks = $wpdb->get_results("SELECT * FROM $t_stocks WHERE active=1 ORDER BY id DESC");
        $holdings = $wpdb->get_results($wpdb->prepare("SELECT h.*, s.name, s.price, s.rate_percent, s.rate_period FROM $t_hold h LEFT JOIN $t_stocks s ON s.id=h.stock_id WHERE h.user_id=%d AND h.status='open' ORDER BY h.created_at DESC", $uid));
        $txs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $t_tx WHERE user_id=%d ORDER BY created_at DESC LIMIT 50", $uid));

        ob_start();
        ?>
        <div class="wsi-tabs-wrap">
          <div class="card" style="padding:12px">
            <h3>Welcome, <?php echo esc_html(wp_get_current_user()->display_name); ?></h3>

            <div class="wsi-tabs" role="tablist" aria-label="WSI Tabs">
              <div class="wsi-tab active" data-target="tab_overview">Overview</div>
              <div class="wsi-tab" data-target="tab_deposit">Deposit</div>
              <div class="wsi-tab" data-target="tab_withdraw">Withdraw</div>
              <div class="wsi-tab" data-target="tab_reinvest">Reinvest</div>
              <div class="wsi-tab" data-target="tab_stocks">Stocks</div>
              <div class="wsi-tab" data-target="tab_holdings">Holdings</div>
              <div class="wsi-tab" data-target="tab_referral">Referral</div>
              <div class="wsi-tab" data-target="tab_transactions">Transactions</div>
              <div class="wsi-tab">
                  <a href="<?php echo esc_url( wp_logout_url( home_url('/') ) ); ?>">Logout</a>
              </div>
            </div>

            <div id="tab_overview" class="wsi-tab-content active">

              <p>
                <strong>Assets:</strong> $<?php echo $assets; ?> &nbsp; 
                <strong>Profit Income:</strong> $<?php echo $profit_income; ?>
              </p>

              <p>
                <strong>Net Margin:</strong> $<?php echo $net_margin; ?> &nbsp;
                <strong>Available Balance:</strong> $<?php echo $available_balance; ?>
              </p>

              <!-- Smart Farming Toggle -->
              <?php
              $sf = get_user_meta($uid, 'wsi_smart_farming', true);
              $sf_checked = ($sf === 'yes') ? 'checked' : '';
              ?>
              <p>
                  <label style="font-weight:bold;">Smart Farming</label><br>
                  <label class="switch">
                      <input type="checkbox" id="wsi_smart_farming_toggle" <?php echo $sf_checked; ?>>
                      <span class="slider round"></span>
                  </label>
                  <span id="wsi_smart_farming_status" style="margin-left:10px;">
                      <?php echo $sf_checked ? 'Enabled' : 'Disabled'; ?>
                  </span>

              </p>

              <style>
              .switch{position:relative;display:inline-block;width:48px;height:24px;}
              .switch input{display:none;}
              .slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#ccc;transition:.4s;border-radius:24px;}
              .slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;transition:.4s;border-radius:50%;}
              input:checked + .slider{background:#4CAF50;}
              input:checked + .slider:before{transform:translateX(24px);}
              </style>

              <script>
              document.addEventListener('DOMContentLoaded', function() {

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
                              status.textContent = enabled === 'yes' ? 'Enabled' : 'Disabled';
                          } else if (trimmed === 'noauth') {
                              alert('Please log in again to change Smart Farming.');
                              toggle.checked = !toggle.checked;
                              status.textContent = toggle.checked ? 'Enabled' : 'Disabled';
                          } else {
                              alert('We could not update Smart Farming right now. Please try again shortly.');
                              toggle.checked = !toggle.checked;
                              status.textContent = toggle.checked ? 'Enabled' : 'Disabled';
                          }
                      })
                      .catch(() => {
                          alert('Network error updating Smart Farming. Please check your connection and try again.');
                          toggle.checked = !toggle.checked;
                          status.textContent = toggle.checked ? 'Enabled' : 'Disabled';
                      });
                  });
              });
              </script>


              <p><em>*Assets cannot be withdrawn until 60 days after deposit. Only Profit Income is withdrawable before the 60-day period.</em></p>

              <p>
                  <label>Your invite link</label>
                  <input type="text" value="<?php echo esc_attr($invite_link); ?>" readonly style="width:60%">
              </p>
            </div>

            <div id="tab_deposit" class="wsi-tab-content">

              
    <h4>Deposit</h4>
      <?php if (($opts['deposit_mode'] ?? 'manual') === 'manual'): ?>
      <?php else: ?>
        <p><strong>Auto-confirm deposit enabled.</strong></p>
      <?php endif; ?>

      <form id="wsi-deposit-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
          <input type="hidden" name="redirect_to" value="<?php echo esc_attr( (isset($_SERVER["REQUEST_URI"]) ? esc_url_raw( $_SERVER["REQUEST_URI"] ) : home_url("/" ) ) ); ?>" />

          <input type="hidden" name="action" value="wsi_submit_deposit">
          <?php wp_nonce_field('wsi_deposit_nonce'); ?>
          <input type="hidden" name="is_ajax" value="1" id="is_ajax_field">

          <div>
            <label><input type="radio" name="payment_type" value="naira" checked> Naira Payment</label>
            <label style="margin-left:10px;"><input type="radio" name="payment_type" value="crypto"> Crypto Payment</label>
          </div>

          <div id="naira_section">
            <div>
              <label>Enter amount (₦):</label><br>
              <input name="amount_naira" id="amount_naira" type="number" min="0" value="<?php echo esc_attr($opts['min_invest'] ?? 50) * ($opts['exchange_rate'] ?? 1000); ?>" style="width:160px;">
            </div>
            <div>
              <label>Equivalent ($):</label><br>
              <input type="text" id="amount_usd_display" readonly style="width:160px;">
            </div>
            <input type="hidden" name="amount" id="amount_usd" value="">
            <div id="rate_info">Exchange Rate: $1 = ₦<?php echo esc_html(number_format($opts['exchange_rate'] ?? 1000,2)); ?></div>
            <div id="naira_instructions"><?php echo nl2br(esc_html($opts['naira_payment_info'] ?? $opts['manual_payment_info'] ?? '')); ?></div>
          </div>

          <div id="crypto_section" style="display:none;">
            <div>
              <label>Enter amount ($):</label><br>
              <input name="crypto_amount" id="crypto_amount" type="number" min="0" placeholder="<?php echo esc_attr($opts['min_invest'] ?? 50); ?>" style="width:160px;">
            </div>
            <div id="crypto_wallet_select" style="display:none;">
              <label>Select wallet:</label><br>
              <select id="crypto_wallet" name="crypto_wallet">
                <option value="">-- choose --</option>
                <?php if(!empty($opts['usdt_trc_wallet'])): ?><option value="usdt_trc">USDT (TRC20)</option><?php endif; ?>
                <?php if(!empty($opts['usdt_erc_wallet'])): ?><option value="usdt_erc">USDT (ERC20)</option><?php endif; ?>
                <?php if(!empty($opts['sol_wallet'])): ?><option value="sol">Solana (SOL)</option><?php endif; ?>
                <?php if(!empty($opts['eth_wallet'])): ?><option value="eth">ETH</option><?php endif; ?>
              </select>
            </div>
            <div id="crypto_wallet_info" style="display:none;margin-top:8px;">
              <div id="wallet_address"></div>
              <div id="wallet_instruction" style="margin-top:6px;"></div>
            </div>
          </div>

          <button type="button" id="wsi_deposit_submit" style="display:none;" type="submit">Submit Deposit</button>
      </form>

        <script>
        (function(){

            function initWsiDepositForm(){

                var rate = <?php $o = wsi_get_opts(); echo floatval($o['exchange_rate'] ?? 1000); ?>;
                var minUSD = <?php echo floatval($opts['min_invest'] ?? 50); ?>;
                var naira = document.getElementById("amount_naira");
                var usd_display = document.getElementById("amount_usd_display");
                var usd_hidden = document.getElementById("amount_usd");
                var submitBtn = document.getElementById("wsi_deposit_submit");
                var nairaSection = document.getElementById("naira_section");
                var cryptoSection = document.getElementById("crypto_section");
                var cryptoAmount = document.getElementById("crypto_amount");
                var cryptoSelectWrap = document.getElementById("crypto_wallet_select");
                var cryptoSelect = document.getElementById("crypto_wallet");
                var walletInfo = document.getElementById("crypto_wallet_info");
                var walletAddress = document.getElementById("wallet_address");
                var walletInstruction = document.getElementById("wallet_instruction");

                function recalcNaira(){
                    var n = parseFloat(naira.value) || 0;
                    var usd = n / rate;
                    usd_display.value = usd.toFixed(2);
                    usd_hidden.value = usd.toFixed(2);
                    if(usd >= minUSD){
                        submitBtn.style.display = '';
                    } else { submitBtn.style.display = 'none'; }
                }

                function checkCryptoAmount(){
                    var c = parseFloat(cryptoAmount.value) || 0;
                    if(c >= minUSD){
                        cryptoSelectWrap.style.display = '';
                    } else {
                        cryptoSelectWrap.style.display = 'none';
                        cryptoSelect.value = '';
                        walletInfo.style.display = 'none';
                        submitBtn.style.display = 'none';
                    }
                }

                function showWalletInfo(code){
                    if(!code) { walletInfo.style.display='none'; submitBtn.style.display='none'; return; }
                    var labels = {
                        usdt_trc: 'USDT (TRC20)',
                        usdt_erc: 'USDT (ERC20)',
                        sol: 'Solana (SOL)',
                        eth: 'ETH'
                    };
                    var opts = <?php echo json_encode(array(
                        'usdt_trc' => $opts['usdt_trc_wallet'] ?? '',
                        'usdt_trc_ins' => $opts['usdt_trc_instruction'] ?? '',
                        'usdt_erc' => $opts['usdt_erc_wallet'] ?? '',
                        'usdt_erc_ins' => $opts['usdt_erc_instruction'] ?? '',
                        'sol' => $opts['sol_wallet'] ?? '',
                        'sol_ins' => $opts['sol_instruction'] ?? '',
                        'eth' => $opts['eth_wallet'] ?? '',
                        'eth_ins' => $opts['eth_instruction'] ?? ''
                    )); ?>;
                    var addr = opts[code] || '';
                    var ins = opts[code + '_ins'] || '';
                    var label = labels[code] || code.toUpperCase();
                    walletAddress.innerText = addr ? (label + ' Address: ' + addr) : '';
                    walletInstruction.innerHTML = ins ? ins.replace(/\n/g, "<br>") : '';
                    walletInfo.style.display = addr ? '' : 'none';
                    if(addr) submitBtn.style.display = '';
                }

                recalcNaira();

                if(naira) naira.addEventListener('input', recalcNaira);
                document.querySelectorAll('input[name="payment_type"]').forEach(function(r){
                    r.addEventListener('change', function(){
                        if(this.value === 'naira'){
                            nairaSection.style.display='';
                            cryptoSection.style.display='none';
                            recalcNaira();
                        } else {
                            nairaSection.style.display='none';
                            cryptoSection.style.display='';
                            submitBtn.style.display='none';
                        }
                    });
                });
                if(cryptoAmount) cryptoAmount.addEventListener('input', checkCryptoAmount);
                if(cryptoSelect) cryptoSelect.addEventListener('change', function(){ showWalletInfo(this.value); });

                function showModal(title, html){
                    var m = document.getElementById('wsi_deposit_modal');
                    document.getElementById('wsi_modal_title').innerText = title;
                    document.getElementById('wsi_modal_body').innerHTML = html;
                    m.style.display = 'flex';
                }

                function closeModal(){
                    var el = document.getElementById('wsi_deposit_modal');
                    if(el) el.style.display = 'none';
                }

                document.addEventListener('click', function(e){
                    if(e.target && e.target.id === 'wsi_modal_close') closeModal();
                });

            }

            document.addEventListener("DOMContentLoaded", initWsiDepositForm);

            jQuery(window).on("elementor/frontend/init", function () {
                elementorFrontend.hooks.addAction("frontend/element_ready/global", initWsiDepositForm);
            });

        })();
        </script>


        <script>
        jQuery(function($){

            function wsi_init_deposit_js() {

                var rate = <?php $o = wsi_get_opts(); echo floatval($o['exchange_rate'] ?? 1000); ?>;
                var minUSD = <?php echo floatval($opts['min_invest'] ?? 50); ?>;

                var naira = document.getElementById("amount_naira");
                var usd_display = document.getElementById("amount_usd_display");
                var usd_hidden = document.getElementById("amount_usd");
                var submitBtn = document.getElementById("wsi_deposit_submit");
                var nairaSection = document.getElementById("naira_section");
                var cryptoSection = document.getElementById("crypto_section");
                var cryptoAmount = document.getElementById("crypto_amount");
                var cryptoSelectWrap = document.getElementById("crypto_wallet_select");
                var cryptoSelect = document.getElementById("crypto_wallet");
                var walletInfo = document.getElementById("crypto_wallet_info");
                var walletAddress = document.getElementById("wallet_address");
                var walletInstruction = document.getElementById("wallet_instruction");

                if(!naira || !usd_display || !usd_hidden || !submitBtn) return;

                function recalcNaira(){
                    var n = parseFloat(naira.value) || 0;
                    var usd = n / rate;
                    usd_display.value = usd.toFixed(2);
                    usd_hidden.value = usd.toFixed(2);
                    submitBtn.style.display = (usd >= minUSD) ? '' : 'none';
                }

                function checkCryptoAmount(){
                    var c = parseFloat(cryptoAmount.value) || 0;
                    if(c >= minUSD){
                        cryptoSelectWrap.style.display = '';
                    } else {
                        cryptoSelectWrap.style.display = 'none';
                        cryptoSelect.value = '';
                        walletInfo.style.display = 'none';
                        submitBtn.style.display = 'none';
                    }
                }

                function showWalletInfo(code){
                    if(!code){
                        walletInfo.style.display='none';
                        submitBtn.style.display='none';
                        return;
                    }
                    var labels = {
                        usdt_trc: 'USDT (TRC20)',
                        usdt_erc: 'USDT (ERC20)',
                        sol: 'Solana (SOL)',
                        eth: 'ETH'
                    };
                    var opts = <?php echo json_encode(array(
                        'usdt_trc' => $opts['usdt_trc_wallet'] ?? '',
                        'usdt_trc_ins' => $opts['usdt_trc_instruction'] ?? '',
                        'usdt_erc' => $opts['usdt_erc_wallet'] ?? '',
                        'usdt_erc_ins' => $opts['usdt_erc_instruction'] ?? '',
                        'sol' => $opts['sol_wallet'] ?? '',
                        'sol_ins' => $opts['sol_instruction'] ?? '',
                        'eth' => $opts['eth_wallet'] ?? '',
                        'eth_ins' => $opts['eth_instruction'] ?? ''
                    )); ?>;

                    var addr = opts[code] || '';
                    var ins = opts[code + '_ins'] || '';

                    var label = labels[code] || code.toUpperCase();

                    walletAddress.innerText = addr ? (label + ' Address: ' + addr) : '';
                    walletInstruction.innerHTML = ins ? ins.replace(/\n/g, "<br>") : '';
                    walletInfo.style.display = addr ? '' : 'none';

                    if(addr) submitBtn.style.display = '';
                }

                // INITIAL RUN
                recalcNaira();

                // EVENTS
                naira.addEventListener('input', recalcNaira);

                document.querySelectorAll('input[name="payment_type"]').forEach(function(r){
                    r.addEventListener('change', function(){
                        if(this.value === 'naira'){
                            nairaSection.style.display='';
                            cryptoSection.style.display='none';
                            recalcNaira();
                        } else {
                            nairaSection.style.display='none';
                            cryptoSection.style.display='';
                            submitBtn.style.display='none';
                        }
                    });
                });

                cryptoAmount?.addEventListener('input', checkCryptoAmount);
                cryptoSelect?.addEventListener('change', function(){
                    showWalletInfo(this.value);
                });
            }

            // Run once on normal page load
            wsi_init_deposit_js();

            // Run every time Elementor refreshes or loads content dynamically
            $(document).on("elementor/frontend/init elementor/popup/show elementor/frontend/elements_ready", function(){
                wsi_init_deposit_js();
            });

        });
        </script>
        <script>
        jQuery(function($) {

            // Global event delegation – survives Elementor refresh
            $(document).off("click.wsiDeposit").on("click.wsiDeposit", "#wsi_deposit_submit", function(e) {
                e.preventDefault();

                let btn = $(this);
                let form = btn.closest("form");

                if (!form.length) {
                    alert("Deposit form not found.");
                    return;
                }

                let formData = new FormData(form[0]);
                formData.append("action", "wsi_submit_deposit"); // ensures AJAX route

                btn.prop("disabled", true).text("Processing...");

                $.ajax({
                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,

                    success: function(resp) {
                        btn.prop("disabled", false).text("Submit");

                        if (typeof resp === "string") {
                            try { resp = JSON.parse(resp); } catch (e) {}
                        }

                        if (resp && resp.success) {
                            var data = resp.data || {};
                            var msg = data.message || "Deposit submitted successfully.";
                            alert(msg);
                            var redirect = data.redirect || data.redirect_to || "<?php echo esc_url(home_url('/wsi/dashboard/')); ?>";
                            window.location.href = redirect;
                        } else {
                            var err = (resp && resp.data && resp.data.message) ? resp.data.message : "Deposit failed.";
                            alert(err);
                        }
                    },

                    error: function() {
                        btn.prop("disabled", false).text("Submit");
                        alert("Network error, please try again.");
                    }
                });
            });

        });
        </script>


        <script>
        (function(){
            function initWsiRecalc2(){
                var rate = <?php $o = wsi_get_opts(); echo floatval($o['exchange_rate'] ?? 1000); ?>;
                var naira = document.getElementById("amount_naira");
                var usd_display = document.getElementById("amount_usd_display");
                var usd_hidden = document.getElementById("amount_usd");

                function recalc(){
                    var n = parseFloat(naira.value || 0);
                    var usd = 0;
                    if (rate > 0) usd = n / rate;
                    usd_display.value = usd.toFixed(2);
                    if (usd_hidden) usd_hidden.value = usd.toFixed(2);
                }

                if (naira){
                    naira.addEventListener('input', recalc);
                    recalc();
                }
            }

            // Run normally
            document.addEventListener("DOMContentLoaded", initWsiRecalc2);

            // Run inside Elementor builder
            jQuery(window).on("elementor/frontend/init", function(){
                elementorFrontend.hooks.addAction("frontend/element_ready/global", initWsiRecalc2);
            });
        })();
        </script>

            </div>
            <?php

            
            ?>

            <div id="tab_withdraw" class="wsi-tab-content">
                <h4>Withdraw</h4>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="wsi_submit_withdraw">
                    <?php wp_nonce_field('wsi_withdraw_nonce'); ?>

                    <!-- Amount Input -->
                    <div>
                        <input name="amount" type="number" step="0.01" placeholder="Amount" required>
                    </div>

                    <!-- Crypto Type Selector (NEW) -->
                    <div>
                        <select name="crypto_type" required>
                            <option value="">Select Network</option>
                            <option value="BTC">Bitcoin (BTC)</option>
                            <option value="ETH">Ethereum (ETH)</option>
                            <option value="USDT-TRC20">USDT (TRC20)</option>
                            <option value="USDT-ERC20">USDT (ERC20)</option>
                            <option value="BNB">BNB</option>
                            <option value="TRX">TRON (TRX)</option>
                        </select>
                    </div>

                    <!-- Wallet Address Input -->
                    <div>
                        <input type="text" name="account_details" placeholder="Enter Wallet Address" required>
                    </div>

                    <button>Request Withdrawal</button>
                </form>
            </div>


            <div id="tab_reinvest" class="wsi-tab-content">
              <h4>Reinvest from Profit</h4>
              <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wsi_submit_reinvest">
                <?php wp_nonce_field('wsi_reinvest_nonce'); ?>
                <div><input name="amount" type="number" step="0.01" placeholder="Amount from profit" required></div>
                <button>Reinvest</button>
              </form>
            </div>

            <div id="tab_stocks" class="wsi-tab-content">
                <h4>Available Stocks</h4>
                <?php if (empty($stocks)) echo '<p>No stocks available yet.</p>'; else { ?>
                  <table class="widefat">
                    <thead>
                      <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Rate</th>
                        <th>Period</th>
                        <th>Buy</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($stocks as $s) { ?>
                      <tr>
                        <td>
                          <?php if (!empty($s->image)) { ?>
                            <img src="<?php echo esc_url($s->image); ?>" width="40" height="40" style="border-radius:6px;">
                          <?php } else { ?>
                            <span>No image</span>
                          <?php } ?>
                        </td>

                        <td><?php echo esc_html($s->name); ?></td>
                        <td>$<?php echo number_format($s->price, 2); ?></td>
                        <td><?php echo esc_html($s->rate_percent ?? "0"); ?>%</td>
                        <td><?php echo esc_html($s->rate_period ?? "N/A"); ?></td>

                        <td>
                          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                              <input type="hidden" name="action" value="wsi_buy_stock">
                              <?php wp_nonce_field('wsi_buy_stock_nonce'); ?>
                              <input type="hidden" name="stock_id" value="<?php echo intval($s->id); ?>">
                              <button type="submit">Buy</button>
                          </form>
                        </td>
                      </tr>
                    <?php } ?>
                    </tbody>
                  </table>
                <?php } ?>
            </div>


            <div id="tab_holdings" class="wsi-tab-content">
              <h4>Your Holdings</h4>
              <?php if (empty($holdings)) echo '<p>No holdings.</p>'; else { ?>
                <table class="widefat"><thead><tr><th>Stock</th><th>Invested</th><th>Shares</th><th>Accum. Profit</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($holdings as $h) { ?>
                  <tr>
                    <td><?php echo esc_html($h->name); ?></td>
                    <td>$<?php echo number_format($h->invested_amount, 2); ?></td>
                    <td><?php echo esc_html($h->shares); ?></td>
                    <td>$<?php echo number_format($h->accumulated_profit, 2); ?></td>
                    <td>
                      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="wsi_sell_holding">
                        <?php wp_nonce_field('wsi_sell_holding_nonce'); ?>
                        <input type="hidden" name="holding_id" value="<?php echo intval($h->id); ?>">
                        <button>Sell (collect profit)</button>
                      </form>
                    </td>
                  </tr>
                <?php } ?>
                </tbody></table>
              <?php } ?>
            </div>

            <div id="tab_referral" class="wsi-tab-content">
              <h4>Referral</h4>
              <p>Your invite code: <strong><?php echo esc_html(get_user_meta($uid, 'wsi_invite_code', true)); ?></strong></p>
              <p>First-level bonus: 10% | Second-level bonus: 5% (awarded on first confirmed deposit of referred user)</p>
            </div>

            <div id="tab_transactions" class="wsi-tab-content">
                <h4>Transactions</h4>

                <?php
                global $wpdb;
                $uid = get_current_user_id();

                // --- Filtering ---
                $allowed_filters = [
                    '' => '',
                    'deposit' => 'deposit',
                    'withdrawal' => 'withdraw_request',
                    'smart' => 'smart_farm_interest',
                    'reinvest' => 'reinvest',
                    'stocks' => 'buy_stock'
                ];

                $filter_key = isset($_GET['ftype']) ? sanitize_text_field($_GET['ftype']) : '';
                $filter_type = isset($allowed_filters[$filter_key]) ? $allowed_filters[$filter_key] : '';

                // --- Sorting ---
                $allowed_sort = ['created_at','amount','type'];
                $requested_orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
                $orderby = in_array($requested_orderby, $allowed_sort, true) ? $requested_orderby : 'created_at';
                $order = (isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC') ? 'ASC' : 'DESC';

                // --- Pagination ---
                $per_page = 20;
                $page = max(1, intval($_GET['pg'] ?? 1));
                $offset = ($page - 1) * $per_page;

                // Build WHERE and params
                $where = "WHERE user_id=%d";
                $params = [$uid];

                if ($filter_type !== '') {
                    $where .= " AND type=%s";
                    $params[] = $filter_type;
                }

                // Total count (use prepare if params exist)
                $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}wsi_transactions $where";
                if (!empty($params)) {
                    $count = $wpdb->get_var($wpdb->prepare($count_sql, $params));
                } else {
                    $count = $wpdb->get_var($count_sql);
                }

                // Build main SQL safely: whitelist column name, then prepare for params & limit
                $order_by_col = $orderby; // safe because whitelisted above

                $main_sql = "SELECT * FROM {$wpdb->prefix}wsi_transactions
                             $where
                             ORDER BY {$order_by_col} {$order}
                             LIMIT %d OFFSET %d";

                // prepare params for main query
                $main_params = $params;
                $main_params[] = $per_page;
                $main_params[] = $offset;

                if (!empty($params)) {
                    $txs = $wpdb->get_results($wpdb->prepare($main_sql, $main_params));
                } else {
                    // when no where params (shouldn't happen for normal users), still prepare limit/offset
                    $txs = $wpdb->get_results($wpdb->prepare($main_sql, $per_page, $offset));
                }

                // --- CSV Export ---
                if (isset($_GET['export']) && $_GET['export'] === 'csv') {
                    // Only export current filtered set (no pagination)
                    $export_sql = "SELECT * FROM {$wpdb->prefix}wsi_transactions $where ORDER BY {$order_by_col} {$order}";
                    if (!empty($params)) {
                        $export_rows = $wpdb->get_results($wpdb->prepare($export_sql, $params), ARRAY_A);
                    } else {
                        $export_rows = $wpdb->get_results($export_sql, ARRAY_A);
                    }

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

                // Helper to build query strings preserving other params
                $build_qs = function($overrides = []) {
                    $q = $_GET;
                    foreach ($overrides as $k => $v) {
                        if ($v === null) unset($q[$k]);
                        else $q[$k] = $v;
                    }
                    return esc_url(add_query_arg($q, remove_query_arg('paged')));
                };
                ?>

                <!-- FILTERS + EXPORT -->
                <div style="margin-bottom:15px; display:flex; gap:20px;">
                    <div>
                        <strong>Filter:</strong>
                        <a href="<?php echo $build_qs(['ftype' => '', 'pg' => 1]); ?>">All</a> |
                        <a href="<?php echo $build_qs(['ftype' => 'deposit','pg' => 1]); ?>">Deposit</a> |
                        <a href="<?php echo $build_qs(['ftype' => 'withdrawal','pg' => 1]); ?>">Withdrawal</a> |
                        <a href="<?php echo $build_qs(['ftype' => 'smart','pg' => 1]); ?>">Smart Farming</a> |
                        <a href="<?php echo $build_qs(['ftype' => 'reinvest','pg' => 1]); ?>">Reinvest</a> |
                        <a href="<?php echo $build_qs(['ftype' => 'stocks','pg' => 1]); ?>">Stocks</a>
                    </div>

                    <div style="margin-left:auto;">
                        <?php
                        $export_qs = $build_qs(['export' => 'csv', 'pg' => null, '_wpnonce' => wp_create_nonce('wsi_tx_export')]);
                        ?>
                        <a class="button" href="<?php echo $export_qs; ?>">Export CSV</a>
                    </div>
                </div>

                <!-- TABLE -->
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><a href="<?php echo $build_qs(['orderby' => 'created_at', 'order' => ($orderby === 'created_at' && $order === 'DESC') ? 'ASC' : 'DESC', 'pg' => 1]); ?>">When<?php echo ($orderby === 'created_at') ? ($order === 'DESC' ? ' ↓' : ' ↑') : ''; ?></a></th>
                            <th><a href="<?php echo $build_qs(['orderby' => 'amount', 'order' => ($orderby === 'amount' && $order === 'DESC') ? 'ASC' : 'DESC', 'pg' => 1]); ?>">Amount<?php echo ($orderby === 'amount') ? ($order === 'DESC' ? ' ↓' : ' ↑') : ''; ?></a></th>
                            <th><a href="<?php echo $build_qs(['orderby' => 'type', 'order' => ($orderby === 'type' && $order === 'DESC') ? 'ASC' : 'DESC', 'pg' => 1]); ?>">Type<?php echo ($orderby === 'type') ? ($order === 'DESC' ? ' ↓' : ' ↑') : ''; ?></a></th>
                            <th>Desc</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($txs) {
                            foreach ($txs as $t) {
                                $style = ($t->type === 'smart_farm_interest') ? ' style="background:#e6ffe6;"' : '';
                                echo '<tr' . $style . '>';
                                echo '<td>' . esc_html($t->created_at) . '</td>';
                                echo '<td>$' . number_format($t->amount, 2) . '</td>';
                                echo '<td>' . esc_html($t->type) . '</td>';
                                echo '<td>' . esc_html($t->description) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4">No transactions yet.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>

                <!-- PAGINATION -->
                <?php
                $pages = max(1, ceil(intval($count) / $per_page));
                if ($pages > 1) {
                    echo '<div style="margin-top:12px;">';
                    for ($i = 1; $i <= $pages; $i++) {
                        if ($i == $page) echo "<strong>$i</strong> ";
                        else echo '<a href="' . $build_qs(['pg' => $i]) . '">' . $i . '</a> ';
                    }
                    echo '</div>';
                }
                ?>
            </div>


          </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /* -------------------------------------------------------------------------
        Ajax Handler For Smart Farming Button
    --------------------------------------------------------------------------*/
    add_action('wp_ajax_wsi_toggle_smart_farming', 'wsi_toggle_smart_farming');
    function wsi_toggle_smart_farming() {
        if (!is_user_logged_in()) wp_die('0');
        $uid = get_current_user_id();

        $status = ($_POST['status'] === 'yes') ? 'yes' : 'no';
        update_user_meta($uid, 'wsi_smart_farming', $status);

        // send user email on change
        if ($status === 'yes') {
            wsi_send_email_template($uid, 'email_smart_farming_on');
        } else {
            wsi_send_email_template($uid, 'email_smart_farming_off');
        }

        wp_die('1');
    }



    /* -------------------------------------------------------------------------
       admin-post handlers for frontend forms
    ------------------------------------------------------------------------- */

    /*--------------------------------------------------------------------------
        Deposit submit 
    --------------------------------------------------------------------------*/
    // AJAX handler for deposit (replaces admin-post since admin-post is causing 500 errors)
    add_action('wp_ajax_wsi_submit_deposit', 'wsi_submit_deposit');
    add_action('wp_ajax_nopriv_wsi_submit_deposit', 'wsi_submit_deposit');
    
    function wsi_submit_deposit() {
        // For AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            if (!is_user_logged_in()) {
                wp_send_json_error(['message' => 'Not logged in']);
            }
            
            if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'wsi_deposit_nonce')) {
                wp_send_json_error(['message' => 'Security check failed']);
            }
            
            global $wpdb;
            $uid = get_current_user_id();
            $t_deposits = $wpdb->prefix . 'wsi_deposits';
            
            $amount_usd = floatval($_POST['amount'] ?? $_POST['amount_usd'] ?? 0);
            $payment_type = sanitize_text_field($_POST['payment_type'] ?? 'naira');
            $amount_local = floatval($_POST['amount_naira'] ?? 0);
            $crypto_wallet = sanitize_text_field($_POST['crypto_wallet'] ?? '');
            
            if ($amount_usd <= 0) {
                wp_send_json_error(['message' => 'Invalid amount']);
            }
            
            error_log("WSI: AJAX deposit - User: $uid, Amount: $amount_usd");
            
            $inserted = $wpdb->insert(
                $t_deposits,
                [
                    'user_id'      => $uid,
                    'amount'       => $amount_usd,
                    'amount_local' => $amount_local ?: null,
                    'payment_type' => $payment_type,
                    'crypto_wallet' => $crypto_wallet,
                    'status'       => 'pending',
                    'created_at'   => current_time('mysql')
                ],
                ['%d', '%f', '%f', '%s', '%s', '%s', '%s']
            );
            
            if ($inserted === false) {
                error_log('WSI: Deposit insert failed - ' . $wpdb->last_error);
                wp_send_json_error(['message' => 'Failed to create deposit']);
            }
            
            $deposit_id = $wpdb->insert_id;
            wsi_log_tx($uid, $amount_usd, 'deposit_pending', "Deposit #{$deposit_id} submitted");
            $user_label = wsi_get_user_label($uid);
            wsi_notify_admin('New Deposit', "{$user_label} submitted deposit of $" . number_format($amount_usd, 2));
            
            wp_send_json_success(['message' => 'Deposit submitted successfully', 'redirect' => add_query_arg('deposit', 'success', site_url('/wsi/deposit/'))]);
        }
        
        // Legacy fallback for non-AJAX requests
        try {
            error_log('WSI: Deposit submission started');
            
            if (!is_user_logged_in()) {
                error_log('WSI: User not logged in, redirecting to login');
                wp_safe_redirect(site_url('/wsi/login/'));
                exit;
            }
            
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wsi_deposit_nonce')) {
                error_log('WSI: Deposit nonce failed');
                wp_safe_redirect(site_url('/wsi/deposit/'));
                exit;
            }
            
            global $wpdb;
            $uid = get_current_user_id();
            $t_deposits = $wpdb->prefix . 'wsi_deposits';
            
            $amount_usd = floatval($_POST['amount'] ?? $_POST['amount_usd'] ?? 0);
            $payment_type = sanitize_text_field($_POST['payment_type'] ?? 'naira');
            $amount_local = floatval($_POST['amount_naira'] ?? 0);
            $crypto_wallet = sanitize_text_field($_POST['crypto_wallet'] ?? '');
            
            $redirect_to = site_url('/wsi/deposit/');
            
            if ($amount_usd <= 0) {
                error_log('WSI: Invalid deposit amount');
                wp_safe_redirect($redirect_to);
                exit;
            }
            
            error_log("WSI: Inserting deposit - User: $uid, Amount: $amount_usd");
            
            // Insert deposit
            $inserted = $wpdb->insert(
                $t_deposits,
                [
                    'user_id'      => $uid,
                    'amount'       => $amount_usd,
                    'amount_local' => $amount_local ?: null,
                    'payment_type' => $payment_type,
                    'crypto_wallet' => $crypto_wallet,
                    'status'       => 'pending',
                    'created_at'   => current_time('mysql')
                ],
                ['%d', '%f', '%f', '%s', '%s', '%s', '%s']
            );
            
            if ($inserted === false) {
                error_log('WSI: Deposit insert failed - ' . $wpdb->last_error);
                wp_safe_redirect($redirect_to);
                exit;
            }
            
            $deposit_id = $wpdb->insert_id;
            error_log("WSI: Deposit inserted successfully - ID: $deposit_id");
            
            wsi_log_tx($uid, $amount_usd, 'deposit_pending', "Deposit #{$deposit_id} submitted");
            $user_label = wsi_get_user_label($uid);
            wsi_notify_admin('New Deposit', "{$user_label} submitted deposit of $" . number_format($amount_usd, 2));
            
            wp_safe_redirect(add_query_arg('deposit', 'success', $redirect_to));
            exit;
        } catch (Exception $e) {
            error_log('WSI: Deposit handler error - ' . $e->getMessage());
            wp_safe_redirect(site_url('/wsi/deposit/?error=1'));
            exit;
        }
    }




    /*--------------------------------------------------------------------------
        SMART FARMING AJAX HANDLER
    --------------------------------------------------------------------------*/
    add_action('wp_ajax_wsi_toggle_farming', 'wsi_toggle_farming');
    add_action('wp_ajax_nopriv_wsi_toggle_farming', 'wsi_toggle_farming');

    function wsi_toggle_farming() {
        if (!is_user_logged_in()) {
            echo 'noauth';
            wp_die();
        }

        $uid = get_current_user_id();
        $state = sanitize_text_field($_POST['state'] ?? 'no');

        if ($state === 'yes') {
            update_user_meta($uid, 'wsi_smart_farming', 'yes');
            wsi_send_email_template($uid, 'email_smart_farming_on');
        } else {
            update_user_meta($uid, 'wsi_smart_farming', 'no');
            wsi_send_email_template($uid, 'email_smart_farming_off');
        }

        echo 'ok';
        wp_die();
    }


    /* Withdraw submit */
    add_action('admin_post_wsi_submit_withdraw', 'wsi_handle_withdraw');
    add_action('wp_ajax_wsi_submit_withdraw', 'wsi_handle_withdraw');
    
    function wsi_set_profit($uid, $amount) {
        update_user_meta($uid, 'wsi_profit_balance', floatval($amount));
    }


   function wsi_handle_withdraw() {
    $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
    
    if (!is_user_logged_in()) { 
        if ($is_ajax) {
            wp_send_json_error(['message' => 'You must be logged in']);
        } else {
            wp_safe_redirect(wsi_login_url()); 
            exit;
        }
    }
    
    $redirect_url = site_url('/wsi/withdrawal/');
    $dash_url = wsi_get_dashboard_page_url();
    
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'wsi_withdraw_nonce')) {
        if ($is_ajax) {
            wp_send_json_error(['message' => 'Security check failed']);
        } else {
            wsi_popup("Withdrawal Error!", $dash_url);
            exit;
        }
    }
    
    $uid        = get_current_user_id();
    $user_label = wsi_get_user_label($uid);
    $amount     = round(floatval($_POST['amount'] ?? 0), 2);
    $acct       = sanitize_textarea_field($_POST['account_details'] ?? '');
    $method     = sanitize_text_field($_POST['crypto_type'] ?? '');
    
    if ($amount <= 0) { 
        if ($is_ajax) {
            wp_send_json_error(['message' => 'Invalid withdrawal amount']);
        } else {
            wsi_popup("Invalid Withdrawal Amount", $dash_url);
            exit;
        }
    }
    
    global $wpdb;
    
    /* ------------------------------------------------------
       1. Calculate UNLOCKED deposits (60-day rule)
    ------------------------------------------------------- */
    $t_dep = $wpdb->prefix . 'wsi_deposits';
    $deps = $wpdb->get_results($wpdb->prepare(
        "SELECT amount, created_at FROM $t_dep WHERE user_id=%d AND status='approved'",
        $uid
    ));
    
    $now = current_time('timestamp');
    $unlock_seconds = 60 * 24 * 60 * 60; // 60 days
    $unlocked = 0;
    
    foreach ($deps as $d) {
        if (($now - strtotime($d->created_at)) >= $unlock_seconds) {
            $unlocked += floatval($d->amount);
        }
    }
    
    /* ------------------------------------------------------
       2. Calculate PROFIT: meta + accumulated holding profits
    ------------------------------------------------------- */
    $meta_profit = floatval(get_user_meta($uid, 'wsi_profit_balance', true));
    $t_hold = $wpdb->prefix . 'wsi_holdings';
    $accumulated_hold_profit = floatval($wpdb->get_var($wpdb->prepare(
        "SELECT SUM(accumulated_profit) FROM $t_hold WHERE user_id=%d AND status='open'",
        $uid
    )));
    $total_profit = $meta_profit + $accumulated_hold_profit;
    
    /* ------------------------------------------------------
       3. Total available = unlocked deposits + profit
    ------------------------------------------------------- */
    $available = $unlocked + $total_profit;
    
    if ($amount > $available) {
        if ($is_ajax) {
            wp_send_json_error(['message' => 'Insufficient Withdrawable Balance. Available: $' . number_format($available, 2)]);
        } else {
            wsi_popup("Insufficient Withdrawable Balance", $dash_url);
            exit;
        }
    }
    
    $remaining = $amount;
    
    /* ------------------------------------------------------
       4. Deduct from META PROFIT first
    ------------------------------------------------------- */
    if ($meta_profit > 0) {
        $use_meta = min($meta_profit, $remaining);
        update_user_meta($uid, 'wsi_profit_balance', $meta_profit - $use_meta);
        $remaining -= $use_meta;
    }
    
    /* ------------------------------------------------------
       5. Deduct from HOLDINGS accumulated_profit next (FIFO)
    ------------------------------------------------------- */
    if ($remaining > 0) {
        $holdings = $wpdb->get_results($wpdb->prepare(
            "SELECT id, accumulated_profit FROM $t_hold WHERE user_id=%d AND status='open' ORDER BY created_at ASC",
            $uid
        ));
        foreach ($holdings as $h) {
            if ($remaining <= 0) break;
            $use = min(floatval($h->accumulated_profit), $remaining);
            $wpdb->query($wpdb->prepare(
                "UPDATE $t_hold SET accumulated_profit = accumulated_profit - %f WHERE id=%d",
                $use, $h->id
            ));
            $remaining -= $use;
        }
    }
    
    /* ------------------------------------------------------
       6. Deduct from UNLOCKED deposits (main balance)
    ------------------------------------------------------- */
    if ($remaining > 0) {
        $current_main = floatval(wsi_get_main($uid));
        wsi_set_main($uid, $current_main - $remaining);
        $remaining = 0;
    }
    
    /* ------------------------------------------------------
       7. Record withdrawal request
    ------------------------------------------------------- */
    $t = $wpdb->prefix . 'wsi_withdrawals';
    $inserted = $wpdb->insert($t, [
        'user_id'         => $uid,
        'amount'          => $amount,
        'method'          => $method,
        'account_details' => $acct,
        'status'          => 'pending',
        'created_at'      => current_time('mysql')
    ]);
    
    if ($inserted === false) {
        error_log('WSI: Withdraw insert failed - ' . $wpdb->last_error);
        if ($is_ajax) {
            wp_send_json_error(['message' => 'Database error occurred']);
        } else {
            wsi_popup("Database Error", $dash_url);
            exit;
        }
    }
    
    // Log transaction
    wsi_log_tx($uid, $amount, 'withdraw_request', 'Withdrawal requested');
    wsi_audit($uid, 'withdraw_request', "Requested $amount");
    // Notify admin and user even during AJAX (front-end uses AJAX)
    wsi_notify_admin('Withdrawal Requested', "{$user_label} requested withdrawal of $" . number_format($amount, 2));
    wsi_send_email_template($uid, 'email_withdraw_received', ['amount' => $amount]);

    // Return response
    if ($is_ajax) {
        wp_send_json_success([
            'message' => 'Withdrawal request submitted successfully',
            'withdrawal_id' => $wpdb->insert_id,
            'amount' => $amount
        ]);
    } else {
        wsi_popup("Withdrawal Request Submitted", $redirect_url);
        exit;
    }
}




    /* Helper to redirect to the dashboard page */
    function wsi_get_dashboard_page_url() {
        return home_url('/wsi/dashboard/');
    }

    function wsi_dashboard_url() {
        return home_url('/wsi/dashboard/');
    }

    function wsi_login_url() {
        return home_url('/wsi/login/');
    }

    /* -------------------------------------------------------------------------
       ADMIN: Approve/Decline withdrawals AJAX handlers
    ------------------------------------------------------------------------- */
    if (!function_exists('wsi_admin_approve_withdrawal')) {
        function wsi_admin_approve_withdrawal() {
            if (!wsi_admin_can()) {
                wp_send_json_error(['message' => 'Unauthorized']);
            }
            
            if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'wsi_withdraws_nonce')) {
                wp_send_json_error(['message' => 'Security check failed']);
            }
            
            $withdraw_id = intval($_POST['withdraw_id'] ?? 0);
            if ($withdraw_id <= 0) {
                wp_send_json_error(['message' => 'Invalid withdrawal ID']);
            }
            
            global $wpdb;
            $t = $wpdb->prefix . 'wsi_withdrawals';
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d", $withdraw_id));
            
            if (!$row) {
                wp_send_json_error(['message' => 'Withdrawal not found']);
            }
            
            $updated = $wpdb->update(
                $t,
                [
                    'status' => 'approved',
                    'admin_note' => 'Paid by Admin ID: ' . get_current_user_id() . ' on ' . current_time('mysql')
                ],
                ['id' => $withdraw_id],
                ['%s', '%s'],
                ['%d']
            );
            
            if ($updated !== false) {
                wsi_log_tx($row->user_id, $row->amount, 'withdraw_approved', "Withdrawal #{$withdraw_id} approved");
                wsi_notify_user($row->user_id, 'Withdrawal Approved', "Your withdrawal of $" . number_format($row->amount, 2) . " has been processed and sent.");
                wsi_audit(get_current_user_id(), 'approve_withdraw', "Approved withdrawal #{$withdraw_id} for user #{$row->user_id}");
                wp_send_json_success(['message' => 'Withdrawal approved successfully']);
            } else {
                error_log('WSI: Failed to approve withdrawal #' . $withdraw_id . ' - ' . $wpdb->last_error);
                wp_send_json_error(['message' => 'Failed to approve withdrawal']);
            }
        }
    }
    add_action('wp_ajax_wsi_admin_approve_withdrawal', 'wsi_admin_approve_withdrawal');

    if (!function_exists('wsi_admin_decline_withdrawal')) {
        function wsi_admin_decline_withdrawal() {
            if (!wsi_admin_can()) {
                wp_send_json_error(['message' => 'Unauthorized']);
            }
            
            if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'wsi_withdraws_nonce')) {
                wp_send_json_error(['message' => 'Security check failed']);
            }
            
            $withdraw_id = intval($_POST['withdraw_id'] ?? 0);
            if ($withdraw_id <= 0) {
                wp_send_json_error(['message' => 'Invalid withdrawal ID']);
            }
            
            global $wpdb;
            $t = $wpdb->prefix . 'wsi_withdrawals';
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d", $withdraw_id));
            
            if (!$row) {
                wp_send_json_error(['message' => 'Withdrawal not found']);
            }
            
            $updated = $wpdb->update(
                $t,
                [
                    'status' => 'declined',
                    'admin_note' => 'Declined by Admin ID: ' . get_current_user_id() . ' on ' . current_time('mysql')
                ],
                ['id' => $withdraw_id],
                ['%s', '%s'],
                ['%d']
            );
            
            if ($updated !== false) {
                wsi_inc_profit($row->user_id, floatval($row->amount));
                wsi_log_tx($row->user_id, $row->amount, 'withdraw_refund', "Withdrawal #{$withdraw_id} declined, amount refunded to profit balance");
                wsi_notify_user($row->user_id, 'Withdrawal Declined', 'Your withdrawal request was declined and the amount has been refunded to your profit balance.');
                wsi_audit(get_current_user_id(), 'decline_withdraw', "Declined withdrawal #{$withdraw_id} for user #{$row->user_id}");
                wp_send_json_success(['message' => 'Withdrawal declined and refunded']);
            } else {
                error_log('WSI: Failed to decline withdrawal #' . $withdraw_id . ' - ' . $wpdb->last_error);
                wp_send_json_error(['message' => 'Failed to decline withdrawal']);
            }
        }
    }
    add_action('wp_ajax_wsi_admin_decline_withdrawal', 'wsi_admin_decline_withdrawal');


    /* -------------------------------------------------------------------------
       FRONT: Forgot password handler
    ------------------------------------------------------------------------- */
    add_action('admin_post_wsi_forgot_password', 'wsi_handle_forgot_password');
    add_action('admin_post_nopriv_wsi_forgot_password', 'wsi_handle_forgot_password');
    add_action('wp_ajax_wsi_forgot_password', 'wsi_handle_forgot_password');
    add_action('wp_ajax_nopriv_wsi_forgot_password', 'wsi_handle_forgot_password');
    add_action('wp_ajax_wsi_reset_password', 'wsi_handle_reset_password');
    add_action('wp_ajax_nopriv_wsi_reset_password', 'wsi_handle_reset_password');
    
    function wsi_handle_forgot_password() {
        try {
            error_log('WSI: Forgot password AJAX handler called');
            
            // Check nonce
            // Accept standard nonce or fallback to referer check for cached pages
            $nonce = $_POST['_wpnonce'] ?? ($_POST['wsi_fp_nonce'] ?? ($_POST['security'] ?? ''));
            $nonce_valid = $nonce && wp_verify_nonce($nonce, 'wsi_forgot_password_nonce');
            if (!$nonce_valid) {
                $ref = wp_get_referer();
                $host_ok = $ref && parse_url($ref, PHP_URL_HOST) === parse_url(home_url(), PHP_URL_HOST);
                if (!$host_ok) {
                    error_log('WSI: Nonce verification failed (forgot password)');
                    wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.']);
                }
            }

            $login = sanitize_text_field($_POST['user_login'] ?? '');
            
            if (empty($login)) {
                wp_send_json_error(['message' => 'Please enter your email or username']);
            }

            // Find user
            if (is_email($login)) {
                $user = get_user_by('email', $login);
            } else {
                $user = get_user_by('login', $login);
            }

            if (!$user) {
                error_log('WSI: No user found for: ' . $login);
                // Don't reveal whether account exists (security best practice)
                wp_send_json_success(['message' => 'If an account exists, you will receive an email shortly.']);
            }

            error_log('WSI: User found: ' . $user->user_login . ', email: ' . $user->user_email);
            
            // Manually generate reset key and email (don't use retrieve_password as it may hang/exit)
            error_log('WSI: Generating password reset key');
            
            $key = get_password_reset_key($user);
            
            if (is_wp_error($key)) {
                error_log('WSI: Error generating reset key: ' . $key->get_error_message());
                wp_send_json_success(['message' => 'If an account exists, you will receive an email shortly.']);
            }

            // Build reset URL
            $reset_url = add_query_arg([
                'key'   => $key,
                'login' => $user->user_login,
            ], site_url('/wsi/gen-pass/'));
            error_log('WSI: Reset URL: ' . $reset_url);
            
            // Prepare email
            $subject = 'Password Reset Request';
            $message = "Hello " . $user->display_name . ",\n\n";
            $message .= "You requested a password reset. Click the link below to reset your password:\n\n";
            $message .= $reset_url . "\n\n";
            $message .= "If you did not request this, you can ignore this email.\n\n";
            $message .= "This link expires in 24 hours.\n\n";
            $message .= "Best regards,\nCOFCO CAPITAL Team";
            
            error_log('WSI: Sending password reset email to: ' . $user->user_email);
            
            // Send email immediately; fallback logging on failure
            $sent = wsi_send_email_template($user->ID, 'email_password_reset', [
                'reset_url' => esc_url_raw($reset_url),
            ]);
            if (!$sent) {
                wsi_email_log('failed', [
                    'template' => 'email_password_reset',
                    'user_id'  => intval($user->ID),
                    'to'       => $user->user_email,
                    'reason'   => 'password_reset_request_send_failed',
                ]);
            } else {
                wsi_email_log('sent', [
                    'template' => 'email_password_reset',
                    'user_id'  => intval($user->ID),
                    'to'       => $user->user_email,
                    'reason'   => 'password_reset_request',
                ]);
            }
            
            error_log('WSI: Sending success response');
            wp_send_json_success(['message' => 'Check your email for password reset instructions']);
            exit;
            
        } catch (Exception $e) {
            error_log('WSI: Forgot password exception: ' . $e->getMessage() . ' at line ' . $e->getLine());
            wp_send_json_error(['message' => 'An error occurred. Please try again.']);
        }
    }

    /**
     * Handle password reset (new password form)
     */
    function wsi_handle_reset_password() {
        try {
            $nonce = $_POST['_wpnonce'] ?? '';
            if (empty($nonce) || !wp_verify_nonce($nonce, 'wsi_reset_password_nonce')) {
                wp_send_json_error(['message' => 'Security check failed.']);
            }

            $login = sanitize_text_field($_POST['user_login'] ?? '');
            $key   = sanitize_text_field($_POST['reset_key'] ?? '');
            $pass1 = $_POST['pass1'] ?? '';
            $pass2 = $_POST['pass2'] ?? '';

            if (empty($login) || empty($key)) {
                wp_send_json_error(['message' => 'Reset link is missing data. Please request a new link.']);
            }

            if ($pass1 !== $pass2) {
                wp_send_json_error(['message' => 'Passwords do not match.']);
            }

            if (strlen($pass1) < 8) {
                wp_send_json_error(['message' => 'Password must be at least 8 characters.']);
            }

            $user = check_password_reset_key($key, $login);
            if (is_wp_error($user)) {
                wp_send_json_error(['message' => 'This reset link is invalid or has expired. Please request a new link.']);
            }

            reset_password($user, $pass1);

            wp_send_json_success([
                'message'  => 'Your password has been updated. You can now log in.',
                'redirect' => site_url('/wsi/login/'),
            ]);
        } catch (Exception $e) {
            error_log('WSI: Reset password exception: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Unable to reset password. Please try again.']);
        }
    }

    /* -------------------------------------------------------------------------
       Async email helper (prevents front-end waits on slow SMTP)
    ------------------------------------------------------------------------- */
    add_action('wsi_async_email_template', function($user_id, $template_key, $vars = []) {
        wsi_send_email_template($user_id, $template_key, $vars);
    }, 10, 3);

function wsi_queue_email_template($user_id, $template_key, $vars = []) {
    $user_id = intval($user_id);
    $template_key = sanitize_key($template_key);
    $vars = is_array($vars) ? $vars : [];

    // Avoid stacking duplicate jobs for the same user/template in the same second
    if (!wp_next_scheduled('wsi_async_email_template', [$user_id, $template_key, $vars])) {
        wp_schedule_single_event(time() + 1, 'wsi_async_email_template', [$user_id, $template_key, $vars]);
    }
}

/**
 * Maintain a lightweight email log in options (capped for safety).
 * Stored with autoload = no to avoid bloating front-end loads.
 */
function wsi_email_log($status, $data = []) {
    $entry = array_merge([
        'time'   => current_time('mysql'),
        'status' => sanitize_text_field($status),
    ], $data);

    $log = get_option('wsi_email_log', []);
    if (!is_array($log)) {
        $log = [];
    }

    array_unshift($log, $entry);
    // Cap log size to 100 entries
    $log = array_slice($log, 0, 100);

    update_option('wsi_email_log', $log, false);
}

function wsi_get_email_log() {
    $log = get_option('wsi_email_log', []);
    return is_array($log) ? $log : [];
}

add_action('wp_mail_failed', function($error) {
    $data = is_wp_error($error) ? $error->get_error_data() : [];
    $to = is_array($data['to'] ?? null) ? implode(',', $data['to']) : ($data['to'] ?? '');
    wsi_email_log('failed', [
        'template' => $data['template_key'] ?? '',
        'user_id'  => intval($data['user_id'] ?? 0),
        'to'       => sanitize_text_field($to),
        'message'  => is_wp_error($error) ? $error->get_error_message() : 'Unknown mail error',
    ]);
});

function wsi_default_email_templates() {
    return [
        'email_deposit_received_subject'  => 'Deposit received',
        'email_deposit_received'          => 'Hi {name}, we received your deposit of {amount} on {date}.',
        'email_deposit_approved_subject'  => 'Deposit approved',
        'email_deposit_approved'          => 'Hi {name}, your deposit of {amount} was approved on {date}.',
        'email_deposit_declined_subject'  => 'Deposit declined',
        'email_deposit_declined'          => 'Hi {name}, your deposit of {amount} was declined on {date}.',
        'email_withdraw_received_subject' => 'Withdrawal request received',
        'email_withdraw_received'         => 'Hi {name}, we received your withdrawal request of {amount} on {date}.',
        'email_withdraw_approved_subject' => 'Withdrawal approved',
        'email_withdraw_approved'         => 'Hi {name}, your withdrawal of {amount} was approved on {date}.',
        'email_withdraw_declined_subject' => 'Withdrawal declined',
        'email_withdraw_declined'         => 'Hi {name}, your withdrawal of {amount} was declined on {date}.',
        'email_registration_welcome_subject' => 'Welcome to {site_name}',
        'email_registration_welcome'         => 'Welcome {name}! Your account at {site_name} was created on {date}.',
        'email_password_reset_subject'    => 'Password reset request',
        'email_password_reset'            => 'Hi {name}, reset your password using this link: {reset_url}',
        'email_smart_farming_on_subject'  => 'Smart Farming activated',
        'email_smart_farming_on'          => 'Hi {name}, Smart Farming has been activated on your account.',
        'email_smart_farming_off_subject' => 'Smart Farming deactivated',
        'email_smart_farming_off'         => 'Hi {name}, Smart Farming has been deactivated on your account.',
    ];
}

function wsi_seed_email_templates() {
    $opts = wsi_get_opts();
    $defaults = wsi_default_email_templates();

    foreach ($defaults as $k => $v) {
        if (empty($opts[$k])) {
            $opts[$k] = $v;
        }
    }

    // Ensure notification flag exists
    if (!isset($opts['email_notifications'])) {
        $opts['email_notifications'] = 1;
    }

    update_option('wsi_options', $opts);
}


    /* Helper For Mailing */
function wsi_send_email_template($user_id, $template_key, $vars = []) {
    $template_key = sanitize_key($template_key);
    $opts = wsi_get_opts();
    $defaults = wsi_default_email_templates();

    if (empty($opts['email_notifications'])) {
        wsi_email_log('skipped', [
            'template' => $template_key,
            'user_id'  => intval($user_id),
            'reason'   => 'notifications_disabled',
        ]);
        return false;
    }

    $user = get_userdata($user_id);
    if (!$user || !is_email($user->user_email)) {
        wsi_email_log('skipped', [
            'template' => $template_key,
            'user_id'  => intval($user_id),
            'reason'   => 'invalid_user_email',
        ]);
        return false;
    }

    $template = $opts[$template_key] ?? ($defaults[$template_key] ?? '');
    if (trim($template) === '') {
        wsi_email_log('skipped', [
            'template' => $template_key,
            'user_id'  => intval($user_id),
            'reason'   => 'empty_template',
        ]);
        return false;
    }

    $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    $domain = parse_url(home_url(), PHP_URL_HOST);
    $from_email = is_email('no-reply@' . $domain) ? 'no-reply@' . $domain : get_option('admin_email');
    $reply_to = is_email(get_option('admin_email')) ? get_option('admin_email') : $from_email;

    $replacements = [
        '{name}'      => $user->display_name ?: $user->user_login,
        '{amount}'    => isset($vars['amount']) ? number_format((float)$vars['amount'], 2) : '',
        '{date}'      => date('Y-m-d H:i:s'),
        '{plan}'      => $vars['plan'] ?? '',
        '{reset_url}' => $vars['reset_url'] ?? '',
        '{site_name}' => $site_name,
    ];

    $message = str_replace(array_keys($replacements), array_values($replacements), $template);

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <' . $from_email . '>',
        'Reply-To: ' . $reply_to,
    ];

    $subject = $opts[$template_key . '_subject'] ?? ($defaults[$template_key . '_subject'] ?? 'WSI Notification');

    $sent = false;
    try {
        $sent = wp_mail($user->user_email, $subject, nl2br($message), $headers);
    } catch (Throwable $e) {
        error_log(sprintf('WSI: wp_mail exception for user_id=%d template=%s to=%s : %s', intval($user_id), $template_key, $user->user_email, $e->getMessage()));
        wsi_email_log('failed', [
            'template' => $template_key,
            'user_id'  => intval($user_id),
            'to'       => $user->user_email,
            'message'  => $e->getMessage(),
        ]);
        return false;
    }

    if (!$sent) {
        error_log(sprintf('WSI: wp_mail failed for user_id=%d template=%s to=%s', intval($user_id), $template_key, $user->user_email));
        error_log('WSI: email subject: ' . $subject);
        error_log('WSI: email body (first 512 chars): ' . substr($message, 0, 512));
        wsi_email_log('failed', [
            'template' => $template_key,
            'user_id'  => intval($user_id),
            'to'       => $user->user_email,
            'subject'  => $subject,
        ]);
    } else {
        wsi_email_log('sent', [
            'template' => $template_key,
            'user_id'  => intval($user_id),
            'to'       => $user->user_email,
            'subject'  => $subject,
        ]);
    }

    return $sent;
}

// Admin log page for email attempts
add_action('admin_menu', function() {
    if (!wsi_admin_can()) return;
    add_submenu_page(
        'tools.php',
        'WSI Email Log',
        'WSI Email Log',
        'wsi_admin_access',
        'wsi-email-log',
        'wsi_render_email_log_page'
    );
});

function wsi_render_email_log_page() {
    if (!wsi_admin_can()) { wp_die(__('You do not have permission to access this page.')); }
    $log = wsi_get_email_log();
    ?>
    <div class="wrap">
        <h1>WSI Email Log</h1>
        <p>Last <?php echo esc_html(count($log)); ?> attempts (capped at 100). Status is informational and does not guarantee inbox delivery.</p>
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Time</th>
                <th>Status</th>
                <th>User ID</th>
                <th>Template</th>
                <th>To</th>
                <th>Subject/Reason</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($log)): ?>
                <tr><td colspan="6">No email activity logged yet.</td></tr>
            <?php else: ?>
                <?php foreach ($log as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row['time'] ?? ''); ?></td>
                        <td><?php echo esc_html($row['status'] ?? ''); ?></td>
                        <td><?php echo esc_html($row['user_id'] ?? ''); ?></td>
                        <td><?php echo esc_html($row['template'] ?? ''); ?></td>
                        <td><?php echo esc_html($row['to'] ?? ''); ?></td>
                        <td>
                            <?php
                            $subject = $row['subject'] ?? '';
                            $reason  = $row['reason'] ?? '';
                            echo esc_html($subject ?: $reason);
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}


    /* Helper to redirect to the login page */
    function wsi_get_login_page_url() {
        return home_url('/wsi/login/');
    }


    /* Popup Function Sitewide */
    function wsi_popup($message, $redirect = '') {
        $msg = esc_js($message);

        // Always have a valid redirect (no history.back to prevent loops)
        if (!$redirect) {
            $redirect = wsi_get_dashboard_page_url();
        }
        $redirect = esc_url_raw($redirect);

        echo "
        <script>
            (function(){
                alert('{$msg}');
                window.location.href='{$redirect}';
            })();
        </script>";
        exit;
    }



    /* Reinvest submit */
    add_action('admin_post_wsi_submit_reinvest', 'wsi_handle_reinvest');
    function wsi_handle_reinvest() {
        if (!is_user_logged_in()) { wp_safe_redirect(wsi_login_url()); exit; }

        global $wpdb;
        $dash_url = wsi_get_dashboard_page_url();

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'wsi_reinvest_nonce')) { 
            wsi_popup("Reinvest Error", $dash_url); 
            exit; 
        }

        $uid = get_current_user_id();
        $amount = round(floatval($_POST['amount'] ?? 0), 2);

        if ($amount <= 0) { 
            wsi_popup("Invalid Reinvest Amount", $dash_url); 
            exit; 
        }

        if ($amount > wsi_get_profit($uid)) { 
            wsi_popup("Insufficient Profit Balance", $dash_url); 
            exit; 
        }

        // Deduct from meta profit first, then accumulated_profit in holdings (FIFO), mirroring display logic
        $remaining = $amount;

        $meta_profit = floatval(get_user_meta($uid, 'wsi_profit_balance', true));
        if ($meta_profit > 0) {
            $use_meta = min($meta_profit, $remaining);
            $new_meta = max(0, $meta_profit - $use_meta);
            update_user_meta($uid, 'wsi_profit_balance', $new_meta);
            $remaining -= $use_meta;
        }

        if ($remaining > 0) {
            $t_hold = $wpdb->prefix . 'wsi_holdings';
            $holdings = $wpdb->get_results($wpdb->prepare(
                "SELECT id, accumulated_profit FROM $t_hold WHERE user_id=%d AND status='open' ORDER BY created_at ASC",
                $uid
            ));
            foreach ($holdings as $h) {
                if ($remaining <= 0) break;
                $current_ap = floatval($h->accumulated_profit);
                $use = min($current_ap, $remaining);
                if ($use > 0) {
                    $new_ap = max(0, $current_ap - $use);
                    $wpdb->query($wpdb->prepare(
                        "UPDATE $t_hold SET accumulated_profit = %f WHERE id=%d",
                        $new_ap, $h->id
                    ));
                    $remaining -= $use;
                }
            }
        }

        if ($remaining > 0) {
            // Safety net; should not happen because we pre-check total profit
            wsi_popup("Insufficient Profit Balance", $dash_url); 
            exit; 
        }

        wsi_inc_main($uid, $amount);
        wsi_log_tx($uid, $amount, 'reinvest', 'Reinvest from profit');
        wsi_audit($uid, 'reinvest', "Reinvested {$amount}");

        wsi_popup("Reinvested Successfully", $dash_url);
        exit;
    }


    /** User Settings Page: Logic For Processing Form Data **/
    add_action('admin_post_save_user_form', 'handle_save_user_form');
    add_action('admin_post_nopriv_save_user_form', 'handle_save_user_form'); // optional if non-logged-in users need it

    function handle_save_user_form() {

        // Verify nonce
        if ( !isset($_POST['save_user_form_nonce']) ||
             !wp_verify_nonce($_POST['save_user_form_nonce'], 'save_user_form_action') ) {
            wp_die('Security check failed');
        }

        // Sanitize and save
        $user_id = get_current_user_id();

        update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name'] ?? ''));
        update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name'] ?? ''));
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone'] ?? ''));
        update_user_meta($user_id, 'birth_date', sanitize_text_field($_POST['birth_date'] ?? ''));
        update_user_meta($user_id, 'address1', sanitize_text_field($_POST['address1'] ?? ''));
        update_user_meta($user_id, 'address2', sanitize_text_field($_POST['address2'] ?? ''));
        update_user_meta($user_id, 'landmark', sanitize_text_field($_POST['landmark'] ?? ''));
        update_user_meta($user_id, 'street', sanitize_text_field($_POST['street'] ?? ''));
        update_user_meta($user_id, 'country', sanitize_text_field($_POST['country'] ?? ''));
        update_user_meta($user_id, 'zip', sanitize_text_field($_POST['zip'] ?? ''));
        update_user_meta($user_id, 'state', sanitize_text_field($_POST['state'] ?? ''));
        update_user_meta($user_id, 'city', sanitize_text_field($_POST['city'] ?? ''));

        // Redirect back to form with success
        wp_redirect( wp_get_referer() . '&updated=1' );
        exit;
    }


    /* Buy stock submit */
    add_action('admin_post_wsi_buy_stock', 'wsi_handle_buy_stock');
    function wsi_handle_buy_stock() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wsi_buy_stock_nonce')) {
            wp_die('Security check failed');
        }

        if (!is_user_logged_in()) {
            wsi_popup("You Must Be Logged In", home_url('/'));
            exit;
        }

        $uid = get_current_user_id();
        $stock_id = intval($_POST['stock_id']);

        global $wpdb;
        $table_stocks   = $wpdb->prefix . 'wsi_stocks';
        $table_holdings = $wpdb->prefix . 'wsi_holdings';

        $stock = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_stocks WHERE id = %d", 
            $stock_id
        ));

        if (!$stock) {
            wsi_popup("Invalid Stock Selected", wsi_get_dashboard_page_url());
            exit;
        }

        // auto-use stock price
        $amount  = floatval($stock->price);
        $balance = floatval(wsi_get_main($uid));

        if ($balance < $amount) {
            wsi_popup("Insufficient Balance", wsi_get_dashboard_page_url());
            exit;
        }

        // deduct from main balance
        wsi_inc_main($uid, -$amount);

        // calculate shares (amount ÷ price)
        $shares = $stock->price > 0 ? ($amount / floatval($stock->price)) : 0;

        $wpdb->insert($table_holdings, [
            'user_id'            => $uid,
            'stock_id'           => $stock_id,
            'invested_amount'    => $amount,
            'shares'             => $shares,
            'accumulated_profit' => 0,
            'status'             => 'open',
            'created_at'         => current_time('mysql')
        ]);

        wsi_log_tx($uid, -$amount, 'buy_stock', "Bought {$stock->name}");

        wsi_popup("Stock Purchased Successfully! Check 'Holdings'", wsi_get_dashboard_page_url());
        exit;
    }


    /* Sell holding submit */
    add_action('admin_post_wsi_sell_holding', 'wsi_handle_sell_holding');
    function wsi_handle_sell_holding() {
        if (!is_user_logged_in()) { wp_safe_redirect(wsi_login_url()); exit; }

        $dash_url = wsi_get_dashboard_page_url();

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'wsi_sell_holding_nonce')) { 
            wsi_popup("Sell Error", $dash_url); 
            exit; 
        }

        $uid = get_current_user_id();
        $hid = intval($_POST['holding_id'] ?? 0);

        global $wpdb;
        $h = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wsi_holdings WHERE id=%d AND user_id=%d AND status='open'", 
            $hid, 
            $uid
        ));

        if (!$h) { 
            wsi_popup("Holding Not Found", $dash_url);
            exit;
        }

        $profit = floatval($h->accumulated_profit);

        if ($profit > 0) {
            wsi_inc_main($uid, $profit);
            wsi_log_tx($uid, $profit, 'sell_profit', "Sold holding #{$hid} profit transferred");
        }

        $wpdb->update(
            $wpdb->prefix . 'wsi_holdings', 
            ['status' => 'closed'], 
            ['id' => $hid]
        );

        wsi_audit($uid, 'sell_holding', "Sold holding {$hid}");

        wsi_popup("Holding Sold Successfully", $dash_url);
        exit;
    }


/* -------------------------------------------------------------------------
   REFERRAL: 10% first level, 5% second level (applied only on first confirmed deposit)
------------------------------------------------------------------------- */
function wsi_apply_referral($user_id, $amount, $deposit_id = 0) {
    $amount = floatval($amount);
    if ($amount <= 0) return;

    // Stop if already processed for this user
    if (get_user_meta($user_id, 'wsi_first_confirmed_deposit', true)) return;

    // Prefer the canonical inviter meta, but fall back to legacy key if needed
    $inv1 = intval(get_user_meta($user_id, 'wsi_inviter_id', true));
    if (!$inv1) {
        $inv1 = intval(get_user_meta($user_id, 'wsi_referred_by', true));
    }

    $paid = false;

    if ($inv1 && $inv1 !== $user_id) {
        $bonus1 = round($amount * 0.10, 2); // 10%
        if ($bonus1 > 0) {
            // Credit referral earnings into profit balance
            wsi_inc_profit($inv1, $bonus1);
            wsi_log_tx($inv1, $bonus1, 'referral_first', "10% from user {$user_id} deposit #{$deposit_id}");
            wsi_notify_user($inv1, 'Referral Bonus', "You received $" . number_format($bonus1, 2) . " for a referral deposit.");
            wsi_audit($inv1, 'referral_first', "Awarded {$bonus1}");
            $paid = true;
        }

        // Second-level bonus
        $inv2 = intval(get_user_meta($inv1, 'wsi_inviter_id', true));
        if ($inv2 && $inv2 !== $user_id) {
            $bonus2 = round($amount * 0.05, 2); // 5%
            if ($bonus2 > 0) {
                // Credit second-level referral into profit balance
                wsi_inc_profit($inv2, $bonus2);
                wsi_log_tx($inv2, $bonus2, 'referral_second', "5% from user {$user_id} deposit #{$deposit_id}");
                wsi_notify_user($inv2, 'Referral Bonus (2nd level)', "You received $" . number_format($bonus2, 2) . " for a second-level referral deposit.");
                wsi_audit($inv2, 'referral_second', "Awarded {$bonus2}");
                $paid = true;
            }
        }
    }

    // Mark as processed only when a bonus was paid
    if ($paid) {
        update_user_meta($user_id, 'wsi_first_confirmed_deposit', 1);
    }
}

    /* -------------------------------------------------------------------------
       One-time backfill: copy wsi_referred_by into wsi_inviter_id
       (covers older accounts so second-level bonuses trigger)
    ------------------------------------------------------------------------- */
    add_action('admin_init', function() {
        if (!wsi_admin_can()) return;
        if (get_option('wsi_inviter_backfill_done')) return;

        global $wpdb;
        $rows = $wpdb->get_results("
            SELECT m1.user_id, m1.meta_value AS ref_by
            FROM {$wpdb->usermeta} m1
            LEFT JOIN {$wpdb->usermeta} m2
              ON m2.user_id = m1.user_id
             AND m2.meta_key = 'wsi_inviter_id'
            WHERE m1.meta_key = 'wsi_referred_by'
              AND m1.meta_value <> ''
              AND (m2.meta_value IS NULL OR m2.meta_value = '')
            LIMIT 5000
        ");

        if (!empty($rows)) {
            foreach ($rows as $r) {
                update_user_meta($r->user_id, 'wsi_inviter_id', intval($r->ref_by));
            }
        }

        update_option('wsi_inviter_backfill_done', gmdate('Y-m-d H:i:s'));
    });

    /* -------------------------------------------------------------------------
       ACCRUALS: hourly and daily processes
    ------------------------------------------------------------------------- */
    add_action('wsi_hourly_accrue', 'wsi_hourly_accrue_fn');
    function wsi_hourly_accrue_fn() {
        global $wpdb;
        $t_hold = $wpdb->prefix . 'wsi_holdings';
        $t_stocks = $wpdb->prefix . 'wsi_stocks';
        // apply hourly interest for holdings where stock.rate_period = hourly
        $rows = $wpdb->get_results("SELECT h.*, s.rate_percent FROM $t_hold h JOIN $t_stocks s ON s.id=h.stock_id WHERE h.status='open' AND s.rate_period='hourly' AND s.active=1");
        foreach ($rows as $h) {
            $percent = floatval($h->rate_percent) / 100.0;
            $add = round(floatval($h->invested_amount) * $percent, 2);
            if ($add > 0) {
                $wpdb->update($t_hold, ['accumulated_profit' => floatval($h->accumulated_profit) + $add], ['id' => $h->id]);
                wsi_log_tx($h->user_id, $add, 'holding_hourly_interest', "Holding #{$h->id} hourly interest");
            }
        }
    }

    add_action('wsi_daily_accrue', 'wsi_daily_accrue_fn');
    function wsi_daily_accrue_fn() {
        global $wpdb;

        $opts = wsi_get_opts();
        $percent = floatval($opts['main_daily_percent'] ?? 2.29) / 100.0;

        // get all users who enabled Smart Farming
        $users = $wpdb->get_col("
            SELECT user_id 
            FROM {$wpdb->usermeta}
            WHERE meta_key='wsi_smart_farming' AND meta_value='yes'
        ");

        foreach ($users as $uid) {

            // MAIN BALANCE INTEREST (controlled by Smart Farming switch)
            $main = wsi_get_main($uid);
            if ($main > 0) {
                $interest = round($main * $percent, 2);
                if ($interest > 0) {
                    wsi_inc_profit($uid, $interest);
                    wsi_log_tx($uid, $interest, 'daily_interest', "Daily interest applied (Smart Farming)");
                }
            }
        }

        // HOLDINGS ACCRUAL (unchanged — NOT controlled by Smart Farming)
        $t_hold = $wpdb->prefix . 'wsi_holdings';
        $t_stocks = $wpdb->prefix . 'wsi_stocks';

        $rows = $wpdb->get_results("
            SELECT h.*, s.rate_percent 
            FROM $t_hold h 
            JOIN $t_stocks s ON s.id = h.stock_id 
            WHERE h.status='open' AND s.rate_period='daily' AND s.active=1
        ");

        foreach ($rows as $h) {
            $percent = floatval($h->rate_percent) / 100.0;
            $add = round(floatval($h->invested_amount) * $percent, 2);
            if ($add > 0) {
                $wpdb->update(
                    $t_hold,
                    ['accumulated_profit' => floatval($h->accumulated_profit) + $add],
                    ['id' => $h->id]
                );
                wsi_log_tx($h->user_id, $add, 'holding_daily_interest', "Holding #{$h->id} daily interest");
            }
        }
    }


    /* -------------------------------------------------------------------------
       Ensure invite code on login
    ------------------------------------------------------------------------- */
    add_action('wp_login', function ($user_login, $user) {
        wsi_ensure_invite_code($user->ID);
    }, 10, 2);

    /* -------------------------------------------------------------------------
       Shortcode: transactions list (users see own, admin sees all)
    ------------------------------------------------------------------------- */
    //add_shortcode('wsi_transactions', 'wsi_shortcode_transactions');
    function wsi_shortcode_transactions() {
        if (!is_user_logged_in()) return '<div>Please login</div>';
        global $wpdb;
        $uid = get_current_user_id();
        if (wsi_admin_can()) $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wsi_transactions ORDER BY created_at DESC LIMIT 500");
        else $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wsi_transactions WHERE user_id=%d ORDER BY created_at DESC LIMIT 200", $uid));
        ob_start();
        echo '<table class="widefat"><thead><tr><th>When</th><th>Amount</th><th>Type</th><th>Desc</th></tr></thead><tbody>';
        foreach ($rows as $r) echo '<tr><td>' . esc_html($r->created_at) . '</td><td>$' . number_format($r->amount, 2) . '</td><td>' . esc_html($r->type) . '</td><td>' . esc_html($r->description) . '</td></tr>';
        echo '</tbody></table>';
        return ob_get_clean();
    }

    /* Stock Buy option */

    add_action('admin_post_wsi_inv_buy_stock', 'wsi_inv_buy_stock');
    add_action('admin_post_nopriv_wsi_inv_buy_stock', 'wsi_inv_buy_stock');
    function wsi_inv_buy_stock() {
        if (!is_user_logged_in()) wp_die('Not allowed');
        if (!isset($_POST['stock_id']) || !wp_verify_nonce($_POST['_wpnonce'], 'wsi_inv_buy_stock_nonce')) wp_die('Security failed');

        global $wpdb;
        $uid = get_current_user_id();
        $sid = intval($_POST['stock_id']);
        $stock = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wsi_stocks WHERE id=%d", $sid));
        if (!$stock) wp_die('Stock not found');

        $balance = wsi_inv_get_main($uid);
        if ($balance < $stock->price) wp_die('Insufficient balance');

        wsi_inv_change_main($uid, -$stock->price);
        $wpdb->insert("{$wpdb->prefix}wsi_holdings", [
            'user_id' => $uid,
            'stock_id' => $sid,
            'invested_amount' => $stock->price,
            'accumulated_profit' => 0,
            'status' => 'open',
            'created_at' => current_time('mysql')
        ]);
        wsi_inv_log_transaction($uid, -$stock->price, 'buy_stock', "Bought {$stock->name}");
        wp_redirect(wp_get_referer());
        exit;
    }



    /* -------------------------------------------------------------------------
       End of plugin
    ------------------------------------------------------------------------- */


    /* ---------------------------------------------------------
       4. Registration access only via invite link
    --------------------------------------------------------- */
    add_action('template_redirect', function() {

        $current = trim($_SERVER['REQUEST_URI'], '/');
        if ($current !== 'wsi/register') return;

        $ref = sanitize_text_field($_GET['ref'] ?? '');
        if (empty($ref)) {
            wp_die(__('You need an invite link to access registration.', 'wsi'));
        }

        global $wpdb;
        $user_id = $wpdb->get_var($wpdb->prepare("
            SELECT user_id FROM $wpdb->usermeta
            WHERE meta_key = 'wsi_invite_code'
            AND LOWER(meta_value) = LOWER(%s)
            LIMIT 1
        ", $ref));

        if (!$user_id) {
            wp_die(__('Invalid invite link.', 'wsi'));
        }

        if (!session_id()) session_start();
        $_SESSION['wsi_referrer_id'] = $user_id;
    });


    /* ---------------------------------------------------------
       5. Render login page directly at /wsi/login
    --------------------------------------------------------- */
    add_action('template_redirect', function () {

        // Normalize URL path (removes query strings)
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        if ($path === 'wsi/login') {

            if (is_user_logged_in()) {
                wp_safe_redirect(wsi_dashboard_url());
                exit;
            }

            $args = [
                'redirect'        => home_url('/wsi/dashboard/'),
                'form_id'         => 'wsi-loginform',
                'label_username'  => __('Username or Email'),
                'label_password'  => __('Password'),
                'label_log_in'    => __('Login'),
                'remember'        => true,
            ];

            get_header();
            echo '<div class="wsi-login-page">';
            wp_login_form($args);
            echo '</div>';
            get_footer();
            exit;
        }
    });

    /*
     * Force login redirect for your custom form
     */
    add_filter('login_redirect', function ($redirect_to, $requested, $user) {

        if (
            isset($_POST['form_id']) &&
            $_POST['form_id'] === 'wsi-loginform'
        ) {
            return wsi_get_dashboard_page_url();
        }

        return $redirect_to;

    }, 9999, 3);


    /* -------------------------------------------------------
       6. Stay on same page when login fails (front-end forms)
    ------------------------------------------------------- */
    add_action('wp_footer', function() {

        // Show error ONLY on the custom login page
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        if ($path !== 'wsi/login') return;

        if ($msg = get_transient('wsi_login_error')) {
            echo '<div class="wsi-alert wsi-error" style="color:red;margin:10px 0;">' . esc_html($msg) . '</div>';
            delete_transient('wsi_login_error');
        }
    });

    /*---------------------------------------------------------------------

        Capture failed login attempts & redirect back to /wsi/login

    ----------------------------------------------------------------------*/

    add_action('wp_login_failed', function($username) {

        // Store error message
        set_transient('wsi_login_error', 'Invalid username or password.', 30);

        // Redirect back to your custom login page
        wp_safe_redirect(site_url('/wsi/login'));
        exit;
    });

    /*-----------------------------------------------------------------------------

        Stop WordPress from redirecting to wp-login on authentication errors

    ------------------------------------------------------------------------------*/
    add_filter('authenticate', function($user, $username, $password) {

        if (!empty($_POST['form_id']) && $_POST['form_id'] === 'wsi-loginform') {

            if (empty($username) || empty($password)) {
                set_transient('wsi_login_error', 'Username and password are required.', 30);
                return null;
            }
        }

        return $user;

    }, 1, 3);


    // Handle registration from front-end form
    add_action('init', function() {
        if (isset($_POST['wsi_register_nonce']) && wp_verify_nonce($_POST['wsi_register_nonce'], 'wsi_register_action')) {
            $username    = sanitize_user($_POST['username'] ?? '');
            $email       = sanitize_email($_POST['email'] ?? '');
            $password    = sanitize_text_field($_POST['password'] ?? '');
            $ref         = sanitize_text_field($_POST['ref'] ?? '');
            $first_name  = sanitize_text_field($_POST['first_name'] ?? '');
            $last_name   = sanitize_text_field($_POST['last_name'] ?? '');
            $phone_code  = sanitize_text_field($_POST['phone_code'] ?? '');
            $phone_input = sanitize_text_field($_POST['phone'] ?? '');
            $phone_full  = trim($phone_code . ' ' . $phone_input);

            if (empty($username) || empty($email) || empty($password)) {
                set_transient('wsi_register_error', __('All fields are required.', 'wsi'), 30);
                wp_safe_redirect($_SERVER['HTTP_REFERER']);
                exit;
            }

            if (username_exists($username) || email_exists($email)) {
                set_transient('wsi_register_error', __('Username or email already exists.', 'wsi'), 30);
                wp_safe_redirect($_SERVER['HTTP_REFERER']);
                exit;
            }

            // Validate invite
            global $wpdb;
            $ref_user_id = $wpdb->get_var($wpdb->prepare("
                SELECT user_id FROM $wpdb->usermeta
                WHERE meta_key = 'wsi_invite_code'
                AND LOWER(meta_value) = LOWER(%s)
                LIMIT 1
            ", $ref));

            if (!$ref_user_id) {
                set_transient('wsi_register_error', __('Invalid invite link.', 'wsi'), 30);
                wp_safe_redirect($_SERVER['HTTP_REFERER']);
                exit;
            }

            $user_id = wp_create_user($username, $password, $email);
            if (is_wp_error($user_id)) {
                set_transient('wsi_register_error', $user_id->get_error_message(), 30);
                wp_safe_redirect($_SERVER['HTTP_REFERER']);
                exit;
            }

            // Success: store profile info + referral and login (keep both meta keys for compatibility)
            update_user_meta($user_id, 'wsi_referred_by', $ref_user_id);
            update_user_meta($user_id, 'wsi_inviter_id', $ref_user_id);
            if ($first_name || $last_name) {
                wp_update_user([
                    'ID'         => $user_id,
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                    'display_name' => trim($first_name . ' ' . $last_name) ?: $username,
                ]);
            }
            if ($phone_full) {
                update_user_meta($user_id, 'phone', $phone_full);
                update_user_meta($user_id, 'wsi_phone', $phone_full);
            }
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            // ALWAYS redirect new users to /wsi/dashboard/
            wp_safe_redirect(home_url('/wsi/dashboard/'));
            exit;

        }
    });

    // Display registration errors on the page
    add_action('wp_footer', function() {
        if ($msg = get_transient('wsi_register_error')) {
            echo '<div class="wsi-alert wsi-error" style="color:red;margin:10px 0;">' . esc_html($msg) . '</div>';
            delete_transient('wsi_register_error');
        }
    });

    // --- Deposit success modal + AJAX fix ---
    add_action('wp_footer', function() {
    ?>
    <script>
    (function(){

        function initWsiDepositAjax(){

            const btn = document.getElementById("wsi_deposit_submit");
            if (!btn) return;

            btn.style.display = "block";

            btn.addEventListener("click", async function (e) {
                e.preventDefault();

                const form = btn.closest("form");
                if (!form) {
                    alert("Deposit form not found.");
                    return;
                }

                const formData = new FormData(form);

                const postUrl = "<?php echo admin_url('admin-ajax.php'); ?>";

                // make sure nonce is sent for server verification
                var nonceEl = form.querySelector('input[name="_wpnonce"]');
                if (nonceEl) formData.append('_wpnonce', nonceEl.value);

                formData.append("action", "wsi_submit_deposit");
                formData.append("is_ajax", "1");


                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = "Submitting...";

                try {
                    const response = await fetch(postUrl, {
                        method: "POST",
                        body: formData
                    });

                    const text = await response.text();

                    if (text.startsWith("SUCCESS:")) {

                        const parts = text.replace("SUCCESS:", "").split("|");

                        const dep = {
                            id: parts[0],
                            usd: parts[1],
                            ngn: parts[2],
                            payment_type: parts[3],
                            wallet: parts[4]
                        };

                        const modal = document.createElement("div");
                        modal.className = "wsi-modal-overlay";
                        modal.innerHTML = `
                            <div class="wsi-modal-box">
                                <h3>✅ Your deposit has been submitted and is pending approval by admin.</h3>
                                <p>
                                    <strong>Deposit ID:</strong> #${dep.id}<br>
                                    <strong>Amount (USD):</strong> $${dep.usd}<br>
                                    <strong>Amount (Local):</strong> ₦${dep.ngn}<br>
                                    <strong>Payment Type:</strong> ${dep.payment_type}<br>
                                    ${dep.wallet ? `<strong>Wallet:</strong> ${dep.wallet}<br>` : ""}
                                </p>
                                <button class="wsi-modal-close">Close</button>
                            </div>
                        `;

                        document.body.appendChild(modal);

                        modal.querySelector(".wsi-modal-close").addEventListener("click", () => {
                            modal.remove();
                            window.location.reload();
                        });

                    } else if (text.startsWith("ERROR:")) {
                        alert(text.replace("ERROR:", ""));
                    } else {
                        alert("Unexpected response from server: " + text);
                    }

                } catch (err) {
                    console.error(err);
                    alert("Network error. Please try again.");
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }

            });

        }

        // Run on normal page load
        document.addEventListener("DOMContentLoaded", initWsiDepositAjax);

        // Run when Elementor loads widgets dynamically
        jQuery(window).on("elementor/frontend/init", function () {
            elementorFrontend.hooks.addAction("frontend/element_ready/global", initWsiDepositAjax);
        });

    })();
    </script>

    <?php
    });


    // --- Ensure JSON response in handler ---
    add_filter('wsi_handle_deposit_response', function($response){
      if (is_array($response)) {
        header('Content-Type: application/json');
        echo json_encode($response);
        wp_die();
      }
      return $response;
    });

    function wsi_get_user_transactions($user_id = 0, $limit = 100) {
        global $wpdb;
        $t = $wpdb->prefix . 'wsi_transactions';
        $user_id = intval($user_id) ?: get_current_user_id();
        $sql = $wpdb->prepare("SELECT * FROM {$t} WHERE user_id=%d ORDER BY created_at DESC LIMIT %d", $user_id, intval($limit));
        return $wpdb->get_results($sql);
    }
    /* Add Routes ------------------------/
    -------------------------------------*/
    add_action('init', function () {
        add_rewrite_rule(
            '^wsi/([^/]*)/?',
            'index.php?sv_page=$matches[1]',
            'top'
        );
    });

    add_filter('query_vars', function ($vars) {
        $vars[] = 'sv_page';
        return $vars;
    });

    add_action('template_redirect', function () {
        $page = get_query_var('sv_page');

        if ($page) {
            // AUTHENTICATION CHECKS before loading page
            
            // Public pages that don't require login
            $public_pages = ['login', 'forgot-password', 'signup', 'gen-pass'];
            $is_public = in_array($page, $public_pages);

            // Block non-admin from wp-admin
            if (is_admin() && !defined('DOING_AJAX')) {
                if (!wsi_admin_can()) {
                    wp_safe_redirect(home_url('/wsi/login/'));
                    exit;
                }
            }

            // Redirect logged-out users from private pages to login
            if (!$is_public && !is_user_logged_in()) {
                wp_safe_redirect(home_url('/wsi/login/'));
                exit;
            }

            // Redirect logged-in users from login page to dashboard
            if ($page === 'login' && is_user_logged_in()) {
                wp_safe_redirect(home_url('/wsi/dashboard/'));
                exit;
            }

            // Load the page file
            $file = WP_PLUGIN_DIR . '/stock-vest/pages/' . $page . '.php';
            if (file_exists($file)) {
                require_once $file;
                exit;
            }
        }
    });
