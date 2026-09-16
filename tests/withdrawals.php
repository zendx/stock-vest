<?php
/** Run: php tests/withdrawals.php (local MySQL; creates and removes an isolated test database). */
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', __DIR__ . '/');
define('DAY_IN_SECONDS', 86400);
define('MINUTE_IN_SECONDS', 60);
define('DOING_AJAX', true);

class WP_Error {
    public $code;
    private $message;
    private $data;
    function __construct($code, $message, $data = []) { $this->code = $code; $this->message = $message; $this->data = $data; }
    function get_error_message() { return $this->message; }
    function get_error_data() { return $this->data; }
}
function add_action(...$args) {}
function nocache_headers() {}
function is_user_logged_in() { return $GLOBALS['test_logged_in'] ?? true; }
function wp_verify_nonce($nonce, $action) { return $nonce === 'test-nonce' && in_array($action, ['wsi_deposit_status_nonce', 'wsi_withdraw_nonce'], true); }
class TestJsonResponse extends RuntimeException {
    public $data;
    public $status;
    public $success;
    function __construct($data, $status, $success) { $this->data = $data; $this->status = $status; $this->success = $success; }
}
function wp_send_json_error($data, $status = 200) { throw new TestJsonResponse($data, $status, false); }
function wp_send_json_success($data) { throw new TestJsonResponse($data, 200, true); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_cache_delete($id, $group) {}
function wp_timezone() { return new DateTimeZone('Africa/Lagos'); }
function wp_date($format, $timestamp) { return (new DateTimeImmutable('@' . $timestamp))->setTimezone(wp_timezone())->format($format); }
function current_time($type) { return wp_date('Y-m-d H:i:s', time()); }
function get_current_user_id() { return 1; }
function get_option($key, $default = false) { return $key === 'wsi_options' ? ['deposit_unlock_days' => $GLOBALS['unlock_days'] ?? 60] : ($key === 'date_format' ? 'Y-m-d' : $default); }
function wsi_log_tx(...$args) { $GLOBALS['logs'][] = $args; if (!empty($GLOBALS['test_notification_failure'])) { echo 'Unexpected mail output'; throw new RuntimeException('Mail unavailable'); } }
function site_url($path) { return 'https://example.test' . $path; }
function wsi_get_dashboard_page_url() { return site_url('/wsi/dashboard/'); }
function wsi_get_user_label($uid) { return 'Fixture User'; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return is_scalar($value) ? trim(strip_tags($value)) : ''; }
function sanitize_textarea_field($value) { return sanitize_text_field($value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)); }
function wsi_notify_admin(...$args) {}
function wsi_send_email_template(...$args) {}
function wsi_notify_user(...$args) {}
function wsi_audit(...$args) {}

class TestDB {
    public $prefix = 'wp_';
    public $users = 'wp_users';
    public $usermeta = 'wp_usermeta';
    public $last_error = '';
    public $insert_id = 0;
    public $fail = '';
    public $pdo;
    function __construct($pdo) { $this->pdo = $pdo; }
    function prepare($sql, ...$args) {
        $i = 0;
        return preg_replace_callback('/%[sdf]/', function ($m) use ($args, &$i) {
            $value = $args[$i++];
            return $m[0] === '%s' ? $this->pdo->quote((string) $value) : (string) (float) $value;
        }, $sql);
    }
    function run($sql, $args = []) {
        $this->last_error = '';
        if ($this->fail && strpos($sql, $this->fail) !== false) {
            $this->fail = '';
            $this->last_error = 'Injected database failure';
            return false;
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($args);
            return $stmt;
        } catch (PDOException $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }
    function query($sql) { $stmt = $this->run($sql); return $stmt ? $stmt->rowCount() : false; }
    function get_var($sql) { $stmt = $this->run($sql); return $stmt ? $stmt->fetchColumn() : null; }
    function get_row($sql) { $stmt = $this->run($sql); return $stmt ? $stmt->fetchObject() : null; }
    function get_results($sql) { $stmt = $this->run($sql); return $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : null; }
    function get_col($sql, $column = 0) { $stmt = $this->run($sql); return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, $column) : null; }
    function insert($table, $data) {
        $stmt = $this->run("INSERT INTO $table (" . implode(',', array_keys($data)) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')', array_values($data));
        $this->insert_id = $stmt ? (int) $this->pdo->lastInsertId() : 0;
        return $stmt ? $stmt->rowCount() : false;
    }
    function update($table, $data, $where) {
        $sets = implode(', ', array_map(function ($key) { return "$key=?"; }, array_keys($data)));
        $conditions = implode(' AND ', array_map(function ($key) { return "$key=?"; }, array_keys($where)));
        $stmt = $this->run("UPDATE $table SET $sets WHERE $conditions", array_merge(array_values($data), array_values($where)));
        return $stmt ? $stmt->rowCount() : false;
    }
}
function get_user_meta($uid, $key, $single) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare('SELECT meta_value FROM wp_usermeta WHERE user_id=%d AND meta_key=%s', $uid, $key));
}
function update_user_meta($uid, $key, $value) {
    global $wpdb;
    $id = $wpdb->get_var($wpdb->prepare('SELECT umeta_id FROM wp_usermeta WHERE user_id=%d AND meta_key=%s', $uid, $key));
    return $id ? $wpdb->update('wp_usermeta', ['meta_value' => $value], ['umeta_id' => $id]) : $wpdb->insert('wp_usermeta', ['user_id' => $uid, 'meta_key' => $key, 'meta_value' => $value]);
}

// Load the actual balance functions without bootstrapping WordPress or touching site data.
define('WSI_VER', '1.0.7');
$wanted = ['wsi_handle_withdraw', 'wsi_safe_migration', 'wsi_get_opts', 'wsi_get_deposit_unlock_days', 'wsi_get_main', 'wsi_get_profit', 'wsi_get_withdrawal_balances', 'wsi_inc_profit', 'wsi_set_profit', 'wsi_rest_balance_snapshot'];
$tokens = token_get_all(file_get_contents(dirname(__DIR__) . '/stock-vest.php'));
for ($i = 0; $i < count($tokens); $i++) {
    if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
    $name = $tokens[$i + 2];
    if (!is_array($name) || !in_array($name[1], $wanted, true)) continue;
    $code = '';
    $depth = 0;
    $opened = false;
    for (; $i < count($tokens); $i++) {
        $token = $tokens[$i];
        $text = is_array($token) ? $token[1] : $token;
        $code .= $text;
        if ($text === '{' || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) { $depth++; $opened = true; }
        if ($text === '}') $depth--;
        if ($opened && $depth === 0) break;
    }
    eval($code);
}
require dirname(__DIR__) . '/includes/withdrawals.php';
require dirname(__DIR__) . '/includes/reinvest.php';
require dirname(__DIR__) . '/includes/deposits.php';

$dsn = getenv('WSI_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;charset=utf8mb4';
$pdo = new PDO($dsn, getenv('WSI_TEST_USER') ?: 'root', getenv('WSI_TEST_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database = isset($argv[2]) && $argv[1] === '--worker' ? $argv[2] : 'wsi_withdrawal_test_' . bin2hex(random_bytes(6));
if (!preg_match('/^wsi_withdrawal_test_[a-f0-9]{12}$/D', $database)) throw new RuntimeException('Unsafe test database name');
$worker = ($argv[1] ?? '') === '--worker';
if (!$worker) $pdo->exec("CREATE DATABASE `$database`");
$pdo->exec("USE `$database`");
$wpdb = new TestDB($pdo);
if ($worker) {
    $result = wsi_create_withdrawal_request(1, '80.00', 'available_balance', 'USDT', 'test-wallet');
    echo is_wp_error($result) ? $result->code : 'success';
    exit;
}

$count = 0;
function check($condition, $message) {
    global $count;
    if (!$condition) throw new RuntimeException($message);
    echo 'PASS ' . ++$count . ': ' . $message . PHP_EOL;
}
function reset_fixture($main = 1000, $profit = 100, $holding = 50) {
    global $wpdb;
    foreach (['wp_usermeta', 'wp_wsi_holdings', 'wp_wsi_deposits', 'wp_wsi_withdrawals'] as $table) $wpdb->query("DELETE FROM $table");
    update_user_meta(1, 'wsi_main_balance', $main);
    update_user_meta(1, 'wsi_profit_balance', $profit);
    $wpdb->insert('wp_wsi_holdings', ['id' => 1, 'user_id' => 1, 'accumulated_profit' => $holding, 'status' => 'open', 'created_at' => '2025-01-01 00:00:00']);
    $GLOBALS['unlock_days'] = 60;
}
function deposit($timestamp, $status = 'approved', $approved = true) {
    global $wpdb;
    $date = wp_date('Y-m-d H:i:s', $timestamp);
    $wpdb->insert('wp_wsi_deposits', ['user_id' => 1, 'amount' => 1000, 'created_at' => $date, 'approved_at' => $approved ? $date : null, 'status' => $status]);
}
function request_money($amount, $source) { return wsi_create_withdrawal_request(1, $amount, $source, 'USDT', 'test-wallet'); }
function unchanged($main, $profit) { return wsi_get_main(1) === (float) $main && wsi_get_profit(1) === (float) $profit; }

try {
    $pdo->exec('CREATE TABLE wp_users (ID bigint PRIMARY KEY) ENGINE=InnoDB');
    $pdo->exec('INSERT INTO wp_users VALUES (1)');
    $pdo->exec('CREATE TABLE wp_usermeta (umeta_id bigint AUTO_INCREMENT PRIMARY KEY, user_id bigint, meta_key varchar(100), meta_value varchar(100), KEY(user_id)) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE wp_wsi_holdings (id bigint PRIMARY KEY, user_id bigint, accumulated_profit decimal(14,2), status varchar(32), created_at datetime, KEY(user_id)) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE wp_wsi_deposits (id bigint AUTO_INCREMENT PRIMARY KEY, user_id bigint, amount decimal(14,2), amount_local decimal(14,2), payment_type varchar(20), crypto_wallet varchar(255), created_at datetime, approved_at datetime NULL, status varchar(32)) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE wp_wsi_withdrawals (id bigint AUTO_INCREMENT PRIMARY KEY, user_id bigint, amount decimal(14,2), source varchar(32) NOT NULL DEFAULT 'available_balance', method varchar(80), account_details text, status varchar(32), admin_note text, created_at datetime) ENGINE=InnoDB");

    reset_fixture();
    deposit(time() - 61 * DAY_IN_SECONDS);
    $snapshot = wsi_rest_balance_snapshot(1);
    check($snapshot['totalAssets'] === 1000.0 && $snapshot['available'] === 150.0 && !$snapshot['totalAssetsLocked'], 'Mature assets never enter Available Balance; REST reports unlocked');
    $request = request_money('200.00', 'total_assets');
    check(!is_wp_error($request) && unchanged(800, 150), 'Total Assets withdrawal debits only principal');
    check(!is_wp_error(wsi_process_withdrawal_request($request['withdrawal_id'], 'decline')) && unchanged(1000, 150), 'Decline returns principal to Total Assets');
    check(is_wp_error(wsi_process_withdrawal_request($request['withdrawal_id'], 'decline')) && unchanged(1000, 150), 'Repeated decline cannot refund twice');
    check(is_wp_error(wsi_process_withdrawal_request($request['withdrawal_id'], 'approve')), 'Declined request cannot later be approved');

    reset_fixture();
    deposit(time() - DAY_IN_SECONDS);
    $snapshot = wsi_get_withdrawal_balances(1);
    check($snapshot['total_assets_locked'] && abs(strtotime($snapshot['total_assets_unlock_at']) - (time() + 59 * DAY_IN_SECONDS)) <= 1, 'Lock deadline is a correct UTC instant for site-local dates');
    check(is_wp_error(request_money('50.00', 'total_assets')) && unchanged(1000, 150), 'Locked Total Assets cannot be withdrawn through a forged request');
    $request = request_money('125.00', 'available_balance');
    check(!is_wp_error($request) && unchanged(1000, 25), 'Available Balance remains withdrawable while assets are locked, including holding profit');
    check(!is_wp_error(wsi_process_withdrawal_request($request['withdrawal_id'], 'decline')) && unchanged(1000, 150), 'Available Balance refund does not duplicate remaining holding profits');
    wsi_inc_profit(1, 10);
    check(unchanged(1000, 160), 'Profit credit adds only the new credit');

    reset_fixture();
    deposit(time() - 60 * DAY_IN_SECONDS);
    check(!wsi_get_withdrawal_balances(1)['total_assets_locked'], 'Assets unlock exactly at the deadline');
    deposit(time());
    update_user_meta(1, 'wsi_main_balance', 2000);
    $mixed = wsi_get_withdrawal_balances(1);
    check(!$mixed['total_assets_locked'] && $mixed['total_assets_unlocked_amount'] === 1000.0 && $mixed['total_assets_locked_amount'] === 1000.0, 'New deposits lock only their own amount');
    check(is_wp_error(request_money('1000.01', 'total_assets')) && unchanged(2000, 150), 'Mixed deposits cannot withdraw more than matured principal');
    $mixed_request = request_money('400', 'total_assets');
    check(!is_wp_error($mixed_request) && wsi_get_withdrawal_balances(1)['total_assets_unlocked_amount'] === 600.0, 'Matured principal remains withdrawable alongside locked deposits');
    wsi_process_withdrawal_request($mixed_request['withdrawal_id'], 'decline');
    check(wsi_get_withdrawal_balances(1)['total_assets_unlocked_amount'] === 1000.0, 'Refund restores matured principal without unlocking new deposits');
    $GLOBALS['unlock_days'] = 0;
    check(!wsi_get_withdrawal_balances(1)['total_assets_locked'], 'Zero-day lock allows immediate withdrawal');
    reset_fixture();
    deposit(time(), 'pending');
    deposit(time() - 61 * DAY_IN_SECONDS, 'approved', false);
    check(!wsi_get_withdrawal_balances(1)['total_assets_locked'], 'Pending deposits are ignored; legacy approved deposits use creation date');
    $wpdb->query("UPDATE wp_wsi_deposits SET created_at='0000-00-00 00:00:00' WHERE status='approved'");
    check(wsi_get_withdrawal_balances(1)['total_assets_locked'], 'Invalid investment dates fail closed');

    reset_fixture();
    foreach (['0', '-5', '1e2', '12junk', '0.001', 'INF', [], '10000000000000'] as $invalid) {
        check(is_wp_error(request_money($invalid, 'available_balance')) && unchanged(1000, 150), 'Malformed amount rejected: ' . json_encode($invalid));
    }
    check(is_wp_error(request_money('10', 'wrong_account')) && unchanged(1000, 150), 'Unknown source never falls back to a different account');
    check(is_wp_error(request_money('151', 'available_balance')) && unchanged(1000, 150), 'Available Balance request cannot spill into principal');
    check(is_wp_error(request_money('1001', 'total_assets')) && unchanged(1000, 150), 'Total Assets request cannot spill into profit');
    check(is_wp_error(wsi_create_withdrawal_request(1, '10', 'available_balance', '', '')), 'Missing payout details rejected');

    foreach (['available_balance', 'total_assets'] as $source) {
        reset_fixture();
        $wpdb->fail = 'INSERT INTO wp_wsi_withdrawals';
        check(is_wp_error(request_money('125', $source)) && unchanged(1000, 150), 'Failed request insert rolls back ' . $source);
    }
    reset_fixture();
    $wpdb->fail = 'UPDATE wp_wsi_holdings';
    check(is_wp_error(request_money('125', 'available_balance')) && unchanged(1000, 150), 'Failed holding debit rolls back earlier profit deduction');
    $request = request_money('125', 'available_balance');
    $wpdb->fail = 'UPDATE wp_usermeta';
    check(is_wp_error(wsi_process_withdrawal_request($request['withdrawal_id'], 'decline')) && unchanged(1000, 25), 'Failed refund rolls back status and balance together');
    check(!is_wp_error(wsi_process_withdrawal_request($request['withdrawal_id'], 'decline')) && unchanged(1000, 150), 'Failed refund can be retried exactly once');
    $request = request_money('50', 'total_assets');
    check(!is_wp_error(wsi_process_withdrawal_request($request['withdrawal_id'], 'approve')) && unchanged(950, 150), 'Approval does not deduct funds again');
    check(is_wp_error(wsi_process_withdrawal_request($request['withdrawal_id'], 'decline')) && unchanged(950, 150), 'Approved withdrawal cannot be refunded by a repeated admin action');

    reset_fixture();
    $wpdb->fail = 'SELECT amount, created_at, approved_at';
    check(is_wp_error(request_money('10', 'total_assets')) && unchanged(1000, 150), 'Failed deposit lookup cannot bypass the asset lock');
    $wpdb->fail = 'SELECT SUM(accumulated_profit)';
    check(is_wp_error(request_money('10', 'available_balance')) && unchanged(1000, 150), 'Failed profit lookup prevents withdrawal');
    $wpdb->fail = 'SELECT SUM(accumulated_profit)';
    check(is_wp_error(wsi_rest_balance_snapshot(1)), 'REST reports failed balance reads instead of false unlocked balances');
    reset_fixture(1000, 0.03, 0.02);
    check(!is_wp_error(request_money('0.05', 'available_balance')) && unchanged(1000, 0), 'Exact cent withdrawals empty Available Balance without rounding residue');
    $wpdb->query('ALTER TABLE wp_wsi_withdrawals DROP COLUMN source');
    wsi_safe_migration();
    check($wpdb->get_var('SELECT source FROM wp_wsi_withdrawals LIMIT 1') === 'available_balance', 'Existing installations migrate the withdrawal source column');

    reset_fixture(1000, 100, 0);
    $pdo->beginTransaction();
    $pdo->query('SELECT ID FROM wp_users WHERE ID=1 FOR UPDATE');
    $workers = [];
    for ($i = 0; $i < 2; $i++) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, '--worker', $database], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) throw new RuntimeException('Unable to start concurrency test');
        fclose($pipes[0]);
        $workers[] = [$process, $pipes];
    }
    $pdo->commit();
    $results = [];
    foreach ($workers as [$process, $pipes]) {
        $results[] = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        if (proc_close($process) !== 0) throw new RuntimeException('Worker failed: ' . $stderr);
    }
    sort($results);
    check($results === ['success', 'wsi_insufficient'] && unchanged(1000, 20), 'Concurrent withdrawals cannot overspend the same balance');
    reset_fixture();
    check(is_wp_error(wsi_create_withdrawal_request(1, '10', 'available_balance', 'bank', '0012345678')), 'Bank withdrawals require a bank name');
    check(is_wp_error(wsi_create_withdrawal_request(1, '10', 'available_balance', 'bank', '', 'Test Bank')), 'Bank withdrawals require an account number');
    $bank = wsi_create_withdrawal_request(1, '10', 'available_balance', 'bank', '0012345678', 'Test Bank');
    $stored = $wpdb->get_row('SELECT method, account_details FROM wp_wsi_withdrawals WHERE id=' . $bank['withdrawal_id']);
    check($stored->method === 'bank' && strpos($stored->account_details, 'Test Bank') !== false && strpos($stored->account_details, '0012345678') !== false && unchanged(1000, 140), 'Bank details preserve leading zeros and charge only the selected balance');
    reset_fixture();
    deposit(time() - 600, 'pending');
    $deposit_id = $wpdb->insert_id;
    $status = wsi_get_deposit_status(1, $deposit_id);
    check($status['status'] === 'pending' && abs($status['elapsed_seconds'] - 600) <= 1 && $status['estimated_seconds'] === 1800, 'Deposit waiting estimate uses server time in the site timezone');
    check(is_wp_error(wsi_get_deposit_status(2, $deposit_id)), 'A user cannot read another user’s deposit status');
    check(wsi_get_deposit_status(1)['deposit_id'] === $deposit_id, 'Reload resumes the latest pending deposit');
    $wpdb->update('wp_wsi_deposits', ['status' => 'approved'], ['id' => $deposit_id]);
    check(wsi_get_deposit_status(1, $deposit_id)['status'] === 'approved', 'Approval is visible to polling immediately');
    check(is_wp_error(wsi_get_deposit_status(1)), 'Approved deposit is not selected as a new pending request');
    $wpdb->update('wp_wsi_deposits', ['status' => 'declined'], ['id' => $deposit_id]);
    check(wsi_get_deposit_status(1, $deposit_id)['status'] === 'declined', 'Declined status stops the approval wait');
    $wpdb->fail = 'SELECT id, amount, status, created_at';
    check(wsi_get_deposit_status(1, $deposit_id)->get_error_data()['status'] === 503, 'Deposit lookup errors are retryable rather than mistaken for approval');
    foreach ([
        [false, 'test-nonce', $deposit_id, 401],
        [true, 'bad-nonce', $deposit_id, 403],
        [true, 'test-nonce', [], 400],
        [true, 'test-nonce', '999999999', 404],
        [true, 'test-nonce', $deposit_id, 200],
    ] as [$logged_in, $nonce, $id, $expected]) {
        $GLOBALS['test_logged_in'] = $logged_in;
        $_POST = ['_wpnonce' => $nonce, 'deposit_id' => $id];
        try { wsi_check_deposit_status(); throw new RuntimeException('Expected JSON response'); }
        catch (TestJsonResponse $response) { check($response->status === $expected && $response->success === ($expected === 200), 'Deposit polling endpoint returns expected HTTP status ' . $expected); }
    }
    reset_fixture();
    $GLOBALS['test_logged_in'] = true;
    $GLOBALS['test_notification_failure'] = true;
    $_POST = ['_wpnonce' => 'test-nonce', 'amount' => '10.00', 'withdrawal_source' => 'available_balance', 'payout_method' => 'bank', 'bank_name' => 'Example Bank', 'account_number' => '0012345678'];
    ob_start();
    try { wsi_handle_withdraw(); }
    catch (TestJsonResponse $response) {
        $noise = ob_get_clean();
        check($response->success && $noise === '' && unchanged(1000, 140), 'AJAX bank withdrawal returns success without stray output even if notification fails');
    }
    $GLOBALS['test_notification_failure'] = false;
    $_POST['bank_name'] = '';
    try { wsi_handle_withdraw(); }
    catch (TestJsonResponse $response) {
        check(!$response->success && unchanged(1000, 140), 'AJAX rejects incomplete bank details without deducting funds');
    }
    reset_fixture();
    deposit(time() - 61 * DAY_IN_SECONDS);
    $reinvest = wsi_create_reinvestment(1, '1150.00');
    $balances = wsi_get_withdrawal_balances(1);
    check(!is_wp_error($reinvest) && unchanged(1150, 0), 'Full reinvestment combines principal and all profits without doubling principal');
    check($balances['total_assets_locked'] && $balances['total_assets_locked_amount'] === 1150.0 && $balances['total_assets_unlocked_amount'] === 0.0, 'Entire reinvestment is locked immediately');
    check(abs(strtotime($balances['total_assets_unlock_at']) - (time() + 60 * DAY_IN_SECONDS)) <= 1, 'Reinvestment starts a fresh configured lock period');
    check(is_wp_error(request_money('0.01', 'total_assets')) && is_wp_error(request_money('0.01', 'available_balance')), 'Reinvested money cannot be withdrawn from either balance');
    check(is_wp_error(wsi_create_reinvestment(1, '1150.00')) && unchanged(1150, 0), 'Duplicate reinvestment submission cannot reuse locked principal');
    $matured = wp_date('Y-m-d H:i:s', time() - 60 * DAY_IN_SECONDS);
    $wpdb->update('wp_wsi_deposits', ['approved_at' => $matured], ['id' => $reinvest['deposit_id']]);
    check(wsi_get_withdrawal_balances(1)['total_assets_unlocked_amount'] === 1150.0, 'Full reinvestment unlocks at its deadline');

    reset_fixture(2000);
    deposit(time() - 61 * DAY_IN_SECONDS);
    deposit(time() - DAY_IN_SECONDS);
    $existing_id = $wpdb->insert_id;
    $existing_date = $wpdb->get_var('SELECT approved_at FROM wp_wsi_deposits WHERE id=' . $existing_id);
    check(!is_wp_error(wsi_create_reinvestment(1, '1150.00')) && unchanged(2150, 0), 'Mixed balances reinvest only matured principal plus earnings');
    $balances = wsi_get_withdrawal_balances(1);
    check($balances['total_assets_locked_amount'] === 2150.0 && $balances['total_assets_unlocked_amount'] === 0.0 && $wpdb->get_var('SELECT approved_at FROM wp_wsi_deposits WHERE id=' . $existing_id) === $existing_date, 'Existing locked deposits keep their original unlock date');

    reset_fixture();
    deposit(time() - 61 * DAY_IN_SECONDS);
    request_money('400.00', 'total_assets');
    check(!is_wp_error(wsi_create_reinvestment(1, '750.00')) && unchanged(750, 0), 'Previously withdrawn principal is excluded from reinvestment');
    reset_fixture(1000, 0, 0);
    deposit(time() - 61 * DAY_IN_SECONDS);
    check(!is_wp_error(wsi_create_reinvestment(1, '1000.00')) && unchanged(1000, 0) && wsi_get_withdrawal_balances(1)['total_assets_locked'], 'Principal-only reinvestment relocks assets without increasing their value');
    reset_fixture();
    deposit(time() - DAY_IN_SECONDS);
    check(!is_wp_error(wsi_create_reinvestment(1, '150.00')) && unchanged(1150, 0), 'Available earnings can be reinvested while original principal is locked');

    reset_fixture();
    deposit(time() - 61 * DAY_IN_SECONDS);
    foreach (['1150.01', '0', '-1', '1e3', '1150.001', [], '1150x'] as $amount) {
        check(is_wp_error(wsi_create_reinvestment(1, $amount)) && unchanged(1000, 150), 'Excessive or malformed reinvestment rejected: ' . json_encode($amount));
    }
    foreach (['SELECT amount, created_at, approved_at', 'UPDATE wp_wsi_holdings', 'INSERT INTO wp_wsi_deposits'] as $failure) {
        $wpdb->fail = $failure;
        check(is_wp_error(wsi_create_reinvestment(1, '1150.00')) && unchanged(1000, 150) && (int) $wpdb->get_var('SELECT COUNT(*) FROM wp_wsi_deposits') === 1, 'Failed reinvestment rolls back earnings, assets and deposit: ' . $failure);
    }
    foreach ([['50.00', 1050, 100, 1000], ['115.00', 1115, 35, 1000], ['575.00', 1150, 0, 575]] as [$amount, $assets, $earnings, $unlocked]) {
        reset_fixture();
        deposit(time() - 61 * DAY_IN_SECONDS);
        $reinvest = wsi_create_reinvestment(1, $amount);
        $balances = wsi_get_withdrawal_balances(1);
        check(!is_wp_error($reinvest) && (float) $reinvest['amount'] === (float) $amount && unchanged($assets, $earnings), 'Partial reinvestment uses earnings first and preserves remaining balances: ' . $amount);
        check($balances['total_assets_locked_amount'] === (float) $amount && $balances['total_assets_unlocked_amount'] === (float) $unlocked, 'Only the selected amount is locked: ' . $amount);
        check(!is_wp_error(request_money((string) $unlocked, 'total_assets')), 'Unselected principal remains withdrawable: ' . $amount);
    }
    reset_fixture(1000, 0, 0);
    deposit(time() - 61 * DAY_IN_SECONDS);
    check(!is_wp_error(wsi_create_reinvestment(1, '100.00')) && unchanged(1000, 0) && wsi_get_withdrawal_balances(1)['total_assets_unlocked_amount'] === 900.0, 'Partial principal-only reinvestment does not inflate Total Assets');
    check(is_wp_error(wsi_create_reinvestment(1, '900.01')) && unchanged(1000, 0), 'Previously reinvested principal cannot be reused');
    reset_fixture();
    deposit(time() - 61 * DAY_IN_SECONDS);
    $wpdb->fail = 'INSERT INTO wp_wsi_deposits';
    check(is_wp_error(wsi_create_reinvestment(1, '575.00')) && unchanged(1000, 150) && wsi_get_withdrawal_balances(1)['total_assets_unlocked_amount'] === 1000.0, 'Failed partial reinvestment rolls back debits and locks');
    reset_fixture(0, 0.03, 0.02);
    check(!is_wp_error(wsi_create_reinvestment(1, '0.05')) && unchanged(0.05, 0), 'Cent-level reinvestment consumes both profit sources exactly');
    reset_fixture();
    deposit(time() - 61 * DAY_IN_SECONDS);
    $GLOBALS['unlock_days'] = 7;
    wsi_create_reinvestment(1, '1150.00');
    check(abs(strtotime(wsi_get_withdrawal_balances(1)['total_assets_unlock_at']) - (time() + 7 * DAY_IN_SECONDS)) <= 1, 'Reinvestment uses the configured lock duration');
    $GLOBALS['unlock_days'] = 0;
    check(wsi_get_withdrawal_balances(1)['total_assets_unlocked_amount'] === 1150.0, 'Zero-day configuration also applies to reinvestment');
    echo "All $count checks passed.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Only remove this run's generated fixture database, never the application database.
    $pdo->exec("DROP DATABASE `$database`");
}
