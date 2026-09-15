<?php
if (!defined('ABSPATH')) exit;

/** Return only a deposit owned by the authenticated user, or their latest pending request. */
function wsi_get_deposit_status($uid, $deposit_id = 0) {
    global $wpdb;
    $table = $wpdb->prefix . 'wsi_deposits';
    $where = $deposit_id > 0 ? $wpdb->prepare('id=%d', $deposit_id) : "status='pending'";
    $deposit = $wpdb->get_row($wpdb->prepare(
        "SELECT id, amount, status, created_at FROM $table WHERE user_id=%d AND $where ORDER BY id DESC LIMIT 1", $uid
    ));
    if ($wpdb->last_error) return new WP_Error('wsi_deposit_unavailable', 'Unable to check your deposit right now.', ['status' => 503]);
    if (!$deposit) return new WP_Error('wsi_deposit_missing', 'Deposit not found for your account.', ['status' => 404]);
    $status = strtolower(trim($deposit->status));
    if (!in_array($status, ['pending', 'approved', 'declined'], true)) {
        return new WP_Error('wsi_deposit_status', 'Your deposit status needs to be checked. Please contact support.', ['status' => 409]);
    }
    $created = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $deposit->created_at, wp_timezone());
    return [
        'deposit_id' => (int) $deposit->id,
        'amount_usd' => (float) $deposit->amount,
        'status' => $status,
        'elapsed_seconds' => $created ? max(0, time() - $created->getTimestamp()) : 0,
        'estimated_seconds' => 30 * MINUTE_IN_SECONDS,
    ];
}

function wsi_check_deposit_status() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Your session has expired. Sign in again to check your deposit.'], 401);
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'wsi_deposit_status_nonce')) {
        wp_send_json_error(['message' => 'Please refresh this page to continue checking your deposit.'], 403);
    }
    $raw_id = $_POST['deposit_id'] ?? '';
    if (!is_scalar($raw_id) || !ctype_digit((string) $raw_id) || (int) $raw_id <= 0) {
        wp_send_json_error(['message' => 'Invalid deposit reference.'], 400);
    }
    $result = wsi_get_deposit_status(get_current_user_id(), (int) $raw_id);
    nocache_headers();
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()], $result->get_error_data()['status'] ?? 500);
    }
    wp_send_json_success($result);
}
add_action('wp_ajax_wsi_check_deposit_status', 'wsi_check_deposit_status');
add_action('wp_ajax_nopriv_wsi_check_deposit_status', 'wsi_check_deposit_status');
