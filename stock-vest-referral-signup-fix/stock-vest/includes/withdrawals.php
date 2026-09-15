<?php
if (!defined('ABSPATH')) exit;

/** Serialize withdrawal debits/refunds and roll back every balance change on failure. */
function wsi_withdrawal_transaction($uid, $callback) {
    global $wpdb;
    $tables = [$wpdb->users, $wpdb->usermeta, $wpdb->prefix . 'wsi_holdings', $wpdb->prefix . 'wsi_withdrawals'];
    foreach ($tables as $table) {
        $engine = $wpdb->get_var($wpdb->prepare(
            'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table
        ));
        if (strtoupper((string) $engine) !== 'INNODB') {
            return new WP_Error('wsi_storage', 'Withdrawal storage is unavailable. Please contact support.');
        }
    }
    if ($wpdb->query('START TRANSACTION') === false) {
        return new WP_Error('wsi_storage', 'Unable to start withdrawal. Please try again.');
    }
    try {
        // The user row also serializes requests when a balance meta row does not exist yet.
        $user = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE ID=%d FOR UPDATE", $uid));
        if (!$user) throw new RuntimeException('Unable to lock user');
        if ($wpdb->query($wpdb->prepare("SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id=%d FOR UPDATE", $uid)) === false ||
            $wpdb->query($wpdb->prepare("SELECT id FROM {$wpdb->prefix}wsi_holdings WHERE user_id=%d FOR UPDATE", $uid)) === false) {
            throw new RuntimeException('Unable to lock balances');
        }
        wp_cache_delete($uid, 'user_meta');
        $result = $callback();
        if (is_wp_error($result)) {
            $wpdb->query('ROLLBACK');
        } elseif ($wpdb->query('COMMIT') === false) {
            throw new RuntimeException('Unable to commit withdrawal');
        }
        return $result;
    } catch (Throwable $error) {
        $wpdb->query('ROLLBACK');
        error_log('WSI withdrawal: ' . $error->getMessage());
        return new WP_Error('wsi_storage', 'Unable to save withdrawal. Your balance has not been changed. Please try again.');
    } finally {
        wp_cache_delete($uid, 'user_meta');
    }
}

function wsi_create_withdrawal_request($uid, $amount, $source, $method, $account, $bank_name = '') {
    global $wpdb;
    // Reject malformed amounts and sources instead of silently charging a different account.
    if (!is_scalar($amount) || !preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D', (string) $amount) || (float) $amount <= 0) {
        return new WP_Error('wsi_amount', 'Enter a valid withdrawal amount with at most two decimal places.');
    }
    if (!in_array($source, ['available_balance', 'total_assets'], true)) {
        return new WP_Error('wsi_source', 'Select Available Balance or Total Assets.');
    }
    if ($method === 'bank') {
        if (trim($bank_name) === '' || trim($account) === '' || strlen($bank_name) > 150 || strlen($account) > 64) {
            return new WP_Error('wsi_bank_details', 'Enter a valid bank name and account number.');
        }
        $account = 'Bank Name: ' . $bank_name . "\nAccount Number: " . $account;
    }
    if (trim($method) === '' || trim($account) === '') {
        return new WP_Error('wsi_destination', 'Select a payout network and enter your wallet address.');
    }
    $amount = round((float) $amount, 2);
    return wsi_withdrawal_transaction($uid, function () use ($wpdb, $uid, $amount, $source, $method, $account) {
        $balances = wsi_get_withdrawal_balances($uid);
        if ($balances['balance_error']) throw new RuntimeException('Unable to read balances');
        if ($source === 'total_assets' && $balances['total_assets_locked']) {
            $message = $balances['total_assets_unlock_at']
                ? 'Total Assets are locked until ' . wp_date(get_option('date_format') . ' H:i T', strtotime($balances['total_assets_unlock_at']))
                : 'Total Assets are locked. Please contact support to verify the investment dates.';
            return new WP_Error('wsi_locked', $message);
        }
        $withdrawable = $source === 'total_assets' ? $balances['total_assets_unlocked_amount'] : $balances['available_balance'];
        if ($amount > $withdrawable) {
            return new WP_Error('wsi_insufficient', 'Insufficient balance in the selected account. Available: $' . number_format($withdrawable, 2));
        }
        if ($source === 'total_assets') {
            if (update_user_meta($uid, 'wsi_main_balance', round($balances['total_assets'] - $amount, 2)) === false) {
                throw new RuntimeException('Unable to deduct total assets');
            }
        } else {
            $remaining = (int) round($amount * 100);
            $meta = (int) round((float) get_user_meta($uid, 'wsi_profit_balance', true) * 100);
            $use = min(max(0, $meta), $remaining);
            if ($use > 0 && update_user_meta($uid, 'wsi_profit_balance', ($meta - $use) / 100) === false) {
                throw new RuntimeException('Unable to deduct available balance');
            }
            $remaining -= $use;
            $holdings = $wpdb->get_results($wpdb->prepare(
                "SELECT id, accumulated_profit FROM {$wpdb->prefix}wsi_holdings WHERE user_id=%d AND status='open' ORDER BY created_at ASC, id ASC", $uid
            ));
            if ($wpdb->last_error) throw new RuntimeException('Unable to read holding profits');
            foreach ($holdings as $holding) {
                if ($remaining <= 0) break;
                $profit = (int) round((float) $holding->accumulated_profit * 100);
                $use = min(max(0, $profit), $remaining);
                if ($use > 0 && $wpdb->update($wpdb->prefix . 'wsi_holdings',
                    ['accumulated_profit' => ($profit - $use) / 100], ['id' => $holding->id]) !== 1) {
                    throw new RuntimeException('Unable to deduct holding profit');
                }
                $remaining -= $use;
            }
            if ($remaining !== 0) throw new RuntimeException('Available balance changed');
        }
        if ($wpdb->insert($wpdb->prefix . 'wsi_withdrawals', [
            'user_id' => $uid, 'amount' => $amount, 'source' => $source, 'method' => $method,
            'account_details' => $account, 'status' => 'pending', 'created_at' => current_time('mysql'),
        ]) === false) {
            throw new RuntimeException('Unable to record withdrawal');
        }
        return ['withdrawal_id' => (int) $wpdb->insert_id, 'amount' => $amount, 'source' => $source];
    });
}

/** Both admin entry points use the same one-time status transition and source-aware refund. */
function wsi_process_withdrawal_request($id, $action) {
    global $wpdb;
    if (!in_array($action, ['approve', 'decline'], true)) {
        return new WP_Error('wsi_action', 'Invalid withdrawal action.');
    }
    $table = $wpdb->prefix . 'wsi_withdrawals';
    $uid = (int) $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $table WHERE id=%d", $id));
    if (!$uid) return new WP_Error('wsi_missing', 'Withdrawal request not found.');
    $result = wsi_withdrawal_transaction($uid, function () use ($wpdb, $uid, $table, $id, $action) {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d FOR UPDATE", $id));
        if (!$row || strtolower(trim($row->status)) !== 'pending') {
            return new WP_Error('wsi_processed', 'This withdrawal has already been processed.');
        }
        if (!in_array($row->source, ['available_balance', 'total_assets'], true)) {
            return new WP_Error('wsi_source', 'Withdrawal account is invalid. Please verify the request.');
        }
        $approved = $action === 'approve';
        if ($wpdb->update($table, [
            'status' => $approved ? 'approved' : 'declined',
            'admin_note' => ($approved ? 'Paid' : 'Declined') . ' by Admin ID: ' . get_current_user_id() . ' on ' . current_time('mysql'),
        ], ['id' => $id]) !== 1) throw new RuntimeException('Unable to update withdrawal');
        if (!$approved) {
            $key = $row->source === 'total_assets' ? 'wsi_main_balance' : 'wsi_profit_balance';
            $balance = (float) get_user_meta($uid, $key, true);
            if (update_user_meta($uid, $key, round($balance + (float) $row->amount, 2)) === false) {
                throw new RuntimeException('Unable to refund withdrawal');
            }
        }
        return $row;
    });
    if (is_wp_error($result)) return $result;
    $label = $result->source === 'total_assets' ? 'Total Assets' : 'Available Balance';
    if ($action === 'approve') {
        wsi_log_tx($uid, $result->amount, 'withdraw_approved', "Withdrawal #{$id} from {$label} approved");
        wsi_notify_user($uid, 'Withdrawal Approved', 'Your withdrawal of $' . number_format($result->amount, 2) . ' has been processed and sent.');
    } else {
        wsi_log_tx($uid, $result->amount, 'withdraw_refund', "Withdrawal #{$id} declined, amount refunded to {$label}");
        wsi_notify_user($uid, 'Withdrawal Declined', 'Your withdrawal was declined and refunded to your ' . $label . '.');
    }
    wsi_audit(get_current_user_id(), $action . '_withdraw', "Processed withdrawal #{$id} for user #{$uid} from {$label}");
    return $result;
}
