<?php
if (!defined('ABSPATH')) exit;

/** Reinvest the selected amount, using available earnings before unlocked principal. */
function wsi_create_reinvestment($uid, $amount) {
    global $wpdb;
    if (!is_scalar($amount) || !preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D', (string) $amount) || (float) $amount <= 0) {
        return new WP_Error('wsi_amount', 'Enter a valid reinvestment amount with at most two decimal places.');
    }
    $amount_cents = (int) round((float) $amount * 100);
    $table = $wpdb->prefix . 'wsi_deposits';
    return wsi_withdrawal_transaction($uid, function () use ($wpdb, $uid, $amount_cents, $table) {
        if ($wpdb->query($wpdb->prepare("SELECT id FROM $table WHERE user_id=%d FOR UPDATE", $uid)) === false) {
            throw new RuntimeException('Unable to lock deposits');
        }
        $balances = wsi_get_withdrawal_balances($uid);
        if ($balances['balance_error']) throw new RuntimeException('Unable to read balances');
        $principal = (int) round($balances['total_assets_unlocked_amount'] * 100);
        $available = (int) round($balances['available_balance'] * 100);
        $total = $principal + $available;
        if ($available < 0 || $amount_cents > $total) {
            return new WP_Error('wsi_balance_changed', 'The amount exceeds your balance available to reinvest. Refresh the page and try a smaller amount.');
        }

        // Debit earnings in cents, including profits held in open holdings.
        $earnings_used = min($available, $amount_cents);
        $remaining = $earnings_used;
        $meta = (int) round((float) get_user_meta($uid, 'wsi_profit_balance', true) * 100);
        $use = min(max(0, $meta), $remaining);
        if ($use > 0 && update_user_meta($uid, 'wsi_profit_balance', ($meta - $use) / 100) === false) {
            throw new RuntimeException('Unable to deduct available balance');
        }
        $remaining -= $use;
        $holdings = $wpdb->get_results($wpdb->prepare(
            "SELECT id, accumulated_profit FROM {$wpdb->prefix}wsi_holdings WHERE user_id=%d AND status='open' ORDER BY created_at ASC, id ASC", $uid
        ));
        if ($wpdb->last_error || !is_array($holdings)) throw new RuntimeException('Unable to read holding profits');
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

        // Principal is already in Total Assets; only earnings increase its value.
        if ($earnings_used > 0 && update_user_meta($uid, 'wsi_main_balance', round($balances['total_assets'] + $earnings_used / 100, 2)) === false) {
            throw new RuntimeException('Unable to credit total assets');
        }
        $approved_at = current_time('mysql');
        if ($wpdb->insert($table, [
            'user_id' => $uid, 'amount' => $amount_cents / 100, 'amount_local' => 0,
            'payment_type' => 'reinvest', 'crypto_wallet' => '', 'status' => 'approved',
            'approved_at' => $approved_at, 'created_at' => $approved_at,
        ]) === false) {
            throw new RuntimeException('Unable to record reinvestment');
        }
        return ['deposit_id' => (int) $wpdb->insert_id, 'amount' => $amount_cents / 100];
    }, [$table], 'reinvestment');
}
