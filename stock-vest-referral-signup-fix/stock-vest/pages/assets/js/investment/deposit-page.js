(function () {
    'use strict';

    // Poll serially. A timer can update the estimate, but only approved status can redirect.
    function createTracker(options) {
        let stopped = false;
        let timer;
        const schedule = options.schedule || setTimeout;
        const cancel = options.cancel || clearTimeout;
        async function poll() {
            try {
                const deposit = await options.check();
                if (stopped) return;
                options.onStatus(deposit);
                if (deposit.status === 'approved' || deposit.status === 'declined') {
                    stopped = true;
                    return;
                }
                timer = schedule(poll, 5000);
            } catch (error) {
                if (stopped) return;
                const terminal = [400, 401, 403, 404, 409].includes(error.status);
                options.onError(error, terminal);
                if (terminal) stopped = true;
                else timer = schedule(poll, 10000);
            }
        }
        poll();
        return {stop() { stopped = true; cancel(timer); }};
    }
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = {createTracker};
        return;
    }

    function init() {
        const config = JSON.parse(document.getElementById('wsi-deposit-config').textContent);
        const byId = id => document.getElementById(id);
        const form = byId('wsi-deposit-form');
        const submit = byId('wsi_deposit_submit');
        const dialog = byId('wsi-deposit-wait');
        const title = byId('wsi-deposit-wait-title');
        const message = byId('wsi-deposit-wait-message');
        const clock = byId('wsi-deposit-wait-clock');
        const connection = byId('wsi-deposit-wait-connection');
        const close = byId('wsi-deposit-wait-close');
        let submitting = false;
        let tracker;
        let ticker;
        let waitStarted = 0;
        let estimate = 1800;
        let activeDeposit = null;
        let lastFocus;
        let resumeAction = null;
        let networkIssue = false;

        function feedback(text) {
            byId('wsi-deposit-feedback').textContent = text;
            byId('wsi-deposit-feedback').hidden = !text;
        }
        function recalculate() {
            const crypto = form.querySelector('[name="payment_type"]:checked').value === 'crypto';
            byId('naira_section').style.display = crypto ? 'none' : '';
            byId('crypto_section').style.display = crypto ? '' : 'none';
            const amount = crypto ? Number(byId('crypto_amount').value) : Number(byId('amount_naira').value) / config.exchange_rate;
            const valid = Number.isFinite(amount) && amount > 0 && amount >= config.min_invest;
            byId('amount_usd').value = valid ? amount.toFixed(2) : '';
            byId('amount_usd_display').value = Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
            byId('crypto_wallet_select').style.display = valid ? '' : 'none';
            const wallet = config.wallets[byId('crypto_wallet').value];
            const walletReady = crypto && valid && wallet && wallet.address;
            byId('crypto_wallet_info').style.display = walletReady ? '' : 'none';
            byId('wallet_address').textContent = walletReady ? wallet.label + ' Address: ' + wallet.address : '';
            byId('wallet_instruction').textContent = walletReady ? wallet.instruction : '';
            submit.style.display = '';
            submit.disabled = submitting || !!activeDeposit || !valid || (crypto && !walletReady);
            return valid && (!crypto || !!walletReady);
        }

        async function request(body) {
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 15000);
            try {
                const response = await fetch(config.ajax_url, {method: 'POST', body, credentials: 'same-origin', cache: 'no-store', signal: controller.signal});
                const result = await response.json();
                if (!response.ok || !result.success) {
                    const error = new Error(result.data?.message || 'Unable to reach the server.');
                    error.status = response.status;
                    throw error;
                }
                return result.data;
            } finally { clearTimeout(timeout); }
        }
        function updateEstimate() {
            const remaining = Math.max(0, Math.ceil(estimate - (Date.now() - waitStarted) / 1000));
            const minutes = Math.floor(remaining / 60);
            clock.textContent = remaining ? String(minutes).padStart(2, '0') + ':' + String(remaining % 60).padStart(2, '0') : 'Still pending';
            byId('wsi-deposit-wait-estimate').textContent = remaining ? 'Estimated review time remaining' : 'Review is taking a little longer';
            if (!remaining && !networkIssue) message.textContent = 'Your deposit is still awaiting admin approval. We will take you to your dashboard as soon as it is approved.';
        }
        function remember(id) {
            const url = new URL(window.location.href);
            url.searchParams.delete('deposit');
            if (id) url.searchParams.set('deposit_id', id);
            else url.searchParams.delete('deposit_id');
            window.history.replaceState({}, '', url);
        }
        function stopWaiting() {
            if (tracker) tracker.stop();
            clearInterval(ticker);
        }
        function showWaiting(deposit) {
            stopWaiting();
            activeDeposit = deposit.deposit_id;
            remember(activeDeposit);
            estimate = deposit.estimated_seconds || 1800;
            waitStarted = Date.now() - (deposit.elapsed_seconds || 0) * 1000;
            networkIssue = false;
            resumeAction = null;
            dialog.dataset.state = 'pending';
            title.textContent = 'Your deposit is on its way';
            message.textContent = 'We are waiting for admin confirmation. You will be taken to your dashboard automatically once your deposit is approved.';
            byId('wsi-deposit-wait-reference').textContent = 'Deposit #' + activeDeposit + (deposit.amount_usd ? ' · $' + Number(deposit.amount_usd).toFixed(2) : '');
            connection.textContent = 'Checking approval automatically';
            close.textContent = 'Continue to dashboard';
            lastFocus = document.activeElement;
            if (!dialog.open) dialog.showModal();
            updateEstimate();
            ticker = setInterval(updateEstimate, 1000);
            recalculate();
            tracker = createTracker({
                check: () => {
                    const body = new FormData();
                    body.set('action', 'wsi_check_deposit_status');
                    body.set('_wpnonce', config.status_nonce);
                    body.set('deposit_id', activeDeposit);
                    return request(body);
                },
                onStatus: snapshot => {
                    networkIssue = false;
                    if (snapshot.status === 'approved') {
                        stopWaiting();
                        dialog.dataset.state = 'approved';
                        dialog.close();
                        window.location.assign(config.dashboard_url);
                    } else if (snapshot.status === 'declined') {
                        stopWaiting();
                        dialog.dataset.state = 'declined';
                        title.textContent = 'Deposit was not approved';
                        message.textContent = 'The admin declined this deposit. Please check your payment details or contact support before submitting another request.';
                        connection.textContent = 'Review complete';
                        close.textContent = 'Back to deposit';
                        activeDeposit = null;
                        remember(null);
                        recalculate();
                    } else {
                        waitStarted = Date.now() - snapshot.elapsed_seconds * 1000;
                        connection.textContent = 'Checking approval automatically';
                        message.textContent = 'We are waiting for admin confirmation. You will be taken to your dashboard automatically once your deposit is approved.';
                        updateEstimate();
                    }
                },
                onError: (error, terminal) => {
                    networkIssue = true;
                    connection.textContent = terminal ? 'Approval check paused' : 'Connection interrupted · retrying automatically';
                    if (!terminal) return;
                    stopWaiting();
                    dialog.dataset.state = 'error';
                    title.textContent = 'Let’s reconnect';
                    message.textContent = error.message;
                    close.textContent = 'Refresh page';
                    resumeAction = () => window.location.reload();
                },
            });
        }
        dialog.addEventListener('cancel', event => event.preventDefault());
        close.addEventListener('click', () => {
            if (resumeAction) return resumeAction();
            if (activeDeposit) return window.location.assign(config.dashboard_url);
            dialog.close();
            if (lastFocus) lastFocus.focus();
        });
        window.addEventListener('pagehide', stopWaiting);
        form.addEventListener('input', recalculate);
        form.addEventListener('change', recalculate);
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (submitting || activeDeposit || !recalculate() || !form.reportValidity()) return;
            submitting = true;
            feedback('');
            submit.disabled = true;
            submit.textContent = 'Submitting deposit…';
            const body = new FormData(form);
            body.set('action', 'wsi_submit_deposit');
            try {
                const deposit = await request(body);
                if (!Number.isInteger(Number(deposit.deposit_id)) || Number(deposit.deposit_id) <= 0) throw new Error('Unable to confirm your deposit reference. Refresh the page to check before submitting again.');
                showWaiting(deposit);
            } catch (error) {
                feedback(error.message || 'Connection interrupted. Refresh the page to check your pending deposits before submitting again.');
            } finally {
                submitting = false;
                submit.textContent = 'Submit Deposit';
                recalculate();
            }
        });
        recalculate();
        if (config.pending_deposit) showWaiting(config.pending_deposit);
    }
    document.addEventListener('DOMContentLoaded', init);
})();
