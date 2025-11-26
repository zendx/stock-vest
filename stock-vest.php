<?php
/**
 * Plugin Name: WSI — Single-file Investment Plugin
 * Description: A Wordpress Plugin For Investment and stocks
 * Version: 1.0.0
 * Author: HENRY SHEDRACK
 * Text Domain: wsi
 */

if (!defined('ABSPATH')) exit;





/* Silent auto-migration: add payment_type and wallet columns if missing */
add_action('admin_init', 'wsi_maybe_add_deposit_columns');
function wsi_maybe_add_deposit_columns() {
    global $wpdb;
    $t = $wpdb->prefix . 'wsi_deposits';
    // check columns
    $cols = $wpdb->get_results("SHOW COLUMNS FROM `{$t}` LIKE 'payment_type'"); 
    if (empty($cols)) {
        // add columns silently
        $wpdb->query("ALTER TABLE `{$t}` ADD COLUMN `payment_type` VARCHAR(20) NULL DEFAULT NULL");
    }
    $cols = $wpdb->get_results("SHOW COLUMNS FROM `{$t}` LIKE 'wallet'"); 
    if (empty($cols)) {
        $wpdb->query("ALTER TABLE `{$t}` ADD COLUMN `wallet` VARCHAR(100) NULL DEFAULT NULL");
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
    wsi_create_tables();

    // schedule hourly (for hourly stock accrual)
    if (!wp_next_scheduled('wsi_hourly_accrue')) {
        wp_schedule_event(time(), 'hourly', 'wsi_hourly_accrue');
    }

    // schedule daily (for daily interest + holdings accrual)
    if (!wp_next_scheduled('wsi_daily_accrue')) {
        wp_schedule_event(time(), 'daily', 'wsi_daily_accrue');
    }

    // create default options if missing
    if (get_option('wsi_options') === false) {
        update_option('wsi_options', [
            'main_daily_percent' => 2.29,
            'min_invest' => 50.00,
            'deposit_mode' => 'manual',
            'manual_payment_info' => "Bank: Example Bank\nAccount: 0123456789\nName: WSI Investments",
            'email_notifications' => 1
        ]);
    }
}



/*-- Repair Function--*/
function wsi_fix_missing_deposit_columns() {
    global $wpdb;
    $t = $wpdb->prefix . 'wsi_deposits';

    // Check & add amount_local
    if (!$wpdb->get_var("SHOW COLUMNS FROM $t LIKE 'amount_local'")) {
        $wpdb->query("ALTER TABLE $t ADD COLUMN amount_local DECIMAL(14,2) DEFAULT 0 AFTER amount");
    }

    // Check & add payment_type
    if (!$wpdb->get_var("SHOW COLUMNS FROM $t LIKE 'payment_type'")) {
        $wpdb->query("ALTER TABLE $t ADD COLUMN payment_type VARCHAR(80) DEFAULT '' AFTER amount_local");
    }

    // Check & add wallet
    if (!$wpdb->get_var("SHOW COLUMNS FROM $t LIKE 'wallet'")) {
        $wpdb->query("ALTER TABLE $t ADD COLUMN wallet VARCHAR(255) DEFAULT '' AFTER payment_type");
    }

    // Check & add method
    if (!$wpdb->get_var("SHOW COLUMNS FROM $t LIKE 'method'")) {
        $wpdb->query("ALTER TABLE $t ADD COLUMN method VARCHAR(80) DEFAULT '' AFTER wallet");
    }

    // Check & add admin_note
    if (!$wpdb->get_var("SHOW COLUMNS FROM $t LIKE 'admin_note'")) {
        $wpdb->query("ALTER TABLE $t ADD COLUMN admin_note TEXT AFTER token");
    }
}
add_action('plugins_loaded', 'wsi_fix_missing_deposit_columns');



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
    $t1 = $wpdb->prefix . 'wsi_deposits';
    $t2 = $wpdb->prefix . 'wsi_withdrawals';
    $t3 = $wpdb->prefix . 'wsi_transactions';
    $t4 = $wpdb->prefix . 'wsi_stocks';
    $t5 = $wpdb->prefix . 'wsi_holdings';
    $t6 = $wpdb->prefix . 'wsi_audit';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "
    CREATE TABLE IF NOT EXISTS $t1 (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      user_id BIGINT UNSIGNED NOT NULL,
      amount DECIMAL(14,2) NOT NULL,
      amount_local DECIMAL(14,2) DEFAULT 0,
      payment_type VARCHAR(80) DEFAULT '',
      wallet VARCHAR(255) DEFAULT '',
      method VARCHAR(80) DEFAULT '',
      status VARCHAR(32) DEFAULT 'pending',
      token VARCHAR(128),
      admin_note TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY user_id (user_id),
      KEY status (status)
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS $t2 (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      user_id BIGINT UNSIGNED NOT NULL,
      amount DECIMAL(14,2) NOT NULL,
      method VARCHAR(80) DEFAULT '',          /* <--- ADDED */
      account_details TEXT,
      status VARCHAR(32) DEFAULT 'pending',
      admin_note TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY user_id (user_id),
      KEY status (status)
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS $t3 (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      user_id BIGINT UNSIGNED NOT NULL,
      amount DECIMAL(14,2) NOT NULL,
      type VARCHAR(60) NOT NULL,
      description TEXT,
      meta LONGTEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY user_id (user_id)
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS $t4 (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(255) NOT NULL,
      price DECIMAL(14,2) NOT NULL,
      rate_percent DECIMAL(8,4) NOT NULL,
      rate_period ENUM('daily','hourly') DEFAULT 'daily',
      active TINYINT(1) DEFAULT 1,
      image VARCHAR(255) DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS $t5 (
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
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS $t6 (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      actor_id BIGINT UNSIGNED,
      action VARCHAR(255),
      details TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id)
    ) $charset_collate;
    ";

    dbDelta($sql);
}


/**
 * Auto-migrate database columns for wsi_deposits table
 * Ensures missing columns (amount_local, payment_type, wallet) are created.
 */
function wsi_deposits_auto_migrate() {
    global $wpdb;
    $table = $wpdb->prefix . 'wsi_deposits';

    // Get existing columns
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
add_action('plugins_loaded', 'wsi_deposits_auto_migrate');


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

// Ensure we have readable values for confirmation
$amount_display = number_format((float)$amount, 2);
$amount_local_display = !empty($amount_naira) ? number_format((float)$amount_naira, 2) : '-';
$method_display = !empty($method) ? ucfirst($method) : '-';

// Construct a confirmation message with actual values
$message = "
Your deposit has been submitted and is pending approval.<br><br>
<strong>Deposit ID:</strong> {$deposit_id}<br>
<strong>Amount (USD):</strong> \${$amount_display}<br>
<strong>Amount (Local):</strong> {$amount_local_display}<br>
<strong>Payment Type:</strong> {$method_display}
";

if (defined('DOING_AJAX') && DOING_AJAX) {
    wp_send_json_success(['message' => $message]);
    exit;
} else {
    wp_redirect(add_query_arg(['deposit' => 'success', 'msg' => urlencode($message)], wp_get_referer()));
    exit;
}
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
function wsi_notify_admin($subject, $message) {
    $opts = wsi_get_opts();
    if (empty($opts['email_notifications'])) return;
    $admin = get_option('admin_email');
    if (is_email($admin)) wp_mail($admin, $subject, $message);
}
function wsi_notify_user($uid, $subject, $message) {
    $opts = wsi_get_opts();
    if (empty($opts['email_notifications'])) return;
    $u = get_userdata($uid);
    if ($u && is_email($u->user_email)) wp_mail($u->user_email, $subject, $message);
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
function wsi_get_invite_link($uid = 0) {
    if (!$uid) $uid = get_current_user_id();
    $code = wsi_ensure_invite_code($uid);
    $reg = wsi_get_register_page();
    return esc_url(add_query_arg('ref', $code, $reg));
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
        if (!empty($found)) update_user_meta($user_id, 'wsi_inviter_id', intval($found[0]->ID));
    }
    wsi_ensure_invite_code($user_id);
}

/* -------------------------------------------------------------------------
   Admin menu (fixed slugs and callbacks)
------------------------------------------------------------------------- */
add_action('admin_menu', 'wsi_admin_menu');
function wsi_admin_menu() {
    add_menu_page('WSI', 'WSI', 'manage_options', 'wsi_main', 'wsi_admin_dashboard', 'dashicons-chart-area', 3);
    add_submenu_page('wsi_main', 'Users', 'Users', 'manage_options', 'wsi_users', 'wsi_admin_users');
    add_submenu_page('wsi_main', 'Deposits', 'Deposits', 'manage_options', 'wsi_deposits', 'wsi_admin_deposits');
    add_submenu_page('wsi_main', 'Withdrawals', 'Withdrawals', 'manage_options', 'wsi_withdrawals', 'wsi_admin_withdrawals');
    add_submenu_page('wsi_main', 'Stocks', 'Stocks', 'manage_options', 'wsi_stocks', 'wsi_admin_stocks');
    add_submenu_page('wsi_main', 'Transactions', 'Transactions', 'manage_options', 'wsi_transactions', 'wsi_admin_transactions');
    add_submenu_page('wsi_main', 'Settings', 'Settings', 'manage_options', 'wsi_settings', 'wsi_admin_settings');
}

function wsi_admin_dashboard() {
    if (!current_user_can('manage_options')) return;
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
    if (!current_user_can('manage_options')) return;
    global $wpdb;

    if (!empty($_POST['action_user']) && check_admin_referer('wsi_users_nonce')) {
        $act = sanitize_text_field($_POST['action_user']);
        $uid = intval($_POST['user_id']);

        if ($act === 'delete') {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($uid);
            echo '<div class="notice notice-success"><p>User deleted</p></div>';
            wsi_audit(get_current_user_id(), 'delete_user', "Deleted {$uid}");

        } elseif ($act === 'suspend') {
            update_user_meta($uid, 'wsi_suspended', 1);
            echo '<div class="notice notice-success"><p>User suspended</p></div>';
            wsi_audit(get_current_user_id(), 'suspend_user', "Suspended {$uid}");

        } elseif ($act === 'unsuspend') {
            delete_user_meta($uid, 'wsi_suspended');
            echo '<div class="notice notice-success"><p>User unsuspended</p></div>';
            wsi_audit(get_current_user_id(), 'unsuspend_user', "Unsuspended {$uid}");

        } elseif ($act === 'credit') {
            $amt = floatval($_POST['amount']);
            wsi_inc_main($uid, $amt);
            wsi_log_tx($uid, $amt, 'admin_credit', 'Admin credited');
            echo '<div class="notice notice-success"><p>Credited $' . number_format($amt, 2) . '</p></div>';
            wsi_audit(get_current_user_id(), 'credit_user', "Credited {$amt} to {$uid}");

        } elseif ($act === 'debit') {  // <-- NEW
            $amt = floatval($_POST['amount']);
            wsi_inc_main($uid, -$amt); // deduct
            wsi_log_tx($uid, $amt, 'admin_debit', 'Admin debited');
            echo '<div class="notice notice-success"><p>Debited $' . number_format($amt, 2) . '</p></div>';
            wsi_audit(get_current_user_id(), 'debit_user', "Debited {$amt} from {$uid}");
        }
    }

    $users = get_users(['number' => 200, 'orderby' => 'ID', 'order' => 'DESC']);
    ?>
    <div class="wrap"><h1>Users</h1>
    <table class="widefat striped"><thead>
        <tr>
            <th>ID</th>
            <th>Login</th>
            <th>Email</th>
            <th>Main</th>
            <th>Profit</th>
            <th>Inviter</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead><tbody>
    <?php foreach ($users as $u) {
        $main   = number_format(wsi_get_main($u->ID), 2);
        $profit = number_format(wsi_get_profit($u->ID), 2);
        $inv    = get_user_by('id', intval(get_user_meta($u->ID, 'wsi_inviter_id', true)));
        $status = get_user_meta($u->ID, 'wsi_suspended', true) ? 'Suspended' : 'Active';
        ?>
      <tr>
        <td><?php echo intval($u->ID); ?></td>
        <td><?php echo esc_html($u->user_login); ?></td>
        <td><?php echo esc_html($u->user_email); ?></td>
        <td>$<?php echo $main; ?></td>
        <td>$<?php echo $profit; ?></td>
        <td><?php echo $inv ? esc_html($inv->user_login) : '—'; ?></td>
        <td><?php echo esc_html($status); ?></td>
        <td>
          <form method="post" style="display:inline">
              <?php wp_nonce_field('wsi_users_nonce'); ?>
              <input type="hidden" name="user_id" value="<?php echo intval($u->ID); ?>">
              <button name="action_user" value="delete" class="button" onclick="return confirm('Delete?')">Delete</button>
          </form>

          <form method="post" style="display:inline">
              <?php wp_nonce_field('wsi_users_nonce'); ?>
              <input type="hidden" name="user_id" value="<?php echo intval($u->ID); ?>">
              <button name="action_user" value="suspend" class="button">Suspend</button>
          </form>

          <form method="post" style="display:inline">
              <?php wp_nonce_field('wsi_users_nonce'); ?>
              <input type="hidden" name="user_id" value="<?php echo intval($u->ID); ?>">
              <button name="action_user" value="unsuspend" class="button">Unsuspend</button>
          </form>

          <form method="post" style="display:inline;margin-left:6px">
              <?php wp_nonce_field('wsi_users_nonce'); ?>
              <input type="hidden" name="user_id" value="<?php echo intval($u->ID); ?>">
              <input name="amount" type="number" step="0.01" placeholder="Amt">
              <button name="action_user" value="credit" class="button">Credit</button>
          </form>

          <!-- NEW DEBIT FORM -->
          <form method="post" style="display:inline;margin-left:6px">
              <?php wp_nonce_field('wsi_users_nonce'); ?>
              <input type="hidden" name="user_id" value="<?php echo intval($u->ID); ?>">
              <input name="amount" type="number" step="0.01" placeholder="Amt">
              <button name="action_user" value="debit" class="button">Debit</button>
          </form>

        </td>
      </tr>
    <?php } ?>
    </tbody></table></div>
    <?php
}


/* -------------------------------------------------------------------------
   ADMIN: Deposits page (pending)
------------------------------------------------------------------------- */
function wsi_admin_deposits() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $t = $wpdb->prefix . 'wsi_deposits';

    // Handle Approve / Decline
    if (!empty($_POST['action_deposit']) && check_admin_referer('wsi_deposits_nonce')) {

        $id = intval($_POST['deposit_id']);
        $action = sanitize_text_field($_POST['action_deposit']);
        $dep = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d", $id));

        if ($dep) {

            if ($action === 'approve') {

                $wpdb->update(
                    $t,
                    ['status' => 'approved'],
                    ['id' => $id]
                );

                wsi_inc_main($dep->user_id, floatval($dep->amount));
                wsi_log_tx($dep->user_id, $dep->amount, 'deposit_approved', "Deposit #{$id} approved");
                wsi_apply_referral($dep->user_id, floatval($dep->amount), $id);
                wsi_notify_user($dep->user_id, 'Deposit Approved', "Your deposit of $" . number_format($dep->amount, 2) . " was approved.");
                wsi_audit(get_current_user_id(), 'approve_deposit', "Approved $id");

            } elseif ($action === 'decline') {

                $wpdb->update(
                    $t,
                    ['status' => 'declined'],
                    ['id' => $id]
                );

                wsi_notify_user($dep->user_id, 'Deposit Declined', "Your deposit of $" . number_format($dep->amount, 2) . " was declined.");
                wsi_audit(get_current_user_id(), 'decline_deposit', "Declined $id");
            }

            echo '<div class="notice notice-success"><p>Action executed.</p></div>';
        }
    }

    // Get pending deposits
    $rows = $wpdb->get_results("
        SELECT * FROM $t 
        WHERE TRIM(LOWER(status))='pending'
        ORDER BY created_at DESC
    ");
    ?>

    <div class="wrap"><h1>Pending Deposits</h1>

    <?php if (empty($rows)) { echo '<p>No pending deposits.</p>'; } else { ?>

      <table class="widefat striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Email</th> <!-- ⭐ ADDED -->
            <th>Amount</th>
            <th>Method</th>
            <th>When</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
        <?php foreach ($rows as $r) { 
            $u = get_userdata($r->user_id);
        ?>
          <tr>
            <td><?php echo intval($r->id); ?></td>
            <td><?php echo esc_html($u ? $u->user_login : 'User ' . $r->user_id); ?></td>
            <td><?php echo esc_html($u ? $u->user_email : 'N/A'); ?></td> <!-- ⭐ ADDED -->
            <td>$<?php echo number_format($r->amount, 2); ?></td>
            <td><?php echo esc_html($r->method); ?></td>
            <td><?php echo esc_html($r->created_at); ?></td>

            <td>
              <form method="post" style="display:inline">
                <?php wp_nonce_field('wsi_deposits_nonce'); ?>
                <input type="hidden" name="deposit_id" value="<?php echo intval($r->id); ?>">
                <button name="action_deposit" value="approve" class="button button-primary">Approve</button>
              </form>

              <form method="post" style="display:inline">
                <?php wp_nonce_field('wsi_deposits_nonce'); ?>
                <input type="hidden" name="deposit_id" value="<?php echo intval($r->id); ?>">
                <button name="action_deposit" value="decline" class="button">Decline</button>
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
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $t = $wpdb->prefix . 'wsi_withdrawals';

    if (!empty($_POST['action_withdraw']) && check_admin_referer('wsi_withdraws_nonce')) {
        $id = intval($_POST['withdraw_id']);
        $action = sanitize_text_field($_POST['action_withdraw']);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d", $id));
        if ($row) {
            if ($action === 'approve') {
                $wpdb->update($t, [
                    'status' => 'approved',
                    'admin_note' => 'Paid by ' . get_current_user_id()
                ], ['id' => $id]);

                wsi_log_tx($row->user_id, $row->amount, 'withdraw_approved', "Withdrawal #{$id} approved");
                wsi_notify_user($row->user_id, 'Withdrawal Approved', "Your withdrawal of $" . number_format($row->amount, 2) . " has been marked paid.");
                wsi_audit(get_current_user_id(), 'approve_withdraw', "Approved {$id}");

            } elseif ($action === 'decline') {

                $wpdb->update($t, [
                    'status' => 'declined',
                    'admin_note' => 'Declined by ' . get_current_user_id()
                ], ['id' => $id]);

                // refund to holdings/profit instead of main balance
                wsi_inc_profit($row->user_id, floatval($row->amount));

                wsi_log_tx($row->user_id, $row->amount, 'withdraw_refund', 'Withdraw declined, refunded');
                wsi_notify_user($row->user_id, 'Withdrawal Declined', 'Your withdrawal was declined and refunded.');
                wsi_audit(get_current_user_id(), 'decline_withdraw', "Declined {$id}");
            }

            echo '<div class="notice notice-success"><p>Action executed.</p></div>';
        }
    }

    // Load pending withdrawals
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $t WHERE TRIM(LOWER(status))=%s ORDER BY created_at DESC",
        'pending'
    ));
    ?>
    <div class="wrap"><h1>Pending Withdrawals</h1>
    <?php if (empty($rows)) { echo '<p>No pending withdrawals.</p>'; } else { ?>
      <table class="widefat striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Email</th> <!-- added -->
            <th>Amount</th>
            <th>Network</th>
            <th>Wallet</th>
            <th>When</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
      <?php foreach ($rows as $r) { $u = get_userdata($r->user_id); ?>
        <tr>
          <td><?php echo intval($r->id); ?></td>

          <td><?php echo esc_html($u ? $u->user_login : 'User ' . $r->user_id); ?></td>

          <td><?php echo esc_html($u ? $u->user_email : ''); ?></td> <!-- added -->

          <td>$<?php echo number_format($r->amount, 2); ?></td>

          <!-- NETWORK (method) -->
          <td><?php echo esc_html($r->method); ?></td>

          <!-- WALLET ADDRESS -->
          <td><pre><?php echo esc_html($r->account_details); ?></pre></td>

          <td><?php echo esc_html($r->created_at); ?></td>

          <td>
            <form method="post" style="display:inline">
                <?php wp_nonce_field('wsi_withdraws_nonce'); ?>
                <input type="hidden" name="withdraw_id" value="<?php echo intval($r->id); ?>">
                <button name="action_withdraw" value="approve" class="button button-primary">Approve</button>
            </form>

            <form method="post" style="display:inline">
                <?php wp_nonce_field('wsi_withdraws_nonce'); ?>
                <input type="hidden" name="withdraw_id" value="<?php echo intval($r->id); ?>">
                <button name="action_withdraw" value="decline" class="button">Decline & Refund</button>
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
   ADMIN: Stocks page (complete)
------------------------------------------------------------------------- */
function wsi_admin_stocks() {
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
function wsi_admin_settings() {
    if (!current_user_can('manage_options')) return;
    if (!empty($_POST['wsi_save_settings']) && check_admin_referer('wsi_settings_nonce')) {
        $daily = floatval($_POST['main_daily_percent']);
        $min = floatval($_POST['min_invest']);
        $mode = sanitize_text_field($_POST['deposit_mode']);
        $manual = sanitize_textarea_field($_POST['manual_payment_info']);
        $email = !empty($_POST['email_notifications']) ? 1 : 0;
        $exchange_rate = floatval($_POST['exchange_rate'] ?? ($opts['exchange_rate'] ?? 1000));

        wsi_update_opt('main_daily_percent', $daily);
        wsi_update_opt('min_invest', $min);
        wsi_update_opt('deposit_mode', $mode);
        wsi_update_opt('manual_payment_info', $manual);
        wsi_update_opt('email_notifications', $email);
        wsi_update_opt('exchange_rate', $exchange_rate);
        
        $naira_info = sanitize_textarea_field($_POST['naira_payment_info'] ?? '');
        $btc_wallet = sanitize_text_field($_POST['btc_wallet'] ?? '');
        $btc_instruction = sanitize_textarea_field($_POST['btc_instruction'] ?? '');
        $usdt_wallet = sanitize_text_field($_POST['usdt_wallet'] ?? '');
        $usdt_instruction = sanitize_textarea_field($_POST['usdt_instruction'] ?? '');
        $eth_wallet = sanitize_text_field($_POST['eth_wallet'] ?? '');
        $eth_instruction = sanitize_textarea_field($_POST['eth_instruction'] ?? '');

        wsi_update_opt('naira_payment_info', $naira_info);
        wsi_update_opt('btc_wallet', $btc_wallet);
        wsi_update_opt('btc_instruction', $btc_instruction);
        wsi_update_opt('usdt_wallet', $usdt_wallet);
        wsi_update_opt('usdt_instruction', $usdt_instruction);
        wsi_update_opt('eth_wallet', $eth_wallet);
        wsi_update_opt('eth_instruction', $eth_instruction);
echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
    $opts = wsi_get_opts();
    ?>
    <div class="wrap"><h1>WSI Settings</h1>
    <form method="post"><?php wp_nonce_field('wsi_settings_nonce'); ?>
      <table class="form-table">
        <tr><th>Main balance daily interest (%)</th><td><input name="main_daily_percent" type="number" step="0.001" value="<?php echo esc_attr($opts['main_daily_percent'] ?? 2.29); ?>"></td></tr>
        <tr><th>Minimum investment $</th><td><input name="min_invest" type="number" step="0.01" value="<?php echo esc_attr($opts['min_invest'] ?? 50); ?>"></td></tr>
        <tr><th>Exchange rate ($ per $1)</th><td><input name="exchange_rate" type="number" step="0.01" value="<?php echo esc_attr($opts['exchange_rate'] ?? 1000); ?>"> <small>How many Naira equals $1</small></td></tr>
        <tr><th>Deposit mode</th><td><select name="deposit_mode"><option value="manual" <?php selected(($opts['deposit_mode'] ?? 'manual'), 'manual'); ?>>Naira Payment</option><option value="auto" <?php selected(($opts['deposit_mode'] ?? 'manual'), 'auto'); ?>>Crypto Payment</option></select></td></tr>
        <tr><th>Manual payment info</th><td><textarea name="manual_payment_info" rows="4"><?php echo esc_textarea($opts['manual_payment_info'] ?? ''); ?></textarea></td></tr>
        <tr><th>Naira payment instructions</th><td><textarea name="naira_payment_info" rows="4"><?php echo esc_textarea($opts['naira_payment_info'] ?? ''); ?></textarea></td></tr>
        <tr><th>BTC Wallet Address</th><td><input name="btc_wallet" value="<?php echo esc_attr($opts['btc_wallet'] ?? ''); ?>" style="width:100%"></td></tr>
        <tr><th>BTC Payment Instruction</th><td><textarea name="btc_instruction" rows="3"><?php echo esc_textarea($opts['btc_instruction'] ?? ''); ?></textarea></td></tr>
        <tr><th>USDT Wallet Address</th><td><input name="usdt_wallet" value="<?php echo esc_attr($opts['usdt_wallet'] ?? ''); ?>" style="width:100%"></td></tr>
        <tr><th>USDT Payment Instruction</th><td><textarea name="usdt_instruction" rows="3"><?php echo esc_textarea($opts['usdt_instruction'] ?? ''); ?></textarea></td></tr>
        <tr><th>ETH Wallet Address</th><td><input name="eth_wallet" value="<?php echo esc_attr($opts['eth_wallet'] ?? ''); ?>" style="width:100%"></td></tr>
        <tr><th>ETH Payment Instruction</th><td><textarea name="eth_instruction" rows="3"><?php echo esc_textarea($opts['eth_instruction'] ?? ''); ?></textarea></td></tr>

        <tr><th>Email notifications</th><td><label><input type="checkbox" name="email_notifications" value="1" <?php checked($opts['email_notifications'] ?? 1, 1); ?>> Enable</label></td></tr>
      </table>
      <button class="button button-primary" name="wsi_save_settings" value="1">Save Settings</button>
    </form></div>
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
                      if (result === 'ok') {
                          status.textContent = enabled === 'yes' ? 'Enabled' : 'Disabled';
                      } else {
                          alert('Unable to update Smart Farming setting.');
                      }
                  })
                  .catch(() => alert('Network error updating Smart Farming.'));
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

          
<!-- WSI Deposit Success Modal (injected) -->
<div id="wsi_deposit_modal" style="display:none;position:fixed;z-index:99999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
  <div role="dialog" aria-modal="true" aria-labelledby="wsi_modal_title" style="background:#fff;max-width:520px;width:90%;margin:auto;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,0.2);padding:20px;position:relative;">
    <h2 id="wsi_modal_title" style="margin-top:0;font-size:20px;">Deposit Pending Approval</h2>
    <div id="wsi_modal_body" style="margin-top:10px;font-size:14px;line-height:1.5"></div>
    <div style="text-align:right;margin-top:18px;"><button id="wsi_modal_close" style="background:#0073aa;border:0;color:#fff;padding:8px 14px;border-radius:6px;cursor:pointer;">Close</button></div>
  </div>
</div>

<script type="text/javascript">
(function(){

  function initWsiDepositSuccessModal(){

    function showModal(html){
      var modal = document.getElementById('wsi_deposit_modal');
      var body = document.getElementById('wsi_modal_body');
      if(!modal || !body) return;

      body.innerHTML = html;
      modal.style.display = 'flex';
      modal.style.alignItems = 'center';
      modal.style.justifyContent = 'center';

      var closeBtn = document.getElementById('wsi_modal_close');
      if(closeBtn) closeBtn.focus();
    }

    function closeModal(){
      var m = document.getElementById('wsi_deposit_modal');
      if(m) m.style.display='none';
    }

    document.addEventListener('click', function(e){
      if(e.target && e.target.id === 'wsi_modal_close') closeModal();
    });

    try {
      var params = new URLSearchParams(window.location.search);
      if (params.get('wsi_deposit') === 'success') {
        var id = params.get('wsi_deposit_id') || '';
        var amt = params.get('wsi_deposit_amt') || '';
        var amt_local = params.get('wsi_deposit_amt_naira') || '';
        var method = params.get('wsi_deposit_method') || '';

        var html = '<p>Your deposit has been submitted and is pending approval.</p>';
        html += '<p><strong>Deposit ID:</strong> ' + decodeURIComponent(id) + '</p>';
        html += '<p><strong>Amount (USD):</strong> $' + decodeURIComponent(amt) + '</p>';
        html += '<p><strong>Amount (Local):</strong> ' + decodeURIComponent(amt_local) + '</p>';
        html += '<p><strong>Payment Type:</strong> ' + decodeURIComponent(method) + '</p>';

        showModal(html);

        if (history.replaceState) {
          var clean = window.location.href.split('?')[0];
          history.replaceState({}, document.title, clean);
        }
      }
    } catch (err) {}
  }

  document.addEventListener("DOMContentLoaded", initWsiDepositSuccessModal);

  jQuery(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction("frontend/element_ready/global", initWsiDepositSuccessModal);
  });

})();
</script>


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
            <?php if(!empty($opts['btc_wallet'])): ?><option value="btc">BTC</option><?php endif; ?>
            <?php if(!empty($opts['usdt_wallet'])): ?><option value="usdt">USDT</option><?php endif; ?>
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
                var opts = <?php echo json_encode(array(
                    'btc' => $opts['btc_wallet'] ?? '',
                    'btc_ins' => $opts['btc_instruction'] ?? '',
                    'usdt' => $opts['usdt_wallet'] ?? '',
                    'usdt_ins' => $opts['usdt_instruction'] ?? '',
                    'eth' => $opts['eth_wallet'] ?? '',
                    'eth_ins' => $opts['eth_instruction'] ?? ''
                )); ?>;
                var addr = opts[code] || '';
                var ins = opts[code + '_ins'] || '';
                walletAddress.innerText = addr ? (code.toUpperCase() + ' Address: ' + addr) : '';
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
                var opts = <?php echo json_encode(array(
                    'btc' => $opts['btc_wallet'] ?? '',
                    'btc_ins' => $opts['btc_instruction'] ?? '',
                    'usdt' => $opts['usdt_wallet'] ?? '',
                    'usdt_ins' => $opts['usdt_instruction'] ?? '',
                    'eth' => $opts['eth_wallet'] ?? '',
                    'eth_ins' => $opts['eth_instruction'] ?? ''
                )); ?>;

                var addr = opts[code] || '';
                var ins = opts[code + '_ins'] || '';

                walletAddress.innerText = addr ? (code.toUpperCase() + ' Address: ' + addr) : '';
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

        // Replace your existing withdraw handler with this function in the plugin (stock-vest.php)
        add_action('admin_post_wsi_submit_withdraw', 'wsi_submit_withdraw');
        add_action('admin_post_nopriv_wsi_submit_withdraw', 'wsi_submit_withdraw');

        function wsi_submit_withdraw() {
            if (!is_user_logged_in()) {
                wp_send_json_error(['message' => 'Unauthorized'], 403);
                wp_die();
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wsi_withdraw_nonce')) {
                wp_send_json_error(['message' => 'Security verification failed.'], 403);
                wp_die();
            }

            global $wpdb;
            $uid   = get_current_user_id();
            $t_dep = $wpdb->prefix . 'wsi_deposits';
            $t_wd  = $wpdb->prefix . 'wsi_withdrawals';

            $is_ajax = (!empty($_POST['is_ajax']) && $_POST['is_ajax'] == '1');
            $amount = floatval($_POST['amount'] ?? 0);
            $wallet = sanitize_text_field($_POST['account_details'] ?? '');
            $crypto = sanitize_text_field($_POST['crypto_type'] ?? '');
            $redirect_to = site_url('/wsi/withdrawal/');

            if ($amount <= 0) {
                if ($is_ajax) wp_send_json_error(['message' => 'Invalid withdrawal amount.']);
                wp_redirect(add_query_arg('withdraw_error', urlencode('Invalid amount'), $redirect_to));
                exit;
            }

            if (empty($wallet) || empty($crypto)) {
                if ($is_ajax) wp_send_json_error(['message' => 'Wallet and crypto type are required.']);
                wp_redirect(add_query_arg('withdraw_error', urlencode('Missing wallet or crypto type'), $redirect_to));
                exit;
            }

            // Fetch balances / compute unlocked assets (your existing logic)...
            $profit_income = floatval(wsi_get_profit($uid));
            // Determine unlocked deposits older than your threshold
            $deposits = $wpdb->get_results(
                $wpdb->prepare("SELECT amount, created_at FROM $t_dep WHERE user_id=%d AND status='approved'", $uid)
            );

            $now = current_time('timestamp');
            $unlock_seconds = 60 * 24 * 60 * 60; // 60 days
            $unlocked_assets = 0;

            foreach ($deposits as $d) {
                if (($now - strtotime($d->created_at)) >= $unlock_seconds) {
                    $unlocked_assets += floatval($d->amount);
                }
            }

            $available_balance = $profit_income + $unlocked_assets;

            if ($amount > $available_balance) {
                if ($is_ajax) wp_send_json_error(['message' => 'You can only withdraw up to your available balance.']);
                wp_redirect(add_query_arg('withdraw_error', urlencode('Insufficient balance'), $redirect_to));
                exit;
            }

            // Insert withdrawal request
            $inserted = $wpdb->insert(
                $t_wd,
                [
                    'user_id'        => $uid,
                    'amount'         => $amount,
                    'crypto_type'    => $crypto,
                    'wallet_address' => $wallet,
                    'status'         => 'pending',
                    'created_at'     => current_time('mysql')
                ],
                ['%d','%f','%s','%s','%s','%s']
            );

            if (!$inserted) {
                if ($is_ajax) wp_send_json_error(['message' => 'Unable to create withdrawal. Try later.']);
                wp_redirect(add_query_arg('withdraw_error', urlencode('DB error'), $redirect_to));
                exit;
            }

            // Deduct from user balances
            $remaining = $amount;
            if ($profit_income > 0) {
                $deduct_profit = min($profit_income, $remaining);
                wsi_inc_profit($uid, -$deduct_profit);
                $remaining -= $deduct_profit;
            }
            if ($remaining > 0) {
                wsi_inc_main($uid, -$remaining);
            }

            $withdraw_id = (int) $wpdb->insert_id;

            $payload = [
                'withdraw_id' => $withdraw_id,
                'amount' => number_format($amount,2,'.',''),
                'payment_type' => $crypto,
                'redirect_to' => $redirect_to,
                'message' => 'Your withdrawal has been received and is pending approval.'
            ];

            if ($is_ajax) {
                wp_send_json_success($payload);
                wp_die();
            } else {
                wp_redirect(add_query_arg('withdraw', 'submitted', $redirect_to));
                exit;
            }
        }
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

    wp_die('1');
}


/* -------------------------------------------------------------------------
   admin-post handlers for frontend forms
------------------------------------------------------------------------- */

/*--------------------------------------------------------------------------
    Deposit submit 
--------------------------------------------------------------------------*/
// ensure admin-post (non-AJAX) also handles the deposit action
// Replace your existing deposit handler with this function in the plugin (stock-vest.php)
add_action('admin_post_wsi_submit_deposit', 'wsi_submit_deposit');
add_action('admin_post_nopriv_wsi_submit_deposit', 'wsi_submit_deposit');

function wsi_submit_deposit() {
    // Security + login
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
        wp_die();
    }

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wsi_deposit_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.'], 403);
        wp_die();
    }

    global $wpdb;
    $uid = get_current_user_id();
    $t_deposits = $wpdb->prefix . 'wsi_deposits';

    // read inputs (choose whichever inputs your form sends)
    $is_ajax = (!empty($_POST['is_ajax']) && $_POST['is_ajax'] == '1');
    $amount_usd = floatval($_POST['amount'] ?? $_POST['amount_usd'] ?? 0);
    $payment_type = sanitize_text_field($_POST['payment_type'] ?? 'naira');
    $amount_local = floatval($_POST['amount_naira'] ?? 0); // if present
    $redirect_to = esc_url_raw($_POST['redirect_back'] ?? site_url('/wsi/withdrawal/'));

    if ($amount_usd <= 0) {
        if ($is_ajax) wp_send_json_error(['message' => 'Invalid amount.']);
        wp_redirect(add_query_arg('deposit_error', urlencode('Invalid amount'), $redirect_to));
        exit;
    }

    // insert deposit request (adjust columns to match your DB)
    $inserted = $wpdb->insert(
        $t_deposits,
        [
            'user_id'    => $uid,
            'amount'     => $amount_usd,
            'amount_local' => $amount_local ?: null,
            'payment_type' => $payment_type,
            'status'     => 'pending',
            'created_at' => current_time('mysql')
        ],
        ['%d','%f','%f','%s','%s','%s']
    );

    if (!$inserted) {
        if ($is_ajax) wp_send_json_error(['message' => 'Unable to create deposit. Try again later.']);
        wp_redirect(add_query_arg('deposit_error', urlencode('DB error'), $redirect_to));
        exit;
    }

    $deposit_id = (int) $wpdb->insert_id;

    // Prepare response payload
    $payload = [
        'deposit_id' => $deposit_id,
        'amount_usd' => number_format($amount_usd, 2, '.', ''),
        'amount_local' => $amount_local ? number_format($amount_local, 2, '.', '') : '',
        'payment_type' => $payment_type,
        'redirect_to' => $redirect_to,
        'message' => "Your deposit has been submitted and is pending approval."
    ];

    if ($is_ajax) {
        wp_send_json_success($payload);
        wp_die();
    } else {
        // Not AJAX: redirect back; add success param if you want
        wp_redirect(add_query_arg('deposit', 'success', $redirect_to));
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
    } else {
        update_user_meta($uid, 'wsi_smart_farming', 'no');
    }

    echo 'ok';
    wp_die();
}


/* Withdraw submit */
add_action('admin_post_wsi_submit_withdraw', 'wsi_handle_withdraw');
add_action('admin_post_nopriv_wsi_submit_withdraw', 'wsi_handle_withdraw');
function wsi_set_profit($uid, $amount) {
    update_user_meta($uid, 'wsi_profit_balance', floatval($amount));
}


function wsi_handle_withdraw() {
    if (!is_user_logged_in()) { wp_redirect(wp_login_url()); exit; }

    $redirect_url = site_url('/wsi/withdrawal/');

    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'wsi_withdraw_nonce')) { 
        wsi_popup("Withdrawal Error!", $dash_url); 
        exit; 
    }

    $uid    = get_current_user_id();
    $amount = round(floatval($_POST['amount'] ?? 0), 2);
    $acct   = sanitize_textarea_field($_POST['account_details'] ?? '');
    $method = sanitize_text_field($_POST['crypto_type'] ?? '');

    if ($amount <= 0) { 
        wsi_popup("Invalid Withdrawal Amount", $dash_url);
        exit; 
    }

    global $wpdb;

    /* ------------------------------------------------------
       1. Calculate UNLOCKED deposits (60-day rule)
    ------------------------------------------------------- */
    $t_dep = $wpdb->prefix . 'wsi_deposits';

    $deps = $wpdb->get_results($wpdb->prepare(
        "SELECT amount, created_at 
         FROM $t_dep 
         WHERE user_id=%d AND status='approved'",
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
        "SELECT SUM(accumulated_profit) 
         FROM $t_hold 
         WHERE user_id=%d AND status='open'",
        $uid
    )));

    $total_profit = $meta_profit + $accumulated_hold_profit;

    /* ------------------------------------------------------
       3. Total available = unlocked deposits + profit
    ------------------------------------------------------- */
    $available = $unlocked + $total_profit;

    if ($amount > $available) {
        wsi_popup("Insufficient Withdrawable Balance", $dash_url);
        exit;
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
            "SELECT id, accumulated_profit 
             FROM $t_hold
             WHERE user_id=%d AND status='open'
             ORDER BY created_at ASC",
            $uid
        ));

        foreach ($holdings as $h) {
            if ($remaining <= 0) break;

            $use = min(floatval($h->accumulated_profit), $remaining);

            $wpdb->query($wpdb->prepare(
                "UPDATE $t_hold 
                 SET accumulated_profit = accumulated_profit - %f 
                 WHERE id=%d",
                $use, $h->id
            ));

            $remaining -= $use;
        }
    }

    /* ------------------------------------------------------
       6. Deduct from UNLOCKED deposits (assets)
    ------------------------------------------------------- */
    if ($remaining > 0) {
        // Deduct from main balance (assets)
        $current_main = floatval(wsi_get_main($uid));
        wsi_set_main($uid, $current_main - $remaining);
        $remaining = 0;
    }

    /* ------------------------------------------------------
       7. Record withdrawal
    ------------------------------------------------------- */
    $t = $wpdb->prefix . 'wsi_withdrawals';

    $wpdb->insert($t, [
        'user_id'         => $uid,
        'amount'          => $amount,
        'method'          => $method,
        'account_details' => $acct,
        'status'          => 'pending',
        'created_at'      => current_time('mysql')
    ]);

    wsi_log_tx($uid, $amount, 'withdraw_request', 'Withdrawal requested');
    wsi_audit($uid, 'withdraw_request', "Requested $amount");
    wsi_notify_admin('Withdrawal Requested', "User #{$uid} requested withdrawal of $" . number_format($amount, 2));

    wsi_popup("Withdrawal Request Submitted", $redirect_url);
    exit;
}



/* Helper to redirect to the dashboard page */
function wsi_get_dashboard_page_url() {
    return home_url('/wsi/dashboard/');
}

/* Helper to redirect to the login page */
function wsi_get_login_page_url() {
    return home_url('/wsi/login/');
}


/* Popup Function Sitewide */
function wsi_popup($message, $redirect = '') {
    $msg = esc_js($message);

    // safer fallback handling
    $redirect_js = $redirect
        ? "window.location.href='" . esc_url_raw($redirect) . "';"
        : "window.history.back();";

    echo "
    <script>
        (function(){
            alert('{$msg}');
            {$redirect_js}
        })();
    </script>";
    exit;
}



/* Reinvest submit */
add_action('admin_post_wsi_submit_reinvest', 'wsi_handle_reinvest');
add_action('admin_post_nopriv_wsi_submit_reinvest', 'wsi_handle_reinvest');
function wsi_handle_reinvest() {
    if (!is_user_logged_in()) { wp_redirect(wp_login_url()); exit; }

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

    wsi_inc_profit($uid, -$amount);
    wsi_inc_main($uid, $amount);
    wsi_log_tx($uid, $amount, 'reinvest', 'Reinvest from profit');
    wsi_audit($uid, 'reinvest', "Reinvested {$amount}");

    wsi_popup("Reinvested Successfully", $dash_url);
    exit;
}


/* Buy stock submit */
add_action('admin_post_wsi_buy_stock', 'wsi_handle_buy_stock');
add_action('admin_post_nopriv_wsi_buy_stock', 'wsi_handle_buy_stock');
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
add_action('admin_post_nopriv_wsi_sell_holding', 'wsi_handle_sell_holding');
function wsi_handle_sell_holding() {
    if (!is_user_logged_in()) { wp_redirect(wp_login_url()); exit; }

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
    $flag = get_user_meta($user_id, 'wsi_first_confirmed_deposit', true);
    if ($flag) return;
    $inv1 = intval(get_user_meta($user_id, 'wsi_inviter_id', true));
    if ($inv1) {
        $bonus1 = round($amount * 0.10, 2); // 10%
        wsi_inc_main($inv1, $bonus1);
        wsi_log_tx($inv1, $bonus1, 'referral_first', "10% from user {$user_id} deposit #{$deposit_id}");
        wsi_notify_user($inv1, 'Referral Bonus', "You received $" . number_format($bonus1, 2) . " for a referral deposit.");
        wsi_audit($inv1, 'referral_first', "Awarded {$bonus1}");
        $inv2 = intval(get_user_meta($inv1, 'wsi_inviter_id', true));
        if ($inv2) {
            $bonus2 = round($amount * 0.05, 2); // 5%
            wsi_inc_main($inv2, $bonus2);
            wsi_log_tx($inv2, $bonus2, 'referral_second', "5% from user {$user_id} deposit #{$deposit_id}");
            wsi_notify_user($inv2, 'Referral Bonus (2nd level)', "You received $" . number_format($bonus2, 2) . " for a second-level referral deposit.");
            wsi_audit($inv2, 'referral_second', "Awarded {$bonus2}");
        }
    }
    update_user_meta($user_id, 'wsi_first_confirmed_deposit', 1);
}

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
    if (current_user_can('manage_options')) $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wsi_transactions ORDER BY created_at DESC LIMIT 500");
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



/* -------------------------------------------------------
   WSI LOGIN / LOGOUT / DASHBOARD REDIRECT SYSTEM
------------------------------------------------------- */

function wsi_dashboard_url() {
    return function_exists('wsi_get_dashboard_page_url')
        ? wsi_get_dashboard_page_url()
        : home_url('/wsi/dashboard/');
}

function wsi_login_url() {
    return function_exists('wsi_get_login_page_url')
        ? wsi_get_login_page_url()
        : home_url('/wsi/login/');
}

/* ---------------------------------------------------------
   Redirect logged-in users away from the login page to /wsi/dashboard
--------------------------------------------------------- */
add_action('template_redirect', function () {

    // Get the current page URI (remove leading/trailing slashes)
    $current = trim($_SERVER['REQUEST_URI'], '/');

    // Check if the current page is 'wsi/login' and user is logged in
    if ($current === '/wsi/login/' && is_user_logged_in()) {
        
        // Redirect to the custom dashboard page
        wp_safe_redirect(home_url('/wsi/dashboard/'));
        exit;
    }
});



/* -------------------------------------------------------
   2. Redirect user after logout
------------------------------------------------------- */
add_action('wp_logout', function () {
    wp_safe_redirect(wsi_login_url());
    exit;
});


/* -------------------------------------------------------
   3. Control access to login / dashboard pages
------------------------------------------------------- */
add_action('template_redirect', function () {

    $current = trim($_SERVER['REQUEST_URI'], '/');
    $is_login     = ($current === 'wsi/login');
    $is_dashboard = ($current === 'wsi/dashboard');

    /* ---- Logged-in user cannot see login page ---- */
    if ($is_login && is_user_logged_in()) {
        wp_safe_redirect(wsi_dashboard_url());
        exit;
    }

    /* ---- Logged-out user cannot access dashboard ---- */
    if ($is_dashboard && !is_user_logged_in()) {
        wp_safe_redirect(wsi_login_url());
        exit;
    }
});


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
            'redirect'        => wsi_dashboard_url(),
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



/* -------------------------------------------------------
   6. Stay on same page when login fails (front-end forms)
------------------------------------------------------- */



add_action('wp_footer', function() {
    if ($msg = get_transient('wsi_login_error')) {
        echo '<div class="wsi-alert wsi-error" style="color:red;margin:10px 0;">' . esc_html($msg) . '</div>';
        delete_transient('wsi_login_error');
    }
});


// Handle registration from front-end form
add_action('init', function() {
    if (isset($_POST['wsi_register_nonce']) && wp_verify_nonce($_POST['wsi_register_nonce'], 'wsi_register_action')) {
        $username = sanitize_user($_POST['username'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = sanitize_text_field($_POST['password'] ?? '');
        $ref = sanitize_text_field($_POST['ref'] ?? '');

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

        // Success: store referral and login
        update_user_meta($user_id, 'wsi_referred_by', $ref_user_id);
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
        $file = WP_PLUGIN_DIR . '/stock-vest/pages/' . $page . '.php';
        if (file_exists($file)) {
            require_once $file;
            exit;
        }
    }
});
