<?php
// Run: php tests/deposit-timers.php. No database required.
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', __DIR__);
define('DAY_IN_SECONDS', 86400);
function add_action(...$args) {}
function wp_timezone() { return new DateTimeZone('Africa/Lagos'); }
function wsi_get_deposit_unlock_days() { return $GLOBALS['unlock_days'] ?? 60; }
require dirname(__DIR__) . '/includes/deposits.php';
function check_timer($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
$deposit = (object) ['approved_at' => '2026-10-05 12:00:00', 'created_at' => '2026-10-01 10:00:00'];
$expected = (new DateTimeImmutable('2026-10-05 12:00:00', wp_timezone()))->getTimestamp();
check_timer(wsi_deposit_unlock_timestamp($deposit) === $expected + 60 * DAY_IN_SECONDS, 'Approval time must start the lock in site timezone');
$deposit->approved_at = null;
$created = (new DateTimeImmutable($deposit->created_at, wp_timezone()))->getTimestamp();
check_timer(wsi_deposit_unlock_timestamp($deposit) === $created + 60 * DAY_IN_SECONDS, 'Legacy deposits must fall back to creation time');
$GLOBALS['unlock_days'] = 0;
check_timer(wsi_deposit_unlock_timestamp($deposit) === $created, 'Zero-day locks must unlock immediately');
$deposit->created_at = '2026-02-30 10:00:00';
check_timer(wsi_deposit_unlock_timestamp($deposit) === null, 'Invalid dates must remain locked');
$deposit->created_at = '';
check_timer(wsi_deposit_unlock_timestamp($deposit) === null, 'Missing dates must remain locked');
echo "PASS: approval time, timezone, legacy dates, zero-day locks, and invalid dates.\n";
