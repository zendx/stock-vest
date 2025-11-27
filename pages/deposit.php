<?php

if (!defined('ABSPATH')) exit;
$wsi = plugins_url('assets/', __FILE__);

$opts = function_exists('wsi_get_opts') ? wsi_get_opts() : [];

// Normalize values with safe defaults
$exchange_rate       = floatval($opts['exchange_rate'] ?? 1000); // ₦ per $1
$min_invest          = floatval($opts['min_invest'] ?? 50);
$deposit_mode        = sanitize_text_field($opts['deposit_mode'] ?? 'manual');
$manual_payment_info = $opts['manual_payment_info'] ?? '';
$naira_payment_info  = $opts['naira_payment_info'] ?? '';

$btc_wallet      = trim($opts['btc_wallet'] ?? '');
$btc_instruction = $opts['btc_instruction'] ?? '';
$usdt_wallet     = trim($opts['usdt_wallet'] ?? '');
$usdt_instruction= $opts['usdt_instruction'] ?? '';
$eth_wallet      = trim($opts['eth_wallet'] ?? '');
$eth_instruction = $opts['eth_instruction'] ?? '';

// ------------------------------
// Email templates (added)
// ------------------------------
$email_on_deposit         = $opts['email_on_deposit'] ?? '';        // user email when deposit is made
$email_on_withdraw        = $opts['email_on_withdraw'] ?? '';       // user email when withdrawal is made
$email_on_registration    = $opts['email_on_registration'] ?? '';   // welcome email
$email_on_stock_purchase  = $opts['email_on_stock_purchase'] ?? ''; // stock purchase email
$email_on_holding_sale    = $opts['email_on_holding_sale'] ?? '';   // sale notification
$email_admin_new_deposit  = $opts['email_admin_new_deposit'] ?? ''; // admin gets notified
$email_admin_new_withdraw = $opts['email_admin_new_withdraw'] ?? ''; // admin withdraw alert


// default naira amount shown = min_invest * exchange_rate
$default_naira = number_format($min_invest * max(1, $exchange_rate), 2, '.', '');

/** Get Wallet Details **/
?>

<!DOCTYPE html>
<html lang="en">
<!-- dir="rtl"-->

<head>
    <!-- Required meta tags  -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>COFCO CAPITAL | User Deposit</title>
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

    <script defer src="<?php echo plugin_dir_url(__FILE__) . 'assets/js/app435e.js?1096aad991449c8654b2'; ?>"></script><link href="<?php echo plugin_dir_url(__FILE__) . 'assets/css/app435e.css?1096aad991449c8654b2'; ?>" rel="stylesheet">
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
                                            <li class="breadcrumb-item bi"><a href="investment-dashboard.html"><i class="bi bi-house-door me-1 fs-14"></i> Dashboard</a></li>
                                            <li class="breadcrumb-item active bi" aria-current="page">Deposit</li>
                                        </ol>
                                    </nav>
                                    <h5>Deposit</h5>
                                </div>
                                <div class="col-12 col-sm-auto text-end py-3 py-sm-0">

                                </div>
                            </div>
                        </div>

                        <!-- content -->
                        <div class="container mt-4" id="main-content" data-bs-spy="scroll" data-bs-target="#list-example" data-bs-smooth-scroll="true">

                            <!--div class="position-sticky z-index-5 mb-4 adminuiux-header" style="top: 5rem;">
                                <nav class="navbar rounded p-1">
                                    <ul id="list-example" class="nav nav-pills bg-none">
                                        <li class="nav-item"><a class="nav-link" href="#list-item-1">My Deposit</a></li>
                                        <li class="nav-item mx-1"><a class="nav-link" href="#list-item-2">100% Guaranteed</a></li>
                                        <li class="nav-item"><a class="nav-link" href="#list-item-3">Market Linked</a></li>
                                    </ul>
                                </nav>
                            </div-->

                            <div class="row" id="list-item-1">

                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col-12 col-lg-8 mb-4">

                                                <div class="card adminuiux-card">
                                                    <div class="card-header">
                                                        <h5>Create Deposit @ 7.00%</h5>
                                                        <p class="text-secondary">Start your money growing with smart investment</p>
                                                    </div>

                                                    <div class="card-body">

                                                        <?php if (($opts['deposit_mode'] ?? 'manual') === 'manual'): ?>
                                                        <?php else: ?>
                                                            <p><strong>Auto-confirm deposit enabled.</strong></p>
                                                        <?php endif; ?>

                                                        <form id="wsi-deposit-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                                                            
                                                            <input type="hidden" name="redirect_to" value="<?php echo esc_attr( (isset($_SERVER["REQUEST_URI"]) ? esc_url_raw($_SERVER["REQUEST_URI"]) : home_url("/") ) ); ?>">
                                                            <input type="hidden" name="action" value="wsi_submit_deposit">
                                                            <?php wp_nonce_field('wsi_deposit_nonce'); ?>
                                                            <input type="hidden" name="is_ajax" value="1" id="is_ajax_field">
                                                            <input type="hidden" name="amount" id="amount_usd" value="">


                                                            <!-- Payment Type -->
                                                            <div class="mb-3">
                                                                <label><input type="radio" name="payment_type" value="naira" checked> Naira Payment</label>
                                                                <label class="ms-3"><input type="radio" name="payment_type" value="crypto"> Crypto Payment</label>
                                                            </div>


                                                            <!-- NAIRA SECTION -->
                                                            <div id="naira_section">
                                                                <div class="row mb-2">
                                                                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                        <div class="form-floating">
                                                                            <input name="amount_naira" id="amount_naira" type="number" min="0" 
                                                                                   class="form-control"
                                                                                   value="<?php echo esc_attr($opts['min_invest'] ?? 50) * ($opts['exchange_rate'] ?? 1000); ?>">
                                                                            <label for="amount_naira">Enter Amount (₦)</label>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                        <div class="form-floating">
                                                                            <input type="text" id="amount_usd_display" readonly class="form-control">
                                                                            <label for="amount_usd_display">Equivalent ($)</label>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div id="rate_info" class="text-secondary small mb-2">
                                                                    Exchange Rate: $1 = ₦<?php echo esc_html(number_format($opts['exchange_rate'] ?? 1000,2)); ?>
                                                                </div>

                                                                <div id="naira_instructions" class="mb-3">
                                                                    <?php echo nl2br(esc_html($opts['naira_payment_info'] ?? $opts['manual_payment_info'] ?? '')); ?>
                                                                </div>
                                                            </div>


                                                            <!-- CRYPTO SECTION -->
                                                            <div id="crypto_section" style="display:none;">
                                                                <div class="row mb-2">
                                                                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                                                                        <div class="form-floating">
                                                                            <input name="crypto_amount" id="crypto_amount" type="number"
                                                                                   class="form-control"
                                                                                   min="0"
                                                                                   placeholder="<?php echo esc_attr($opts['min_invest'] ?? 50); ?>">
                                                                            <label for="crypto_amount">Enter Amount ($)</label>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-12 col-md-6 col-xl-4 mb-3" id="crypto_wallet_select" style="display:none;">
                                                                        <div class="form-floating">
                                                                            <select id="crypto_wallet" name="crypto_wallet" class="form-select">
                                                                                <option value="">-- choose --</option>
                                                                                <?php if(!empty($opts['btc_wallet'])): ?><option value="btc">BTC</option><?php endif; ?>
                                                                                <?php if(!empty($opts['usdt_wallet'])): ?><option value="usdt">USDT</option><?php endif; ?>
                                                                                <?php if(!empty($opts['eth_wallet'])): ?><option value="eth">ETH</option><?php endif; ?>
                                                                            </select>
                                                                            <label for="crypto_wallet">Select wallet</label>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div id="crypto_wallet_info" style="display:none;">
                                                                    <div id="wallet_address" class="fw-bold"></div>
                                                                    <div id="wallet_instruction" class="mt-2 small"></div>
                                                                </div>
                                                            </div>


                                                            <div class="row align-items-center mt-4">
                                                                <div class="col">
                                                                    <p class="text-secondary small">Amount will be processed after admin confirmation</p>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <button type="button" id="wsi_deposit_submit" class="btn btn-theme" style="display:none;">
                                                                        Submit Deposit
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </form>

                                                    </div>
                                                </div>
                                            </div>



                                            <!-- Right Offer Card (unchanged) -->
                                            <div class="col-12 col-lg-4 mb-4">
                                                <div class="card adminuiux-card position-relative overflow-hidden bg-theme-1 h-100">
                                                    <div class="position-absolute top-0 start-0 h-100 w-100 z-index-0 coverimg opacity-50">
                                                        <img src="assets/img/modern-ai-image/flamingo-4.jpg" alt="">
                                                    </div>
                                                    <div class="card-body z-index-1">
                                                        <div class="avatar avatar-60 rounded bg-white-opacity text-white mb-4">
                                                            <i class="bi bi-tags h4"></i>
                                                        </div>
                                                        <h2>Great Offer!</h2>
                                                        <h4 class="fw-medium">You have <b>LOAN</b> of <b>$ 800000.00</b> offer from HSBCD Bank</h4>
                                                        <p class="mb-4">No documentation required...</p>
                                                        <button class="btn btn-light my-1">Apply Now</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>



                            <!-- UNIFIED + CLEANED SCRIPT (all duplicate blocks removed) -->
                            <script>
                            (function(){

                                function initDeposit() {

                                    var rate = <?php echo floatval($opts['exchange_rate'] ?? 1000); ?>;
                                    var minUSD = <?php echo floatval($opts['min_invest'] ?? 50); ?>;

                                    var naira = document.getElementById("amount_naira");
                                    var usd_display = document.getElementById("amount_usd_display");
                                    var usd_hidden = document.getElementById("amount_usd");
                                    var submitBtn = document.getElementById("wsi_deposit_submit");

                                    var cryptoAmount = document.getElementById("crypto_amount");
                                    var cryptoSelectWrap = document.getElementById("crypto_wallet_select");
                                    var cryptoSelect = document.getElementById("crypto_wallet");
                                    var walletInfo = document.getElementById("crypto_wallet_info");
                                    var walletAddress = document.getElementById("wallet_address");
                                    var walletInstruction = document.getElementById("wallet_instruction");

                                    var nairaSection = document.getElementById("naira_section");
                                    var cryptoSection = document.getElementById("crypto_section");

                                    // --- FIXED CALCULATION ---
                                    function recalcNaira() {
                                        var n = parseFloat(naira.value) || 0;
                                        var usd = n / rate;
                                        usd_display.value = usd.toFixed(2);
                                        usd_hidden.value = usd.toFixed(2);
                                        submitBtn.style.display = (usd >= minUSD) ? "" : "none";
                                    }

                                    // --- Crypto validation ---
                                    function checkCrypto() {
                                        var c = parseFloat(cryptoAmount.value) || 0;
                                        if (c >= minUSD) {
                                            cryptoSelectWrap.style.display = "";
                                        } else {
                                            cryptoSelectWrap.style.display = "none";
                                            walletInfo.style.display = "none";
                                            submitBtn.style.display = "none";
                                        }
                                    }

                                    function showWallet(code) {
                                        if (!code) {
                                            walletInfo.style.display = "none";
                                            submitBtn.style.display = "none";
                                            return;
                                        }

                                        var opts = <?php echo json_encode([
                                            'btc' => $opts['btc_wallet'] ?? '',
                                            'btc_ins' => $opts['btc_instruction'] ?? '',
                                            'usdt' => $opts['usdt_wallet'] ?? '',
                                            'usdt_ins' => $opts['usdt_instruction'] ?? '',
                                            'eth' => $opts['eth_wallet'] ?? '',
                                            'eth_ins' => $opts['eth_instruction'] ?? '',
                                        ]); ?>;

                                        var addr = opts[code];
                                        var ins = opts[code + "_ins"];

                                        walletAddress.innerText = code.toUpperCase() + " Address: " + addr;
                                        walletInstruction.innerHTML = ins.replace(/\n/g, "<br>");

                                        walletInfo.style.display = "";
                                        submitBtn.style.display = "";
                                    }

                                    // --- Payment type switch ---
                                    document.querySelectorAll('input[name="payment_type"]').forEach(function(radio) {
                                        radio.addEventListener("change", function() {
                                            if (this.value === "naira") {
                                                nairaSection.style.display = "";
                                                cryptoSection.style.display = "none";
                                                recalcNaira();
                                            } else {
                                                nairaSection.style.display = "none";
                                                cryptoSection.style.display = "";
                                                submitBtn.style.display = "none";
                                            }
                                        });
                                    });

                                    // --- Event bindings ---
                                    naira.addEventListener("input", recalcNaira);
                                    cryptoAmount.addEventListener("input", checkCrypto);
                                    cryptoSelect.addEventListener("change", function() {
                                        showWallet(this.value);
                                    });

                                    recalcNaira();


                                    // ========================================================
                                    // ðŸ”¥ FIX: REAL FORM SUBMIT HANDLER
                                    // ========================================================
                                    submitBtn.addEventListener("click", function() {
                                        document.getElementById("wsi-deposit-form").submit();
                                    }); // FIXED
                                }

                                document.addEventListener("DOMContentLoaded", initDeposit);
                            })();
                            </script>

                            </div>

                        </div>
                    </main>

            </div>


            <!-- page footer -->
            <?php
            if (file_exists(__DIR__ . '/assets/inc/footer.php')) {
                include_once __DIR__ . '/assets/inc/footer.php';
            }
            ?>

            <script>
            (function(){
                // Options passed from PHP to JS safe JSON
                var WSI_OPTS = <?php echo json_encode([
                    'exchange_rate' => $exchange_rate,
                    'min_invest' => $min_invest,
                    'btc_wallet' => $btc_wallet,
                    'btc_instruction' => $btc_instruction,
                    'usdt_wallet' => $usdt_wallet,
                    'usdt_instruction' => $usdt_instruction,
                    'eth_wallet' => $eth_wallet,
                    'eth_instruction' => $eth_instruction,
                ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;

                function qs(id){ return document.getElementById(id); }

                function formatNumber(n){
                    // keep 2 decimals
                    return Number(n).toFixed(2);
                }

                function init(){
                    var rate = parseFloat(WSI_OPTS.exchange_rate) || 1000;
                    var minUSD = parseFloat(WSI_OPTS.min_invest) || 50;

                    var naira = qs('amount_naira');
                    var usd_display = qs('amount_usd_display');
                    var usd_hidden = qs('amount_usd');
                    var submitBtn = qs('wsi_deposit_submit');

                    var cryptoAmount = qs('crypto_amount');
                    var cryptoSelectWrap = qs('crypto_wallet_select');
                    var cryptoSelect = qs('crypto_wallet');
                    var walletInfo = qs('crypto_wallet_info');
                    var walletAddress = qs('wallet_address');
                    var walletInstruction = qs('wallet_instruction');

                    var nairaSection = qs('naira_section');
                    var cryptoSection = qs('crypto_section');

                    function recalcNaira(){
                        var n = parseFloat(naira.value) || 0;
                        // avoid divide by zero
                        var usd = rate > 0 ? (n / rate) : 0;
                        usd_display.value = formatNumber(usd);
                        usd_hidden.value = formatNumber(usd);
                        // show submit if meets min
                        submitBtn.style.display = (usd >= minUSD) ? '' : 'none';
                    }

                    function checkCrypto(){
                        var c = parseFloat(cryptoAmount.value) || 0;
                        if (c >= minUSD) {
                            cryptoSelectWrap.style.display = '';
                            // hide wallet info until selection
                            walletInfo.classList.add('wsi-hidden');
                            submitBtn.style.display = 'none';
                        } else {
                            cryptoSelectWrap.style.display = 'none';
                            walletInfo.classList.add('wsi-hidden');
                            submitBtn.style.display = 'none';
                        }
                    }

                    function showWallet(code){
                        if (!code) {
                            walletInfo.classList.add('wsi-hidden');
                            submitBtn.style.display = 'none';
                            return;
                        }

                        var map = {
                            'btc': { addr: WSI_OPTS.btc_wallet || '', ins: WSI_OPTS.btc_instruction || '' },
                            'usdt': { addr: WSI_OPTS.usdt_wallet || '', ins: WSI_OPTS.usdt_instruction || '' },
                            'eth': { addr: WSI_OPTS.eth_wallet || '', ins: WSI_OPTS.eth_instruction || '' },
                        };

                        var entry = map[code] || { addr: '', ins: '' };
                        walletAddress.innerText = code.toUpperCase() + ' Address: ' + entry.addr;
                        walletInstruction.innerHTML = (entry.ins || '').replace(/\n/g, '<br>');
                        walletInfo.classList.remove('wsi-hidden');
                        submitBtn.style.display = '';
                    }

                    // Payment radio switch
                    document.querySelectorAll('input[name="payment_type"]').forEach(function(radio){
                        radio.addEventListener('change', function(){
                            if (this.value === 'naira') {
                                nairaSection.style.display = '';
                                cryptoSection.style.display = 'none';
                                recalcNaira();
                            } else {
                                nairaSection.style.display = 'none';
                                cryptoSection.style.display = '';
                                submitBtn.style.display = 'none';
                            }
                        });
                    });

                    // Event bindings
                    naira.addEventListener('input', recalcNaira);
                    if (cryptoAmount) cryptoAmount.addEventListener('input', checkCrypto);
                    if (cryptoSelect) cryptoSelect.addEventListener('change', function(){ showWallet(this.value); });

                    // initial state
                    recalcNaira();

                    // Show crypto wallet select initially if admin provided any wallet AND user enters >= min
                    // Also ensure crypto wallet select options exist (we printed only those with values)

                    // Submit handler: prefer AJAX; fallback to default behavior if server doesn't return JSON
                    submitBtn.addEventListener('click', function(){
                        doSubmit();
                    });

                    function doSubmit(){
                        var form = qs('wsi-deposit-form');
                        var formData = new FormData(form);

                        // Small client-side validation (USD minimum)
                        var paymentType = document.querySelector('input[name="payment_type"]:checked').value;
                        var usdVal = 0;
                        if (paymentType === 'naira') {
                            usdVal = parseFloat(qs('amount_usd').value) || 0;
                        } else {
                            usdVal = parseFloat(qs('crypto_amount').value) || 0;
                        }
                        if (usdVal < minUSD) {
                            Swal.fire('Amount too small', 'The amount must be at least $' + minUSD.toFixed(2), 'error');
                            return;
                        }

                        // Disable while submitting
                        submitBtn.disabled = true;
                        submitBtn.innerText = 'Processing...';

                        fetch(form.action, {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: formData
                        }).then(function(res){
                            // Attempt to parse JSON first
                            return res.text().then(function(text){
                                // Try parse JSON; if fails, treat as non-JSON fallback
                                try {
                                    var data = JSON.parse(text);
                                    return { parsed: true, data: data };
                                } catch (e) {
                                    return { parsed: false, text: text, status: res.status };
                                }
                            });
                        }).then(function(result){
                            submitBtn.disabled = false;
                            submitBtn.innerText = 'Submit Deposit';

                            if (result.parsed) {
                                var data = result.data;
                                if (data && data.success) {

                                    let d = data.data || {};

                                    Swal.fire({
                                        title: "Deposit Submitted",
                                        html: `
                                            Your deposit has been submitted and is pending approval.<br><br>
                                            <strong>Deposit ID:</strong> ${d.deposit_id}<br>
                                            <strong>Amount (USD):</strong> $${d.amount_usd}<br>
                                            <strong>Amount (Local):</strong> ${d.amount_local}<br>
                                            <strong>Payment Type:</strong> ${d.payment_type}
                                        `,
                                        icon: "success",
                                        confirmButtonText: "OK"
                                    }).then(function () {
                                        if (d.redirect_to) {
                                            window.location.href = d.redirect_to;
                                        } else {
                                            window.location.reload();
                                        }
                                    });

                                } else {
                                    // server returned json but indicates failure
                                    var msg = (data && (data.data && data.data.message)) || data.message || 'An error occurred';
                                    Swal.fire('Error', msg, 'error');
                                }
                            } else {
                                // Non-JSON response: fallback to navigate to response (servers that redirect to page)
                                // open result.text in new document
                                var doc = window.open('', '_self');
                                doc.document.write(result.text);
                                doc.document.close();
                            }
                        }).catch(function(err){
                            submitBtn.disabled = false;
                            submitBtn.innerText = 'Submit Deposit';
                            Swal.fire('Network error', 'Unable to submit deposit. Please try again.', 'error');
                            console.error(err);
                        });
                    }
                } // end init

                // DOM ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();
            </script>
            <script>
            document.getElementById('deposit-form').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = this;
                const formData = new FormData(form);
                formData.append('is_ajax', '1');

                fetch(form.action, {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Deposit Submitted!",
                            text: data.data.message,
                            confirmButtonText: "OK"
                        }).then(() => {
                            window.location.href = data.data.redirect_to;
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Deposit Failed",
                            text: data.data.message || "An error occurred."
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: "error",
                        title: "Connection Error",
                        text: "Unable to submit deposit."
                    });
                });
            });
            </script>
            <!--Deposit Modal-->
            <div id="deposit-toast" style="
                display:none;
                position:fixed;
                top:20px;
                right:20px;
                background:#4CAF50;
                color:#fff;
                padding:12px 20px;
                border-radius:6px;
                box-shadow:0 4px 12px rgba(0,0,0,0.2);
                z-index:9999;
                font-family:sans-serif;
                font-size:14px;
            ">
                Deposit Successful
            </div>
            <script>
                function showDepositToast() {
                    const toast = document.getElementById('deposit-toast');
                    toast.style.display = 'block';
                    
                    setTimeout(() => {
                        toast.style.display = 'none';
                    }, 3000); // hide after 3 seconds
                }

                document.getElementById('wsi_deposit_submit').addEventListener('click', function() {
                    // Submit the form normally
                    document.getElementById('wsi-deposit-form').submit();

                    // Show the simple success popup
                    showDepositToast();
                });


            </script>


        </body>

</html>